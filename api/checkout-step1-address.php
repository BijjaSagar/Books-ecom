<?php
/**
 * Checkout Step 1: Address Endpoint
 * Validates and saves shipping address
 *
 * POST /api/checkout-step1-address.php
 * JSON: {
 *     "session_id": "...",
 *     "customer_id": 123,
 *     "first_name": "John",
 *     "last_name": "Doe",
 *     "email": "john@example.com",
 *     "phone": "+1234567890",
 *     "company": "Company Ltd",
 *     "address": "123 Main St",
 *     "address2": "Suite 100",
 *     "city": "New York",
 *     "state": "NY",
 *     "zip": "10001",
 *     "country": "US",
 *     "save_address": true
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CheckoutManager.php';
require_once __DIR__ . '/../includes/AddressValidator.php';

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
        die(json_encode(['success' => false, 'error' => 'Missing session_id or customer_id']));
    }

    // Initialize managers
    $checkout_manager = new CheckoutManager($conn);
    $address_validator = new AddressValidator($conn);

    // Prepare address data
    $address = [
        'first_name' => trim($data['first_name'] ?? ''),
        'last_name' => trim($data['last_name'] ?? ''),
        'email' => trim($data['email'] ?? ''),
        'phone' => trim($data['phone'] ?? ''),
        'company' => trim($data['company'] ?? ''),
        'address' => trim($data['address'] ?? ''),
        'address2' => trim($data['address2'] ?? ''),
        'city' => trim($data['city'] ?? ''),
        'state' => trim($data['state'] ?? ''),
        'zip' => trim($data['zip'] ?? ''),
        'country' => trim($data['country'] ?? '')
    ];

    // Validate address
    $validation = $address_validator->validateAddress($address);

    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $validation['errors']
        ]);
        exit;
    }

    // Save address if customer is logged in
    $address_id = null;
    if (!empty($data['customer_id']) && intval($data['customer_id']) > 0) {
        $save_result = $address_validator->saveAddress(intval($data['customer_id']), $address);
        if ($save_result['success']) {
            $address_id = $save_result['address_id'];
            if (!empty($data['save_address'])) {
                $address_validator->setDefaultAddress(intval($data['customer_id']), $address_id);
            }
        }
    }

    // Complete Step 1 in checkout
    $step_result = $checkout_manager->completeStep1_Address($data['session_id'], $address, $address_id);

    if (!$step_result['success']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $step_result['error'] ?? 'Failed to complete step 1'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'session_id' => $data['session_id'],
        'address_id' => $address_id,
        'current_step' => 2,
        'message' => 'Address validated and saved. Proceed to shipping selection.'
    ]);

} catch (Exception $e) {
    error_log("[Checkout Step 1 API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process address'
    ]);
}
?>
