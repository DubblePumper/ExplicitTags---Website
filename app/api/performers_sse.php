<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Set error reporting and headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('max_execution_time', 0);

// Clean output buffer
while (ob_get_level()) ob_end_clean();

// Headers with specific order and values
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Initial connection response
echo "retry: 3000\n\n";

// Function to send SSE message
function sendSSE($event, $data)
{
    echo "id: " . time() . "\n";
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

try {
    // Send initial ping
    sendSSE('ping', ['status' => 'connected']);

    // Database connection
    $pdo = testDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get and sanitize inputs
    $search = urldecode(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $gender = filter_input(INPUT_GET, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

    // Query building with FULLTEXT search
    $query = "SELECT DISTINCT p.id AS performer_id, p.name, p.gender, MIN(pi.image_url) AS image_url 
              FROM performers p 
              LEFT JOIN performer_images pi ON p.id = pi.performer_id";
    
    $params = [];
    $conditions = [];

    if (!empty($search)) {
        // Simple LIKE search instead of FULLTEXT for now
        $conditions[] = "p.name LIKE :search";
        $params[':search'] = '%' . $search . '%';
        
        error_log("Search term: " . $search);
    }

    if (!empty($gender)) {
        $conditions[] = "p.gender = :gender";
        $params[':gender'] = $gender;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " GROUP BY p.id, p.name, p.gender ORDER BY p.name ASC LIMIT 15";

    // Debug logging
    error_log("Final query: " . $query);
    error_log("Parameters: " . print_r($params, true));

    // Execute query
    $stmt = $pdo->prepare($query);
    if (!$stmt->execute($params)) {
        throw new Exception('Query execution failed');
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug log the results
    error_log("Query results count: " . count($results));
    if (empty($results)) {
        error_log("No results found for search");
    }

    // After executing query, dump first few rows for debugging
    if ($results) {
        error_log("First result: " . print_r($results[0] ?? 'no results', true));
    }

    // Process results
    array_walk($results, function (&$result) {
        if (!empty($result['image_url']) && isset($result['image_url'])) {
            // Remove local path and replace backslashes with forward slashes
            $result['image_url'] = str_replace('E:\\github repos\\porn_ai_analyser\\app\\datasets\\pornstar_images', '', $result['image_url']);
            $result['image_url'] = str_replace('\\', '/', $result['image_url']);
            // Construct the correct GitHub URL
            $result['image_url'] = 'https://cdn.jsdelivr.net/gh/DubblePumper/porn_ai_analyser@main/app/datasets/pornstar_images' . $result['image_url'];
        } else {
            $result['image_url'] = null;
        }
    });

    // Send results
    sendSSE('performers', array_map(function ($result) {
        return [
            'performer_id' => $result['performer_id'],
            'name' => $result['name'],
            'gender' => $result['gender'],
            'image_url' => $result['image_url']
        ];
    }, $results));

    // End connection cleanly
    exit(0);
} catch (Exception $e) {
    error_log("SSE Error: " . $e->getMessage());
    sendSSE('error', ['error' => 'Search service error', 'details' => $e->getMessage()]);
    exit(1);
}
