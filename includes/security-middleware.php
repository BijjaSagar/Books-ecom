<?php
/**
 * Security Middleware
 * Include this file at the top of every page/API endpoint
 * Provides CSRF protection, security headers, and input validation
 *
 * Usage: require_once __DIR__ . '/security-middleware.php';
 */

require_once __DIR__ . '/SecurityManager.php';

// Initialize security
$security_manager = new SecurityManager($conn ?? null);

// Set security headers (do this on every request)
SecurityManager::setSecurityHeaders();

// Validate CSRF token on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (empty($csrf_token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'CSRF token missing']));
    }

    if (!$security_manager->validateCSRFToken($csrf_token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
    }
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically (every 5 minutes)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Helper function to get CSRF token in templates
function csrf_token() {
    global $security_manager;
    return $security_manager->generateCSRFToken();
}

// Helper function to output CSRF token input
function csrf_token_input() {
    global $security_manager;
    return $security_manager->getCSRFTokenInput();
}

// Helper function to escape HTML output
function esc_html($string) {
    return SecurityManager::escapeHTML($string);
}

// Helper function to escape JS output
function esc_js($string) {
    return SecurityManager::escapeJS($string);
}

// Helper function to escape URL output
function esc_url($string) {
    return SecurityManager::escapeURL($string);
}

// Helper function to escape attribute output
function esc_attr($string) {
    return SecurityManager::escapeAttribute($string);
}

// Helper function to sanitize input
function sanitize($input, $type = 'string') {
    global $security_manager;
    return $security_manager->sanitizeInput($input, $type);
}

// Helper function to validate email
function is_valid_email($email) {
    global $security_manager;
    return $security_manager->validateEmail($email);
}

// Helper function to check rate limiting
function check_rate_limit($action, $user_id, $limit = 10, $window = 60) {
    global $security_manager;
    return $security_manager->checkRateLimit($action, $user_id, $limit, $window);
}

// Helper function to log security events
function log_security_event($event_type, $user_id = null, $description = '', $ip = null) {
    global $security_manager;
    return $security_manager->logSecurityEvent($event_type, $user_id, $description, $ip);
}

// Auto-detect potential attacks and log them
$is_potential_attack = false;
$attack_type = null;

// Check all inputs for SQL injection
foreach ($_POST as $key => $value) {
    if ($security_manager->detectSQLInjection($value)) {
        $is_potential_attack = true;
        $attack_type = 'SQL_INJECTION';
        break;
    }
    if ($security_manager->detectXSS($value)) {
        $is_potential_attack = true;
        $attack_type = 'XSS_ATTEMPT';
        break;
    }
}

foreach ($_GET as $key => $value) {
    if ($security_manager->detectSQLInjection($value)) {
        $is_potential_attack = true;
        $attack_type = 'SQL_INJECTION_GET';
        break;
    }
    if ($security_manager->detectXSS($value)) {
        $is_potential_attack = true;
        $attack_type = 'XSS_ATTEMPT_GET';
        break;
    }
}

if ($is_potential_attack) {
    $user_id = $_SESSION['user_id'] ?? null;
    $description = "Potential {$attack_type} detected: " . json_encode($_REQUEST);

    $security_manager->logSecurityEvent(
        $attack_type,
        $user_id,
        $description,
        $security_manager->getClientIP()
    );

    // Block the request
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Request blocked for security reasons']));
}
?>
