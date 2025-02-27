<?php
/**
 * Background worker script to process video downloads using youtube-dl-php
 * This script is meant to be run as a background process
 */

// Include dependencies
require_once dirname(dirname(dirname(__FILE__))) . '/assets/vendor/autoload.php';

// Include database functions
require_once dirname(dirname(__FILE__)) . '/tagVideoThroughUrl/database-functions.php';

// Include the VideoDownloader service
require_once dirname(dirname(__FILE__)) . '/services/VideoDownloader.php';

use Utils\Services\VideoDownloader;

// Function to log messages with timestamp
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message" . PHP_EOL;
    // Also write to log file in production environments
    if (file_exists('/var/log/explicittags')) {
        file_put_contents(
            '/var/log/explicittags/video_processing.log',
            "[$timestamp] $message" . PHP_EOL,
            FILE_APPEND
        );
    }
}

// Process a specific video if ID is provided as command line argument
if (isset($argv[1]) && is_numeric($argv[1])) {
    $videoId = (int)$argv[1];
    
    logMessage("Starting processing for video ID: $videoId");
    
    try {
        // Get database connection
        $pdo = testDBConnection();
        
        if (!$pdo) {
            throw new Exception("Failed to connect to database");
        }
        
        // Get video data
        $stmt = $pdo->prepare("SELECT * FROM processed_videos WHERE id = :id");
        $stmt->execute([':id' => $videoId]);
        $videoData = $stmt->fetch();
        
        if (!$videoData) {
            throw new Exception("Video ID $videoId not found");
        }
        
        // Only process URL submissions that need downloading
        if ($videoData['source_type'] !== 'url' || empty($videoData['video_url'])) {
            throw new Exception("Video ID $videoId is not a URL submission or has no URL");
        }
        
        // Create downloader instance
        $downloader = new VideoDownloader($pdo);
        
        // Update status
        updateVideoStatus($videoId, 'processing', 'Starting download...', 0);
        
        // Download the video
        logMessage("Downloading video from: " . $videoData['video_url']);
        $result = $downloader->downloadVideo($videoId, $videoData['video_url']);
        
        if ($result['status'] === 'success') {
            logMessage("Download successful. File saved at: " . $result['path']);
            updateVideoStatus($videoId, 'processing', 'Download complete, preparing for AI analysis...', 100);
            
            // Here you would call your AI analysis code
            // For now, just simulate completion with some demo results
            
            // Wait a bit to simulate processing time
            sleep(3);
            
            // Store mock results - replace with actual AI analysis in production
            $demoResults = [
                'performers' => [
                    ['name' => 'Demo Performer 1', 'confidence' => 89],
                    ['name' => 'Demo Performer 2', 'confidence' => 75],
                ],
                'tags' => ['tag1', 'tag2', 'tag3', 'demo'],
                'video_length' => '10:23',
                'processed_on' => date('Y-m-d H:i:s')
            ];
            
            storeVideoResults($videoId, $demoResults);
            logMessage("Video processing completed for ID: $videoId");
            
        } else {
            logMessage("Download failed: " . ($result['message'] ?? 'Unknown error'));
            updateVideoStatus($videoId, 'failed', 'Download failed: ' . ($result['message'] ?? 'Unknown error'));
        }
        
    } catch (Exception $e) {
        logMessage("Error processing video: " . $e->getMessage());
        
        // Log the error to the database
        logProcessingError($videoId, $e->getMessage());
    }
} else {
    logMessage("No video ID provided, checking for pending videos");
    
    // Process any pending videos in the queue
    try {
        $pendingVideos = getPendingVideos(5);
        
        if (empty($pendingVideos)) {
            logMessage("No pending videos found");
            exit;
        }
        
        logMessage("Found " . count($pendingVideos) . " pending videos to process");
        
        // Process each video
        foreach ($pendingVideos as $video) {
            logMessage("Starting worker process for video ID: " . $video['id']);
            
            // Start a separate process for each video
            if (stripos(PHP_OS, 'WIN') === 0) {
                // Windows
                pclose(popen("start /B php " . __FILE__ . " " . $video['id'], "r"));
            } else {
                // Linux/Unix
                exec("php " . __FILE__ . " " . $video['id'] . " > /dev/null 2>&1 &");
            }
            
            // Small delay to prevent overwhelming the system
            sleep(1);
        }
        
    } catch (Exception $e) {
        logMessage("Error checking pending videos: " . $e->getMessage());
    }
}

logMessage("Worker script completed");
