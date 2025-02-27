<?php
// Remove any whitespace or newlines before opening PHP tag
// Start output buffering if not already started
if (!ob_get_level()) {
    ob_start();
}

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include global variables
require_once __DIR__ . '/globals.php';

// Include head.php directly with proper buffering management
require_once __DIR__ . '/head.php';

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