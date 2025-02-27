<?php
/**
 * Database helper functions for tag page and video processing
 */

// Make sure we have a database connection function
if (!function_exists('testDBConnection')) {
    /**
     * Create a PDO database connection
     * @return PDO|false PDO instance on success, false on failure
     */
    function testDBConnection() {
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';
            
            if (isset($host) && isset($dbname) && isset($username) && isset($password)) {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                return $pdo;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get list of supported adult websites from database
 * @return array List of supported websites with their names and URLs
 */
function getSupportedWebsites() {
    try {
        $pdo = testDBConnection();
        
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $pdo->query("SELECT website_name, website_url FROM supported_adult_websites ORDER BY website_name");
        
        if (!$stmt) {
            throw new Exception('Query failed');
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $results;
    } catch (Exception $e) {
        error_log("Error getting supported websites: " . $e->getMessage());
        return [];
    }
}

/**
 * Get video processing status by ID
 * @param int $videoId Video ID to check
 * @return array|null Video status data or null if not found
 */
function getVideoStatus($videoId) {
    try {
        $pdo = testDBConnection();
        
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                processing_status, 
                status_message, 
                download_progress,
                result_data,
                created_at,
                updated_at
            FROM processed_videos 
            WHERE id = :id
        ");
        
        $stmt->execute([':id' => $videoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['result_data']) {
            // Convert JSON string to PHP array if present
            $result['result_data'] = json_decode($result['result_data'], true);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error getting video status: " . $e->getMessage());
        return null;
    }
}

/**
 * Update video processing status
 * @param int $videoId Video ID to update
 * @param string $status New status (pending, processing, completed, failed)
 * @param string $message Status message
 * @param float $progress Download progress (0-100)
 * @return bool Success status
 */
function updateVideoStatus($videoId, $status, $message = '', $progress = null) {
    try {
        $pdo = testDBConnection();
        
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $params = [
            ':id' => $videoId,
            ':status' => $status,
            ':message' => $message
        ];
        
        $sql = "
            UPDATE processed_videos 
            SET processing_status = :status,
                status_message = :message,
                updated_at = NOW()
        ";
        
        // Add download progress if provided
        if ($progress !== null) {
            $sql .= ", download_progress = :progress";
            $params[':progress'] = $progress;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
        
    } catch (Exception $e) {
        error_log("Error updating video status: " . $e->getMessage());
        return false;
    }
}

/**
 * Store video analysis results
 * @param int $videoId Video ID
 * @param array $results Results to store (will be JSON encoded)
 * @return bool Success status
 */
function storeVideoResults($videoId, $results) {
    try {
        $pdo = testDBConnection();
        
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $pdo->prepare("
            UPDATE processed_videos 
            SET result_data = :results,
                processing_status = 'completed',
                status_message = 'Analysis complete',
                updated_at = NOW()
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id' => $videoId,
            ':results' => json_encode($results)
        ]);
        
    } catch (Exception $e) {
        error_log("Error storing video results: " . $e->getMessage());
        return false;
    }
}

// Check if getRandomGradientClass function already exists in globals.php before declaring it
if (!function_exists('getRandomGradientClass')) {
    /**
     * Generate a random gradient class for UI styling
     * @param bool $returnAll Whether to return all gradient properties
     * @return string|array Gradient class string or array of properties
     */
    function getRandomGradientClass($returnAll = false) {
        $gradients = [
            [
                'class' => 'gradient-primairy',
                'from' => 'from-secondary',
                'to' => 'to-tertery',
                'text_from' => 'text-secondary',
                'text_to' => 'text-tertery',
            ],
            [
                'class' => 'gradient-secondary',
                'from' => 'from-secondary',
                'to' => 'to-secondaryTerteryMix',
                'text_from' => 'text-secondary',
                'text_to' => 'text-secondaryTerteryMix',
            ],
            [
                'class' => 'gradient-tertery',
                'from' => 'from-tertery',
                'to' => 'to-primairy',
                'text_from' => 'text-tertery',
                'text_to' => 'text-primairy',
            ],
        ];
        
        $randomIndex = rand(0, count($gradients) - 1);
        
        return $returnAll ? $gradients[$randomIndex] : $gradients[$randomIndex]['class'];
    }
}

/**
 * Get pending videos that need processing
 * @param int $limit Maximum number of videos to retrieve
 * @return array List of videos that need processing
 */
function getPendingVideos($limit = 5) {
    try {
        $pdo = testDBConnection();
        
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $pdo->prepare("
            SELECT id, source_type, source_path, video_url 
            FROM processed_videos 
            WHERE processing_status IN ('pending', 'processing')
            AND updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ORDER BY created_at ASC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting pending videos: " . $e->getMessage());
        return [];
    }
}

/**
 * Log processing error for a video
 * @param int $videoId Video ID
 * @param string $errorMessage Error description
 * @return bool Success status
 */
function logProcessingError($videoId, $errorMessage) {
    try {
        return updateVideoStatus($videoId, 'failed', $errorMessage);
    } catch (Exception $e) {
        error_log("Error logging processing error: " . $e->getMessage());
        return false;
    }
}
