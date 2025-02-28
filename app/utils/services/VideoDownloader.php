<?php

namespace Utils\Services;

use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Exception;

class VideoDownloader
{
    private $youtubeDl;
    private $uploadDir;
    private $pdo;
    private $debug = true; // Enable debugging

    public function __construct($pdo)
    {
        // Ensure the upload directory exists
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/videos/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $this->pdo = $pdo;
        
        $this->log("VideoDownloader initialized. Upload directory: " . $this->uploadDir);

        // Create YoutubeDl instance with yt-dlp binary
        $this->youtubeDl = new YoutubeDl();
        
        // Use yt-dlp instead of youtube-dl
        $ytdlpPath = $this->findYtDlpBinary();
        $this->log("Using yt-dlp binary at: " . $ytdlpPath);
        $this->youtubeDl->setBinPath($ytdlpPath);
    }
    
    /**
     * Internal logging method
     */
    private function log($message)
    {
        if ($this->debug) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [VideoDownloader] $message";
            
            // Log to file
            $logDir = $_SERVER['DOCUMENT_ROOT'] . '/logs/';
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }
            
            file_put_contents(
                $logDir . 'video_downloader.log',
                $logMessage . PHP_EOL,
                FILE_APPEND
            );
            
            // Output to console if CLI
            if (php_sapi_name() === 'cli') {
                echo $logMessage . PHP_EOL;
            }
        }
    }
    
    /**
     * Find the yt-dlp binary on the system
     * @return string Path to yt-dlp binary
     */
    private function findYtDlpBinary()
    {
        // First check the vendor directory (PREFERRED LOCATION)
        $vendorPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/yt-dlp/yt-dlp';
        if (stripos(PHP_OS, 'WIN') !== false) {
            $vendorPath .= '.exe';
        }
        
        if (file_exists($vendorPath)) {
            $this->log("Found yt-dlp in vendor directory: {$vendorPath}");
            return $vendorPath;
        }
        
        // Search in common locations
        $possiblePaths = [
            // Check all variations of vendor paths
            $_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/bin/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/assets/bin/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/bin/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/vendor/bin/yt-dlp',
            '/usr/local/bin/yt-dlp',
            '/usr/bin/yt-dlp',
            'yt-dlp', // For Windows or when in PATH
            'yt-dlp.exe' // Windows executable
        ];
        
        if (stripos(PHP_OS, 'WIN') !== false) {
            // Add .exe to paths if on Windows
            foreach ($possiblePaths as &$path) {
                if (substr($path, -4) !== '.exe') {
                    $path .= '.exe';
                }
            }
        }
        
        foreach ($possiblePaths as $path) {
            if ($path === 'yt-dlp' || $path === 'yt-dlp.exe' || file_exists($path)) {
                // Test the binary by running it with --version
                try {
                    $testCmd = escapeshellcmd($path) . " --version";
                    $this->log("Testing yt-dlp binary with command: " . $testCmd);
                    $output = shell_exec($testCmd . " 2>&1");
                    $this->log("yt-dlp test output: " . $output);
                    
                    if ($output && strpos($output, 'yt-dlp') !== false) {
                        $this->log("yt-dlp binary verified at: " . $path);
                        return $path;
                    } else {
                        $this->log("yt-dlp binary test failed for path: " . $path);
                    }
                } catch (\Exception $e) {
                    $this->log("Error testing yt-dlp at {$path}: " . $e->getMessage());
                }
            }
        }
        
        // If we get here, we need to download yt-dlp
        try {
            $downloadedPath = $this->downloadYtDlp();
            if ($downloadedPath) {
                $this->log("Successfully downloaded yt-dlp to: " . $downloadedPath);
                return $downloadedPath;
            }
        } catch (\Exception $e) {
            $this->log("Failed to download yt-dlp: " . $e->getMessage());
        }
        
        // Default to yt-dlp and hope it's in the PATH
        $this->log("Using default 'yt-dlp' command (no verified path found)");
        return 'yt-dlp';
    }
    
    /**
     * Download yt-dlp if not available
     */
    private function downloadYtDlp()
    {
        $binDir = $_SERVER['DOCUMENT_ROOT'] . '/bin/';
        if (!file_exists($binDir)) {
            mkdir($binDir, 0755, true);
        }
        
        $targetPath = $binDir . 'yt-dlp';
        $sourceUrl = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp';
        
        // For Windows
        if (stripos(PHP_OS, 'WIN') !== false) {
            $sourceUrl .= '.exe';
            $targetPath .= '.exe';
        }
        
        $this->log("Downloading yt-dlp from {$sourceUrl} to {$targetPath}");
        
        // Download the file
        $fileContent = @file_get_contents($sourceUrl);
        if ($fileContent === false) {
            throw new \Exception("Failed to download yt-dlp");
        }
        
        if (file_put_contents($targetPath, $fileContent) === false) {
            throw new \Exception("Failed to save yt-dlp to {$targetPath}");
        }
        
        // Make executable on Unix systems
        if (stripos(PHP_OS, 'WIN') === false) {
            chmod($targetPath, 0755);
        }
        
        return $targetPath;
    }

    /**
     * Download a video from a URL
     *
     * @param int $videoId The ID of the video in the database
     * @param string $url The URL of the video to download
     * @return array Result with status and file path
     */
    public function downloadVideo($videoId, $url)
    {
        try {
            // Update status to downloading
            $this->updateStatus($videoId, 'processing', 'Initializing download...');
            $this->log("Starting download for video ID {$videoId} from URL: {$url}");

            // Generate a unique filename
            $fileName = uniqid('video_') . '.mp4';
            $filePath = $this->uploadDir . $fileName;
            $this->log("Target file path: {$filePath}");
            
            // Try direct download with yt-dlp command first (more reliable)
            $this->updateStatus($videoId, 'processing', 'Preparing to download video...');
            
            $directResult = $this->directDownload($videoId, $url, $fileName);
            if ($directResult['status'] === 'success') {
                $this->log("Direct download successful: " . json_encode($directResult));
                return $directResult;
            }
            
            // If direct download fails, try with library
            $this->log("Direct download failed, trying with YoutubeDl library");
            $this->updateStatus($videoId, 'processing', 'Downloading video (library method)...');
            
            // Configure youtube-dl options
            $options = Options::create()
                ->downloadPath($this->uploadDir)
                ->output($fileName)
                ->format('best[ext=mp4]/best') // Try to get mp4 format
                ->maxFilesize('500M')          // Limit file size to 500MB
                ->retries(3)                   // Retry 3 times if download fails
                ->continue(true);              // Continue on download errors
            
            $this->log("Download options set: " . json_encode($options->toArray()));
            
            // Set progress callback
            $this->youtubeDl->onProgress(function ($progress) use ($videoId) {
                // Update download progress in database
                if (isset($progress['percentage'])) {
                    $downloadPercent = floatval($progress['percentage']);
                    $this->log("Download progress: {$downloadPercent}%");
                    $this->updateProgress($videoId, $downloadPercent);
                }
            });
            
            // Fix: Check the correct method signature based on the library version
            try {
                // Set URL in options first, then call download() with just options
                $options = $options->url($url);
                $collection = $this->youtubeDl->download($options);
            } catch (\TypeError $e) {
                $this->log("TypeError with download method: " . $e->getMessage());
                // Try alternative approaches if needed
                try {
                    $this->log("Trying with array of URLs");
                    $options = Options::create()
                        ->downloadPath($this->uploadDir)
                        ->output($fileName)
                        ->format('best[ext=mp4]/best')
                        ->maxFilesize('500M')
                        ->retries(3)
                        ->url($url);
                    $collection = $this->youtubeDl->download($options);
                } catch (\Exception $e2) {
                    $this->log("Second attempt failed: " . $e2->getMessage());
                    throw $e2;
                }
            }
            
            // Handle the collection depending on its format
            if (is_array($collection) && isset($collection[0])) {
                $video = $collection[0];
            } elseif (is_object($collection) && method_exists($collection, 'getVideos')) {
                $videos = $collection->getVideos();
                $video = !empty($videos) ? $videos[0] : null;
            } else {
                $this->log("Warning: Unexpected collection format: " . gettype($collection));
                $video = $collection;
            }
            
            if ($video) {
                $relativePath = '/uploads/videos/' . $fileName;
                $this->log("Download complete. Relative path: {$relativePath}");
                
                // Update the database with the downloaded file path
                $this->updateVideoPath($videoId, $relativePath);
                
                return [
                    'status' => 'success',
                    'message' => 'Video downloaded successfully',
                    'path' => $relativePath
                ];
            }
            
            throw new Exception('Video download completed but no video was returned');
            
        } catch (ProcessFailedException $e) {
            // Handle process exception
            $errorMsg = 'Download process failed: ' . $e->getMessage();
            $this->log("ProcessFailedException: " . $errorMsg);
            $this->updateStatus($videoId, 'failed', $errorMsg);
            
            return [
                'status' => 'error',
                'message' => $errorMsg
            ];
        } catch (Exception $e) {
            // Handle general exception
            $errorMsg = 'Download failed: ' . $e->getMessage();
            $this->log("Exception: " . $errorMsg);
            $this->updateStatus($videoId, 'failed', $errorMsg);
            
            return [
                'status' => 'error',
                'message' => $errorMsg
            ];
        }
    }
    
    /**
     * Direct download using yt-dlp command
     */
    private function directDownload($videoId, $url, $fileName)
    {
        try {
            $outputPath = $this->uploadDir . $fileName;
            $ytdlpPath = $this->findYtDlpBinary(); // Use the vendor yt-dlp if available
            
            // Build the yt-dlp command
            $cmd = sprintf(
                '%s --format "best[ext=mp4]/best" --max-filesize 500M --output "%s" "%s" --no-playlist', 
                escapeshellcmd($ytdlpPath),
                escapeshellarg($outputPath),
                escapeshellarg($url)
            );
            
            $this->log("Executing direct download command: {$cmd}");
            $this->updateStatus($videoId, 'processing', 'Running download command...');
            
            // Execute the command and capture output
            $output = [];
            $returnVal = null;
            exec($cmd . " 2>&1", $output, $returnVal);
            
            $outputStr = implode("\n", $output);
            $this->log("Command output: {$outputStr}");
            $this->log("Return value: {$returnVal}");
            
            if ($returnVal !== 0) {
                throw new \Exception("Command failed with return code {$returnVal}: {$outputStr}");
            }
            
            // Check if file was created
            if (!file_exists($outputPath)) {
                throw new \Exception("File not created after download command");
            }
            
            $fileSize = filesize($outputPath);
            $this->log("Download successful. File size: {$fileSize} bytes");
            
            if ($fileSize < 1000) { // If file is too small, it might be an error message
                $content = file_get_contents($outputPath);
                if (stripos($content, 'error') !== false) {
                    throw new \Exception("Download produced error file: {$content}");
                }
            }
            
            // Update the database with the downloaded file path
            $relativePath = '/uploads/videos/' . $fileName;
            $this->updateVideoPath($videoId, $relativePath);
            $this->updateProgress($videoId, 100);
            
            return [
                'status' => 'success',
                'message' => 'Video downloaded successfully',
                'path' => $relativePath
            ];
            
        } catch (\Exception $e) {
            $this->log("Direct download failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Direct download failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update video status in the database
     */
    private function updateStatus($videoId, $status, $message = '')
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE processed_videos 
                SET processing_status = :status,
                    status_message = :message,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':id' => $videoId,
                ':status' => $status,
                ':message' => $message
            ]);
            
            $this->log("Updated status for video ID {$videoId} to '{$status}' with message '{$message}'. Result: " . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (\PDOException $e) {
            $this->log("Database error when updating status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update download progress in the database
     */
    private function updateProgress($videoId, $progressPercent)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE processed_videos 
                SET download_progress = :progress,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':id' => $videoId,
                ':progress' => $progressPercent
            ]);
            
            $this->log("Updated progress for video ID {$videoId} to {$progressPercent}%. Result: " . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (\PDOException $e) {
            $this->log("Database error when updating progress: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update video path in the database
     */
    private function updateVideoPath($videoId, $path)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE processed_videos 
                SET source_path = :path,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':id' => $videoId,
                ':path' => $path
            ]);
            
            $this->log("Updated video path for ID {$videoId} to '{$path}'. Result: " . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (\PDOException $e) {
            $this->log("Database error when updating video path: " . $e->getMessage());
            return false;
        }
    }
}
