<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

echo "retry: 3000\n\n";

function sendSSE($event, $data) {
    echo "id: " . time() . "\n";
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

try {
    $pdo = testDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $performer_id = filter_input(INPUT_GET, 'performer_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (!$performer_id) {
        throw new Exception('No performer ID provided');
    }

    // Update query to include image URLs
    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM performer_images WHERE performer_id = p.id) as image_amount,
              GROUP_CONCAT(DISTINCT pi.image_url) as image_urls
              FROM performers p 
              LEFT JOIN performer_images pi ON p.id = pi.performer_id
              WHERE p.id = :performer_id
              GROUP BY p.id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':performer_id' => $performer_id]);
    
    $performer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($performer) {
        // Clean data and process images
        $performer = array_map(function($value) {
            return $value === null ? "" : $value;
        }, $performer);

        // Process image URLs
        if ($performer['image_urls']) {
            $imageUrls = explode(',', $performer['image_urls']);
            $imageUrls = array_map(function($url) {
                // Remove Windows path prefix and convert backslashes
                $url = str_replace('E:\\github repos\\porn_ai_analyser\\app\\datasets\\pornstar_images\\', '', $url);
                $url = str_replace('\\', '/', $url);
                
                // Clean any potential dots at the end of folder names
                $parts = explode('/', $url);
                array_walk($parts, function(&$part) {
                    $part = rtrim($part, '.');
                });
                $url = implode('/', $parts);
                
                // Add CDN prefix
                return 'https://cdn.jsdelivr.net/gh/DubblePumper/porn_ai_analyser@main/app/datasets/pornstar_images/' . $url;
            }, $imageUrls);

            // Filter out any empty or invalid URLs
            $imageUrls = array_filter($imageUrls, function($url) {
                return !empty($url) && strpos($url, '.jpg') !== false;
            });

            $performer['image_urls'] = array_values($imageUrls);
        } else {
            $performer['image_urls'] = [];
        }
        
        sendSSE('performer_detail', [$performer]);
    } else {
        sendSSE('error', ['error' => 'Performer not found']);
    }

} catch (Exception $e) {
    error_log("Performer Detail SSE Error: " . $e->getMessage());
    sendSSE('error', ['error' => 'Error fetching performer details']);
}

exit(0);
