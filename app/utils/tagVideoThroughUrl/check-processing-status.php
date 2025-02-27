<?php
/**
 * AJAX endpoint to check video processing status
 * Returns JSON response with current status and results if available
 */

// Don't output warnings/notices as part of the JSON response
error_reporting(E_ERROR);

// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Include database functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/tagVideoThroughUrl/database-functions.php';

// Get video ID from query string
$videoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$videoId) {
    echo json_encode([
        'error' => 'Missing video ID',
        'status' => 'error'
    ]);
    exit;
}

// Get current status from database
$videoData = getVideoStatus($videoId);

if (!$videoData) {
    echo json_encode([
        'error' => 'Video not found',
        'status' => 'error'
    ]);
    exit;
}

// Prepare response
$response = [
    'status' => $videoData['processing_status'],
    'message' => $videoData['status_message'] ?? '',
    'progress' => (float)($videoData['download_progress'] ?? 0)
];

// Include results if processing is complete
if ($videoData['processing_status'] === 'completed' && !empty($videoData['result_data'])) {
    $response['results'] = $videoData['result_data'];
}

// Return progress information, add last update time
$response['last_updated'] = $videoData['updated_at'];

// Encode and output the response
echo json_encode($response);
