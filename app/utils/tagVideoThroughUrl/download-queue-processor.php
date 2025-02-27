<?php
/**
 * Script to process the queue of videos to download
 * This script should be run via cron/scheduled task
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/tagVideoThroughUrl/video-downloader.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    exit('This script can only be run from the command line.');
}

// Initialize database connection
$pdo = testDBConnection();
if (!$pdo) {
    exit('Failed to connect to database');
}

// Get videos that are pending and have a URL (not uploaded)
try {
    $stmt = $pdo->query("
        SELECT id, video_url 
        FROM processed_videos 
        WHERE source_type = 'url' 
        AND processing_status = 'pending' 
        AND video_url IS NOT NULL
        ORDER BY created_at ASC
        LIMIT 5
    ");
    
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching pending videos: " . $e->getMessage());
    exit('Database error: ' . $e->getMessage());
}

// Process each pending video
$processed = 0;
foreach ($videos as $video) {
    // Create downloader instance
    $downloader = new VideoDownloader($pdo, $video['id'], $video['video_url']);
    
    // Start download
    if ($downloader->startDownload()) {
        $processed++;
        echo "Started download for video ID {$video['id']}\n";
    } else {
        echo "Failed to start download for video ID {$video['id']}\n";
    }
    
    // Limit to 3 concurrent downloads
    if ($processed >= 3) {
        break;
    }
    
    // Wait a bit between starting downloads
    sleep(2);
}

if ($processed === 0) {
    echo "No pending videos to process\n";
}

exit(0);
