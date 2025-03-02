<?php

namespace Utils\Services\DownloaderUtils;

use Exception;

class YtDlpDownloader
{
    private $ytDlpPath;
    private $pythonAvailable;
    private $logger;
    
    public function __construct($ytDlpPath, $pythonAvailable, $logger)
    {
        $this->ytDlpPath = $ytDlpPath;
        $this->pythonAvailable = $pythonAvailable;
        $this->logger = $logger;
    }
    
    /**
     * Direct download using yt-dlp command
     */
    public function directDownload($videoId, $url, $fileName, $uploadDir)
    {
        try {
            $outputPath = $uploadDir . $fileName;
            $ytdlpPath = $this->ytDlpPath;
            
            // Ensure binary is executable
            if (stripos(PHP_OS, 'WIN') === false && file_exists($ytdlpPath)) {
                chmod($ytdlpPath, 0755);
            }
            
            // Build the command 
            $cmd = sprintf(
                '%s --format "best[ext=mp4]/best" --max-filesize %s --output "%s" "%s" --no-playlist', 
                escapeshellcmd($ytdlpPath),
                escapeshellarg(MAX_VIDEO_SIZE),
                escapeshellarg($outputPath),
                escapeshellarg($url)
            );
            
            $this->logger->log("Executing yt-dlp command: {$cmd}");
            
            // Execute the command and capture output
            $output = [];
            $returnVal = null;
            exec($cmd . " 2>&1", $output, $returnVal);
            
            $outputStr = implode("\n", $output);
            $this->logger->log("Command output: {$outputStr}");
            $this->logger->log("Return value: {$returnVal}");
            
            if ($returnVal !== 0) {
                throw new Exception("Command failed with return code {$returnVal}: {$outputStr}");
            }
            
            // Check if file was created
            if (!file_exists($outputPath)) {
                throw new Exception("File not created after download command");
            }
            
            $fileSize = filesize($outputPath);
            $this->logger->log("Download successful. File size: {$fileSize} bytes");
            
            if ($fileSize < 1000) { // If file is too small, it might be an error message
                $content = file_get_contents($outputPath);
                if (stripos($content, 'error') !== false) {
                    throw new Exception("Download produced error file: {$content}");
                }
            }
            
            // Return success
            $relativePath = '/uploads/videos/' . $fileName;
            
            return [
                'status' => 'success',
                'message' => 'Video downloaded successfully',
                'path' => $relativePath
            ];
            
        } catch (Exception $e) {
            $this->logger->log("Direct download failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Direct download failed: ' . $e->getMessage()
            ];
        }
    }
}
