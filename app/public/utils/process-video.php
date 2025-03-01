<?php
// Simple redirect script to the actual processing file
// Use proper path resolution for config
define('BASE_PATH', dirname(__DIR__, 2));


// Include only essential files - path adjusted for actual structure
require_once BASE_PATH  . '/src/includes/include-all.php';
ob_start();
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/src/utils/tagVideoThroughUrl/process-video.php';
ob_end_flush();
?>
