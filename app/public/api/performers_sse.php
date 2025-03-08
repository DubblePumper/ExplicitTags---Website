<?php
// Define base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Include database functionality
require_once BASE_PATH . '/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('max_execution_time', 0);

// Clear output buffer
while (ob_get_level()) ob_end_clean();

// Headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

echo "retry: 3000\n\n";

// SSE function
function sendSSE($event, $data) {
    echo "id: " . time() . "\n";
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Fuzzy matching function
function calculateFuzzyScore($search, $name) {
    $search = strtolower(trim($search));
    $name = strtolower(trim($name));

    // Normalize common substitutions
    $normalizeStr = function($str) {
        $replacements = [
            'y' => 'i',
            'ie' => 'y',
            'ph' => 'f',
            'h' => '',  // Handle silent 'h'
            'j' => 'g'  // Handle j/g substitutions
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $str);
    };

    // Perfect match
    if ($search === $name) return 100.0;
    
    // Normalized match
    $normalizedSearch = $normalizeStr($search);
    $normalizedName = $normalizeStr($name);
    if ($normalizedSearch === $normalizedName) return 99.0;

    // Phonetic matches - check if function exists (for PHP compatibility)
    if (function_exists('metaphone') && metaphone($search) === metaphone($name)) return 95.0;
    if (function_exists('soundex') && soundex($search) === soundex($name)) return 90.0;

    // Exact word matches at start of name
    $words = explode(' ', $name);
    if (strcasecmp($search, $words[0]) === 0) {
        return 99.0;
    }

    // All search terms found in correct order
    $searchWords = explode(' ', $search);
    $consecutive = true;
    $lastPos = -1;
    foreach ($searchWords as $word) {
        $pos = strpos($name, $word);
        if ($pos === false || ($lastPos !== -1 && $pos < $lastPos)) {
            $consecutive = false;
            break;
        }
        $lastPos = $pos;
    }
    if ($consecutive && !empty($searchWords)) {
        return 95.0;
    }

    // Word boundary match
    if (preg_match('/\b' . preg_quote($search, '/') . '/i', $name)) {
        return 90.0;
    }

    // Starts with search term
    if (strpos($name, $search) === 0) {
        return 85.0;
    }

    // Contains all search terms in any order
    $allTermsFound = true;
    foreach ($searchWords as $word) {
        if (strpos($name, $word) === false) {
            $allTermsFound = false;
            break;
        }
    }
    if ($allTermsFound) {
        return 80.0;
    }

    // Partial match at start of any word
    foreach ($words as $word) {
        if (strpos($word, $search) === 0) {
            return 75.0;
        }
    }

    // Contains the search term anywhere
    if (strpos($name, $search) !== false) {
        return 70.0;
    }

    // Levenshtein distance as last resort
    $maxScore = 0;
    foreach ($words as $word) {
        $distance = levenshtein($search, $word);
        $score = 1 - ($distance / max(strlen($search), strlen($word)));
        $maxScore = max($maxScore, $score * 60.0); // Scale to be lower than other matches
    }

    return $maxScore;
}

try {
    sendSSE('ping', ['status' => 'connected']);

    // Connect to database
    $pdo = testDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get search parameters and sanitize
    $search = isset($_GET['search']) ? filter_var($_GET['search'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
    $gender = isset($_GET['gender']) ? filter_var($_GET['gender'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
    
    $search = urldecode($search);

    // Build the SQL query with SOUNDEX support
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
        // Simpler, more reliable search condition
        $conditions[] = "LOWER(p.name) LIKE LOWER(:searchWild)";
        $params[':searchWild'] = '%' . $search . '%';
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " GROUP BY p.id, p.name, p.gender LIMIT 30";

    // Try executing the query
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception('Database query failed: ' . $e->getMessage());
    }

    // Fuzzy matching if search term provided
    if (!empty($search)) {
        foreach ($results as &$result) {
            $result['similarity'] = calculateFuzzyScore($search, $result['name']);
        }

        // Stricter filtering and better sorting
        $results = array_filter($results, fn($result) => $result['similarity'] > 50.0);
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        $results = array_slice($results, 0, 15);
    }

    // Remove duplicates
    $seen = [];
    $results = array_values(array_filter($results, function($result) use (&$seen) {
        $nameKey = strtolower(trim($result['name']));
        if (isset($seen[$nameKey])) {
            return false;
        }
        $seen[$nameKey] = true;
        return true;
    }));

    // Fix image URLs
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

    // Send the results
    $formattedResults = array_map(fn($result) => [
        'performer_id' => $result['performer_id'],
        'name'         => $result['name'],
        'gender'       => $result['gender'],
        'image_url'    => $result['image_url']
    ], $results);
    
    sendSSE('performers', $formattedResults);

    exit(0);
} catch (Exception $e) {
    // Log error and send error event
    error_log("SSE Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    sendSSE('error', ['error' => 'Search service error', 'details' => $e->getMessage()]);
    exit(1);
}