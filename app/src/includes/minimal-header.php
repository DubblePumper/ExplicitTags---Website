<?php
// No output, just setting up required environment
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// Function to test database connection
function getDatabase() {
    global $dsn, $db_user, $db_pass, $options;
    try {
        return new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}
?>
