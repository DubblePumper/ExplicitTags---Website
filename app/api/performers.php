<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Temporarily disable the HTTPS check for local development
// if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
//     header('HTTP/1.1 403 Forbidden');
//     echo json_encode(['error' => 'HTTPS is required']);
//     exit;
// }

// Rate limiting
session_start();
if (!isset($_SESSION['rate_limit'])) {
    $_SESSION['rate_limit'] = [];
}
$rate_limit = &$_SESSION['rate_limit'];
$ip = $_SERVER['REMOTE_ADDR'];
$time = time();
$rate_limit[$ip] = array_filter($rate_limit[$ip] ?? [], function($timestamp) use ($time) {
    return $timestamp > $time - 60; // Keep requests from the last 60 seconds
});
if (count($rate_limit[$ip]) >= 60) { // Limit to 60 requests per minute
    header('HTTP/1.1 429 Too Many Requests');
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . htmlspecialchars($e->getMessage())]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    case 'PUT':
        handlePut($pdo);
        break;
    case 'DELETE':
        handleDelete($pdo);
        break;
    default:
        echo json_encode(['error' => 'Invalid request method']);
        break;
}

function handleGet($pdo) {
    $where = filter_input(INPUT_GET, 'where', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '1';
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 15, 'min_range' => 1, 'max_range' => 100]]);
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $gender = filter_input(INPUT_GET, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

    $genderCondition = '';
    if ($gender) {
        $genderCondition = "AND p.gender = :gender";
    }

    try {
        $stmt = $pdo->prepare("
            SELECT p.id AS performer_id, p.name, p.gender, MIN(pi.image_url) AS image_url
            FROM performers p
            LEFT JOIN performer_images pi ON p.id = pi.performer_id
            WHERE $where AND p.name LIKE :search $genderCondition
            GROUP BY p.id, p.name, p.gender
            LIMIT :limit
        ");
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        if ($gender) {
            $stmt->bindValue(':gender', $gender, PDO::PARAM_STR);
        }
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update image URLs to be served through the GitHub repository
        foreach ($results as &$result) {
            if (isset($result['image_url'])) {
                // Remove local path and replace backslashes with forward slashes
                $result['image_url'] = str_replace('E:\\github repos\\porn_ai_analyser\\app\\datasets\\pornstar_images', '', $result['image_url']);
                $result['image_url'] = str_replace('\\', '/', $result['image_url']);
                // Construct the correct GitHub URL
                $result['image_url'] = 'https://cdn.jsdelivr.net/gh/DubblePumper/porn_ai_analyser@main/app/datasets/pornstar_images' . $result['image_url'];
            }
        }

        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
    }
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    $table = filter_var($data['table'] ?? 'performers', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $columns = implode(', ', array_keys($data['values']));
    $placeholders = ':' . implode(', :', array_keys($data['values']));
    $values = array_map('htmlspecialchars', $data['values']);

    try {
        $stmt = $pdo->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
        $stmt->execute($values);
        echo json_encode(['success' => 'Record added']);
    } catch (PDOException $e) {
        echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
    }
}

function handlePut($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    $table = filter_var($data['table'] ?? 'performers', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $id = filter_var($data['id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $set = '';
    foreach ($data['values'] as $column => $value) {
        $set .= "$column = :$column, ";
    }
    $set = rtrim($set, ', ');
    $values = array_map('htmlspecialchars', $data['values']);
    $values['id'] = $id;

    try {
        $stmt = $pdo->prepare("UPDATE $table SET $set WHERE id = :id");
        $stmt->execute($values);
        echo json_encode(['success' => 'Record updated']);
    } catch (PDOException $e) {
        echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
    }
}

function handleDelete($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    $table = filter_var($data['table'] ?? 'performers', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $id = filter_var($data['id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['success' => 'Record deleted']);
    } catch (PDOException $e) {
        echo json_encode(['error' => htmlspecialchars($e->getMessage())]);
    }
}
?>
