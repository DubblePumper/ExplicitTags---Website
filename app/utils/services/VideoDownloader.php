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

    public function __construct($pdo)
    {
        // Ensure the upload directory exists
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/videos/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $this->pdo = $pdo;

        // Create YoutubeDl instance with yt-dlp binary
        $this->youtubeDl = new YoutubeDl();
        
        // Use yt-dlp instead of youtube-dl
        $this->youtubeDl->setBinPath($this->findYtDlpBinary());
    }
    
    /**
     * Find the yt-dlp binary on the system
     * @return string Path to yt-dlp binary
     */
    private function findYtDlpBinary()
    {
        // Search in common locations
        $possiblePaths = [
            '/usr/local/bin/yt-dlp',
            '/usr/bin/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/bin/yt-dlp',
            'yt-dlp' // For Windows or when in PATH
        ];
        
        foreach ($possiblePaths as $path) {
            if ($path === 'yt-dlp' || file_exists($path)) {
                return $path;
            }
        }
        
        // Default to yt-dlp and hope it's in the PATH
        return 'yt-dlp';
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
            $this->updateStatus($videoId, 'processing', 'Downloading video...');

            // Generate a unique filename
            $fileName = uniqid('video_') . '.mp4';
            $filePath = $this->uploadDir . $fileName;
            
            // Configure youtube-dl options
            $options = Options::create()
                ->downloadPath($this->uploadDir)
                ->output($fileName)
                ->format('best[ext=mp4]/best') // Try to get mp4 format
                ->maxFilesize('500M')          // Limit file size to 500MB
                ->retries(3);                  // Retry 3 times if download fails
            
            // Download the video
            $this->youtubeDl->onProgress(function ($progress) use ($videoId) {
                // Update download progress in database
                $downloadPercent = isset($progress['percentage']) ? floatval($progress['percentage']) : 0;
                $this->updateProgress($videoId, $downloadPercent);
            });
            
            $collection = $this->youtubeDl->download($options, $url);
            $video = $collection->first();
            
            if ($video) {
                $relativePath = '/uploads/videos/' . $fileName;
                
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
            $this->updateStatus($videoId, 'failed', 'Download process failed: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Download process failed: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            // Handle general exception
            $this->updateStatus($videoId, 'failed', 'Download failed: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Download failed: ' . $e->getMessage()
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
            
            return $stmt->execute([
                ':id' => $videoId,
                ':status' => $status,
                ':message' => $message
            ]);
        } catch (\PDOException $e) {
            error_log("Database error when updating status: " . $e->getMessage());
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
            
            return $stmt->execute([
                ':id' => $videoId,
                ':progress' => $progressPercent
            ]);
        } catch (\PDOException $e) {
            error_log("Database error when updating progress: " . $e->getMessage());
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
            
            return $stmt->execute([
                ':id' => $videoId,
                ':path' => $path
            ]);
        } catch (\PDOException $e) {
            error_log("Database error when updating video path: " . $e->getMessage());
            return false;
        }
    }
}
