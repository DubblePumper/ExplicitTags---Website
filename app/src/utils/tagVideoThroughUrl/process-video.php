<?php
// Make sure there are no whitespace or newlines before the opening PHP tag

// Buffer output to prevent "headers already sent" errors
ob_start();

// Define base path only if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

// Basic error setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/src/Utils/tagVideoThroughUrl/database-functions.php';

// Set up custom logging
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use YoutubeDl\YoutubeDl;
use YoutubeDl\Options;
use YoutubeDl\Exception\ExecutableNotFoundException;
use YoutubeDl\Exception\YoutubeDlException;
use YoutubeDl\Exception\DownloadException;

use Symfony\Component\Process\Exception\ProcessFailedException;
// Initialize log directories
$logDir = BASE_PATH . '/storage/logs';
$uploadsDir = BASE_PATH . '/storage/uploads/videos';

// Create directories if they don't exist
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Setup loggers
$downloadLogger = new Logger('video_downloader');
$downloadLogger->pushHandler(new StreamHandler($logDir . '/video_downloader.log', Logger::DEBUG));

$processingLogger = new Logger('video_processing');
$processingLogger->pushHandler(new StreamHandler($logDir . '/video_processing.log', Logger::DEBUG));

/**
 * Extract viewkey from URL
 * 
 * @param string $url URL to extract viewkey from
 * @return string Extracted viewkey or '0' if not found
 */
function extractViewkey($url)
{
    $viewkey = '0';

    // Extract from common patterns
    if (preg_match('/[?&]viewkey=([^&]+)/', $url, $matches)) {
        $viewkey = $matches[1];
    } elseif (preg_match('/\/([a-z0-9]+)(?:\/|\.|$)/', $url, $matches)) {
        $viewkey = $matches[1];
    }

    // Remove any invalid characters
    $viewkey = preg_replace('/[^a-z0-9_-]/', '', $viewkey);

    return $viewkey ?: '0';
}

// Function to download video
function downloadVideo($url, $downloadLogger, $uploadsDir, $videoId = null)
{
    try {
        // Extract viewkey from URL
        $viewkey = extractViewkey($url);

        // Configure youtube-dl
        $binPath = BASE_PATH . '/vendor/yt-dlp_linux';
        if (PHP_OS_FAMILY === 'Windows') {
            $binPath = BASE_PATH . '/vendor/yt-dlp.exe';
        }

        $downloadLogger->info('Starting download process for URL: ' . $url);
        $downloadLogger->info('Using binary at: ' . $binPath);

        if (!file_exists($binPath)) {
            throw new ExecutableNotFoundException('yt-dlp binary not found at: ' . $binPath);
        }

        // Generate unique ID part of filename
        $uniqueId = uniqid();

        // Define custom filename format with extension placeholder: video_{$videoId}_{$viewkey}_{$uniqueId}.%(ext)s
        $customFileName = "video_{$videoId}_{$viewkey}_{$uniqueId}.%(ext)s";

        $downloadLogger->info('Using custom filename format', [
            'format' => $customFileName,
            'video_id' => $videoId,
            'viewkey' => $viewkey,
            'unique_id' => $uniqueId
        ]);

        // Create YoutubeDl instance
        $youtubeDl = new YoutubeDl();
        $youtubeDl->setBinPath($binPath);

        // Create options using fluent interface - CORRECT WAY TO SET DOWNLOAD PATH
        $options = Options::create()
            ->downloadPath($uploadsDir)     // Set download path first
            ->output($customFileName)        // Set filename with extension placeholder
            ->format('best[height<=720]')
            ->noCheckCertificate(true)
            ->noPlaylist()
            ->url($url);

        $downloadLogger->info('Starting download with options', [
            'format' => 'best[height<=720]',
            'output' => $customFileName,
            'download_path' => $uploadsDir
        ]);

        // Download the video
        $video = $youtubeDl->download($options);

        // Get the video information
        $videos = $video->getVideos();

        if (empty($videos)) {
            throw new YoutubeDlException('No videos were downloaded');
        }

        $videoFilePath = $videos[0]->getFile();

        // If file not found, try to find it by pattern
        if (!$videoFilePath || !file_exists($videoFilePath)) {
            $expectedFilePattern = $uploadsDir . "/video_{$videoId}_{$viewkey}_{$uniqueId}.*";
            $matchingFiles = glob($expectedFilePattern);

            if (!empty($matchingFiles)) {
                $videoFilePath = $matchingFiles[0];
            } else {
                // Final fallback: get latest file in directory
                $files = glob($uploadsDir . '/*');
                if (!empty($files)) {
                    $latestFile = null;
                    $latestTime = 0;

                    foreach ($files as $file) {
                        $fileTime = filemtime($file);
                        if ($fileTime > $latestTime) {
                            $latestTime = $fileTime;
                            $latestFile = $file;
                        }
                    }

                    $videoFilePath = $latestFile;
                }
            }
        }

        $downloadLogger->info('Download completed successfully', ['file_path' => $videoFilePath]);

        return [
            'success' => true,
            'file_path' => $videoFilePath,
            'info' => $videos[0]->getTitle(),
            'viewkey' => $viewkey
        ];
    } catch (ExecutableNotFoundException $e) {
        $downloadLogger->error('Executable not found: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'YouTube downloader executable not found: ' . $e->getMessage()
        ];
    } catch (YoutubeDlException $e) {
        $downloadLogger->error('Download error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Failed to download video: ' . $e->getMessage()
        ];
    } catch (ProcessFailedException $e) {
        $downloadLogger->error('Process failed: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Video processing failed: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        $downloadLogger->error('General error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'An error occurred: ' . $e->getMessage()
        ];
    }
}

