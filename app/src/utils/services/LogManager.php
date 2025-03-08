<?php

namespace Utils\Services;

/**
 * Logger utility class to manage logs for the application
 */
class LogManager
{
    private $logFile;
    private $debug;
    private $maxLogSize = 10485760; // 10MB
    
    /**
     * Create a new Logger instance
     * 
     * @param string $logFile Path to log file
     * @param bool $debug Whether to output debug messages
     */
    public function __construct($logFile, $debug = false)
    {
        $this->logFile = $logFile;
        $this->debug = $debug;
        
        // Create log directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Create log file if it doesn't exist
        if (!file_exists($logFile)) {
            file_put_contents($logFile, "");
            chmod($logFile, 0644);
        }
        
        // Rotate log if necessary
        $this->rotateLogIfNeeded();
    }
    
    /**
     * Write a log message
     * 
     * @param string $message The log message
     * @param string $level The log level (INFO, WARNING, ERROR, DEBUG)
     * @return bool Whether the log was written successfully
     */
    public function log($message, $level = 'INFO')
    {
        // Skip debug messages if debug is false
        if ($level === 'DEBUG' && !$this->debug) {
            return false;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[$timestamp] [$level] $message" . PHP_EOL;
        
        // Write to log file
        $result = file_put_contents($this->logFile, $logLine, FILE_APPEND);
        
        // Also write to error_log for server logs
        if ($level === 'ERROR') {
            error_log("[$level] $message");
        }
        
        return ($result !== false);
    }
    
    /**
     * Log an info message
     * 
     * @param string $message The log message
     * @return bool Whether the log was written successfully
     */
    public function info($message)
    {
        return $this->log($message, 'INFO');
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message The log message
     * @return bool Whether the log was written successfully
     */
    public function warning($message)
    {
        return $this->log($message, 'WARNING');
    }
    
    /**
     * Log an error message
     * 
     * @param string $message The log message
     * @return bool Whether the log was written successfully
     */
    public function error($message)
    {
        return $this->log($message, 'ERROR');
    }
    
    /**
     * Log a debug message
     * 
     * @param string $message The log message
     * @return bool Whether the log was written successfully
     */
    public function debug($message)
    {
        return $this->log($message, 'DEBUG');
    }
    
    /**
     * Rotate the log file if it exceeds the maximum size
     */
    private function rotateLogIfNeeded()
    {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) > $this->maxLogSize) {
            $backupFile = $this->logFile . '.' . date('Y-m-d-His');
            rename($this->logFile, $backupFile);
            file_put_contents($this->logFile, "Log rotated from $backupFile at " . date('Y-m-d H:i:s') . PHP_EOL);
        }
    }
}
