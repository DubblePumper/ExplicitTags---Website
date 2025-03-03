<?php
namespace Utils\Video;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use YoutubeDl\YoutubeDl;
use YoutubeDl\Options;
use YoutubeDl\Exception\ExecutableNotFoundException;
use YoutubeDl\Exception\YoutubeDlException;

use Symfony\Component\Process\Exception\ProcessFailedException;

use Exception;

/**
 * Manages video downloads from various supported websites
 */
class VideoDownloadManager {
    private $logger;
    private $binPath;
    private $downloadPath;
    
    /**
     * Constructor
     * 
     * @param Logger $logger Logger instance for recording events
     * @param string $downloadPath Path where videos will be stored
     * @param string|null $binPath Path to yt-dlp binary (will be auto-detected if null)
     */
    public function __construct(Logger $logger, string $downloadPath, ?string $binPath = null) {
        $this->logger = $logger;
        $this->downloadPath = $downloadPath;
        
        // Determine binary path if not provided
        if ($binPath === null) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
            if (PHP_OS_FAMILY === 'Windows') {
                $this->binPath = $basePath . '/vendor/yt-dlp.exe';
            } else {
                $this->binPath = $basePath . '/vendor/yt-dlp_linux';
            }
        } else {
            $this->binPath = $binPath;
        }
        
        $this->logger->info('VideoDownloadManager initialized', [
            'binary_path' => $this->binPath, 
            'download_path' => $this->downloadPath
        ]);
        
