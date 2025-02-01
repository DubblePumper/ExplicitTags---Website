<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include global variables
require_once __DIR__ . '/globals.php';

// Include other common includes
require_once __DIR__ . '/head.php';


?>