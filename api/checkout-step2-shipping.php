<?php
/**
 * Checkout Step 2: Shipping Method Endpoint
 * Calculates and returns available shipping methods
 *
 * POST /api/checkout-step2-shipping.php
 * JSON: {
 *     "session_id": "...",
 *     "address_id": 456,
 *     "cart_items": [
 *         {"product_id": 1, "quantity": 2, "weight": 0.5},
 *         {"product_id": 2, "quantity": 1, "weight": 0.3}
 *     ],
 *     "subtotal": 100.00
 * }
 *
 * Returns: available_methods with costs and delivery times
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CheckoutManager.php';
require_once __DIR__ . '/../includes/ShippingCalculator.php';

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
    if (!isset($data['session_id']) || !isset($data['address_id'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Missing session_id or address_id']));
    }

    // Initialize managers
    $checkout_manager = new CheckoutManager($conn);
    $shipping_calc = new ShippingCalculator($conn);

    // Get address details
    $stmt = $conn->prepare("SELECT city, state, zip, country FROM customer_addresses WHERE id = ?");
    $stmt->bind_param("i", $data['address_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc();

    if (!$address) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Address not found']));
    }

    // Prepare shipping data
    $shipping_data = [
        'destination_country' => $address['country'],
        'destination_state' => $address['state'],
        'destination_zip' => $address['zip'],
        'cart_items' => $data['cart_items'] ?? [],
        'subtotal' => floatval($data['subtotal'] ?? 0)
    ];

    // Get available shipping methods
    $methods = $shipping_calc->getAvailableShippingMethods($shipping_data);

    if (!$methods['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $methods['error'] ?? 'Failed to calculate shipping'
        ]);
        exit;
    }

    // Complete Step 2 in checkout
    $step_result = $checkout_manager->completeStep2_Shipping(
        $data['session_id'],
        $data['address_id'],
        $methods['methods'] ?? []
    );

    if (!$step_result['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $step_result['error'] ?? 'Failed to complete step 2'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'session_id' => $data['session_id'],
        'shipping_zone' => $methods['zone_name'] ?? 'Unknown',
        'available_methods' => $methods['methods'] ?? [],
        'default_method' => $methods['default_method'] ?? null,
        'current_step' => 2,
        'message' => 'Shipping options calculated. Select preferred method to proceed.'
    ]);

} catch (Exception $e) {
    error_log("[Checkout Step 2 API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to calculate shipping'
    ]);
}
?>
