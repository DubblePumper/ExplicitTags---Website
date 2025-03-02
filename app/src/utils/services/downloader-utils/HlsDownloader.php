<?php

namespace Utils\Services\DownloaderUtils;

use Exception;

class HlsDownloader
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
     * Handle download of HLS (m3u8) streams
     * 
     * @param string $m3u8Url URL of the m3u8 playlist
     * @param string $outputPath Where to save the final video
     * @param string $manifestContent Optional manifest content if already downloaded
     * @return array Result with status
     */
    public function handleHlsDownload($m3u8Url, $outputPath, $manifestContent = null)
    {
        $this->logger->log("Handling HLS (m3u8) download from: {$m3u8Url}");
        
        // Try to use yt-dlp first, but if it fails, we'll use a pure PHP approach
        if ($this->pythonAvailable && $this->ytDlpPath) {
            $ytdlpResult = $this->tryYtDlpForHls($m3u8Url, $outputPath);
            
            if ($ytdlpResult['success']) {
                return $ytdlpResult;
            }
        } else {
            $this->logger->log("yt-dlp not available, will try pure PHP approach");
        }
        
        // Fallback to pure PHP approach
        $this->logger->log("Falling back to pure PHP HLS download...");
        
        // If we don't have manifest content, download it
        if ($manifestContent === null) {
            $curlDownloader = new CurlDownloader($this->logger);
            $manifestContent = $curlDownloader->downloadTextFile($m3u8Url);
            if (!$manifestContent) {
                return ['success' => false, 'message' => "Failed to download m3u8 manifest"];
            }
        }
        
        // Parse the m3u8 file to get video segments
        $this->logger->log("Parsing m3u8 content...");
        $segments = $this->parseM3u8($manifestContent, $m3u8Url);
        
        if (empty($segments)) {
            // This might be a master playlist pointing to other playlists
            $bestVariant = $this->findBestVariantFromMaster($manifestContent, $m3u8Url);
            if ($bestVariant) {
                $this->logger->log("Found variant playlist: {$bestVariant}");
                
                // Download variant playlist
                $curlDownloader = new CurlDownloader($this->logger);
                $variantContent = $curlDownloader->downloadTextFile($bestVariant);
                if (!$variantContent) {
                    return ['success' => false, 'message' => "Failed to download variant playlist"];
                }
                
                // Parse segments from variant playlist
                $segments = $this->parseM3u8($variantContent, $bestVariant);
            }
        }
        
        $this->logger->log("Found " . count($segments) . " segments in m3u8 file");
        
        if (empty($segments)) {
            return ['success' => false, 'message' => "Could not find any video segments in the HLS stream"];
        }
        
        // Now download and concatenate all segments
        $result = $this->downloadAndConcatenateSegments($segments, $outputPath);
        
        return $result;
    }
    
    /**
     * Try to use yt-dlp for HLS download
     */
    private function tryYtDlpForHls($m3u8Url, $outputPath) 
    {
        try {
            // Create a unique temporary file to store the m3u8 URL
            $tempUrlFile = dirname($outputPath) . '/' . uniqid('url_') . '.txt';
            file_put_contents($tempUrlFile, $m3u8Url);
            
            $ytdlpPath = $this->ytDlpPath;
            
            // Ensure binary is executable
            if (stripos(PHP_OS, 'WIN') === false && file_exists($ytdlpPath)) {
                chmod($ytdlpPath, 0755);
            }
            
            // Build the command using yt-dlp which properly handles HLS streams
            $cmd = sprintf(
                '%s --no-check-certificate --format "best[ext=mp4]/best" --no-playlist --output "%s" --force-overwrites "file:%s"', 
                escapeshellcmd($ytdlpPath),
                escapeshellarg($outputPath),
                escapeshellarg($tempUrlFile)
            );
            
            $this->logger->log("Executing yt-dlp command for HLS download: {$cmd}");
            
            // Execute the command and capture output
            $output = [];
            $returnVal = null;
            exec($cmd . " 2>&1", $output, $returnVal);
            
            // Clean up the temporary file
            if (file_exists($tempUrlFile)) {
                unlink($tempUrlFile);
            }
            
            $outputStr = implode("\n", $output);
            $this->logger->log("HLS download command output: {$outputStr}");
            $this->logger->log("HLS download return value: {$returnVal}");
            
            if ($returnVal !== 0 || !file_exists($outputPath) || filesize($outputPath) < 10240) {
                // If yt-dlp failed, try using the original URL directly
                $cmd = sprintf(
                    '%s --no-check-certificate --format "best[ext=mp4]/best" --no-playlist --output "%s" --force-overwrites "%s"', 
                    escapeshellcmd($ytdlpPath),
                    escapeshellarg($outputPath),
                    escapeshellarg($m3u8Url)
                );
                
                $this->logger->log("Retrying with direct URL: {$cmd}");
                
                $output = [];
                $returnVal = null;
                exec($cmd . " 2>&1", $output, $returnVal);
                
                $outputStr = implode("\n", $output);
                $this->logger->log("Second attempt output: {$outputStr}");
                $this->logger->log("Second attempt return value: {$returnVal}");
            }
            
            if ($returnVal === 0 && file_exists($outputPath) && filesize($outputPath) > 10240) {
                $filesize = filesize($outputPath);
                $this->logger->log("yt-dlp HLS download successful. File size: {$filesize} bytes");
                
                return [
                    'success' => true, 
                    'message' => "Downloaded HLS stream, size: {$filesize} bytes",
                    'size' => $filesize
                ];
            }
            
            return ['success' => false, 'message' => "HLS download failed with code {$returnVal}: {$outputStr}"];
            
        } catch (Exception $e) {
            $this->logger->log("yt-dlp failed with error: " . $e->getMessage());
            return ['success' => false, 'message' => "yt-dlp failed: " . $e->getMessage()];
        }
    }
    
    /**
     * Parse m3u8 file to get the list of video segments
     */
    private function parseM3u8($content, $baseUrl) 
    {
        $this->logger->log("Parsing m3u8 content...");
        
        // Extract base URL for resolving relative paths
        $parsedUrl = parse_url($baseUrl);
        $urlBase = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $pathBase = '';
        
        if (isset($parsedUrl['path'])) {
            $pathParts = explode('/', $parsedUrl['path']);
            array_pop($pathParts);
            $pathBase = implode('/', $pathParts);
        }
        
        $segments = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines, comments, and control tags
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Process the segment URL
            if (strpos($line, 'http') === 0) {
                // Absolute URL
                $segments[] = $line;
            } elseif (strpos($line, '/') === 0) {
                // Absolute path
                $segments[] = $urlBase . $line;
            } else {
                // Relative path
                $segments[] = $urlBase . $pathBase . '/' . $line;
            }
        }
        
        $this->logger->log("Found " . count($segments) . " segments in m3u8 file");
        return $segments;
    }
    
    /**
     * Find the best variant from a master playlist
     */
    private function findBestVariantFromMaster($content, $baseUrl) 
    {
        $this->logger->log("Parsing master playlist to find best variant...");
        
        // Extract base URL for resolving relative paths
        $parsedUrl = parse_url($baseUrl);
        $urlBase = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $pathBase = '';
        
        if (isset($parsedUrl['path'])) {
            $pathParts = explode('/', $parsedUrl['path']);
            array_pop($pathParts);
            $pathBase = implode('/', $pathParts);
        }
        
        $variants = [];
        $currentBandwidth = 0;
        
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Look for stream info lines
            if (strpos($line, '#EXT-X-STREAM-INF') === 0) {
                // Extract bandwidth
                if (preg_match('/BANDWIDTH=(\d+)/', $line, $matches)) {
                    $currentBandwidth = (int)$matches[1];
                } else {
                    $currentBandwidth = 0;
                }
                continue;
            }
            
            // Skip other tags and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // This is a variant URL
            if (strpos($line, 'http') === 0) {
                $variants[$currentBandwidth] = $line;
            } elseif (strpos($line, '/') === 0) {
                $variants[$currentBandwidth] = $urlBase . $line;
            } else {
                $variants[$currentBandwidth] = $urlBase . $pathBase . '/' . $line;
            }
        }
        
        if (empty($variants)) {
            $this->logger->log("No variants found in master playlist");
            return null;
        }
        
        // Sort by bandwidth (highest first)
        krsort($variants);
        
        // Return the highest bandwidth variant
        $best = reset($variants);
        $this->logger->log("Selected best variant with bandwidth: " . key($variants));
        
        return $best;
    }
    
    /**
     * Download and concatenate all segments into a single MP4 file
     */
    private function downloadAndConcatenateSegments($segments, $outputPath)
    {
        $this->logger->log("Starting to download " . count($segments) . " segments");
        
        // Create output file
        $outputFile = fopen($outputPath, 'wb');
        if (!$outputFile) {
            return ['success' => false, 'message' => "Could not create output file"];
        }
        
        $downloadedSize = 0;
        $segmentCount = count($segments);
        $successCount = 0;
        
        // If too many segments, sample a few for progress reporting
        $reportEvery = max(1, floor($segmentCount / 10));
        $this->logger->log("Will report progress every {$reportEvery} segments");
        
        foreach ($segments as $index => $segmentUrl) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $segmentUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $segmentData = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $segmentData !== false) {
                fwrite($outputFile, $segmentData);
                $downloadedSize += strlen($segmentData);
                $successCount++;
                
                // Report progress occasionally
                if ($index % $reportEvery === 0 || $index === $segmentCount - 1) {
                    $progress = round(($index + 1) / $segmentCount * 100);
                    $this->logger->log("Downloaded segment {$index}/{$segmentCount} ({$progress}%)");
                }
            } else {
                $this->logger->log("Failed to download segment {$index}: {$error}, HTTP code: {$httpCode}");
            }
        }
        
        fclose($outputFile);
        
        if ($successCount === 0) {
            $this->logger->log("Failed to download any segments");
            return ['success' => false, 'message' => "Failed to download any segments"];
        }
        
        $this->logger->log("Successfully downloaded {$successCount} out of {$segmentCount} segments");
        $this->logger->log("Total downloaded size: {$downloadedSize} bytes");
        
        if ($downloadedSize < 10240) {
            $this->logger->log("Downloaded file is suspiciously small (<10KB)");
            return ['success' => false, 'message' => "Downloaded file is too small, possible error"];
        }
        
        return [
            'success' => true,
            'message' => "Downloaded and concatenated {$successCount} segments, total size: {$downloadedSize} bytes",
            'size' => $downloadedSize
        ];
    }
}