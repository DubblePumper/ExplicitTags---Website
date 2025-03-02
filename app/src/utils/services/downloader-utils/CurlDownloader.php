<?php

namespace Utils\Services\DownloaderUtils;

class CurlDownloader
{
    private $logger;
    
    public function __construct($logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Attempt download using cURL
     */
    public function attemptCurlDownload($videoId, $url, $fileName, $uploadDir) 
    {
        $this->logger->log("Attempting direct cURL download...");
        
        $outputPath = $uploadDir . $fileName;
        $result = $this->curlDownload($url, $outputPath);
        
        if ($result['success']) {
            $this->logger->log("cURL download successful: {$result['message']}");
            
            $relativePath = '/uploads/videos/' . $fileName;
            
            return [
                'status' => 'success',
                'message' => 'Video downloaded successfully via cURL',
                'path' => $relativePath
            ];
        } else {
            $this->logger->log("cURL download failed: {$result['message']}");
            return [
                'status' => 'error',
                'message' => $result['message']
            ];
        }
    }
    
    /**
     * Download using PHP's cURL extension
     * This doesn't require any external dependencies
     */
    public function curlDownload($url, $outputPath)
    {
        $this->logger->log("Starting cURL download from URL: {$url}");
        
        $ch = curl_init();
        
        // Common headers for adult sites
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept: */*',
            'Accept-Language: en-US,en;q=0.5',
            'DNT: 1',
            'Connection: keep-alive',
            'Referer: https://www.google.com/'
        ];
        
        // First we need to extract the actual video URL from the page
        $videoUrl = $this->extractDirectVideoUrl($url);
        
        if ($videoUrl) {
            $this->logger->log("Extracted direct video URL: {$videoUrl}");
            
            // Check if this is an m3u8 playlist
            if (stripos($videoUrl, '.m3u8') !== false) {
                $hlsDownloader = new HlsDownloader(null, false, $this->logger);
                return $hlsDownloader->handleHlsDownload($videoUrl, $outputPath);
            }
            
            $url = $videoUrl;
        }
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 min timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Write to file
        $fp = fopen($outputPath, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        
        // Execute
        $success = curl_exec($ch);
        
        if ($success === false) {
            fclose($fp);
            $error = curl_error($ch);
            $this->logger->log("cURL error: {$error}");
            curl_close($ch);
            return ['success' => false, 'message' => $error];
        }
        
        // Get info
        $info = curl_getinfo($ch);
        $httpCode = $info['http_code'];
        $contentType = isset($info['content_type']) ? $info['content_type'] : '';
        $filesize = $info['size_download'];
        
        fclose($fp);
        curl_close($ch);
        
        $this->logger->log("cURL download complete: Status {$httpCode}, Size: {$filesize} bytes, Type: {$contentType}");
        
        // If we got an m3u8 file or tiny file, we need a different approach
        if (($filesize < 10240 && (stripos($contentType, 'm3u8') !== false || stripos($url, '.m3u8') !== false)) || 
            (stripos($contentType, 'text/html') !== false && $filesize > 0)) {
            // Read the content to check if it's an m3u8 file
            $content = file_get_contents($outputPath);
            if (stripos($content, '#EXTM3U') !== false) {
                $hlsDownloader = new HlsDownloader(null, false, $this->logger);
                return $hlsDownloader->handleHlsDownload($url, $outputPath, $content);
            }
            
            // If it's HTML content, try to extract a video URL
            if (stripos($contentType, 'text/html') !== false) {
                $extractedUrl = $this->extractVideoUrlFromHtml($content, $url);
                if ($extractedUrl) {
                    // Delete the HTML file
                    @unlink($outputPath);
                    // Try downloading the extracted URL
                    return $this->curlDownload($extractedUrl, $outputPath);
                }
            }
        }
        
        // Verify we got a successful response and a video file
        if ($httpCode != 200) {
            return ['success' => false, 'message' => "HTTP error: Status code {$httpCode}"];
        }
        
        if ($filesize < 10240) { // Less than 10KB probably isn't a video
            $content = file_get_contents($outputPath);
            if (stripos($content, '<html') !== false || stripos($content, '<!DOCTYPE') !== false) {
                // We got HTML instead of a video
                return ['success' => false, 'message' => 'Received HTML instead of video data'];
            }
        }
        
        return [
            'success' => true, 
            'message' => "Downloaded {$filesize} bytes",
            'size' => $filesize,
            'content_type' => $contentType
        ];
    }
    
    /**
     * Download a text file from a URL
     */
    public function downloadTextFile($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $content = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || $content === false) {
            $this->logger->log("Failed to download from URL: {$url}, error: {$error}, HTTP code: {$httpCode}");
            return false;
        }
        
        return $content;
    }
    
    /**
     * Extract video URL from HTML content
     */
    private function extractVideoUrlFromHtml($html, $baseUrl)
    {
        // Look for video tags
        if (preg_match('/<video[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            $videoUrl = $matches[1];
            // Handle relative URLs
            if (strpos($videoUrl, 'http') !== 0) {
                $videoUrl = $this->resolveRelativeUrl($videoUrl, $baseUrl);
            }
            return $videoUrl;
        }
        
        // Look for m3u8 URLs
        if (preg_match('/["\']([^"\']+\.m3u8[^"\']*)["\']/', $html, $matches)) {
            $m3u8Url = $matches[1];
            // Handle relative URLs
            if (strpos($m3u8Url, 'http') !== 0) {
                $m3u8Url = $this->resolveRelativeUrl($m3u8Url, $baseUrl);
            }
            return $m3u8Url;
        }
        
        return null;
    }
    
    /**
     * Resolve a relative URL against a base URL
     */
    private function resolveRelativeUrl($relativeUrl, $baseUrl)
    {
        $parsedBase = parse_url($baseUrl);
        
        // If the relative URL starts with a slash, it's relative to the root
        if (strpos($relativeUrl, '/') === 0) {
            $base = $parsedBase['scheme'] . '://' . $parsedBase['host'];
            return $base . $relativeUrl;
        }
        
        // Otherwise, it's relative to the current path
        $pathInfo = pathinfo($parsedBase['path'] ?? '');
        $directory = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
        if ($directory == '.') $directory = '';
        $base = $parsedBase['scheme'] . '://' . $parsedBase['host'] . $directory;
        
        // Make sure we have a trailing slash on the directory
        if ($base && substr($base, -1) !== '/') {
            $base .= '/';
        }
        
        return $base . $relativeUrl;
    }
    
    /**
     * Try to extract a direct video URL from a webpage
     * This is site-specific and may need to be updated for different sites
     */
    public function extractDirectVideoUrl($pageUrl)
    {
        $this->logger->log("Attempting to extract video URL from: {$pageUrl}");
        
        // Get the page content
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $html = curl_exec($ch);
        curl_close($ch);
        
        if (!$html) {
            $this->logger->log("Failed to get page content");
            return null;
        }
        
        // Save the HTML for debugging if needed
        $debugFile = dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))) . '/storage/logs/debug_html_' . uniqid() . '.txt';
        file_put_contents($debugFile, $html);
        $this->logger->log("Saved debug HTML to: {$debugFile}");
        
