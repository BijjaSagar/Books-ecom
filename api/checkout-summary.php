<?php
/**
 * Checkout Summary Endpoint
 * Returns complete order summary for review before payment
 *
 * GET /api/checkout-summary.php?session_id=...&customer_id=...
 * Returns: address, items, subtotal, tax, shipping, total
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CheckoutManager.php';
require_once __DIR__ . '/../includes/TaxCalculator.php';
require_once __DIR__ . '/../includes/CurrencyHelper.php';

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
    // Get parameters
    $session_id = $_GET['session_id'] ?? null;
    $customer_id = $_GET['customer_id'] ?? null;

    if (!$session_id) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Missing session_id']));
    }

    // Initialize managers
    $checkout_manager = new CheckoutManager($conn);
    $tax_calc = new TaxCalculator($conn);
    $currency_helper = new CurrencyHelper($conn);

    // Get checkout session
    $session = $checkout_manager->getCheckoutSession($session_id);

    if (!$session) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Session not found']));
    }

    // Get order summary from checkout manager
    $summary = $checkout_manager->getOrderSummary($session_id);

    if (!$summary['success']) {
        http_response_code(400);
        echo json_encode($summary);
        exit;
    }

    // Get customer's preferred currency
    $currency = 'USD';
    if (!empty($customer_id)) {
        $currency_result = $currency_helper->getCustomerPreferredCurrency(intval($customer_id));
        if ($currency_result) {
            $currency = $currency_result;
        }
    }

    // Get address details
    $address_data = [];
    if (!empty($session['selected_address_id'])) {
        $stmt = $conn->prepare("
            SELECT first_name, last_name, email, phone, company, address, address2,
                   city, state, zip, country
            FROM customer_addresses WHERE id = ?
        ");
        $stmt->bind_param("i", $session['selected_address_id']);
        $stmt->execute();
        $address_data = $stmt->get_result()->fetch_assoc();
    }

    // Build response
    $response = [
        'success' => true,
        'session_id' => $session_id,
        'current_step' => intval($session['current_step']),
        'currency' => $currency,
        'address' => $address_data ? [
            'full_name' => ($address_data['first_name'] ?? '') . ' ' . ($address_data['last_name'] ?? ''),
            'email' => $address_data['email'] ?? '',
            'phone' => $address_data['phone'] ?? '',
            'company' => $address_data['company'] ?? '',
            'street' => $address_data['address'] ?? '',
            'street2' => $address_data['address2'] ?? '',
            'city' => $address_data['city'] ?? '',
            'state' => $address_data['state'] ?? '',
            'zip' => $address_data['zip'] ?? '',
            'country' => $address_data['country'] ?? ''
        ] : null
    ];

    // Add summary data
    if (!empty($summary['summary'])) {
        $response['summary'] = $summary['summary'];
    }

    // Format all amounts in selected currency
    if (!empty($response['summary'])) {
        $response['summary']['subtotal_formatted'] = $currency_helper->formatAmount(
            $response['summary']['subtotal'] ?? 0,
            $currency,
            true
        );
        $response['summary']['tax_formatted'] = $currency_helper->formatAmount(
            $response['summary']['tax'] ?? 0,
            $currency,
            true
        );
        $response['summary']['shipping_formatted'] = $currency_helper->formatAmount(
            $response['summary']['shipping'] ?? 0,
            $currency,
            true
        );
        $response['summary']['total_formatted'] = $currency_helper->formatAmount(
            $response['summary']['total'] ?? 0,
            $currency,
            true
        );
    }

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("[Checkout Summary API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve order summary'
    ]);
}
?>
