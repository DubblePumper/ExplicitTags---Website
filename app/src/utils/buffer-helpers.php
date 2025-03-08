<?php
/**
 * Helper functions for output buffering
 */

/**
 * Start output buffering at the beginning of a script
 * to prevent "headers already sent" errors
 */
function start_output_buffer() {
    if (ob_get_level() === 0) {
        ob_start();
    }
}

/**
 * Flush the output buffer and send content to the browser
 */
function flush_output_buffer() {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

/**
 * Clear the output buffer without sending content
 */
function clear_output_buffer() {
    if (ob_get_level() > 0) {
        ob_clean();
    }
}
