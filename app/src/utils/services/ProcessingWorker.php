<?php

namespace Utils\Services;

use Exception;
use Utils\Services\LogManager;

/**
 * Worker class that handles background video processing
 */
class ProcessingWorker
{
    private $pdo;
    private $logger;
    
    /**
     * Initialize the worker
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        
        // Setup logger
        $logFile = dirname($_SERVER['DOCUMENT_ROOT'], 1) . '/storage/logs/video_processing.log';
        $this->logger = new LogManager($logFile, true);
        $this->logger->info("ProcessingWorker initialized");
    }
    
    /**
     * Process a single pending video
     * 
     * @param int $id Video ID to process
     * @return array Processing result
     */
    public function processVideo($id)
    {
        $this->logger->info("Processing video ID: {$id}");
        
        try {
            // Fetch video information
            $stmt = $this->pdo->prepare("
                SELECT * FROM processed_videos
                WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);
            $video = $stmt->fetch();
            
            if (!$video) {
                $this->logger->error("Video ID {$id} not found");
                return [
                    'status' => 'error',
                    'message' => 'Video not found'
                ];
            }
            
            // Update status to processing
            $this->updateStatus($id, 'processing', 'Starting processing...');
            
            // Different handling based on source type
            if ($video['source_type'] === 'url') {
                // For URL source, download the video
                return $this->handleUrlSource($id, $video['video_url']);
            } else if ($video['source_type'] === 'upload') {
                // For uploaded file, just process the file
                return $this->handleUploadedFile($id, $video['source_path']);
            } else {
                $this->logger->error("Unknown source type: {$video['source_type']}");
                $this->updateStatus($id, 'failed', 'Unknown source type');
                return [
                    'status' => 'error',
                    'message' => 'Unknown source type'
                ];
            }
        } catch (Exception $e) {
            $this->logger->error("Error processing video ID {$id}: " . $e->getMessage());
            $this->updateStatus($id, 'failed', 'Processing error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle video from URL source
     */
    private function handleUrlSource($id, $url)
    {
        $this->logger->info("Handling URL source for video {$id}: {$url}");
        
        try {
            // Create VideoDownloader instance
            $downloader = new VideoDownloader($this->pdo);
            
            // Download the video
            $result = $downloader->downloadVideo($id, $url);
            $this->logger->info("Download result: " . json_encode($result));
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error downloading video: " . $e->getMessage());
            $this->updateStatus($id, 'failed', 'Download error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle uploaded file source
     */
    private function handleUploadedFile($id, $filePath)
    {
        $this->logger->info("Handling uploaded file for video {$id}: {$filePath}");
        
        try {
            // Update path if necessary
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
            
            if (!file_exists($fullPath)) {
                $this->logger->error("File not found: {$fullPath}");
                $this->updateStatus($id, 'failed', 'File not found');
                return [
                    'status' => 'error',
                    'message' => 'File not found'
                ];
            }
            
            // Process the video directly
            // For now, just add dummy results
            $this->addDummyAnalysisResults($id);
            $this->updateStatus($id, 'completed', 'Processing complete');
            
            return [
                'status' => 'success',
                'message' => 'File processed successfully'
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Error processing file: " . $e->getMessage());
            $this->updateStatus($id, 'failed', 'Processing error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update the processing status for a video
     */
    private function updateStatus($videoId, $status, $message = '', $progress = null)
    {
        try {
            $params = [
                ':id' => $videoId,
                ':status' => $status,
                ':message' => $message
            ];
            
            $sql = "
                UPDATE processed_videos 
                SET processing_status = :status,
                    status_message = :message,
                    updated_at = NOW()
            ";
            
            // Add download progress if provided
            if ($progress !== null) {
                $sql .= ", download_progress = :progress";
                $params[':progress'] = $progress;
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $this->logger->debug("Updated status for video ID {$videoId}: {$status}, {$message}");
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error updating status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add dummy analysis results for testing purposes
     */
    private function addDummyAnalysisResults($videoId)
    {
        // Some sample performers and tags for testing
        $performers = [
            ['name' => 'Riley Reid', 'confidence' => 96],
            ['name' => 'Johnny Sins', 'confidence' => 89],
            ['name' => 'Emily Willis', 'confidence' => 84],
            ['name' => 'Mia Khalifa', 'confidence' => 92],
            ['name' => 'Lana Rhoades', 'confidence' => 88],
            ['name' => 'Abella Danger', 'confidence' => 91]
        ];
        
        // Randomly select 1-3 performers
        shuffle($performers);
        $selectedPerformers = array_slice($performers, 0, rand(1, 3));
        
        // Sample tags
        $allTags = [
            'anal', 'blowjob', 'threesome', 'blonde', 
            'brunette', 'tattoo', 'teen', 'milf', 
            'hardcore', 'creampie', 'squirt', 'lesbian',
            'big tits', 'ass', 'interracial', 'gangbang',
            'POV', 'cumshot', 'facial', 'natural tits'
        ];
        
        // Randomly select 3-8 tags
        shuffle($allTags);
        $selectedTags = array_slice($allTags, 0, rand(3, 8));
        
        // Combine into results
        $results = [
            'performers' => $selectedPerformers,
            'tags' => $selectedTags
        ];
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE processed_videos 
                SET result_data = :results
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $videoId,
                ':results' => json_encode($results)
            ]);
            
            $this->logger->debug("Added dummy analysis results for video ID {$videoId}");
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error adding dummy results: " . $e->getMessage());
            return false;
        }
    }
}
