<?php
// This file should be accessed directly for debugging purposes

// Basic security to prevent public access in production
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    $allowedIp = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
    // Replace with your actual IP if needed for remote debugging
    if ($allowedIp !== 'YOUR_IP_HERE') {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied');
    }
}

// Setting error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

echo "<h1>ExplicitTags Debug Information</h1>";
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h2>Directory Structure</h2>";
echo "<pre>";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo "Current script: " . __FILE__ . "\n\n";

// Check critical directories
$directoriesToCheck = [
    '/public',
    '/public/pages',
    '/public/api',
    '/src',
    '/src/includes',
    '/storage/logs',
    '/storage/uploads/videos'
];

foreach ($directoriesToCheck as $dir) {
    $fullPath = BASE_PATH . $dir;
    echo "$dir: " . (file_exists($fullPath) ? "✓ EXISTS" : "✗ MISSING") . "\n";
}

echo "</pre>";

echo "<h2>Critical File Check</h2>";
echo "<pre>";
$filesToCheck = [
    '/public/pages/tag.php',
    '/public/pages/experience.php',
    '/public/.htaccess',
    '/src/includes/include-all.php',
    '/src/includes/header.php',
    '/src/includes/navbar.php',
    '/src/includes/footer.php',
    '/src/includes/scripts.php',
    '/config/config.php'
];

foreach ($filesToCheck as $file) {
    $fullPath = BASE_PATH . $file;
    echo "$file: " . (file_exists($fullPath) ? "✓ EXISTS" : "✗ MISSING") . "\n";
}

echo "</pre>";

// Check includes
echo "<h2>Testing Include Loading</h2>";
echo "<pre>";

try {
    if (file_exists(BASE_PATH . '/src/includes/include-all.php')) {
        echo "Attempting to load include-all.php...\n";
        require_once BASE_PATH . '/src/includes/include-all.php';
        echo "Successfully loaded include-all.php\n";
    } else {
        echo "WARNING: include-all.php file not found\n";
    }
} catch (Exception $e) {
    echo "ERROR loading include-all.php: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Test database connection
echo "\n<h2>Database Connection</h2>\n";

try {
    if (file_exists(BASE_PATH . '/config/config.php')) {
        require_once BASE_PATH . '/config/config.php';
        if (function_exists('testDBConnection')) {
            $db = testDBConnection();
            if ($db !== false) {
                echo "✓ Database connection successful\n";
                
                // Test a simple query
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM processed_videos");
                    $result = $stmt->fetch();
                    echo "Found " . $result['count'] . " records in processed_videos table\n";
                } catch (PDOException $e) {
                    echo "ERROR with query: " . $e->getMessage() . "\n";
                }
            } else {
                echo "✗ Failed to connect to database\n";
            }
        } else {
            echo "✗ testDBConnection function not available\n";
        }
    } else {
        echo "✗ config.php not found\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Check for error logs
echo "\n<h2>Recent Error Logs</h2>\n";
$errorLogPath = BASE_PATH . '/storage/logs';

if (file_exists($errorLogPath)) {
    $logFiles = glob($errorLogPath . '/*.log');
    
    if (count($logFiles) === 0) {
        echo "No log files found.\n";
    } else {
        foreach ($logFiles as $logFile) {
            $fileName = basename($logFile);
            echo "<h3>$fileName</h3>";
            
            if (filesize($logFile) > 0) {
                $contents = file_get_contents($logFile);
                // Get last few lines
                $lines = explode("\n", $contents);
                $lastLines = array_slice($lines, -20);
                
                echo "<pre>";
                foreach ($lastLines as $line) {
                    echo htmlspecialchars($line) . "\n";
                }
                echo "</pre>";
            } else {
                echo "<p>Log file is empty</p>";
            }
        }
    }
} else {
    echo "Logs directory not found.\n";
}
?>
