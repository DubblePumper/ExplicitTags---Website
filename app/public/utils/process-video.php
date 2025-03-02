<?php
// Simple redirect script to the actual processing file
// Use proper path resolution for config
define('BASE_PATH', dirname(__DIR__, 2));

// Make sure we're using the right protocol
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    define('SITE_PROTOCOL', 'https://');
} else {
    define('SITE_PROTOCOL', 'http://');
}

// Set global base URL
define('SITE_URL', SITE_PROTOCOL . $_SERVER['HTTP_HOST']);

// Update HTTP headers to match current protocol
if (defined('SITE_PROTOCOL') && defined('SITE_URL')) {
    header("X-Site-Protocol: " . SITE_PROTOCOL);
    header("X-Site-URL: " . SITE_URL);
}

// Include only essential files - path adjusted for actual structure
require_once BASE_PATH  . '/src/includes/include-all.php';
ob_start();
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/src/utils/tagVideoThroughUrl/process-video.php';
ob_end_flush();
?>
