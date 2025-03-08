<?php
/**
 * Database helper functions for tag page and video processing
 */

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

// Include config file with database connection information
require_once BASE_PATH . '/config/config.php';

/**
 * Get PDO database connection
 * @return PDO|false Database connection or false on failure
 */
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        global $dbConfig;
        
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']}",
                PDO::ATTR_TIMEOUT => 5
            ];
            
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get list of supported websites from database
 * @return array Array of supported websites
 */
if (!function_exists('getSupportedWebsites')) {
    function getSupportedWebsites() {
        $pdo = getDBConnection();
        if (!$pdo) return [];
        
        try {
            $stmt = $pdo->query("SELECT website_name, website_url FROM supported_websites ORDER BY website_name");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting supported websites: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Save a video processing task to the database
 * @param array $data Video data
 * @return int|bool Inserted ID or false on failure
 */
if (!function_exists('saveVideoProcessingTask')) {
    function saveVideoProcessingTask($data) {
        $pdo = getDBConnection();
        if (!$pdo) return false;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO video_processing_tasks 
                (video_url, video_file_path, status, created_at, updated_at)
                VALUES (:video_url, :video_file_path, :status, NOW(), NOW())
            ");
            
            $stmt->bindValue(':video_url', $data['video_url'] ?? null);
            $stmt->bindValue(':video_file_path', $data['video_file_path'] ?? null);
            $stmt->bindValue(':status', $data['status'] ?? 'pending');
            
            $stmt->execute();
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error saving video task: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Update a video processing task status
 * @param int $taskId Task ID
 * @param string $status New status
 * @param array $additionalData Optional additional data to update
 * @return bool Success or failure
 */
if (!function_exists('updateVideoTaskStatus')) {
    function updateVideoTaskStatus($taskId, $status, $additionalData = []) {
        $pdo = getDBConnection();
        if (!$pdo) return false;
        
        try {
            $updateFields = ["status = :status", "updated_at = NOW()"];
            $params = [':status' => $status, ':id' => $taskId];
            
            foreach ($additionalData as $field => $value) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
            
            $updateString = implode(', ', $updateFields);
            $stmt = $pdo->prepare("UPDATE video_processing_tasks SET $updateString WHERE id = :id");
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating task status: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get a video processing task by ID
 * @param int $taskId Task ID
 * @return array|bool Task data or false on failure
 */
if (!function_exists('getVideoTask')) {
    function getVideoTask($taskId) {
        $pdo = getDBConnection();
        if (!$pdo) return false;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM processed_videos WHERE id = :id");
            $stmt->execute([':id' => $taskId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting task: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get video processing status by ID
 * @param int $videoId Video ID to check
 * @return array|null Video status data or null if not found
 */
if (!function_exists('getVideoStatus')) {
    function getVideoStatus($videoId) {
        try {
            $pdo = getDBConnection();
            
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
}

/**
 * Update video processing status
 * @param int $videoId Video ID to update
 * @param string $status New status (pending, processing, completed, failed)
 * @param string $message Status message
 * @param float $progress Download progress (0-100)
 * @return bool Success status
 */
if (!function_exists('updateVideoStatus')) {
    function updateVideoStatus($videoId, $status, $message = '', $progress = null) {
        try {
            $pdo = getDBConnection();
            
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
}

/**
 * Store video analysis results
 * @param int $videoId Video ID
 * @param array $results Results to store (will be JSON encoded)
 * @return bool Success status
 */
if (!function_exists('storeVideoResults')) {
    function storeVideoResults($videoId, $results) {
        try {
            $pdo = getDBConnection();
            
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
}

// Check if getRandomGradientClass function already exists before declaring it
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
if (!function_exists('getPendingVideos')) {
    function getPendingVideos($limit = 5) {
        try {
            $pdo = getDBConnection();
            
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
}

/**
 * Log processing error for a video
 * @param int $videoId Video ID
 * @param string $errorMessage Error description
 * @return bool Success status
 */
if (!function_exists('logProcessingError')) {
    function logProcessingError($videoId, $errorMessage) {
        try {
            return updateVideoStatus($videoId, 'failed', $errorMessage);
        } catch (Exception $e) {
            error_log("Error logging processing error: " . $e->getMessage());
            return false;
        }
    }
}
