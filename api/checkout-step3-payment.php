<?php
/**
 * Checkout Step 3: Payment Processing Endpoint
 * Processes payment and creates order
 *
 * POST /api/checkout-step3-payment.php
 * JSON: {
 *     "session_id": "...",
 *     "customer_id": 123,
 *     "address_id": 456,
 *     "shipping_method_id": 2,
 *     "payment_method": "stripe",
 *     "payment_token": "tok_xxx",
 *     "cart_id": 789,
 *     "currency_code": "USD"
 * }
 *
 * Returns: order_id, order_number, total, status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CheckoutManager.php';
require_once __DIR__ . '/../includes/PaymentGateway.php';
require_once __DIR__ . '/../includes/TaxCalculator.php';
require_once __DIR__ . '/../includes/CurrencyHelper.php';

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required fields
    if (!isset($data['session_id']) || !isset($data['customer_id'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Missing required fields']));
    }

    // Initialize managers
    $checkout_manager = new CheckoutManager($conn);
    $payment_gateway = new PaymentGateway($conn);
    $tax_calc = new TaxCalculator($conn);
    $currency_helper = new CurrencyHelper($conn);

    // Get checkout session
    $session = $checkout_manager->getCheckoutSession($data['session_id']);
    if (!$session || $session['current_step'] < 2) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid checkout state']));
    }

    // Get address and cart details
    $stmt = $conn->prepare("
        SELECT city, state, country FROM customer_addresses WHERE id = ?
    ");
    $stmt->bind_param("i", $data['address_id']);
    $stmt->execute();
    $address = $stmt->get_result()->fetch_assoc();

    // Get cart items and calculate subtotal
    $stmt = $conn->prepare("
        SELECT ci.id, ci.product_id, ci.quantity, ci.price, p.weight
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $data['cart_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $subtotal = 0;

    while ($item = $result->fetch_assoc()) {
        $item_total = floatval($item['price']) * intval($item['quantity']);
        $subtotal += $item_total;
        $cart_items[] = $item;
    }

    // Get shipping cost
    $stmt = $conn->prepare("
        SELECT sm.cost FROM shipping_methods sm WHERE sm.id = ?
    ");
    $stmt->bind_param("i", $data['shipping_method_id']);
    $stmt->execute();
    $shipping_result = $stmt->get_result()->fetch_assoc();
    $shipping_cost = floatval($shipping_result['cost'] ?? 0);

    // Calculate tax
    $tax_data = $tax_calc->calculateOrderTax([
        'items' => $cart_items,
        'subtotal' => $subtotal,
        'destination_country' => $address['country'],
        'destination_state' => $address['state']
    ]);

    $tax_amount = floatval($tax_data['total_tax'] ?? 0);

    // Calculate final total
    $total = $subtotal + $tax_amount + $shipping_cost;

    // Process payment
    $payment_data = [
        'amount' => $total,
        'currency' => strtoupper($data['currency_code'] ?? 'USD'),
        'payment_method' => $data['payment_method'] ?? 'stripe',
        'token' => $data['payment_token'] ?? '',
        'customer_id' => intval($data['customer_id']),
        'description' => 'Books eCommerce Order'
    ];

    $payment_result = $payment_gateway->createPaymentIntent($payment_data);

    if (!$payment_result['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $payment_result['error'] ?? 'Payment processing failed'
        ]);
        exit;
    }

    // Create order
    $order_number = $checkout_manager->generateOrderNumber();

    $stmt = $conn->prepare("
        INSERT INTO orders (
            customer_id, order_number, total, subtotal, tax_amount, shipping_cost,
            shipping_address_id, status, payment_status, payment_method, currency,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $order_status = 'pending';
    $payment_status = 'completed';

    $stmt->bind_param(
        "isdddisisss",
        $data['customer_id'],
        $order_number,
        $total,
        $subtotal,
        $tax_amount,
        $shipping_cost,
        $data['address_id'],
        $order_status,
        $payment_status,
        $payment_data['payment_method'],
        $payment_data['currency']
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }

    $order_id = $stmt->insert_id;

    // Copy cart items to order_items
    foreach ($cart_items as $item) {
        $insert_stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        $insert_stmt->bind_param(
            "iid",
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        );

        if (!$insert_stmt->execute()) {
            error_log("Warning: Failed to add item to order");
        }
    }

    // Mark cart as converted
    $cart_stmt = $conn->prepare("UPDATE shopping_carts SET status = 'converted' WHERE id = ?");
    $cart_stmt->bind_param("i", $data['cart_id']);
    $cart_stmt->execute();

    // Save tax calculation
    $tax_calc->saveTaxCalculation($order_id, $tax_data);

    // Save shipping to order
    $shipping_stmt = $conn->prepare("
        INSERT INTO order_shipping_details (order_id, shipping_method_id, cost)
        VALUES (?, ?, ?)
    ");
    $shipping_stmt->bind_param("iid", $order_id, $data['shipping_method_id'], $shipping_cost);
    $shipping_stmt->execute();

    // Complete Step 3 in checkout
    $checkout_manager->completeStep3_Payment($data['session_id'], $order_id);

    // Format currency for response
    $formatted_total = $currency_helper->formatAmount($total, $payment_data['currency'], true);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'session_id' => $data['session_id'],
        'order_id' => (int)$order_id,
        'order_number' => $order_number,
        'subtotal' => $subtotal,
        'tax' => $tax_amount,
        'shipping' => $shipping_cost,
        'total' => $total,
        'formatted_total' => $formatted_total,
        'currency' => $payment_data['currency'],
        'status' => $order_status,
        'payment_status' => $payment_status,
        'message' => 'Payment processed successfully. Order created.',
        'next_url' => '/order-confirmation.php?order_id=' . $order_id
    ]);

} catch (Exception $e) {
    error_log("[Checkout Step 3 API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Payment processing failed. Please try again.'
    ]);
}
?>
