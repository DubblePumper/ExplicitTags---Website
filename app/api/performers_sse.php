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

// Function to calculate similarity score
function calculateSimilarity($search, $name) {
    $search = strtolower($search);
    $name = strtolower($name);
    $levDistance = levenshtein($search, $name);
    return 1 / (1 + $levDistance); // Higher score means more similar
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

    // Debug logging
    error_log("Search query: " . $search);
    error_log("Gender filter: " . $gender);

    // Query building with case-insensitive search
    $query = "SELECT p.id AS performer_id, p.name, p.gender, MIN(pi.image_url) AS image_url 
              FROM performers p 
              LEFT JOIN performer_images pi ON p.id = pi.performer_id";
    
    $params = [];
    $conditions = [];

    if (!empty($gender)) {
        $conditions[] = "p.gender = :gender";
        $params[':gender'] = $gender;
    }

    // For search, we'll do a broader LIKE query first
    if (!empty($search)) {
        $searchTerms = explode(' ', trim($search));
        $searchConditions = [];
        foreach ($searchTerms as $i => $term) {
            $paramName = ":search{$i}";
            $searchConditions[] = "p.name LIKE {$paramName}";
            $params[$paramName] = "%{$term}%";
        }
        $conditions[] = "(" . implode(" OR ", $searchConditions) . ")";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " GROUP BY p.id, p.name, p.gender";

    // Add debug logging for the final query
    error_log("Final SQL query: " . $query);
    error_log("Query parameters: " . print_r($params, true));

    // Execute query
    $stmt = $pdo->prepare($query);
    if (!$stmt->execute($params)) {
        throw new Exception('Query execution failed');
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Apply Levenshtein filtering and sorting
    if (!empty($search)) {
        // Calculate similarity scores
        foreach ($results as &$result) {
            $result['similarity'] = calculateSimilarity($search, $result['name']);
        }

        // Sort by similarity score
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Limit to top 15 results
        $results = array_slice($results, 0, 15);
    }

    // Remove duplicates based on name
    $seen = [];
    $results = array_filter($results, function($result) use (&$seen) {
        if (isset($seen[strtolower($result['name'])])) {
            return false;
        }
        $seen[strtolower($result['name'])] = true;
        return true;
    });

    // Process results
    array_walk($results, function (&$result) {
        if (!empty($result['image_url']) && isset($result['image_url'])) {
            // Remove local path and replace backslashes with forward slashes
            $result['image_url'] = str_replace('E:\\github repos\\porn_ai_analyser\\app\\datasets\\pornstar_images', '', $result['image_url']);
            $result['image_url'] = str_replace('\\', '/', $result['image_url']);
            
            // Fix folder names by removing trailing dots
            $parts = explode('/', $result['image_url']);
            if (count($parts) > 1) {
                $parts[1] = rtrim($parts[1], '.'); // Remove trailing dots from folder name
                $result['image_url'] = implode('/', $parts);
            }
            
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
    }, array_values($results)));

    // End connection cleanly
    exit(0);
} catch (Exception $e) {
    error_log("SSE Error: " . $e->getMessage());
    sendSSE('error', ['error' => 'Search service error', 'details' => $e->getMessage()]);
    exit(1);
}
