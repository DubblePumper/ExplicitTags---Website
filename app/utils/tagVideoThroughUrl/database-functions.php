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

// Initialize PDO connection
$pdo = testDBConnection();

