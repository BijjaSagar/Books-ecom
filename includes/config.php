<?php
/**
 * Database Configuration and Application Bootstrap
 *
 * This file loads environment variables and establishes database connection
 * All configuration values are now loaded from .env file for security
 *
 * @package Bookory
 * @version 2.0.0
 */

// Load environment variables
require_once __DIR__ . '/env.php';

// ============================================
// MySQL Database Configuration from .env
// ============================================

$db_host = env('DB_HOST', 'localhost');
$db_username = env('DB_USERNAME');
$db_password = env('DB_PASSWORD');
$db_name = env('DB_DATABASE');
$db_port = env('DB_PORT', 3306);
$db_charset = env('DB_CHARSET', 'utf8mb4');

// ============================================
// FALLBACK: If .env is not configured properly
// ============================================
// TEMPORARY: Replace these with your actual Hostinger credentials
// Then configure .env file properly and remove this section
// ============================================

$is_placeholder = in_array($db_password, ['CHANGE_THIS_PASSWORD', 'your_password_here', '', null]);

if (empty($db_username) || empty($db_password) || empty($db_name) || $is_placeholder) {
    error_log("WARNING: .env file not configured. Using fallback configuration.");

    // ⚠️ TEMPORARY FALLBACK - REPLACE WITH YOUR ACTUAL CREDENTIALS! ⚠️
    // Get these values from: hPanel → Hosting → MySQL Databases → Manage
    $db_host = 'localhost';
    $db_username = 'u618910819_books';      // ← REPLACE: Your MySQL username
    $db_password = 'YOUR_PASSWORD_HERE';    // ← REPLACE: Your MySQL password
    $db_name = 'u618910819_bookshelf_db';   // ← REPLACE: Your database name
    $db_port = 3306;

    // If still using placeholder, show configuration guide
    if ($db_password === 'YOUR_PASSWORD_HERE' || empty($db_password)) {
        http_response_code(503);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Configuration Required - Bookory</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.95;
            font-size: 16px;
        }
        .content {
            padding: 40px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .step h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
        .step h3::before {
            content: "→";
            font-size: 24px;
            margin-right: 10px;
        }
        .step ul {
            list-style: none;
            padding-left: 0;
        }
        .step li {
            margin: 10px 0;
            padding-left: 25px;
            position: relative;
        }
        .step li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        code {
            background: #263238;
            color: #aed581;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: "Courier New", Consolas, monospace;
            font-size: 14px;
            display: inline-block;
        }
        .code-block {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            overflow-x: auto;
            font-family: "Courier New", Consolas, monospace;
            font-size: 13px;
            line-height: 1.8;
        }
        .highlight {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .highlight strong {
            color: #856404;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            margin: 10px 10px 10px 0;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        .btn-secondary {
            background: #6c757d;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 40px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 20px;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            color: #6c757d;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        @media (max-width: 768px) {
            .header h1 { font-size: 24px; }
            .content { padding: 20px; }
            .step { padding: 15px; }
            .tabs { overflow-x: auto; }
            .tab { white-space: nowrap; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Database Configuration Required</h1>
            <p>Complete setup to start using Bookory</p>
        </div>

        <div class="content">
            <div class="highlight">
                <strong>⚡ Quick Fix (2 Minutes):</strong><br>
                Edit <code>includes/config.php</code> line 35 and replace <code>YOUR_PASSWORD_HERE</code> with your database password from hPanel.
            </div>

            <div class="tabs">
                <button class="tab active" onclick="switchTab(0)">Option 1: Edit .env (Recommended)</button>
                <button class="tab" onclick="switchTab(1)">Option 2: Edit config.php (Quick)</button>
            </div>

            <div class="tab-content active">
                <div class="step">
                    <h3>Step 1: Get Your Database Credentials</h3>
                    <ul>
                        <li>Login to <a href="https://hpanel.hostinger.com" target="_blank"><strong>hPanel</strong></a></li>
                        <li>Go to <strong>Hosting → MySQL Databases</strong></li>
                        <li>Click <strong>Manage</strong> next to <code>u618910819_bookshelf_db</code></li>
                        <li>Copy: <strong>Database Name</strong>, <strong>Username</strong>, <strong>Password</strong></li>
                    </ul>
                </div>

                <div class="step">
                    <h3>Step 2: Edit .env File</h3>
                    <ul>
                        <li>In hPanel, go to <strong>Files → File Manager</strong></li>
                        <li>Navigate to <code>public_html/bookshelf/</code></li>
                        <li>Right-click <code>.env</code> → <strong>Edit</strong></li>
                        <li>Update lines 24-28:</li>
                    </ul>
                    <div class="code-block">DB_HOST=localhost<br>DB_PORT=3306<br>DB_DATABASE=u618910819_bookshelf_db<br>DB_USERNAME=u618910819_books<br>DB_PASSWORD=<span style="background:#ffc107;color:#000;padding:2px 6px;border-radius:3px;">PASTE_YOUR_PASSWORD_HERE</span></div>
                    <ul>
                        <li>Click <strong>Save</strong></li>
                        <li>Refresh this page</li>
                    </ul>
                </div>
            </div>

            <div class="tab-content">
                <div class="step">
                    <h3>Step 1: Get Your Database Password</h3>
                    <ul>
                        <li>Login to <a href="https://hpanel.hostinger.com" target="_blank"><strong>hPanel</strong></a></li>
                        <li>Go to <strong>Hosting → MySQL Databases</strong></li>
                        <li>Click <strong>Manage</strong> next to your database</li>
                        <li>Copy the <strong>Password</strong> shown</li>
                    </ul>
                </div>

                <div class="step">
                    <h3>Step 2: Edit config.php</h3>
                    <ul>
                        <li>In File Manager, navigate to <code>public_html/bookshelf/includes/</code></li>
                        <li>Right-click <code>config.php</code> → <strong>Edit</strong></li>
                        <li>Find line 35 (around line 35):</li>
                    </ul>
                    <div class="code-block">$db_password = 'YOUR_PASSWORD_HERE';    // ← REPLACE</div>
                    <ul>
                        <li>Replace <code>YOUR_PASSWORD_HERE</code> with your actual password</li>
                        <li>Also verify <code>$db_username</code> and <code>$db_name</code> match your hPanel values</li>
                        <li>Click <strong>Save</strong></li>
                        <li>Refresh this page</li>
                    </ul>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="https://hpanel.hostinger.com" class="btn" target="_blank">Open hPanel →</a>
                <a href="javascript:location.reload()" class="btn btn-secondary">Refresh Page</a>
            </div>
        </div>

        <div class="footer">
            <strong>Server Path:</strong><br>
            <code>/home/u618910819/domains/rhtechnology.in/public_html/bookshelf/includes/config.php</code>
        </div>
    </div>

    <script>
        function switchTab(index) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach((tab, i) => {
                if (i === index) {
                    tab.classList.add('active');
                    contents[i].classList.add('active');
                } else {
                    tab.classList.remove('active');
                    contents[i].classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
        <?php
        exit;
    }
}

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

        if (env('APP_ENV') === 'development' && env('APP_DEBUG', false)) {
            die("Database connection failed: " . $conn->connect_error);
        } else {
            die("Database connection failed. Please check your configuration or contact support.");
        }
    }

    // Set charset
    if (!$conn->set_charset($db_charset)) {
        error_log("Error loading character set {$db_charset}: " . $conn->error);
    }

    // Set timezone for MySQL
    $timezone = env('APP_TIMEZONE', 'UTC');
    $conn->query("SET time_zone = '+00:00'");

    // Enable strict error reporting in development
    if (env('APP_ENV') === 'development' && env('DEBUG_SQL', false)) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }

} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());

    if (env('APP_ENV') === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please contact administrator.");
    }
}

// ============================================
// Application Configuration
// ============================================

// Set PHP timezone
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

// Error reporting based on environment
if (env('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', env('DISPLAY_ERRORS', '1'));
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    $session_config = [
        'name' => env('SESSION_NAME', 'BOOKORY_SESSION'),
        'cookie_lifetime' => env('SESSION_LIFETIME', 120) * 60, // Convert minutes to seconds
        'cookie_secure' => env('SESSION_SECURE', true),
        'cookie_httponly' => env('SESSION_HTTP_ONLY', true),
        'cookie_samesite' => env('SESSION_SAME_SITE', 'Strict'),
        'use_strict_mode' => true,
        'use_only_cookies' => true
    ];

    session_set_cookie_params([
        'lifetime' => $session_config['cookie_lifetime'],
        'path' => '/',
        'domain' => parse_url(env('APP_URL', ''), PHP_URL_HOST) ?: '',
        'secure' => $session_config['cookie_secure'],
        'httponly' => $session_config['cookie_httponly'],
        'samesite' => $session_config['cookie_samesite']
    ]);

    session_name($session_config['name']);
    session_start();
}

// ============================================
// Database Helper Functions
// ============================================

/**
 * Prepare SQL statement
 *
 * @param mysqli $conn Database connection
 * @param string $sql SQL query
 * @return mysqli_stmt|false Statement object or false on failure
 */
function prepare_statement($conn, $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error . " | Query: " . $sql);
        return false;
    }
    return $stmt;
}

