<?php
/**
 * Checkout Session Endpoint
 * Manages checkout session creation and retrieval
 *
 * GET /api/checkout-session.php - Get current/create new session
 * Returns: session_id, current_step, session_data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CheckoutManager.php';

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    // Get or create session ID from cookie/query param
    $session_id = $_GET['session_id'] ?? ($_COOKIE['checkout_session'] ?? null);

    // Initialize checkout manager
    $checkout_manager = new CheckoutManager($conn);

    // If no session ID provided, create new one
    if (!$session_id) {
        $session_id = bin2hex(random_bytes(16));
        setcookie('checkout_session', $session_id, time() + (24 * 60 * 60), '/');

        // Create new session
        $result = $checkout_manager->createCheckoutSession($session_id);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'session_id' => $session_id,
            'current_step' => 1,
            'message' => 'New checkout session created'
        ]);
        exit;
    }

    // Get existing session
    $session = $checkout_manager->getCheckoutSession($session_id);

    if (!$session) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Session not found']));
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'current_step' => (int)$session['current_step'],
        'address_id' => $session['selected_address_id'] ? (int)$session['selected_address_id'] : null,
        'shipping_method_id' => $session['selected_shipping_method_id'] ? (int)$session['selected_shipping_method_id'] : null,
        'created_at' => $session['created_at'],
        'expires_at' => $session['expires_at']
    ]);

} catch (Exception $e) {
    error_log("[Checkout Session API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to manage checkout session'
    ]);
}
?>
