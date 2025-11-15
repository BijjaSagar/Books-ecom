<?php
/**
 * Payment Processing Controller
 * Handles payment authorization and processing
 *
 * POST /checkout/process-payment.php
 * Expected JSON:
 * {
 *     "order_id": int,
 *     "payment_method_id": int,
 *     "payment_method_token": string,
 *     "amount": float,
 *     "currency": string,
 *     "customer_name": string,
 *     "customer_email": string
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../includes/config.php';

// Require Stripe PHP SDK
require_once __DIR__ . '/../vendor/autoload.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

// Verify authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

try {
    // Get request data
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);

    // Validate required fields
    $required_fields = ['order_id', 'payment_method_id', 'amount', 'currency'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // ============================================================
    // VALIDATE ORDER EXISTS AND BELONGS TO CUSTOMER
    // ============================================================

    $sql = "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status IN ('pending', 'payment_required')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param('ii', $data['order_id'], $_SESSION['user_id']);
    $stmt->execute();
    $order_result = $stmt->get_result();

    if ($order_result->num_rows === 0) {
        throw new Exception("Order not found or payment already processed");
    }

    $order = $order_result->fetch_assoc();
    $stmt->close();

    // Verify amount matches order total
    if (floatval($order['total']) !== floatval($data['amount'])) {
        throw new Exception("Amount mismatch: order total does not match payment amount");
    }

    // ============================================================
    // VALIDATE PAYMENT METHOD
    // ============================================================

    $sql = "SELECT * FROM payment_methods WHERE id = ? AND is_active = TRUE";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param('i', $data['payment_method_id']);
    $stmt->execute();
    $method_result = $stmt->get_result();

    if ($method_result->num_rows === 0) {
        throw new Exception("Invalid or inactive payment method");
    }

    $payment_method = $method_result->fetch_assoc();
    $stmt->close();

    // ============================================================
    // ROUTE TO APPROPRIATE PAYMENT GATEWAY
    // ============================================================

    $gateway_name = strtolower($payment_method['gateway_name']);

    switch ($gateway_name) {
        case 'stripe':
            $response = processStripePayment($conn, $data, $order, $_SESSION['user_id']);
            break;
        case 'paypal':
            $response = processPayPalPayment($conn, $data, $order, $_SESSION['user_id']);
            break;
        case 'razorpay':
            $response = processRazorpayPayment($conn, $data, $order, $_SESSION['user_id']);
            break;
        default:
            throw new Exception("Payment gateway not yet implemented: $gateway_name");
    }

    // ============================================================
    // RETURN RESPONSE
    // ============================================================

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    // Log error
    error_log("[Payment Processing] Error: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'payment_error'
    ]);
}

// ============================================================
// STRIPE PAYMENT HANDLER
// ============================================================

function processStripePayment($conn, $data, $order, $user_id) {
    // Include Stripe gateway class
    require_once __DIR__ . '/../includes/payment_gateways/StripeGateway.php';

    // Initialize Stripe gateway
    $stripe = new StripeGateway($conn, true); // true = test mode

    // Prepare payment data
    $payment_data = [
        'order_id' => $data['order_id'],
        'customer_id' => $user_id,
        'amount' => intval($data['amount'] * 100), // Convert to cents
        'currency' => strtolower($data['currency']),
        'payment_method_token' => $data['payment_method_token'] ?? null,
        'customer_email' => $data['customer_email'] ?? $_SESSION['user_email'],
        'customer_name' => $data['customer_name'] ?? $_SESSION['user_name'],
        'description' => "Order #" . $data['order_id'] . " - Books Ecommerce",
        'return_url' => $data['return_url'] ?? $_SERVER['HTTP_ORIGIN'] . '/checkout/success.php'
    ];

    // Create payment intent
    $result = $stripe->createPaymentIntent($payment_data);

    if (!$result['success']) {
        return [
            'success' => false,
            'error' => $result['error'],
            'error_code' => $result['error_code'] ?? 'payment_failed'
        ];
    }

    // Update order status to processing
    $sql = "UPDATE orders SET status = 'processing', payment_method_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $data['payment_method_id'], $data['order_id']);
    $stmt->execute();
    $stmt->close();

    return [
        'success' => true,
        'client_secret' => $result['client_secret'],
        'payment_intent_id' => $result['payment_intent_id'],
        'requires_action' => $result['requires_action'],
        'next_action' => $result['requires_action'] ? 'confirm_3d' : 'complete'
    ];
}

// ============================================================
// PAYPAL PAYMENT HANDLER (Skeleton)
// ============================================================

function processPayPalPayment($conn, $data, $order, $user_id) {
    // TODO: Implement PayPal integration
    // Steps:
    // 1. Get PayPal API credentials from gateway_keys
    // 2. Create payment order via PayPal API
    // 3. Return approval URL for redirect
    // 4. Validate returned token
    // 5. Capture payment

    return [
        'success' => false,
        'error' => 'PayPal payment processing not yet implemented',
        'error_code' => 'gateway_unavailable'
    ];
}

// ============================================================
// RAZORPAY PAYMENT HANDLER (Skeleton)
// ============================================================

function processRazorpayPayment($conn, $data, $order, $user_id) {
    // TODO: Implement Razorpay integration
    // Steps:
    // 1. Get Razorpay API credentials from gateway_keys
    // 2. Create Razorpay order
    // 3. Return order ID for frontend checkout
    // 4. Verify payment after frontend confirmation

    return [
        'success' => false,
        'error' => 'Razorpay payment processing not yet implemented',
        'error_code' => 'gateway_unavailable'
    ];
}
?>
