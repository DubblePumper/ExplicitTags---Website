<?php
// Define base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Include necessary files
require_once BASE_PATH . '/src/Utils/tagVideoThroughUrl/database-functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'status' => null,
    'progress' => null
];

// Check if video ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $videoId = (int)$_GET['id'];
    
    // Get video status from database
    $videoStatus = getVideoStatus($videoId);
    
    if ($videoStatus) {
        $response = [
            'success' => true,
            'status' => $videoStatus['processing_status'],
            'message' => $videoStatus['status_message'],
            'progress' => $videoStatus['download_progress'],
            'timestamp' => time()
        ];
    } else {
        $response['message'] = 'Video not found';
    }
} else {
    $response['message'] = 'Missing or invalid video ID';
}

// Send response
echo json_encode($response);
exit;
