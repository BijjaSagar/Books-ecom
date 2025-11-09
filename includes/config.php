<?php
// includes/config.php
// Database configuration for SQLite

// SQLite database file
$db_file = 'bookshelf.db';

// Create SQLite connection
try {
    $conn = new SQLite3($db_file);
    $conn->exec('PRAGMA foreign_keys = ON;');
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Enable error reporting for development (disable in production)
if (defined('DEBUG') && DEBUG) {
    // SQLite3 doesn't use mysqli_report, but we can enable exceptions
    $conn->enableExceptions(true);
}

// Set timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to emulate MySQLi prepare statement for SQLite
function prepare_statement($conn, $sql) {
    return $conn->prepare($sql);
}
?>