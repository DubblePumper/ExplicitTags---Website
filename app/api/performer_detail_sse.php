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

    // Query for detailed performer info
    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM performer_images WHERE performer_id = p.id) as image_amount 
              FROM performers p 
              WHERE p.id = :performer_id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':performer_id' => $performer_id]);
    
    $performer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($performer) {
        // Clean data and wrap in array
        $performer = array_map(function($value) {
            return $value === null ? "" : $value;
        }, $performer);
        
        // Send as array even for single record
        sendSSE('performer_detail', [$performer]);
    } else {
        sendSSE('error', ['error' => 'Performer not found']);
    }

} catch (Exception $e) {
    error_log("Performer Detail SSE Error: " . $e->getMessage());
    sendSSE('error', ['error' => 'Error fetching performer details']);
}

exit(0);
