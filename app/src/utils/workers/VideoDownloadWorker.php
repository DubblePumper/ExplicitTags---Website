<?php
namespace Utils\Workers;

use Monolog\Level;
use Monolog\Logger;
use Utils\Video\VideoDownloadManager;
use Exception;
use PDO;

/**
 * Worker that processes video download jobs from the queue
 */
class VideoDownloadWorker {
    private $queueService;
    private $queueManager;
    private $logger;
    private $downloadManager;
    private $pdo;
    private $downloadPath;
    
    /**
     * Constructor
     * 
     * @param QueueService $queueService Queue service instance
     * @param VideoQueueManager $queueManager Queue manager instance
     * @param Logger $logger Logger instance
     * @param VideoDownloadManager $downloadManager Download manager instance
     * @param PDO $pdo Database connection
     * @param string $downloadPath Path where videos will be stored
     */
    public function __construct(
        QueueService $queueService, 
        VideoQueueManager $queueManager,
        Logger $logger,
        VideoDownloadManager $downloadManager,
        PDO $pdo,
        string $downloadPath
    ) {
        $this->queueService = $queueService;
        $this->queueManager = $queueManager;
        $this->logger = $logger;
        $this->downloadManager = $downloadManager;
        $this->pdo = $pdo;
        $this->downloadPath = $downloadPath;
        
        $this->logger->info('VideoDownloadWorker initialized');
    }
    
    /**
     * Process a single job from the queue
     * 
     * @param bool $wait Whether to wait for a job if none is immediately available
     * @return bool Whether a job was processed
     */
    public function processJob(bool $wait = false): bool {
        if (!$this->queueService->isConnected()) {
            $this->logger->error('Cannot process job: not connected to queue');
            return false;
        }
        
        // Reserve a job from the queue
        $job = $this->queueService->reserve(['video_downloads'], $wait ? 60 : 0);
        
        if (!$job) {
            return false;
        }
        
        $this->logger->info('Processing job from queue', ['job_id' => $job->getId()]);
        
        try {
            // Parse job data
            $data = json_decode($job->getData(), true);
            
            if (!isset($data['queue_id']) || !isset($data['video_id'])) {
                $this->logger->error('Invalid job data', ['job_id' => $job->getId()]);
                $this->queueService->delete($job);
                return false;
            }
            
            $queueId = $data['queue_id'];
            $videoId = $data['video_id'];
            
            // Update queue entry status
            $this->queueManager->updateStatus($queueId, 'processing');
            
            // Get video data
            $video = $this->queueManager->getVideo($videoId);
            
            if (!$video) {
                $this->logger->error('Video not found', ['video_id' => $videoId]);
                $this->queueManager->updateStatus($queueId, 'failed');
                $this->queueService->delete($job);
                return false;
            }
            
            // Update video status
            $this->updateVideoStatus($videoId, 'processing', 'Downloading video...');
            
            // Download the video
            if ($video['source_type'] === 'url' && !empty($video['video_url'])) {
                $result = $this->downloadManager->downloadVideo($video['video_url'], [], $videoId);
                
                if ($result['success']) {
                    $this->logger->info('Video downloaded successfully', [
                        'video_id' => $videoId,
                        'file_path' => $result['file_path']
                    ]);
                    
                    // Update video with success status
                    $this->updateVideoStatus($videoId, 'completed', 'Video downloaded successfully');
                    $this->updateVideoPath($videoId, $result['file_path']);
                    
                    // Update queue entry
                    $this->queueManager->updateStatus($queueId, 'completed');
                } else {
                    $this->logger->error('Failed to download video', [
                        'video_id' => $videoId,
                        'error' => $result['error']
                    ]);
                    
                    // Update video with error status
                    $this->updateVideoStatus($videoId, 'failed', $result['error']);
                    
                    // Update queue entry
                    $this->queueManager->updateStatus($queueId, 'failed');
                }
            } else {
                $this->logger->error('Invalid video source', ['video_id' => $videoId]);
                $this->updateVideoStatus($videoId, 'failed', 'Invalid video source');
                $this->queueManager->updateStatus($queueId, 'failed');
            }
            
            // Delete the job from the queue
            $this->queueService->delete($job);
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('Error processing job', [
                'job_id' => $job->getId(),
                'error' => $e->getMessage()
            ]);
            
            // Release the job back to the queue
            $this->queueService->release($job, 1024, 30); // Retry after 30 seconds
            
            return false;
        }
    }
    
    /**
     * Run the worker in a loop
     * 
     * @param int $maxJobs Maximum number of jobs to process (0 for unlimited)
     * @param int $maxRuntime Maximum runtime in seconds (0 for unlimited)
     */
    public function run(int $maxJobs = 0, int $maxRuntime = 0): void {
        $this->logger->info('Starting worker loop', [
            'max_jobs' => $maxJobs,
            'max_runtime' => $maxRuntime
        ]);
        
        $startTime = time();
        $jobsProcessed = 0;
        
        while (true) {
            // Check if we've reached the maximum number of jobs
            if ($maxJobs > 0 && $jobsProcessed >= $maxJobs) {
                $this->logger->info('Reached maximum number of jobs', [
                    'jobs_processed' => $jobsProcessed
                ]);
                break;
            }
            
            // Check if we've reached the maximum runtime
            if ($maxRuntime > 0 && (time() - $startTime) >= $maxRuntime) {
                $this->logger->info('Reached maximum runtime', [
                    'runtime' => time() - $startTime
                ]);
                break;
            }
            
            // Process a job
            $result = $this->processJob(true);
            
            if ($result) {
                $jobsProcessed++;
                $this->logger->info('Job processed', [
                    'jobs_processed' => $jobsProcessed
                ]);
            } else {
                // Sleep for a short time to avoid CPU thrashing
                sleep(1);
            }
        }
        
        $this->logger->info('Worker loop completed', [
            'jobs_processed' => $jobsProcessed,
            'runtime' => time() - $startTime
        ]);
    }
    
    /**
     * Update video status in the database
     * 
     * @param int $videoId Video ID
     * @param string $status New status
     * @param string $message Status message
     * @param float|null $progress Progress percentage
     * @return bool Success status
     */
    private function updateVideoStatus(int $videoId, string $status, string $message, ?float $progress = null): bool {
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
            return $stmt->execute($params);
        } catch (Exception $e) {
            $this->logger->error('Failed to update video status', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Update video file path in the database
     * 
     * @param int $videoId Video ID
     * @param string $path File path
     * @return bool Success status
     */
    private function updateVideoPath(int $videoId, string $path): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE processed_videos
                SET source_path = :path
                WHERE id = :id
            ");
            
            return $stmt->execute([
                ':id' => $videoId,
                ':path' => $path
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to update video path', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
