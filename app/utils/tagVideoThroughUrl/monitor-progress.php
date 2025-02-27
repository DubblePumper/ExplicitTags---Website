<?php
/**
 * Script to monitor the download progress and update the database
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/tagVideoThroughUrl/video-downloader.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    exit('This script can only be run from the command line.');
}

// Get parameters
$video_id = isset($argv[1]) ? (int)$argv[1] : null;
$progress_file = isset($argv[2]) ? $argv[2] : null;
$output_file = isset($argv[3]) ? $argv[3] : null;

if (!$video_id || !$progress_file || !$output_file) {
    exit('Missing required parameters. Usage: php monitor-progress.php [video_id] [progress_file] [output_file]');
}

// Initialize database connection
$pdo = testDBConnection();
if (!$pdo) {
    exit('Failed to connect to database');
}

// Set timeout for max download time (30 minutes)
$timeout = time() + (30 * 60);
$lastProgressUpdate = 0;
$consecutiveFailures = 0;

// Monitor progress until complete or timeout
while (time() < $timeout) {
    // Check if file already exists and has content
    if (file_exists($output_file) && filesize($output_file) > 0) {
        // File exists and has data, mark download complete
        VideoDownloader::markDownloadComplete($pdo, $video_id, $output_file);
        exit(0);
    }
    
    // Check progress file
    if (file_exists($progress_file)) {
        $progress_content = file_get_contents($progress_file);
        
        // Extract downloaded and total bytes
        if (preg_match('/download:(\d+)\/(\d+)/', $progress_content, $matches)) {
            $downloaded = (int)$matches[1];
            $total = (int)$matches[2];
            
            // Calculate progress percentage
            if ($total > 0) {
                $progress = (int)(($downloaded / $total) * 100);
                
                // Only update database if progress has changed by at least 5%
                if (abs($progress - $lastProgressUpdate) >= 5) {
                    VideoDownloader::updateProgress($pdo, $video_id, $progress);
                    $lastProgressUpdate = $progress;
                }
                
                // Reset failure counter on successful progress
                $consecutiveFailures = 0;
            }
        } else {
            // If pattern not found, increment failure counter
            $consecutiveFailures++;
        }
    } else {
        // Progress file doesn't exist yet, wait a bit
        $consecutiveFailures++;
    }
    
    // If too many consecutive failures, check if download might be finished
    if ($consecutiveFailures > 10) {
        if (file_exists($output_file) && filesize($output_file) > 0) {
            // Download seems completed
            VideoDownloader::markDownloadComplete($pdo, $video_id, $output_file);
            exit(0);
        } else {
            // Download seems to have failed
            VideoDownloader::markDownloadFailed($pdo, $video_id);
            exit(1);
        }
    }
    
    // Sleep for a bit before checking again
    sleep(3);
}

// Timeout reached, check if download completed
if (file_exists($output_file) && filesize($output_file) > 0) {
    VideoDownloader::markDownloadComplete($pdo, $video_id, $output_file);
    exit(0);
} else {
    // Mark as failed if timeout reached
    VideoDownloader::markDownloadFailed($pdo, $video_id);
    exit(1);
}
