<?php

namespace Utils\Services\DownloaderUtils;

use Exception;

class BinaryFinder
{
    private $logger;
    
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Find the yt-dlp binary on the system
     * @return string Path to yt-dlp binary
     */
    public function findYtDlpBinary()
    {
        // First check the vendor directory (PREFERRED LOCATION)
        $vendorPath = VENDOR_DIR . 'yt-dlp/yt-dlp';
        if (stripos(PHP_OS, 'WIN') !== false) {
            $vendorPath .= '.exe';
        }
        
        if (file_exists($vendorPath)) {
            $this->logger->log("Found yt-dlp in vendor directory: {$vendorPath}");
            $this->ensureExecutable($vendorPath);
            return $vendorPath;
        }
        
        // Search in common locations
        $possiblePaths = [
            // Check all variations of vendor paths
            VENDOR_DIR . 'yt-dlp',
            VENDOR_DIR . 'bin/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/assets/bin/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/bin/yt-dlp',
            $_SERVER['DOCUMENT_ROOT'] . '/vendor/bin/yt-dlp',
            '/usr/local/bin/yt-dlp',
            '/usr/bin/yt-dlp',
            'yt-dlp', // For Windows or when in PATH
            'yt-dlp.exe' // Windows executable
        ];
        
        // Add .exe version to all paths for Windows
        if (stripos(PHP_OS, 'WIN') !== false) {
            foreach ($possiblePaths as $path) {
                if (substr($path, -4) !== '.exe') {
                    $possiblePaths[] = $path . '.exe';
                }
            }
        }
        
        foreach ($possiblePaths as $path) {
            if ($path === 'yt-dlp' || $path === 'yt-dlp.exe' || file_exists($path)) {
                $this->ensureExecutable($path);
                return $path;
            }
        }
        
        // If we get here, we need to download yt-dlp
        try {
            $downloadedPath = $this->downloadYtDlp();
            if ($downloadedPath) {
                $this->logger->log("Successfully downloaded yt-dlp to: " . $downloadedPath);
                $this->ensureExecutable($downloadedPath);
                return $downloadedPath;
            }
        } catch (Exception $e) {
            $this->logger->log("Failed to download yt-dlp: " . $e->getMessage());
        }
        
        // Default to yt-dlp and hope it's in the PATH
        $defaultBin = stripos(PHP_OS, 'WIN') !== false ? 'yt-dlp.exe' : 'yt-dlp';
        $this->logger->log("Using default binary: " . $defaultBin);
        return $defaultBin;
    }
    
    /**
     * Check if Python 3 is available on the system
     * @return bool
     */
    public function isPython3Available()
    {
        try {
            // Check for python3 command
            $cmd = "python3 --version 2>&1";
            $output = shell_exec($cmd);
            $this->logger->log("Python3 check output: " . $output);
            
            if ($output && strpos($output, 'Python 3') !== false) {
                $this->logger->log("Python 3 is available via python3 command");
                return true;
            }
            
            // Try 'python' command which might be Python 3 on some systems
            $cmd = "python --version 2>&1";
            $output = shell_exec($cmd);
            $this->logger->log("Python check output: " . $output);
            
            if ($output && strpos($output, 'Python 3') !== false) {
                $this->logger->log("Python command is Python 3");
                return true;
            }
            
            // Try 'py' command (Windows Python launcher)
            if (stripos(PHP_OS, 'WIN') !== false) {
                $cmd = "py -3 --version 2>&1";
                $output = shell_exec($cmd);
                $this->logger->log("Py launcher check output: " . $output);
                
                if ($output && strpos($output, 'Python 3') !== false) {
                    $this->logger->log("Python 3 is available via py launcher");
                    return true;
                }
            }
            
            $this->logger->log("Python 3 is not available on this system");
            return false;
        } catch (Exception $e) {
            $this->logger->log("Error checking Python: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Download yt-dlp if not available
     */
    private function downloadYtDlp()
    {
        $binDir = VENDOR_DIR . 'yt-dlp/';
        if (!file_exists($binDir)) {
            mkdir($binDir, 0755, true);
        }
        
        $targetPath = $binDir . 'yt-dlp';
        $sourceUrl = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp';
        
        // For Windows
        if (stripos(PHP_OS, 'WIN') !== false) {
            $sourceUrl .= '.exe';
            $targetPath .= '.exe';
        }
        
        $this->logger->log("Downloading yt-dlp from {$sourceUrl} to {$targetPath}");
        
        // Download the file
        $fileContent = @file_get_contents($sourceUrl);
        if ($fileContent === false) {
            throw new Exception("Failed to download yt-dlp");
        }
        
        if (file_put_contents($targetPath, $fileContent) === false) {
            throw new Exception("Failed to save yt-dlp to {$targetPath}");
        }
        
        // Make executable on Unix systems
        if (stripos(PHP_OS, 'WIN') === false) {
            chmod($targetPath, 0755);
        }
        
        return $targetPath;
    }
    
    /**
     * Make binary executable on Linux if needed
     */
    public function ensureExecutable($path)
    {
        // Only for Linux/Unix systems
        if (stripos(PHP_OS, 'WIN') === false && file_exists($path)) {
            $this->logger->log("Setting executable permission on binary");
            chmod($path, 0755);
            return true;
        }
        return false;
    }
}