/**
 * Execute query with prepared statement
 *
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (e.g., 'ssi' for string, string, integer)
 * @param array $params Parameter values
 * @return mysqli_result|bool Result set or false on failure
 */
function execute_query($conn, $sql, $types = '', $params = array()) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Query preparation failed: " . $conn->error . " | Query: " . $sql);
        return false;
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error . " | Query: " . $sql);
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

/**
 * Get last insert ID
 *
 * @param mysqli $conn Database connection
 * @return int Last insert ID
 */
function get_last_insert_id($conn) {
    return $conn->insert_id;
}

/**
 * Execute transaction with callback
 *
 * @param mysqli $conn Database connection
 * @param callable $callback Function to execute within transaction
 * @return mixed Result from callback or false on failure
 */
function db_transaction($conn, $callback) {
    $conn->begin_transaction();

    try {
        $result = $callback($conn);
        $conn->commit();
        return $result;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Safely escape string for SQL (use prepared statements when possible)
 *
 * @param mysqli $conn Database connection
 * @param string $value Value to escape
 * @return string Escaped value
 */
function db_escape($conn, $value) {
    return $conn->real_escape_string($value);
}

// ============================================
// Application Constants
// ============================================

define('DB_HOST', $db_host);
define('DB_USERNAME', $db_username);
define('DB_NAME', $db_name);
define('DB_PORT', $db_port);

define('APP_NAME', env('APP_NAME', 'Bookory'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('APP_URL', env('APP_URL', ''));

define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@bookory.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Bookory'));

define('UPLOAD_MAX_SIZE', env('MAX_UPLOAD_SIZE', 10485760));
define('IMAGE_MAX_SIZE', env('MAX_IMAGE_SIZE', 5242880));

define('CSRF_ENABLED', env('CSRF_PROTECTION', true));
define('RATE_LIMIT', env('RATE_LIMIT', 60));

// ============================================
// Security Functions
// ============================================

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is authenticated
 *
 * @return bool True if authenticated
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 *
 * @return bool True if admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require authentication (redirect if not logged in)
 *
 * @param string $redirect_to URL to redirect to after login
 * @return void
 */
function require_auth($redirect_to = null) {
    if (!is_authenticated()) {
        $redirect = $redirect_to ?? $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_after_login'] = $redirect;
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require admin access (redirect if not admin)
 *
 * @return void
 */
function require_admin() {
    require_auth();

    if (!is_admin()) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

/**
 * Sanitize output for HTML
 *
 * @param string $value Value to sanitize
 * @return string Sanitized value
 */
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize user input
 *
 * @param string $value Value to sanitize
 * @return string Sanitized value
 */
function sanitize_input($value) {
    $value = trim($value);
    $value = stripslashes($value);
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ============================================
// Logging Functions
// ============================================

/**
 * Log message to file
 *
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 * @return void
 */
function log_message($message, $level = 'info') {
    if (!env('ENABLE_ERROR_LOGGING', true)) {
        return;
    }

    $log_path = env('LOG_PATH', __DIR__ . '/../logs/');

    if (!is_dir($log_path)) {
        @mkdir($log_path, 0755, true);
    }

    $log_file = $log_path . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ============================================
// Maintenance Mode Check
// ============================================

if (env('MAINTENANCE_MODE', false)) {
    // Allow access from specific IPs
    $allowed_ips = explode(',', env('MAINTENANCE_ALLOWED_IPS', '127.0.0.1'));
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!in_array($user_ip, $allowed_ips)) {
        http_response_code(503);
        $message = env('MAINTENANCE_MESSAGE', 'We are currently performing scheduled maintenance. Please check back soon!');

        echo '<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Mode</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 40px;
        }
        h1 { font-size: 48px; margin-bottom: 20px; }
        p { font-size: 20px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Maintenance Mode</h1>
        <p>' . h($message) . '</p>
    </div>
</body>
</html>';
        exit;
    }
}

?>
