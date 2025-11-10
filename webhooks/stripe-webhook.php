<?php
/**
 * Stripe Webhook Handler
 * Receives and processes events from Stripe
 *
 * POST /webhooks/stripe-webhook.php
 * Should be configured in Stripe Dashboard:
 * https://dashboard.stripe.com/webhooks
 *
 * Endpoint URL: https://yourdomain.com/webhooks/stripe-webhook.php
 * Events to listen for:
 * - payment_intent.succeeded
 * - payment_intent.payment_failed
 * - charge.refunded
 * - charge.dispute.created
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    // Get request body
    $payload = file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (!$sig_header) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing Stripe signature']));
    }

    // Initialize Stripe gateway to handle webhook
    require_once __DIR__ . '/../includes/payment_gateways/StripeGateway.php';

    // Use test mode (can be made dynamic based on signature validation)
    $stripe = new StripeGateway($conn, true);

    // Handle webhook
    $result = $stripe->handleWebhook($payload, $sig_header);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => $result['message'] ?? 'Webhook processed']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'error' => $result['error'] ?? 'Webhook processing failed']);
    }

} catch (Exception $e) {
    error_log("[Stripe Webhook] Error: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>