        // For different sites, we need different extraction methods
        if (stripos($pageUrl, 'pornhub.com') !== false) {
            // Look for the high quality formats first
            if (preg_match('/"quality":"1080","videoUrl":"([^"]+)"/', $html, $matches)) {
                $videoUrl = str_replace('\\/', '/', $matches[1]);
                return $videoUrl;
            }
            
            if (preg_match('/"quality":"720","videoUrl":"([^"]+)"/', $html, $matches)) {
                $videoUrl = str_replace('\\/', '/', $matches[1]);
                return $videoUrl;
            }
            
            if (preg_match('/"quality":"480","videoUrl":"([^"]+)"/', $html, $matches)) {
                $videoUrl = str_replace('\\/', '/', $matches[1]);
                return $videoUrl;
            }
            
            // Try to get mediaDefinitions blocks
            if (preg_match('/mediaDefinitions":\s*(\[[^\]]+\])/', $html, $matches)) {
                $mediaJson = $matches[1];
                $mediaData = json_decode($mediaJson, true);
                
                if (is_array($mediaData)) {
                    // Sort by quality, prefer higher quality
                    usort($mediaData, function($a, $b) {
                        return (int)$b['quality'] - (int)$a['quality'];
                    });
                    
                    foreach ($mediaData as $media) {
                        if (!empty($media['videoUrl'])) {
                            return str_replace('\\/', '/', $media['videoUrl']);
                        }
                    }
                }
            }
            
            // Fall back to any videoUrl we can find
            if (preg_match('/"videoUrl":"([^"]+)"/', $html, $matches)) {
                $videoUrl = str_replace('\\/', '/', $matches[1]);
                return $videoUrl;
            }
        } elseif (stripos($pageUrl, 'xvideos.com') !== false) {
            // Extract from xvideos
            if (preg_match('/html5player\.setVideoUrlHigh\(\'([^\']+)\'/', $html, $matches)) {
                return $matches[1];
            }
            
            if (preg_match('/html5player\.setVideoUrlLow\(\'([^\']+)\'/', $html, $matches)) {
                return $matches[1];
            }
        } elseif (stripos($pageUrl, 'xnxx.com') !== false) {
            // XNXX uses same player as xvideos
            if (preg_match('/html5player\.setVideoUrlHigh\(\'([^\']+)\'/', $html, $matches)) {
                return $matches[1];
            }
            
            if (preg_match('/html5player\.setVideoUrlLow\(\'([^\']+)\'/', $html, $matches)) {
                return $matches[1];
            }
        }
        
        // Generic extraction for all sites - look for common video patterns
        // Look for HTML5 video sources
        if (preg_match('/<source[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return $matches[1];
        }
        
        // Look for HLS streams
        if (preg_match('/["\']([^"\']+\.m3u8[^"\']*)["\']/', $html, $matches)) {
            return $matches[1];
        }
        
        $this->logger->log("Could not extract video URL from page");
        return null;
    }
}