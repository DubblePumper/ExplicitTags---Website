<?php
// Make sure there are no whitespace or newlines before the opening PHP tag

// Buffer output to prevent "headers already sent" errors
ob_start();

// Include only the configuration files first to avoid output
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Autoload composer dependencies
require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/autoload.php';

// Initialize variables
$error = null;
$success = false;
$videoId = null;
$processingStatus = null;

// Create PDO instance with error handling
try {
    $pdo = testDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
} catch (PDOException $e) {
    error_log("Database connection error in process-video: " . $e->getMessage());
    $error = "Database connection error: " . $e->getMessage();
} catch (Exception $e) {
    error_log("General error in process-video: " . $e->getMessage());
    $error = "Error: " . $e->getMessage();
}

// Helper function to check if URL is from a supported adult website
function isUrlFromSupportedSite($url, $pdo) {
    $supportedDomains = [];
    
    try {
        // Get all supported websites from the database
        $stmt = $pdo->query("SELECT website_url FROM supported_adult_websites");
        if ($stmt) {
            $sites = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Extract domains from URLs
            foreach ($sites as $siteUrl) {
                $domain = parse_url($siteUrl, PHP_URL_HOST);
                if ($domain) {
                    $supportedDomains[] = strtolower(preg_replace('/^www\./', '', $domain));
                }
            }
        }
        
        // Fallback domains if DB query returns no results
        if (empty($supportedDomains)) {
            $supportedDomains = [
                'pornhub.com', 'redtube.com', 'spankbang.com',
                'tnaflix.com', 'tube8.com', 'xhamster.com',
                'xnxx.com', 'xvideos.com', 'youporn.com'
            ];
        }
        
        // Get the hostname from the URL and remove www if present
        $urlHost = parse_url($url, PHP_URL_HOST);
        if (!$urlHost) return false;
        
        $urlHost = strtolower(preg_replace('/^www\./', '', $urlHost));
        
        // Check if hostname matches or ends with any supported domain
        foreach ($supportedDomains as $domain) {
            if ($urlHost === $domain || preg_match('/' . preg_quote($domain, '/') . '$/', $urlHost)) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking supported sites: " . $e->getMessage());
        
        // Fallback hardcoded list if query fails
        $fallbackDomains = [
            'pornhub.com', 'redtube.com', 'spankbang.com',
            'tnaflix.com', 'tube8.com', 'xhamster.com',
            'xnxx.com', 'xvideos.com', 'youporn.com'
        ];
        
        $urlHost = parse_url($url, PHP_URL_HOST);
        if (!$urlHost) return false;
        
        $urlHost = strtolower(preg_replace('/^www\./', '', $urlHost));
        
        foreach ($fallbackDomains as $domain) {
            if ($urlHost === $domain || preg_match('/' . preg_quote($domain, '/') . '$/', $urlHost)) {
                return true;
            }
        }
        
        return false;
    }
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Get user IP for tracking
    $userIp = $_SERVER['REMOTE_ADDR'];
    $sourceType = null;
    $sourcePath = null;
    $videoUrl = null;
    $returnUrl = isset($_POST['return_url']) ? $_POST['return_url'] : '/tag';
    
    // Check if this is a file upload or URL submission
    if (!empty($_FILES['videoFile']['tmp_name'])) {
        // Handle file upload
        $sourceType = 'upload';
        
        // Create uploads directory if it doesn't exist
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/videos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate a unique filename
        $fileName = uniqid('video_') . '_' . basename($_FILES['videoFile']['name']);
        $targetFilePath = $uploadDir . $fileName;
        
        // Move the uploaded file
        if (move_uploaded_file($_FILES['videoFile']['tmp_name'], $targetFilePath)) {
            $sourcePath = '/uploads/videos/' . $fileName;
        } else {
            $error = "Failed to upload file. Please try again.";
        }
    } elseif (!empty($_POST['videoUrl'])) {
        // Handle URL submission
        $sourceType = 'url';
        $videoUrl = trim($_POST['videoUrl']);
        
        // Basic URL validation
        if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            $error = "Invalid URL format. Please enter a valid URL.";
        } 
        // Check if the URL is from a supported site
        elseif (!isUrlFromSupportedSite($videoUrl, $pdo)) {
            $error = "The provided URL is not from a supported adult website. Please use a URL from a supported platform.";
        }
    } else {
        $error = "No video file or URL provided.";
    }
    
    // If no errors, insert into database
    if (!$error) {
        try {
            // Insert into database with status message
            $stmt = $pdo->prepare("
                INSERT INTO processed_videos 
                (source_type, source_path, video_url, processing_status, user_ip, download_progress, status_message) 
                VALUES (:source_type, :source_path, :video_url, 'pending', :user_ip, 0, :status_message)
            ");
            
            $statusMessage = $sourceType === 'upload' ? 'Upload complete, waiting for processing' : 'Waiting to start download';
            
            $stmt->execute([
                ':source_type' => $sourceType,
                ':source_path' => $sourcePath,
                ':video_url' => $videoUrl,
                ':user_ip' => $userIp,
                ':status_message' => $statusMessage
            ]);
            
            $videoId = $pdo->lastInsertId();
            $success = true;
            
            // If URL submission, trigger download asynchronously
            if ($sourceType === 'url') {
                // Update status to "processing" for URLs
                $updateStmt = $pdo->prepare("
                    UPDATE processed_videos 
                    SET processing_status = 'processing',
                        status_message = 'Queued for download'
                    WHERE id = :id
                ");
                
                $updateStmt->execute([':id' => $videoId]);
                $processingStatus = 'processing';
                
                // Trigger the background worker process (non-blocking)
                $rootPath = $_SERVER['DOCUMENT_ROOT'];
                $cmd = "php {$rootPath}/utils/workers/process_video_queue.php {$videoId} > /dev/null 2>&1 &";
                
                if (stripos(PHP_OS, 'WIN') === 0) {
                    // Windows - use start command
                    pclose(popen("start /B php {$rootPath}\\utils\\workers\\process_video_queue.php {$videoId}", "r"));
                } else {
                    // Linux/Unix - use nohup or background process
                    exec($cmd);
                }
            } else {
                // For direct uploads, just update status to processing
                $updateStmt = $pdo->prepare("
                    UPDATE processed_videos 
                    SET processing_status = 'processing',
                        status_message = 'Processing uploaded video'
                    WHERE id = :id
                ");
                
                $updateStmt->execute([':id' => $videoId]);
                $processingStatus = 'processing';
            }
            
            // If we have a return URL and it's a safe path, redirect with the video ID
            if ($returnUrl && (strpos($returnUrl, '/') === 0)) {
                // Add the ID as a parameter to the return URL
                $separator = strpos($returnUrl, '?') !== false ? '&' : '?';
                
                // Clean output buffer before sending headers
                ob_clean();
                
                // Perform the redirect
                header("Location: {$returnUrl}{$separator}id={$videoId}");
                exit;
            }
            
        } catch (PDOException $e) {
            error_log("Database error during insert: " . $e->getMessage());
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Check if this is a direct access with an ID
if (!$success && !$error && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $videoId = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM processed_videos WHERE id = :id
        ");
        $stmt->execute([':id' => $videoId]);
        $videoData = $stmt->fetch();
        
        if ($videoData) {
            $success = true;
            $processingStatus = $videoData['processing_status'];
        } else {
            $error = "Video not found";
        }
    } catch (PDOException $e) {
        error_log("Error retrieving video data: " . $e->getMessage());
        $error = "Error retrieving video data";
    }
}

// If we have a video ID but no processing status yet, fetch the current status
if ($videoId && !$processingStatus) {
    try {
        $stmt = $pdo->prepare("
            SELECT processing_status, status_message 
            FROM processed_videos 
            WHERE id = :id
        ");
        
        $stmt->execute([':id' => $videoId]);
        $result = $stmt->fetch();
        
        if ($result) {
            $processingStatus = $result['processing_status'];
            $statusMessage = $result['status_message'] ?? '';
        }
    } catch (PDOException $e) {
        error_log("Error checking status: " . $e->getMessage());
        $error = "Error checking status: " . $e->getMessage();
    }
}

// Now include the visual elements - this part always comes after all potential redirects
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/include-all.php';
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
            <script>window.location.href = "/tag";</script>
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
        
        // Initial progress based on current status
        let progress = <?php 
            if ($processingStatus === 'pending') echo "10";
            elseif ($processingStatus === 'processing') echo "30";
            elseif ($processingStatus === 'completed') echo "100";
            elseif ($processingStatus === 'failed') echo "0";
            else echo "0";
        ?>;
        
        updateProgressBar(progress);
        
        // Check status periodically
        let checkInterval = setInterval(checkStatus, 3000);
        
        function checkStatus() {
            // Fix: Use relative path and current protocol
            // Always make the request to the same origin to avoid CORS and protocol issues
            const apiUrl = `/utils/tagVideoThroughUrl/check-processing-status.php?id=${videoId}`;
            
            console.log(`Checking status at: ${apiUrl}`);
            
            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server responded with status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Status check response:", data);
                if (data.status === 'processing') {
                    // Increment progress slightly to show movement
                    if (progress < 70) {
                        progress += 5;
                        updateProgressBar(progress);
                    }
                    statusMessage.textContent = "AI is analyzing your video...";
                } 
                else if (data.status === 'completed') {
                    progress = 100;
                    updateProgressBar(progress);
                    statusMessage.textContent = "Analysis complete!";
                    clearInterval(checkInterval);
                    showResults(data.results);
                } 
                else if (data.status === 'failed') {
                    statusMessage.textContent = "Processing failed. Please try again.";
                    clearInterval(checkInterval);
                }
                else {
                    console.log("Unknown status:", data.status);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                
                // Docker environment check - special handling for Docker
                console.log("Attempting to use alternative URL format...");
                
                // Try directly with the full URL, removing the protocol part to use the current protocol
                const currentProtocol = window.location.protocol;
                const hostname = window.location.hostname;
                const port = window.location.port ? `:${window.location.port}` : '';
                
                // Create URL using the current protocol (http: or https:)
                const fallbackUrl = `${currentProtocol}//${hostname}${port}/utils/tagVideoThroughUrl/check-processing-status.php?id=${videoId}`;
                
                console.log(`Trying fallback URL: ${fallbackUrl}`);
                
                // Add a delay to avoid immediate retry
                setTimeout(() => {
                    fetch(fallbackUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache'
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Fallback response:", data);
                        
                        // Handle the response the same way as the main fetch
                        if (data.status === 'processing') {
                            if (progress < 70) {
                                progress += 5;
                                updateProgressBar(progress);
                            }
                            statusMessage.textContent = "AI is analyzing your video...";
                        } 
                        else if (data.status === 'completed') {
                            progress = 100;
                            updateProgressBar(progress);
                            statusMessage.textContent = "Analysis complete!";
                            clearInterval(checkInterval);
                            showResults(data.results);
                        } 
                        else if (data.status === 'failed') {
                            statusMessage.textContent = "Processing failed. Please try again.";
                            clearInterval(checkInterval);
                        }
                    })
                    .catch(innerError => {
                        console.error("Fallback fetch also failed:", innerError);
                        
                        // Special case for Docker: try with fixed HTTP protocol as last resort
                        const httpFallbackUrl = `http://${hostname}${port}/utils/tagVideoThroughUrl/check-processing-status.php?id=${videoId}`;
                        console.log(`Trying HTTP fallback URL: ${httpFallbackUrl}`);
                        
                        fetch(httpFallbackUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'Cache-Control': 'no-cache'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log("HTTP fallback response:", data);
                            // Process the response as before
                            if (data && data.status) {
                                handleStatusUpdate(data);
                            }
                        })
                        .catch(finalError => {
                            console.error("All fallback attempts failed:", finalError);
                        });
                    });
                }, 1000);
                
                // Continue checking but less frequently if there are errors
                clearInterval(checkInterval);
                checkInterval = setInterval(checkStatus, 10000); // Longer interval after error
            });
        }
        
        // Helper function to handle status updates from any source
        function handleStatusUpdate(data) {
            if (data.status === 'processing') {
                if (progress < 70) {
                    progress += 5;
                    updateProgressBar(progress);
                }
                statusMessage.textContent = "AI is analyzing your video...";
            } 
            else if (data.status === 'completed') {
                progress = 100;
                updateProgressBar(progress);
                statusMessage.textContent = "Analysis complete!";
                clearInterval(checkInterval);
                showResults(data.results);
            } 
            else if (data.status === 'failed') {
                statusMessage.textContent = "Processing failed. Please try again.";
                clearInterval(checkInterval);
            }
        }
        
        function updateProgressBar(value) {
            progressBar.style.width = `${value}%`;
        }
        
        function showResults(results) {
            if (results) {
                resultContainer.classList.remove('hidden');
                
                // Format and display results
                let htmlContent = '';
                
                // Check if there are detected performers
                if (results.performers && results.performers.length > 0) {
                    htmlContent += '<div class="mb-4"><h4 class="text-md font-medium text-secondary mb-2">Detected Performers:</h4><ul class="list-disc list-inside space-y-1">';
                    results.performers.forEach(performer => {
                        htmlContent += `<li>${performer.name} <span class="text-gray-400">(${performer.confidence}% confidence)</span></li>`;
                    });
                    htmlContent += '</ul></div>';
                }
                
                // Check if there are detected tags
                if (results.tags && results.tags.length > 0) {
                    htmlContent += '<div><h4 class="text-md font-medium text-secondary mb-2">Detected Tags:</h4><div class="flex flex-wrap gap-2">';
                    results.tags.forEach(tag => {
                        htmlContent += `<span class="px-2 py-1 text-xs rounded-full bg-secondary/20 border border-secondary text-secondary">${tag}</span>`;
                    });
                    htmlContent += '</div></div>';
                }
                
                resultContent.innerHTML = htmlContent || 'No results available';
            }
        }
    });
</script>
<?php endif; ?>