        // Ensure download directory exists
        $this->ensureDirectoryExists($this->downloadPath);
    }
    
    /**
     * Ensure directory exists, create if it doesn't
     * 
     * @param string $directory Directory path to check/create
     * @return bool True if directory exists or was created
     */
    private function ensureDirectoryExists(string $directory): bool {
        if (!file_exists($directory)) {
            $this->logger->info('Creating directory: ' . $directory);
            $result = mkdir($directory, 0755, true);
            
            if (!$result) {
                $this->logger->error('Failed to create directory: ' . $directory);
                return false;
            }
        }
        return true;
    }
    
    /**
     * Extract viewkey from a URL
     * 
     * @param string $url URL to extract viewkey from
     * @return string Extracted viewkey or '0' if not found
     */
    private function extractViewkey(string $url): string {
        $viewkey = '0';
        
        // Extract from common patterns
        if (preg_match('/[?&]viewkey=([^&]+)/', $url, $matches)) {
            $viewkey = $matches[1];
        } elseif (preg_match('/\/([a-z0-9]+)(?:\/|\.|$)/', $url, $matches)) {
            $viewkey = $matches[1];
        }
        
        // Remove any invalid characters
        $viewkey = preg_replace('/[^a-z0-9_-]/', '', $viewkey);
        
        return $viewkey ?: '0';
    }
    
    /**
     * Download a video from a supported website
     * 
     * @param string $url URL of the video to download
     * @param array $customOptions Additional options for youtube-dl
     * @param int|null $videoId ID from processed_videos table
     * @return array Result information including download status and file path
     */
    public function downloadVideo(string $url, array $customOptions = [], ?int $videoId = null): array {
        try {
            // Check if binary exists
            if (!file_exists($this->binPath)) {
                throw new ExecutableNotFoundException('yt-dlp binary not found at: ' . $this->binPath);
            }
            
            $this->logger->info('Starting download', [
                'url' => $url,
                'binary' => $this->binPath
            ]);
            
            // Extract viewkey from URL
            $viewkey = $this->extractViewkey($url);
            
            // Generate unique ID
            $uniqueId = uniqid();
            
            // Set default videoId if not provided
            if (!$videoId) {
                $videoId = time(); // Use timestamp as fallback
            }
            
            // Define custom filename format with extension placeholder: video_{$videoId}_{$viewkey}_{$uniqueId}.%(ext)s
            $customFileName = "video_{$videoId}_{$viewkey}_{$uniqueId}.%(ext)s";
            
            $this->logger->info('Using custom filename format', [
                'format' => $customFileName,
                'video_id' => $videoId,
                'viewkey' => $viewkey,
                'unique_id' => $uniqueId
            ]);
            
            // Create Options object using factory method - FIXED VERSION
            $options = Options::create()
                ->downloadPath($this->downloadPath)  // Set download path first
                ->output($customFileName)            // Set filename with extension placeholder
                ->format('best[height<=720]')
                ->noCheckCertificate(true)
                ->noPlaylist();
                   
            // Add any custom options
            foreach ($customOptions as $key => $value) {
                if (method_exists($options, $key)) {
                    $options->$key($value);
                }
            }
            
            $this->logger->info('Configured download options', [
                'download_path' => $this->downloadPath,
                'format' => 'best[height<=720]',
                'output' => $customFileName
            ]);
            
            // Configure youtube-dl
            $youtubeDl = new YoutubeDl();
            $youtubeDl->setBinPath($this->binPath);
            
            // Add URL to options and download the video
            $options->url($url);
            $collection = $youtubeDl->download($options);
            
            if (empty($collection->getVideos())) {
                throw new YoutubeDlException('No videos were downloaded');
            }
            
            $videoInfo = $collection->getVideos()[0];
            
            // Try to get file path from downloaded video
            $videoFilePath = $videoInfo->getFile();
            
            // If file not found, try to find it by pattern
            if (!$videoFilePath || !file_exists($videoFilePath)) {
                $expectedFilePattern = $this->downloadPath . "/video_{$videoId}_{$viewkey}_{$uniqueId}.*";
                $matchingFiles = glob($expectedFilePattern);
                
                if (!empty($matchingFiles)) {
                    $videoFilePath = $matchingFiles[0];
                } else {
                    // Final fallback: get latest file in directory
                    $files = glob($this->downloadPath . '/*');
                    if (!empty($files)) {
                        $latestFile = null;
                        $latestTime = 0;
                        
                        foreach ($files as $file) {
                            $fileTime = filemtime($file);
                            if ($fileTime > $latestTime) {
                                $latestTime = $fileTime;
                                $latestFile = $file;
                            }
                        }
                        
                        $videoFilePath = $latestFile;
                    } else {
                        throw new Exception("Downloaded file not found in output directory");
                    }
                }
            }
            
            // Log success
            $this->logger->info('Download completed successfully', [
                'file_path' => $videoFilePath,
                'title' => $videoInfo->getTitle(),
                'duration' => $videoInfo->getDuration(),
            ]);
            
            return [
                'success' => true,
                'file_path' => $videoFilePath,
                'title' => $videoInfo->getTitle(),
                'duration' => $videoInfo->getDuration(),
                'thumbnail' => $this->extractThumbnailUrl($videoInfo),
                'description' => $videoInfo->getDescription(),
                'viewkey' => $viewkey,
                'info' => $videoInfo
            ];
            
        } catch (ExecutableNotFoundException $e) {
            $this->logger->error('Executable not found', [
                'message' => $e->getMessage(),
                'binary_path' => $this->binPath
            ]);
            return [
                'success' => false,
                'error' => 'YouTube downloader executable not found: ' . $e->getMessage()
            ];
        } catch (YoutubeDlException $e) {
            $this->logger->error('Download error', [
                'message' => $e->getMessage(),
                'url' => $url
            ]);
            return [
                'success' => false,
                'error' => 'Failed to download video: ' . $e->getMessage()
            ];
        } catch (ProcessFailedException $e) {
            $this->logger->error('Process failed', [
                'message' => $e->getMessage(),
                'command' => $e->getProcess()->getCommandLine()
            ]);
            return [
                'success' => false,
                'error' => 'Video processing failed: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            $this->logger->error('General error during download', [
                'message' => $e->getMessage(),
                'url' => $url
            ]);
            return [
                'success' => false,
                'error' => 'An unexpected error occurred: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract extension from format string
     * 
     * @param string $format Format string from youtube-dl
     * @return string File extension
     */
    private function getExtensionFromFormat(?string $format): string {
        // Default extension
        if (!$format) {
            return 'mp4';
        }
        
        $extensions = ['mp4', 'webm', 'mkv', 'flv', 'avi', 'mov'];
        
        foreach ($extensions as $ext) {
            if (stripos($format, $ext) !== false) {
                return $ext;
            }
        }
        
        return 'mp4'; // Default to mp4 if no match
    }
    
    /**
     * Get video information without downloading
     * 
     * @param string $url URL of the video
     * @return array Video information
     */
    public function getVideoInfo(string $url): array {
        try {
            if (!file_exists($this->binPath)) {
                throw new ExecutableNotFoundException('yt-dlp binary not found at: ' . $this->binPath);
            }
            
            $this->logger->info('Fetching video info for URL', ['url' => $url]);
            
            // Create options for info only using factory method - FIXED VERSION
            $options = Options::create()
                ->skipDownload(true)
                ->noCheckCertificate(true)
                ->noPlaylist()
                ->url($url);
            
            $youtubeDl = new YoutubeDl();
            $youtubeDl->setBinPath($this->binPath);
            
            // Download (just metadata) with options
            $collection = $youtubeDl->download($options);
            $videos = $collection->getVideos();
            
            if (empty($videos)) {
                throw new Exception('No video information found');
            }
            
            $videoInfo = $videos[0];
            
            return [
                'success' => true,
                'title' => $videoInfo->getTitle(),
                'duration' => $videoInfo->getDuration(),
                'thumbnail' => $this->extractThumbnailUrl($videoInfo),
                'description' => $videoInfo->getDescription(),
                'info' => $videoInfo
            ];
            
        } catch (ExecutableNotFoundException $e) {
            $this->logger->error('Executable not found during info fetch', [
                'message' => $e->getMessage(),
                'binary_path' => $this->binPath
            ]);
            return [
                'success' => false,
                'error' => 'YouTube downloader executable not found: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            $this->logger->error('Error fetching video info', [
                'message' => $e->getMessage(),
                'url' => $url
            ]);
            return [
                'success' => false,
                'error' => 'Failed to fetch video information: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if the binary exists and is executable
     * 
     * @return bool True if the binary is valid and executable
     */
    public function isBinaryValid(): bool {
        if (!file_exists($this->binPath)) {
            $this->logger->error('Binary file does not exist', ['path' => $this->binPath]);
            return false;
        }
        
        if (!is_executable($this->binPath) && PHP_OS_FAMILY !== 'Windows') {
            $this->logger->error('Binary file is not executable', ['path' => $this->binPath]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Extract thumbnail URL from video info
     * 
     * @param object $videoInfo Video info object
     * @return string|null Thumbnail URL
     */
    private function extractThumbnailUrl($videoInfo): ?string {
        // Try to get thumbnail from different properties
        if (method_exists($videoInfo, 'getThumbnails') && !empty($videoInfo->getThumbnails())) {
            return $videoInfo->getThumbnails()[0] ?? null;
        }
        
        // Try to get it from raw data
        if (method_exists($videoInfo, 'getRawData')) {
            $rawData = $videoInfo->getRawData();
            return $rawData['thumbnail'] ?? $rawData['thumbnails'][0]['url'] ?? $rawData['thumbnail_url'] ?? null;
        }
        
        return null;
    }
}