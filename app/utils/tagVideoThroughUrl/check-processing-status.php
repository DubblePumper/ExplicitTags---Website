<?php
/**
 * Ajax endpoint to check video processing status
 */

// Set appropriate headers for AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Allow AJAX access from any origin during development/Docker
// For Docker environments, we need to be more permissive with CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    // When direct access or missing origin header
    header("Access-Control-Allow-Origin: *");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept');

// Handle preflight OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just exit with 200 OK status for preflight requests
    exit(0);
}

// Include configuration file
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Initialize response array
$response = [
    'status' => 'unknown',
    'progress' => 0,
    'results' => null,
    'error' => null
];

// Get video ID from query string
$videoId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Log the request to help with debugging
error_log("Status check request received for video ID: {$videoId}");

// Validate video ID
if (!$videoId || $videoId <= 0) {
    $response['error'] = 'Invalid video ID';
    echo json_encode($response);
    exit;
}

// Create PDO instance
try {
    $pdo = testDBConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Query database for video status
    $stmt = $pdo->prepare("
        SELECT processing_status, download_progress, result_data
        FROM processed_videos
        WHERE id = :id
    ");
    
    $stmt->execute([':id' => $videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if video exists
    if (!$video) {
        $response['error'] = 'Video not found';
        echo json_encode($response);
        exit;
    }
    
    // Update response with video data
    $response['status'] = $video['processing_status'];
    
    // If status is downloading, include download progress
    if ($video['processing_status'] === 'downloading') {
        $response['progress'] = (int)$video['download_progress'];
    }
    
    // Include results if available
    if ($video['result_data']) {
        $results = json_decode($video['result_data'], true);
        $response['results'] = $results;
    }
    
    // Success flag for easier frontend checking
    $response['success'] = true;
    
} catch (PDOException $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
    error_log('Database error in check-processing-status.php: ' . $e->getMessage());
} catch (Exception $e) {
    $response['error'] = 'Error: ' . $e->getMessage();
    error_log('Error in check-processing-status.php: ' . $e->getMessage());
}

// Log the response for debugging
error_log("Status check response for video ID {$videoId}: " . json_encode($response));

// Return response as JSON
echo json_encode($response);
