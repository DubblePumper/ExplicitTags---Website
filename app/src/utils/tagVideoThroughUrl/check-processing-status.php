<?php
// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

// Include database functions
require_once BASE_PATH . '/src/Utils/tagVideoThroughUrl/database-functions.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Check if ID is provided
$videoId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$videoId) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Video ID is required'
    ]);
    exit;
}

// Get video status from database
$videoStatus = getVideoStatus($videoId);

if (!$videoStatus) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Video not found'
    ]);
    exit;
}

// Return status information
$response = [
    'status' => $videoStatus['processing_status'],
    'message' => $videoStatus['status_message'],
    'progress' => (float)$videoStatus['download_progress'],
    'updated_at' => $videoStatus['updated_at']
];

// Include results if available and status is completed
if ($videoStatus['processing_status'] === 'completed' && !empty($videoStatus['result_data'])) {
    $response['results'] = $videoStatus['result_data'];
}

echo json_encode($response);
exit;
?>