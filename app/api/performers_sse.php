<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('max_execution_time', 0);

// Maak de output buffer leeg
while (ob_get_level()) ob_end_clean();

// Headers voor SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

echo "retry: 3000\n\n";

// SSE functie
function sendSSE($event, $data) {
    echo "id: " . time() . "\n";
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Fuzzy matching functie
function calculateFuzzyScore($search, $name) {
    $search = strtolower(trim($search));
    $name   = strtolower(trim($name));

    if ($search === $name) {
        return 1.0;
    }

    if (metaphone($search) === metaphone($name) || soundex($search) === soundex($name)) {
        return 0.95;
    }

    similar_text($search, $name, $similarity);
    $levenshteinScore = 1 - (levenshtein($search, $name) / max(strlen($search), strlen($name)));

    return max($similarity / 100, $levenshteinScore);
}

try {
    sendSSE('ping', ['status' => 'connected']);

    $pdo = testDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $search = urldecode(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $gender = filter_input(INPUT_GET, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

    // SQL-query opbouwen met SOUNDEX ondersteuning
    $query = "SELECT p.id AS performer_id, p.name, p.gender, 
              (SELECT image_url FROM performer_images WHERE performer_id = p.id LIMIT 1) AS image_url
              FROM performers p";
    $params = [];
    $conditions = [];

    if (!empty($gender)) {
        $conditions[] = "p.gender = :gender";
        $params[':gender'] = $gender;
    }

    if (!empty($search)) {
        $conditions[] = "(LOWER(p.name) LIKE LOWER(:searchWild) OR SOUNDEX(p.name) = SOUNDEX(:search))";
        $params[':searchWild'] = '%' . $search . '%';
        $params[':search'] = $search;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " GROUP BY p.id, p.name, p.gender LIMIT 30";

    $stmt = $pdo->prepare($query);
    if (!$stmt->execute($params)) {
        throw new Exception('Query execution failed');
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fuzzy matching toepassen
    if (!empty($search)) {
        foreach ($results as &$result) {
            $result['similarity'] = calculateFuzzyScore($search, $result['name']);
        }

        $results = array_filter($results, fn($result) => $result['similarity'] > 0.4);
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        $results = array_slice($results, 0, 15);
    }

    // Duplicaten verwijderen
    $seen = [];
    $results = array_values(array_filter($results, function($result) use (&$seen) {
        $nameKey = strtolower(trim($result['name']));
        if (isset($seen[$nameKey])) {
            return false;
        }
        $seen[$nameKey] = true;
        return true;
    }));

    // Image URL fixen
    array_walk($results, function (&$result) {
        if (!empty($result['image_url'])) {
            $result['image_url'] = str_replace(['E:\\github repos\\porn_ai_analyser\\app\\datasets\\pornstar_images', '\\'], ['', '/'], $result['image_url']);
            $parts = explode('/', $result['image_url']);
            if (count($parts) > 1) {
                $parts[1] = rtrim($parts[1], '.');
                $result['image_url'] = implode('/', $parts);
            }
            $result['image_url'] = 'https://cdn.jsdelivr.net/gh/DubblePumper/porn_ai_analyser@main/app/datasets/pornstar_images' . $result['image_url'];
        } else {
            $result['image_url'] = null;
        }
    });

    sendSSE('performers', array_map(fn($result) => [
        'performer_id' => $result['performer_id'],
        'name'         => $result['name'],
        'gender'       => $result['gender'],
        'image_url'    => $result['image_url']
    ], $results));

    exit(0);
} catch (Exception $e) {
    error_log("SSE Error: " . $e->getMessage());
    sendSSE('error', ['error' => 'Search service error', 'details' => $e->getMessage()]);
    exit(1);
}