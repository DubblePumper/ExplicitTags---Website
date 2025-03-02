<?php

namespace Utils\Services\DownloaderUtils;

use Exception;
use PDO;

class DummyDataGenerator
{
    private $pdo;
    private $logger;
    
    public function __construct($pdo, $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    /**
     * Add dummy analysis results for testing purposes
     */
    public function addDummyAnalysisResults($videoId)
    {
        try {
            // Example performers with confidence values
            $performers = [
                ['name' => 'Emma Watson', 'confidence' => 87],
                ['name' => 'Scarlett Johansson', 'confidence' => 72]
            ];
            
            // Example tags
            $tags = ['blonde', 'outdoor', 'beach', 'swimsuit', 'summer'];
            
            $stmt = $this->pdo->prepare("
                UPDATE processed_videos 
                SET processing_status = 'completed',
                    status_message = 'Processing complete',
                    processed_performers = :performers,
                    processed_tags = :tags,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':id' => $videoId,
                ':performers' => json_encode($performers),
                ':tags' => json_encode($tags)
            ]);
            
            $this->logger->log("Added dummy analysis results for video ID {$videoId}. Result: " . ($result ? 'success' : 'failed'));
            
            return $result;
        } catch (Exception $e) {
            $this->logger->log("Database error when adding dummy results: " . $e->getMessage());
            return false;
        }
    }
}
