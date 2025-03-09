<?php

// Define base path if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 5));
}

// Include Composer autoloader
require BASE_PATH . '/vendor/autoload.php';

// Include database configuration
require_once BASE_PATH . '/config/config.php';

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Utils\Workers\QueueService;
use Utils\Workers\VideoQueueManager;
use Utils\Workers\VideoDownloadWorker;
use Utils\Video\VideoDownloadManager;

// Setup logger
$logDir = BASE_PATH . '/storage/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

$logger = new Logger('queue_worker');
$logger->pushHandler(new RotatingFileHandler($logDir . '/queue_worker.log', 7, Level::Debug));
$logger->pushHandler(new StreamHandler('php://stdout', Level::Info));

$logger->info('Starting queue worker script');

// Parse command line arguments
$command = $argv[1] ?? 'help';
$options = array_slice($argv, 2);

// Connect to database
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    $logger->info('Connected to database', ['host' => $dbConfig['host'], 'dbname' => $dbConfig['dbname']]);
} catch (PDOException $e) {
    $logger->error('Database connection failed', ['error' => $e->getMessage()]);
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Initialize QueueService
$queueService = new QueueService($logger);

if (!$queueService->isConnected()) {
    $logger->error('Could not connect to queue service. Is beanstalkd running?');
    die("Error: Could not connect to queue service. Is beanstalkd running?\n");
}

// Initialize VideoQueueManager
$queueManager = new VideoQueueManager($pdo, $logger);

// Initialize VideoDownloadManager
$downloadPath = BASE_PATH . '/storage/uploads/videos';
if (!file_exists($downloadPath)) {
    mkdir($downloadPath, 0755, true);
}

$downloadLogger = new Logger('video_downloader');
$downloadLogger->pushHandler(new RotatingFileHandler($logDir . '/video_downloader.log', 7, Level::Debug));
$downloadManager = new VideoDownloadManager($downloadLogger, $downloadPath);

// Initialize VideoDownloadWorker
$worker = new VideoDownloadWorker($queueService, $queueManager, $logger, $downloadManager, $pdo, $downloadPath);

// Process commands
switch ($command) {
    case 'run':
        // Parse options
        $maxJobs = 0;
        $maxRuntime = 0;
        
        foreach ($options as $option) {
            if (preg_match('/^--max-jobs=(\d+)$/', $option, $matches)) {
                $maxJobs = (int)$matches[1];
            } elseif (preg_match('/^--max-runtime=(\d+)$/', $option, $matches)) {
                $maxRuntime = (int)$matches[1];
            }
        }
        
        $logger->info('Running worker', [
            'max_jobs' => $maxJobs,
            'max_runtime' => $maxRuntime
        ]);
        
        // Run the worker
        $worker->run($maxJobs, $maxRuntime);
        break;
        
    case 'queue':
        // Queue pending videos
        $count = $queueManager->queuePendingVideos();
        $logger->info("Added {$count} videos to the queue");
        echo "Added {$count} videos to the queue\n";
        break;
        
    case 'status':
        // Show queue status
        $stats = $queueService->stats();
        $tubeStats = $queueService->statsTube('video_downloads');
        
        echo "Queue Status:\n";
        echo "---------------\n";
        echo "Total jobs: {$stats['total-jobs']}\n";
        echo "Current jobs: {$stats['current-jobs-ready']}\n";
        echo "Workers: {$stats['current-workers']}\n";
        echo "\nTube: video_downloads\n";
        echo "Ready jobs: {$tubeStats['current-jobs-ready']}\n";
        echo "Reserved jobs: {$tubeStats['current-jobs-reserved']}\n";
        echo "Delayed jobs: {$tubeStats['current-jobs-delayed']}\n";
        echo "Buried jobs: {$tubeStats['current-jobs-buried']}\n";
        break;
        
    case 'purge':
        // Purge the queue
        try {
            $queueService->purge('video_downloads');
            $logger->info("Queue purged successfully");
            echo "Queue purged successfully\n";
        } catch (Exception $e) {
            $logger->error("Failed to purge queue", ['error' => $e->getMessage()]);
            echo "Failed to purge queue: " . $e->getMessage() . "\n";
        }
        break;
        
    case 'retry-failed':
        // Retry failed jobs
        try {
            $count = $queueManager->retryFailedJobs();
            $logger->info("Reset {$count} failed jobs to pending");
            echo "Reset {$count} failed jobs to pending\n";
        } catch (Exception $e) {
            $logger->error("Failed to retry failed jobs", ['error' => $e->getMessage()]);
            echo "Failed to retry failed jobs: " . $e->getMessage() . "\n";
        }
        break;
        
    case 'help':
    default:
        echo "Usage: php queue_workers.php [command] [options]\n";
        echo "Commands:\n";
        echo "  run             Run the worker process\n";
        echo "    --max-jobs=<N>      Process a maximum of N jobs (0 = unlimited)\n";
        echo "    --max-runtime=<N>   Run for a maximum of N seconds (0 = unlimited)\n";
        echo "  queue           Add pending videos to the queue\n";
        echo "  status          Show status of the queue\n";
        echo "  purge           Purge all jobs from the queue\n";
        echo "  retry-failed    Reset failed jobs to pending\n";
        echo "  help            Show this help message\n";
        break;
}

// Exit with success
exit(0);