<?php
// create-paypal-order.php - Create PayPal order via AJAX
header('Content-Type: application/json');
session_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/payment_gateways/PaymentProcessor.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $order_number = $input['order_number'] ?? '';
    $amount = $input['amount'] ?? 0;
    
    // Validate input
    if (empty($order_number) || $amount <= 0) {
        throw new Exception('Invalid order data');
    }
    
    // Get order details from database
    $order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
    $order_stmt->bind_param("s", $order_number);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $order = $order_result->fetch_assoc();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Initialize payment processor
    $payment_processor = new PaymentProcessor();
    $checkout_config = $payment_processor->getCheckoutConfig('paypal', [
        'order_number' => $order_number,
        'customer_email' => $order['customer_email'],
        'first_name' => $order['first_name'],
        'last_name' => $order['last_name'],
        'phone' => $order['phone'],
        'total_amount' => $amount
    ]);
    
    // Create PayPal order
    $paypal_gateway = new PayPalGateway(
        $checkout_config['client_id'],
        $_SESSION['settings']['paypal_secret'] ?? '', // This would need to be retrieved properly
        true // Sandbox mode
    );
    
    $paypal_order = $paypal_gateway->createOrder([
        'amount' => $amount,
        'currency' => 'USD',
        'reference_id' => $order_number,
        'description' => 'Order #' . $order_number,
        'brand_name' => $_SESSION['settings']['site_name'] ?? 'Bookstore',
        'return_url' => 'https://yoursite.com/verify-payment.php?method=paypal&order=' . urlencode($order_number),
        'cancel_url' => 'https://yoursite.com/cart.php'
    ]);
    
    // Return order ID
    echo json_encode([
        'id' => $paypal_order['id'],
        'status' => $paypal_order['status']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}