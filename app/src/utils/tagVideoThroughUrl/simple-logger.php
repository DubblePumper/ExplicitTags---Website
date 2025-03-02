<?php
/**
 * Simple logger class for fallback when the main LogManager is not available
 */
class SimpleLogger {
    private $logFile;
    private $debug;
    
    public function __construct($logFile, $debug = false) {
        $this->logFile = $logFile;
        $this->debug = $debug;
        
        // Create log directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function log($message, $level = 'INFO') {
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
    
    public function info($message) {
        return $this->log($message, 'INFO');
    }
    
    public function error($message) {
        return $this->log($message, 'ERROR');
    }
    
    public function warning($message) {
        return $this->log($message, 'WARNING');
    }
    
    public function debug($message) {
        return $this->log($message, 'DEBUG');
    }
}
