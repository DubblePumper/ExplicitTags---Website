<?php
/**
 * This file includes all the necessary files for the application.
 * It's used as a single entry point to include all required files.
 */

// Check if the file is accessed directly
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Include necessary configuration files
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/globals.php'; // Fixed path to globals.php\
include_once BASE_PATH . '/src/includes/head.php';

// Set error reporting based on environment
if (isset($environment) && $environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Initialize session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Remove any whitespace or newlines before opening PHP tag
// Start output buffering if not already started
if (!ob_get_level()) {
    ob_start();
}

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include head.php only if we need it and it exists
if (file_exists(__DIR__ . '/head.php')) {
    // We'll include head.php in individual files as needed, not globally
    // to avoid duplicated HTML structure
}

// Function to check if headers have been sent
function canSendHeaders() {
    return !headers_sent();
}

// Function to buffer output and delay header sending
function buffer_callback($buffer) {
    global $buffer_output;
    $buffer_output = $buffer;
    return '';
}

// Function to flush the buffered output
function flush_buffered_output() {
    global $buffer_output;
    if (isset($buffer_output)) {
        echo $buffer_output;
    }
}
?>