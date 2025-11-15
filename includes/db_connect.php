<?php
// DEPRECATED: This file is maintained for backwards compatibility only
// Please use includes/config.php instead for all new code

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the main config file which handles database connection
require_once __DIR__ . '/config.php';

// config.php already establishes $conn and loads $settings
// This file is kept only for backwards compatibility with existing code
?>