<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Function to get all supported adult websites from the database
function getSupportedWebsites() {
    global $pdo;
    
    try {
        if (!$pdo) {
            $pdo = testDBConnection();
        }
        
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $pdo->query("SELECT * FROM supported_adult_websites ORDER BY website_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching supported websites: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        return [];
    }
}

// Function to get just the website domains for validation
function getSupportedDomains() {
    $websites = getSupportedWebsites();
    $domains = [];
    
    foreach ($websites as $site) {
        if (isset($site['website_url'])) {
            $domain = parse_url($site['website_url'], PHP_URL_HOST);
            if ($domain) {
                // Remove 'www.' if present
                $domains[] = preg_replace('/^www\./', '', $domain);
            }
        }
    }
    
    return $domains;
}

// Function to get just the website names for display
function getSupportedNames() {
    $websites = getSupportedWebsites();
    $names = [];
    
    foreach ($websites as $site) {
        if (isset($site['website_name'])) {
            $names[] = $site['website_name'];
        }
    }
    
    return $names;
}

// Function to create necessary database tables if they don't exist
function ensureTablesExist($pdo) {
    try {
        // First, add a check for the updated processed_videos table
        $pdo->exec("ALTER TABLE processed_videos 
            MODIFY COLUMN processing_status 
            ENUM('pending', 'downloading', 'optimizing', 'processing', 'completed', 'failed') 
            DEFAULT 'pending'");
    } catch (PDOException $e) {
        // If table doesn't exist or column can't be modified, create it from scratch
        try {
            // Table for processed videos
            $pdo->exec("CREATE TABLE IF NOT EXISTS processed_videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source_type ENUM('upload', 'url') NOT NULL,
                source_path VARCHAR(255) DEFAULT NULL,
                video_url TEXT DEFAULT NULL,
                processing_status ENUM('pending', 'downloading', 'optimizing', 'processing', 'completed', 'failed') DEFAULT 'pending',
                download_progress INT DEFAULT 0,
                result_data JSON DEFAULT NULL,
                user_ip VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
        } catch (PDOException $innerE) {
            error_log("Error creating processed_videos table: " . $innerE->getMessage());
        }
    }

    // Check if download_progress column exists in processed_videos
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM processed_videos LIKE 'download_progress'");
        if ($stmt->rowCount() == 0) {
            // Column doesn't exist, add it
            $pdo->exec("ALTER TABLE processed_videos ADD COLUMN download_progress INT DEFAULT 0 AFTER processing_status");
        }
    } catch (PDOException $e) {
        error_log("Error checking/adding download_progress column: " . $e->getMessage());
    }

    // Table for video processing queue
    $pdo->exec("CREATE TABLE IF NOT EXISTS video_processing_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (video_id) REFERENCES processed_videos(id) ON DELETE CASCADE
    )");
    
    // Table for supported adult websites
    $pdo->exec("CREATE TABLE IF NOT EXISTS supported_adult_websites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_name VARCHAR(100) NOT NULL,
        website_url VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    return true;
}

// Add a function to ensure database updates whenever the files are accessed
function updateDatabase() {
    global $pdo;
    if ($pdo) {
        ensureTablesExist($pdo);
    }
}

// Initialize PDO connection and ensure tables exist
$pdo = testDBConnection();
if ($pdo) {
    updateDatabase();
}

