<?php
namespace Utils\Workers;

use Monolog\Logger;
use Exception;
use PDO;

/**
 * Manages video processing queue entries in the database
 */
class VideoQueueManager {
    private $pdo;
    private $logger;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     * @param Logger $logger Logger instance
     */
    public function __construct(PDO $pdo, Logger $logger) {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }
    
    /**
     * Add a video to the processing queue
     * 
     * @param int $videoId ID of the video to process
     * @return int|false Queue entry ID or false on failure
     */
    public function addToQueue(int $videoId): int|false {
        try {
            // Check if video is already in queue
            $stmt = $this->pdo->prepare("
                SELECT id FROM video_processing_queue 
                WHERE video_id = :video_id AND status IN ('pending', 'processing')
            ");
            $stmt->execute([':video_id' => $videoId]);
            
            if ($stmt->fetch()) {
                $this->logger->info('Video already in queue', ['video_id' => $videoId]);
                return false;
            }
            
            // Add to queue
            $stmt = $this->pdo->prepare("
                INSERT INTO video_processing_queue (video_id, status)
                VALUES (:video_id, 'pending')
            ");
            $stmt->execute([':video_id' => $videoId]);
            
            $queueId = $this->pdo->lastInsertId();
            $this->logger->info('Added video to processing queue', [
                'video_id' => $videoId,
                'queue_id' => $queueId
            ]);
            
            return $queueId;
        } catch (Exception $e) {
            $this->logger->error('Failed to add video to queue', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Update the status of a queue entry
     * 
     * @param int $queueId Queue entry ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updateStatus(int $queueId, string $status): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE video_processing_queue
                SET status = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                ':id' => $queueId,
                ':status' => $status
            ]);
            
            if ($result) {
                $this->logger->info('Updated queue entry status', [
                    'queue_id' => $queueId,
                    'status' => $status
                ]);
            } else {
                $this->logger->warning('Failed to update queue entry status', [
                    'queue_id' => $queueId
                ]);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error('Error updating queue entry status', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get a queue entry by ID
     * 
     * @param int $queueId Queue entry ID
     * @return array|false Queue entry data or false if not found
     */
    public function getQueueEntry(int $queueId): array|false {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM video_processing_queue
                WHERE id = :id
            ");
            $stmt->execute([':id' => $queueId]);
            
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$entry) {
                $this->logger->warning('Queue entry not found', ['queue_id' => $queueId]);
                return false;
            }
            
            return $entry;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving queue entry', [
                'queue_id' => $queueId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get video data by ID
     * 
     * @param int $videoId Video ID
     * @return array|false Video data or false if not found
     */
    public function getVideo(int $videoId): array|false {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM processed_videos
                WHERE id = :id
            ");
            $stmt->execute([':id' => $videoId]);
            
            $video = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$video) {
                $this->logger->warning('Video not found', ['video_id' => $videoId]);
                return false;
            }
            
            return $video;
        } catch (Exception $e) {
            $this->logger->error('Error retrieving video data', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get pending videos that need to be processed
     * 
     * @param int $limit Maximum number of videos to retrieve
     * @return array List of pending videos
     */
    public function getPendingVideos(int $limit = 10): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.id AS video_id, v.video_url, v.source_type, v.source_path
                FROM processed_videos v
                LEFT JOIN video_processing_queue q ON v.id = q.video_id AND q.status IN ('pending', 'processing')
                WHERE v.processing_status = 'pending'
                AND q.id IS NULL
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('Error retrieving pending videos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Add multiple pending videos to queue
     * 
     * @param int $limit Maximum number of videos to add to queue
     * @return int Number of videos added
     */
    public function queuePendingVideos(int $limit = 10): int {
        $videos = $this->getPendingVideos($limit);
        $added = 0;
        
        foreach ($videos as $video) {
            if ($this->addToQueue($video['video_id'])) {
                $added++;
            }
        }
        
        $this->logger->info('Added pending videos to queue', ['count' => $added]);
        return $added;
    }
    
    /**
     * Retry failed jobs in the queue
     * 
     * @return int Number of jobs reset
     */
    public function retryFailedJobs(): int {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE video_processing_queue
                SET status = 'pending', updated_at = CURRENT_TIMESTAMP
                WHERE status = 'failed'
            ");
            
            $stmt->execute();
            $count = $stmt->rowCount();
            
            $this->logger->info('Reset failed jobs to pending', ['count' => $count]);
            return $count;
        } catch (Exception $e) {
            $this->logger->error('Error resetting failed jobs', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
