<?php

namespace Utils\Services\DownloaderUtils;

use Exception;
use PDO;

class DatabaseUpdater
{
    private $pdo;
    private $logger;
    
    public function __construct($pdo, $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    /**
     * Update video status in the database
     */
    public function updateStatus($videoId, $status, $message = '')
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
            
            $this->logger->log("Updated status for video ID {$videoId} to '{$status}' with message '{$message}'. Result: " . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (Exception $e) {
            $this->logger->log("Database error when updating status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update download progress in the database
     */
    public function updateProgress($videoId, $progressPercent)
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
            
            $this->logger->log("Updated progress for video ID {$videoId} to {$progressPercent}%. Result: " . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (Exception $e) {
            $this->logger->log("Database error when updating progress: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update video path in the database
     */
    public function updateVideoPath($videoId, $path)
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
            
            $this->logger->log("Updated video path for ID {$videoId} to '{$path}'. Result: " . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (Exception $e) {
            $this->logger->log("Database error when updating video path: " . $e->getMessage());
            return false;
        }
    }
}
