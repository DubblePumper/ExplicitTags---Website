<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Include configuration with proper path resolution
require_once dirname(dirname(dirname(__DIR__))) . '/app/includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Initialize response data
$response = [
    'status' => 'unknown',
    'results' => null
];

// Check for video ID in the query string
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $videoId = (int)$_GET['id'];
    
    try {
        // Connect to database
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        
        // Query for the video's processing status
        $stmt = $pdo->prepare("
            SELECT processing_status, result_data 
            FROM processed_videos 
            WHERE id = :id
        ");
        
        $stmt->execute([':id' => $videoId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Set status from database
            $response['status'] = $result['processing_status'];
            
            // If processing is complete, include the results
            if ($result['processing_status'] === 'completed' && $result['result_data']) {
                $response['results'] = json_decode($result['result_data'], true);
            }
            
            // For testing: Simulate a status progression if needed
            // This helps with UI testing when no actual processing is happening
            if (!isset($_GET['no_simulate'])) {
                $created_time = null;
                $stmt = $pdo->prepare("SELECT created_at FROM processed_videos WHERE id = :id");
                $stmt->execute([':id' => $videoId]);
                $time_result = $stmt->fetch();
                if ($time_result) {
                    $created_time = strtotime($time_result['created_at']);
                    $time_diff = time() - $created_time;
                    
                    // Simulate status progression based on time elapsed
                    if ($response['status'] === 'pending' && $time_diff > 10) {
                        $response['status'] = 'processing';
                        $update = $pdo->prepare("UPDATE processed_videos SET processing_status = 'processing' WHERE id = :id");
                        $update->execute([':id' => $videoId]);
                    }
                    else if ($response['status'] === 'processing' && $time_diff > 30) {
                        // After 30 seconds, complete the processing with sample results
                        $response['status'] = 'completed';
                        $sample_results = json_encode([
                            'performers' => [
                                ['name' => 'Test Performer 1', 'confidence' => 87],
                                ['name' => 'Test Performer 2', 'confidence' => 65]
                            ],
                            'tags' => ['sample tag 1', 'sample tag 2', 'test tag 3']
                        ]);
                        
                        $update = $pdo->prepare("UPDATE processed_videos SET processing_status = 'completed', result_data = :result_data WHERE id = :id");
                        $update->execute([
                            ':id' => $videoId,
                            ':result_data' => $sample_results
                        ]);
                        
                        $response['results'] = json_decode($sample_results, true);
                    }
                }
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Video not found';
        }
        
    } catch (PDOException $e) {
        // Log error but don't expose details to client
        error_log("Database error in check-processing-status: " . $e->getMessage());
        $response['status'] = 'error';
        $response['message'] = 'Database error occurred';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Missing or invalid video ID';
}

// Return JSON response
echo json_encode($response);
