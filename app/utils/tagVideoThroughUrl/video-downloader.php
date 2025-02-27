<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/utils/tagVideoThroughUrl/database-functions.php';

/**
 * Class to handle video downloads using youtube-dl
 */
class VideoDownloader {
    private $pdo;
    private $video_id;
    private $video_url;
    private $download_dir;
    private $output_filename;
    private $python_path = 'python'; // Default python path
    private $yt_dlp_path = 'yt-dlp'; // Using yt-dlp (maintained fork of youtube-dl)
    
    /**
     * Constructor
     * @param PDO $pdo Database connection
     * @param int $video_id The ID of the video to download
     * @param string $video_url The URL to download
     */
    public function __construct($pdo, $video_id, $video_url) {
        $this->pdo = $pdo;
        $this->video_id = $video_id;
        $this->video_url = $video_url;
        
        // Create download directory
        $this->download_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/videos/downloaded/';
        if (!file_exists($this->download_dir)) {
            mkdir($this->download_dir, 0755, true);
        }
        
        // Generate unique filename based on video ID
        $this->output_filename = 'video_' . $video_id . '_' . uniqid() . '.mp4';
        
        // Check if we're on Windows and adjust paths if needed
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Try to find python path on Windows
            if (file_exists('C:\Python310\python.exe')) {
                $this->python_path = 'C:\Python310\python.exe';
            } elseif (file_exists('C:\Python39\python.exe')) {
                $this->python_path = 'C:\Python39\python.exe';
            }
            
            // Check if yt-dlp is available in the system
            exec('where yt-dlp', $output, $return_var);
            if ($return_var !== 0) {
                // Try looking for it in common locations
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bin/yt-dlp.exe')) {
                    $this->yt_dlp_path = $_SERVER['DOCUMENT_ROOT'] . '/bin/yt-dlp.exe';
                } elseif (file_exists('C:\yt-dlp\yt-dlp.exe')) {
                    $this->yt_dlp_path = 'C:\yt-dlp\yt-dlp.exe';
                }
            }
        } else {
            // For Linux/Unix, check if youtube-dl/yt-dlp is available
            exec('which yt-dlp', $output, $return_var);
            if ($return_var !== 0) {
                // Try youtube-dl if yt-dlp is not found
                exec('which youtube-dl', $output, $return_var);
                if ($return_var === 0 && !empty($output[0])) {
                    $this->yt_dlp_path = 'youtube-dl';
                }
            } else if (!empty($output[0])) {
                $this->yt_dlp_path = $output[0];
            }
        }
    }
    
    /**
     * Start the download process
     * @return bool True if download started successfully, false otherwise
     */
    public function startDownload() {
        try {
            // Update database to show we're downloading
            $stmt = $this->pdo->prepare("
                UPDATE processed_videos 
                SET processing_status = 'downloading', download_progress = 0 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $this->video_id]);
            
            // Start the download in a non-blocking way
            $this->executeDownloadInBackground();
            return true;
        } catch (Exception $e) {
            error_log("Error starting download: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute the download in the background
     */
    private function executeDownloadInBackground() {
        $output_path = $this->download_dir . $this->output_filename;
        $progress_file = sys_get_temp_dir() . "/video_download_progress_{$this->video_id}.txt";
        
        // Create the command to execute yt-dlp/youtube-dl
        $escapedUrl = escapeshellarg($this->video_url);
        $escapedOutput = escapeshellarg($output_path);
        $escapedProgressFile = escapeshellarg($progress_file);
        
        // Command to download with progress tracking
        $cmd = "{$this->yt_dlp_path} {$escapedUrl} -o {$escapedOutput} " .
               "--newline --progress-template \"download:%(progress.downloaded_bytes)s/%(progress.total_bytes)s\" " .
               "2> {$escapedProgressFile}";
        
        // On Windows use different background execution method
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start /B {$cmd} > NUL";
            pclose(popen($cmd, "r"));
        } else {
            exec($cmd . " > /dev/null 2>&1 &");
        }
        
        // Start progress monitoring in a separate process
        $this->monitorDownloadProgress($progress_file);
    }
    
    /**
     * Monitor the download progress by reading the progress file
     * @param string $progress_file Path to the progress file
     */
    private function monitorDownloadProgress($progress_file) {
        // Start the progress monitoring script in the background
        $monitor_script = $_SERVER['DOCUMENT_ROOT'] . '/utils/tagVideoThroughUrl/monitor-progress.php';
        $escapedScript = escapeshellarg($monitor_script);
        $escapedProgressFile = escapeshellarg($progress_file);
        
        // Command to run the monitor script
        $cmd = "{$this->python_path} {$escapedScript} {$this->video_id} {$escapedProgressFile} " . 
               escapeshellarg($this->download_dir . $this->output_filename);
        
        // Run the monitor script in background
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start /B {$cmd} > NUL";
            pclose(popen($cmd, "r"));
        } else {
            exec($cmd . " > /dev/null 2>&1 &");
        }
    }
    
    /**
     * Update the download progress in the database
     * @param int $progress Progress percentage (0-100)
     */
    public static function updateProgress($pdo, $video_id, $progress) {
        try {
            $stmt = $pdo->prepare("
                UPDATE processed_videos 
                SET download_progress = :progress 
                WHERE id = :id
            ");
            $stmt->execute([
                ':progress' => min(100, max(0, $progress)), // Ensure progress is between 0-100
                ':id' => $video_id
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Error updating download progress: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark the download as complete and update the source_path
     * @param string $file_path Path to the downloaded file
     */
    public static function markDownloadComplete($pdo, $video_id, $file_path) {
        try {
            $stmt = $pdo->prepare("
                UPDATE processed_videos 
                SET processing_status = 'processing', 
                    source_path = :source_path, 
                    download_progress = 100 
                WHERE id = :id
            ");
            
            // Convert absolute path to relative path for storage
            $docRoot = $_SERVER['DOCUMENT_ROOT'];
            $relativePath = str_replace($docRoot, '', $file_path);
            
            $stmt->execute([
                ':source_path' => $relativePath,
                ':id' => $video_id
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Error marking download complete: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark the download as failed
     */
    public static function markDownloadFailed($pdo, $video_id) {
        try {
            $stmt = $pdo->prepare("
                UPDATE processed_videos 
                SET processing_status = 'failed', 
                    download_progress = 0 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $video_id]);
            return true;
        } catch (Exception $e) {
            error_log("Error marking download failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Download video from a supported adult website
     * 
     * @param string $url URL of the video
     * @param string $outputDir Directory to save the video
     * @param string $progressFile File to write progress information to
     * @return string|bool Path to downloaded file or false on failure
     */
    public static function downloadVideo($url, $outputDir, $progressFile) {
        // Create output directory if it doesn't exist
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Generate unique output filename
        $outputFile = $outputDir . '/' . uniqid('dl_') . '.mp4';
        
        // Command to download video with progress reporting
        // Using yt-dlp which supports more sites than youtube-dl
        $command = sprintf(
            'yt-dlp -o "%s" --no-warnings --progress --newline "%s" > "%s" 2>&1 & echo $!',
            escapeshellarg($outputFile),
            escapeshellarg($url),
            escapeshellarg($progressFile)
        );
        
        // Execute command and get process ID
        $pid = exec($command);
        
        // Return output file path (the actual file will be created by yt-dlp)
        return $outputFile;
    }
}