// Initialize variables
$error = null;
$success = false;
$processingStatus = 'pending';
$statusMessage = '';
$videoId = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processingLogger->info('Received video processing request');

    // Check if we have a URL or an uploaded file
    if (!empty($_POST['videoUrl'])) {
        // Process URL submission
        $videoUrl = filter_var($_POST['videoUrl'], FILTER_SANITIZE_URL);
        $processingLogger->info('Processing video URL: ' . $videoUrl);

        // Validate the URL
        if (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            try {
                // Save initial entry to database
                $videoData = [
                    'source_type' => 'url',
                    'video_url' => $videoUrl,
                    'processing_status' => 'pending',
                    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ];

                // Insert into database
                $pdo = testDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO processed_videos 
                    (source_type, video_url, processing_status, user_ip, created_at, updated_at) 
                    VALUES (:source_type, :video_url, :processing_status, :user_ip, NOW(), NOW())
                ");
                $stmt->execute([
                    ':source_type' => $videoData['source_type'],
                    ':video_url' => $videoData['video_url'],
                    ':processing_status' => $videoData['processing_status'],
                    ':user_ip' => $videoData['user_ip']
                ]);

                $videoId = $pdo->lastInsertId();

                $processingLogger->info('Created database entry', ['video_id' => $videoId]);

                // Start the download process
                updateVideoStatus($videoId, 'processing', 'Starting download...', 0);

                // Pass videoId to the downloadVideo function
                $downloadResult = downloadVideo($videoUrl, $downloadLogger, $uploadsDir, $videoId);

                if ($downloadResult['success']) {
                    $videoFilePath = $downloadResult['file_path'];
                    $processingLogger->info('Video downloaded successfully', [
                        'video_id' => $videoId,
                        'file_path' => $videoFilePath
                    ]);

                    // Store viewkey in database along with file path
                    $viewkey = $downloadResult['viewkey'] ?? extractViewkey($videoUrl);

                    // Update database with file path
                    $stmt = $pdo->prepare("
                        UPDATE processed_videos 
                        SET source_path = :source_path,
                            status_message = 'Download complete. Starting AI analysis...',
                            download_progress = 100,
                            viewkey = :viewkey,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':source_path' => $videoFilePath,
                        ':viewkey' => $viewkey,
                        ':id' => $videoId
                    ]);

                    $success = true;
                    $processingStatus = 'processing';
                    $statusMessage = 'Video downloaded. Starting AI analysis...';
                } else {
                    $error = $downloadResult['error'];
                    $processingLogger->error('Download failed', [
                        'video_id' => $videoId,
                        'error' => $error
                    ]);

                    // Update database with error
                    updateVideoStatus($videoId, 'failed', $error, 0);
                }
            } catch (Exception $e) {
                $error = "An unexpected error occurred: " . $e->getMessage();
                $processingLogger->error('Exception during processing', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                if (isset($videoId)) {
                    updateVideoStatus($videoId, 'failed', $error, 0);
                }
            }
        } else {
            $error = "Invalid URL provided. Please enter a valid video URL.";
            $processingLogger->warning('Invalid URL submitted: ' . $videoUrl);
        }
    } elseif (!empty($_FILES['videoFile']) && $_FILES['videoFile']['error'] === UPLOAD_ERR_OK) {
        // Process file upload
        $processingLogger->info('Processing uploaded video file');

        try {
            $uploadedFile = $_FILES['videoFile'];
            $tmpName = $uploadedFile['tmp_name'];
            $originalName = $uploadedFile['name'];

            // Check file type
            $allowedTypes = ['video/mp4', 'video/webm', 'video/avi', 'video/quicktime'];
            $fileType = mime_content_type($tmpName);

            if (in_array($fileType, $allowedTypes)) {
                // Save to database first to get an ID
                $videoData = [
                    'source_type' => 'upload',
                    'processing_status' => 'pending',
                    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ];

                // Insert into database
                $pdo = testDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO processed_videos 
                    (source_type, processing_status, user_ip, created_at, updated_at) 
                    VALUES (:source_type, :processing_status, :user_ip, NOW(), NOW())
                ");
                $stmt->execute([
                    ':source_type' => $videoData['source_type'],
                    ':processing_status' => $videoData['processing_status'],
                    ':user_ip' => $videoData['user_ip']
                ]);

                $videoId = $pdo->lastInsertId();

                // Generate a unique filename using the required format
                $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                $uniqueId = uniqid();
                $newFilename = "video_{$videoId}_0_{$uniqueId}.{$fileExtension}";
                $targetFilePath = $uploadsDir . '/' . $newFilename;

                // Ensure uploads directory exists
                if (!file_exists($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                // Move uploaded file
                if (move_uploaded_file($tmpName, $targetFilePath)) {
                    $processingLogger->info('File uploaded successfully', [
                        'original_name' => $originalName,
                        'saved_as' => $targetFilePath
                    ]);

                    // Update database with file path
                    $stmt = $pdo->prepare("
                        UPDATE processed_videos 
                        SET source_path = :source_path,
                            status_message = 'Upload complete. Starting AI analysis...',
                            processing_status = 'processing',
                            download_progress = 100,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':source_path' => $targetFilePath,
                        ':id' => $videoId
                    ]);

                    $success = true;
                    $processingStatus = 'processing';
                    $statusMessage = 'Upload complete. Starting AI analysis...';

                    $processingLogger->info('Created database entry for uploaded file', ['video_id' => $videoId]);
                } else {
                    $error = "Failed to move uploaded file.";
                    $processingLogger->error('Failed to move uploaded file', [
                        'from' => $tmpName,
                        'to' => $targetFilePath
                    ]);

                    // Update database with error
                    updateVideoStatus($videoId, 'failed', $error, 0);
                }
            } else {
                $error = "Invalid file type. Please upload a video file.";
                $processingLogger->warning('Invalid file type uploaded', [
                    'type' => $fileType,
                    'allowed' => implode(', ', $allowedTypes)
                ]);
            }
        } catch (Exception $e) {
            $error = "An error occurred processing the uploaded file: " . $e->getMessage();
            $processingLogger->error('Exception during file upload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($videoId)) {
                updateVideoStatus($videoId, 'failed', $error, 0);
            }
        }
    } else {
        $error = "No video URL or file provided.";
        $processingLogger->warning('No video URL or file provided in request');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // Check status of existing video
    $videoId = (int)$_GET['id'];
    $processingLogger->info('Checking status for video ID: ' . $videoId);

    $videoData = getVideoTask($videoId);

    if ($videoData) {
        $processingStatus = $videoData['processing_status'] ?? 'pending';
        $statusMessage = $videoData['status_message'] ?? '';
        $success = true;

        $processingLogger->info('Retrieved video status', [
            'video_id' => $videoId,
            'status' => $processingStatus,
            'message' => $statusMessage
        ]);
    } else {
        $error = "Video not found.";
        $processingLogger->warning('Video not found', ['video_id' => $videoId]);
    }
}

// Ensure output buffer is clean before sending any content
if (ob_get_length()) ob_clean();
?>

<div class="min-h-screen py-8 px-4">
    <div class="max-w-3xl mx-auto">
        <?php if ($error): ?>
            <!-- Error message -->
            <div class="bg-red-900/50 border border-red-500 text-red-100 px-6 py-4 rounded-lg shadow-lg mb-6 backdrop-filter backdrop-blur-sm" role="alert">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">Error:</span>
                    <p class="ml-2"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <div class="mt-4 flex justify-center">
                    <a href="/tag" class="inline-flex items-center px-4 py-2 bg-secondaryDarker/30 text-secondary border border-secondary rounded-md hover:bg-secondary/20 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Upload
                    </a>
                </div>
            </div>
        <?php elseif ($success): ?>
            <!-- Success message and processing status -->
            <div class="bg-gray-900/50 border border-primairy p-6 rounded-lg shadow-lg backdrop-filter backdrop-blur-sm">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-secondary mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <h2 class="text-xl md:text-2xl font-bold text-secondary mb-2">Video Received Successfully!</h2>
                    <p class="text-TextWhite mb-6">Your video is now being processed by our AI analysis system.</p>

                    <!-- Processing status indicator -->
                    <div class="mb-8">
                        <div class="relative pt-1">
                            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded-full bg-gray-700">
                                <div id="progress-bar" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-secondary to-tertery" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-400">
                                <span>Upload</span>
                                <span>Download</span>
                                <span>AI Analysis</span>
                                <span>Results</span>
                            </div>
                        </div>
                        <p id="status-message" class="text-gray-300 mt-3">
                            <?php
                            if ($processingStatus === 'pending') {
                                echo "Waiting to start processing...";
                            } elseif ($processingStatus === 'processing') {
                                echo isset($statusMessage) ? htmlspecialchars($statusMessage) : "Processing your video...";
                            } elseif ($processingStatus === 'completed') {
                                echo "Analysis complete!";
                            } elseif ($processingStatus === 'failed') {
                                echo isset($statusMessage) ? htmlspecialchars($statusMessage) : "Processing failed. Please try again.";
                            }
                            ?>
                        </p>
                    </div>

                    <input type="hidden" id="video-id" value="<?php echo $videoId; ?>">

                    <!-- Result will appear here when ready -->
                    <div id="result-container" class="hidden mb-6">
                        <div class="bg-gray-800/50 border border-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-secondary mb-2">Results</h3>
                            <div id="result-content" class="text-TextWhite">
                                <!-- Results will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col md:flex-row justify-center gap-4">
                        <a href="/tag" class="w-full md:w-auto inline-flex items-center justify-center px-6 py-2 bg-secondaryDarker/30 text-secondary border border-secondary rounded-md hover:bg-secondary/20 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Upload Another Video
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Redirect to upload page if accessed directly -->
            <?php
            ob_clean(); // Clear any previous output
            ob_start();
            header("Location: /tag");
            ob_end_flush();
            exit;
            ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoId = document.getElementById('video-id').value;
            const progressBar = document.getElementById('progress-bar');
            const statusMessage = document.getElementById('status-message');
            const resultContainer = document.getElementById('result-container');
            const resultContent = document.getElementById('result-content');

            let checkStatusInterval;
            let failedAttempts = 0;
            const maxFailedAttempts = 5;

            // Function to check processing status
            function checkStatus() {
                fetch(`/api/check-video-status?id=${videoId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Reset failed attempts counter on successful response
                        failedAttempts = 0;

                        // Update progress based on processing stage
                        let progress = 0;

                        if (data.processing_status === 'pending') {
                            progress = 10;
                        } else if (data.processing_status === 'processing') {
                            // If download_progress is available, use it to calculate overall progress
                            if (data.download_progress !== null) {
                                // Scale download progress from 0-100 to 10-50
                                progress = 10 + (data.download_progress * 0.4);
                            } else {
                                // Default progress for processing state
                                progress = 50;
                            }
                        } else if (data.processing_status === 'completed') {
                            progress = 100;

                            // Display results if available
                            if (data.result_data) {
                                resultContainer.classList.remove('hidden');
                                resultContent.innerHTML = formatResults(data.result_data);
                            }

                            // Stop checking status
                            clearInterval(checkStatusInterval);
                        } else if (data.processing_status === 'failed') {
                            // Show error message and stop checking
                            statusMessage.textContent = data.status_message || 'Processing failed';
                            statusMessage.classList.add('text-red-400');
                            clearInterval(checkStatusInterval);
                            return;
                        }

                        // Update progress bar
                        progressBar.style.width = `${progress}%`;

                        // Update status message
                        if (data.status_message) {
                            statusMessage.textContent = data.status_message;
                        }
                    })
                    .catch(error => {
                        console.error('Error checking status:', error);

                        // Increment failed attempts
                        failedAttempts++;

                        // If too many failures, stop checking
                        if (failedAttempts >= maxFailedAttempts) {
                            statusMessage.textContent = 'Error checking status. Please refresh the page.';
                            statusMessage.classList.add('text-red-400');
                            clearInterval(checkStatusInterval);
                        }
                    });
            }

            // Format results for display
            function formatResults(data) {
                if (!data) return '<p>No results available</p>';

                // Build HTML for results
                let html = '<div class="space-y-4">';

                // Display performers if available
                if (data.performers && data.performers.length > 0) {
                    html += `<div class="mb-4">
                    <h4 class="font-medium text-secondary mb-2">Detected Performers:</h4>
                    <div class="flex flex-wrap gap-2">`;

                    data.performers.forEach(performer => {
                        html += `<span class="px-2 py-1 bg-secondary/20 text-secondary rounded-full text-xs">
                        ${performer.name} (${performer.confidence.toFixed(2)}%)
                    </span>`;
                    });

                    html += `</div></div>`;
                }

                // Display tags if available
                if (data.tags && data.tags.length > 0) {
                    html += `<div>
                    <h4 class="font-medium text-secondary mb-2">Content Tags:</h4>
                    <div class="flex flex-wrap gap-2">`;

                    data.tags.forEach(tag => {
                        html += `<span class="px-2 py-1 bg-tertery/20 text-tertery rounded-full text-xs">
                        ${tag.name} (${tag.confidence.toFixed(2)}%)
                    </span>`;
                    });

                    html += `</div></div>`;
                }

                html += '</div>';
                return html;
            }

            // Start checking status periodically
            checkStatus(); // Check immediately once
            checkStatusInterval = setInterval(checkStatus, 3000); // Then check every 3 seconds
        });
    </script>
<?php endif; ?>