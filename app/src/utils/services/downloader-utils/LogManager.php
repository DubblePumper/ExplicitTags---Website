<?php

namespace Utils\Services\DownloaderUtils;

class LogManager
{
    private $logFile;
    private $debug = true;

    public function __construct($logFile, $debug = true)
    {
        $this->logFile = $logFile;
        $this->debug = $debug;
        
        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Log a message
     */
    public function log($message)
    {
        if ($this->debug) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [VideoDownloader] $message";
            
            // Log to file
            file_put_contents(
                $this->logFile,
                $logMessage . PHP_EOL,
                FILE_APPEND
            );
            
            // Output to console if CLI
            if (php_sapi_name() === 'cli') {
                echo $logMessage . PHP_EOL;
            }
        }
    }
}
