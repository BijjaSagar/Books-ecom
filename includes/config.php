<?php
// includes/config.php
// Database configuration for MySQL

// ============================================
// MySQL Database Configuration
// ============================================
// Edit these values with your actual database credentials
$db_host = 'localhost';                    // Your MySQL server host
$db_username = 'root';                     // Your MySQL username
$db_password = '';                         // Your MySQL password
$db_name = 'u618910819_bookshelf_db';      // Your database name
$db_port = 3306;                           // MySQL port (default: 3306)

// Create MySQL connection using MySQLi
try {
    $conn = new mysqli(
        $db_host,
        $db_username,
        $db_password,
        $db_name,
        (int)$db_port
    );

    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please check your configuration.");
    }

    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
    }

    // Set timezone for MySQL
    $conn->query("SET time_zone = '+00:00'");

    // Enable reporting for development (disable in production)
    if (defined('DEBUG') && DEBUG) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Set timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function for prepared statements
function prepare_statement($conn, $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    return $stmt;
}

// Function to safely execute query
function execute_query($conn, $sql, $types = '', $params = array()) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Query failed: " . $conn->error);
        return false;
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Execution failed: " . $stmt->error);
        return false;
    }

    return $stmt->get_result();
}

// Function to get last insert ID
function get_last_insert_id($conn) {
    return $conn->insert_id;
}

// Database constants
define('DB_HOST', $db_host);
define('DB_USERNAME', $db_username);
define('DB_NAME', $db_name);
define('DB_PORT', $db_port);

?>