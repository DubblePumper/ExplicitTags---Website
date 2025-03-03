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
    'message' => 'Invalid request'
];

// Check if video ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $videoId = (int)$_GET['id'];
    
    // Get video status from database
    $videoStatus = getVideoStatus($videoId);
    
    if ($videoStatus) {
        $response = [
            'success' => true,
            'processing_status' => $videoStatus['processing_status'],
            'status_message' => $videoStatus['status_message'],
            'download_progress' => (float)$videoStatus['download_progress'],
            'result_data' => $videoStatus['result_data'],
            'timestamp' => time()
        ];
        
        // Add additional info for better client-side handling
        if ($response['processing_status'] === 'processing' && $response['download_progress'] > 0) {
            $response['download_complete'] = $response['download_progress'] >= 100;
            
            // Add ETA if download is in progress
            if (!$response['download_complete'] && $response['download_progress'] > 5) {
                // Calculate rough ETA based on progress and time elapsed
                $startTime = strtotime($videoStatus['created_at']);
                $currentTime = time();
                $timeElapsed = $currentTime - $startTime;
                
                if ($timeElapsed > 0 && $response['download_progress'] > 0) {
                    $totalEstimatedTime = ($timeElapsed / $response['download_progress']) * 100;
                    $remainingTime = $totalEstimatedTime - $timeElapsed;
                    
                    if ($remainingTime > 0) {
                        $response['eta_seconds'] = round($remainingTime);
                        
                        // Format ETA for display
                        if ($remainingTime < 60) {
                            $response['eta_formatted'] = "Less than a minute";
                        } else if ($remainingTime < 3600) {
                            $response['eta_formatted'] = round($remainingTime / 60) . " minutes";
                        } else {
                            $response['eta_formatted'] = round($remainingTime / 3600, 1) . " hours";
                        }
                    }
                }
            }
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Video not found'
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Missing or invalid video ID'
    ];
}

// Send response
echo json_encode($response);
exit;
