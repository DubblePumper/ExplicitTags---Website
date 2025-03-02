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
header('Access-Control-Allow-Origin: *'); // Allow from any origin
header('Access-Control-Allow-Methods: GET, OPTIONS');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Headers: Content-Type, Origin');
    exit(0);
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

// Include necessary files
// Use proper path resolution for config
require_once BASE_PATH . '/config/config.php';

// For debugging - log that this endpoint was accessed
error_log('check-processing-status.php accessed with ID: ' . ($_GET['id'] ?? 'none'));

// Initialize response
$response = [
    'status' => 'pending',
    'progress' => 0,
    'message' => 'Waiting to start processing',
    'results' => null
];

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    $response['status'] = 'failed';
    $response['message'] = 'Invalid video ID';
    echo json_encode($response);
    exit;
}

$videoId = (int)$_GET['id'];

try {
    // Create PDO instance
    $pdo = testDBConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Query the processing status
    $stmt = $pdo->prepare("
        SELECT processing_status, download_progress, status_message, processed_tags, processed_performers
        FROM processed_videos 
        WHERE id = :id
    ");
    
    $stmt->execute([':id' => $videoId]);
    $videoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$videoData) {
        throw new Exception('Video not found');
    }
    
    // Set the response based on the database data
    $response['status'] = $videoData['processing_status'];
    
    if ($videoData['download_progress'] !== null) {
        $response['progress'] = (int)$videoData['download_progress'];
    } else {
        // If no explicit progress, estimate based on status
        switch ($videoData['processing_status']) {
            case 'pending':
                $response['progress'] = 10;
                break;
            case 'processing':
                $response['progress'] = 50; // Default mid-processing
                break;
            case 'completed':
                $response['progress'] = 100;
                break;
            case 'failed':
                $response['progress'] = 0;
                break;
            default:
                $response['progress'] = 0;
        }
    }
    
    // Include status message if available
    if (!empty($videoData['status_message'])) {
        $response['message'] = $videoData['status_message'];
    } else {
        // Default messages based on status
        switch ($videoData['processing_status']) {
            case 'pending':
                $response['message'] = 'Waiting to start processing';
                break;
            case 'processing':
                $response['message'] = 'Processing your video';
                break;
            case 'completed':
                $response['message'] = 'Processing complete';
                break;
            case 'failed':
                $response['message'] = 'Processing failed';
                break;
            default:
                $response['message'] = 'Unknown status';
        }
    }
    
    // Include results if completed
    if ($videoData['processing_status'] === 'completed') {
        $results = [
            'performers' => [],
            'tags' => []
        ];
        
        // Process performers if available
        if (!empty($videoData['processed_performers'])) {
            $performersData = json_decode($videoData['processed_performers'], true);
            if (is_array($performersData)) {
                $results['performers'] = $performersData;
            }
        }
        
        // Process tags if available
        if (!empty($videoData['processed_tags'])) {
            $tagsData = json_decode($videoData['processed_tags'], true);
            if (is_array($tagsData)) {
                $results['tags'] = $tagsData;
            }
        }
        
        $response['results'] = $results;
    }
    
    // For debugging - log the response
    error_log('Response for video ID ' . $videoId . ': ' . json_encode($response));
    
} catch (PDOException $e) {
    http_response_code(500);
    $response['status'] = 'failed';
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('PDO Error in check-processing-status: ' . $e->getMessage());
} catch (Exception $e) {
    http_response_code(404);
    $response['status'] = 'failed';
    $response['message'] = $e->getMessage();
    error_log('Error in check-processing-status: ' . $e->getMessage());
}

// Return the JSON response
echo json_encode($response);
exit;
?>
