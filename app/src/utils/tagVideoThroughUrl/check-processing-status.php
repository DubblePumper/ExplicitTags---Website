<?php
// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

// Add CORS headers to prevent cross-origin issues
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include database functions
require_once BASE_PATH . '/src/Utils/tagVideoThroughUrl/database-functions.php';

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