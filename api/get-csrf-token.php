<?php
/**
 * CSRF Token Endpoint
 * Returns CSRF token for frontend use
 *
 * GET /api/get-csrf-token.php
 * Returns: csrf_token, token_time
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/SecurityManager.php';

// Set security headers
SecurityManager::setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $security = new SecurityManager($conn);

    // Generate or retrieve CSRF token
    $token = $security->generateCSRFToken();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'csrf_token' => $token,
        'token_time' => $_SESSION['csrf_token_time'] ?? time()
    ]);

} catch (Exception $e) {
    error_log("[CSRF Token API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to generate token']);
}
?>
