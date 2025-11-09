<?php
// Start a session on every page.

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$servername = "localhost"; // This is usually correct on Hostinger
$username = "u618910819_bookshelf_db"; 
$password = "HACK@ers143";
$dbname = "u618910819_bookshelf_db"; 

// --- Create Connection ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Check Connection ---
if ($conn->connect_error) {
    // In production, you might want to log this error instead of showing it to the user.
    die("Database Connection Failed: " . $conn->connect_error);
}

// --- Fetch Dynamic Site Settings ---
// We fetch all settings into a handy associative array called $settings.
$settings = [];
$sql_settings = "SELECT setting_key, setting_value FROM site_settings";
$result_settings = $conn->query($sql_settings);
if ($result_settings->num_rows > 0) {
    while($row = $result_settings->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
// Now you can use $settings['site_name'], $settings['site_currency'], etc. on any page.
?>