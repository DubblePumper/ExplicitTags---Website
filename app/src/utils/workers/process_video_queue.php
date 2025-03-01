<?php
/**
 * Background worker to process video downloads from URLs
 * 
 * This script should be called with the video ID as an argument:
 * php process_video_queue.php 123
 */

// Set unlimited execution time for long-running processes
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '256M');

// No output buffering for worker scripts
ob_implicit_flush(true);
ob_end_flush();

// Make sure this is being run from CLI or as a background task
if (php_sapi_name() !== 'cli' && !isset($_SERVER['REMOTE_ADDR']) && !isset($_GET['force_run'])) {
    echo "This script must be run from the command line or as a background task";
    exit(1);
}

// Check for video ID argument
$videoId = null;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $videoId = (int)$argv[1];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $videoId = (int)$_GET['id'];
}

if (!$videoId) {
    echo "Error: No video ID specified. Usage: php process_video_queue.php [video_id]";
    exit(1);
}

// Include necessary files
$docRoot = dirname(dirname(dirname(__DIR__)));
require_once $docRoot . '/includes/config.php';
require_once $docRoot . '/assets/vendor/autoload.php';

// Initialize variables
$pdo = null;

// Connect to database
try {
    $pdo = testDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
} catch (PDOException $e) {
    logError("Database connection error: " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    logError("Error: " . $e->getMessage());
    exit(1);
}

// Log helper function
function logMessage($message) {
    $date = date('Y-m-d H:i:s');
    echo "[$date] $message" . PHP_EOL;
    error_log("[$date] $message");
}

function logError($message) {
    $date = date('Y-m-d H:i:s');
    echo "[$date] ERROR: $message" . PHP_EOL;
    error_log("[$date] ERROR in process_video_queue: $message");
}

// Find the yt-dlp binary location
function findYtDlpBinary() {
    $docRoot = dirname(dirname(dirname(__DIR__)));
    
    // Try to find in vendor directory first (PREFERRED LOCATION)
    $vendorPath = $docRoot . '/assets/vendor/yt-dlp/yt-dlp';
    if (stripos(PHP_OS, 'WIN') !== false) {
        $vendorPath .= '.exe';
    }
    
    if (file_exists($vendorPath)) {
        return $vendorPath;
    }
    
    // Try other potential locations
    $possiblePaths = [
        $docRoot . '/assets/vendor/yt-dlp',
        $docRoot . '/assets/bin/yt-dlp',
        $docRoot . '/utils/bin/yt-dlp',
        '/usr/local/bin/yt-dlp',
        '/usr/bin/yt-dlp',
        'yt-dlp'
    ];
    
    if (stripos(PHP_OS, 'WIN') !== false) {
        $possiblePaths = array_map(function($path) {
            return $path . '.exe';
        }, $possiblePaths);
    }
    
    foreach ($possiblePaths as $path) {
        if ($path === 'yt-dlp' || $path === 'yt-dlp.exe' || file_exists($path)) {
            return $path;
        }
    }
    
    // Default fallback
    return stripos(PHP_OS, 'WIN') !== false ? 'yt-dlp.exe' : 'yt-dlp';
}

// Update video status in database
function updateVideoStatus($id, $status, $message = null, $progress = null) {
    global $pdo;
    
    try {
        $sql = "UPDATE processed_videos SET processing_status = :status";
        $params = [':status' => $status, ':id' => $id];
        
        if ($message !== null) {
            $sql .= ", status_message = :message";
            $params[':message'] = $message;
        }
        
        if ($progress !== null) {
            $sql .= ", download_progress = :progress";
            $params[':progress'] = $progress;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        logError("Failed to update video status: " . $e->getMessage());
        return false;
    }
}

// Main process
try {
    // Fetch video details
    $stmt = $pdo->prepare("SELECT * FROM processed_videos WHERE id = :id");
    $stmt->execute([':id' => $videoId]);
    $videoData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$videoData) {
        throw new Exception("Video record not found for ID $videoId");
    }
    
    // Check if it's a URL that needs downloading
    if ($videoData['source_type'] !== 'url' || empty($videoData['video_url'])) {
        throw new Exception("Invalid source type or missing URL");
    }
    
    $videoUrl = $videoData['video_url'];
    
    // Update status to show we're working on it
    updateVideoStatus($videoId, 'processing', 'Starting download...', 10);
    logMessage("Starting to process video from URL: $videoUrl");
    
    // Find yt-dlp binary
    $ytdlpPath = findYtDlpBinary();
    logMessage("Using yt-dlp binary at: $ytdlpPath");
    
    // Create download directory if it doesn't exist
    $downloadDir = $docRoot . '/uploads/videos/';
    if (!is_dir($downloadDir)) {
        if (!mkdir($downloadDir, 0755, true)) {
            throw new Exception("Failed to create download directory");
        }
    }
    
    // Generate output filename
    $outputFile = $downloadDir . 'video_' . $videoId . '.mp4';
    $tempJsonFile = $downloadDir . 'video_' . $videoId . '_info.json';
    
    // Build command - first get video info
    $infoCommand = escapeshellcmd($ytdlpPath) . 
                   ' --dump-json ' . 
                   ' --no-playlist ' .
                   escapeshellarg($videoUrl) . 
                   ' > ' . escapeshellarg($tempJsonFile);
    
    // Execute info command
    logMessage("Executing info command: $infoCommand");
    updateVideoStatus($videoId, 'processing', 'Getting video information...', 15);
    exec($infoCommand, $output, $returnVar);
    
    if ($returnVar !== 0 || !file_exists($tempJsonFile)) {
        throw new Exception("Failed to get video info. Return code: $returnVar");
    }
    
    // Read the JSON data
    $videoInfo = json_decode(file_get_contents($tempJsonFile), true);
    if (!$videoInfo || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse video info JSON: " . json_last_error_msg());
    }
    
    // Get video title for better naming
    $videoTitle = isset($videoInfo['title']) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $videoInfo['title']) : 'video_' . $videoId;
    $outputFile = $downloadDir . $videoTitle . '_' . $videoId . '.mp4';
    
    // Download the actual video
    $downloadCommand = escapeshellcmd($ytdlpPath) . 
                      ' --format "best[ext=mp4]/best" ' .
                      ' --output ' . escapeshellarg($outputFile) . 
                      ' --no-playlist ' .
                      ' --no-warnings ' .
                      ' --no-continue ' .
                      escapeshellarg($videoUrl);
    
    logMessage("Executing download command: $downloadCommand");
    updateVideoStatus($videoId, 'processing', 'Downloading video...', 20);
    
    // Execute the download with progress updates
    $process = popen($downloadCommand . ' 2>&1', 'r');
    if ($process) {
        $progress = 20;
        while (!feof($process)) {
            $line = fgets($process);
            if ($line) {
                // Check for download progress percentage
                if (preg_match('/(\d+\.\d+)%/', $line, $matches)) {
                    $downloadPercent = floatval($matches[1]);
                    $progress = 20 + ($downloadPercent * 0.6); // Scale from 20% to 80%
                    updateVideoStatus($videoId, 'processing', "Downloading: {$downloadPercent}%", $progress);
                }
                logMessage("Download output: $line");
            }
        }
        pclose($process);
    }
    
    // Verify the file was downloaded
    if (!file_exists($outputFile)) {
        throw new Exception("Download failed - output file not found");
    }
    
    // Update the database with the local file path
    $relativePath = '/uploads/videos/' . basename($outputFile);
    $stmt = $pdo->prepare("
        UPDATE processed_videos 
        SET local_file_path = :path,
            download_progress = 100,
            status_message = 'Download complete, analyzing content...'
        WHERE id = :id
    ");
    $stmt->execute([':path' => $relativePath, ':id' => $videoId]);
    
    // Cleanup the JSON info file
    if (file_exists($tempJsonFile)) {
        unlink($tempJsonFile);
    }
    
    // Run AI analysis (this would be your actual analysis code)
    // For demonstration, we'll just update the status with dummy tags
    logMessage("Download complete. Running analysis...");
    updateVideoStatus($videoId, 'processing', 'Analyzing video content...', 90);
    
    // Simulate processing time
    sleep(5);
    
    // Update with "completed" status and dummy results
    $dummyTags = ["tag1", "tag2", "tag3"];
    $dummyPerformers = [
        ["name" => "Performer 1", "confidence" => 85],
        ["name" => "Performer 2", "confidence" => 92]
    ];
    
    $stmt = $pdo->prepare("
        UPDATE processed_videos 
        SET processing_status = 'completed',
            status_message = 'Processing complete',
            download_progress = 100,
            processed_tags = :tags,
            processed_performers = :performers
        WHERE id = :id
    ");
    $stmt->execute([
        ':tags' => json_encode($dummyTags),
        ':performers' => json_encode($dummyPerformers),
        ':id' => $videoId
    ]);
    
    logMessage("Processing completed successfully for video ID: $videoId");
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    logError($errorMessage);
    updateVideoStatus($videoId, 'failed', "Error: $errorMessage");
}
