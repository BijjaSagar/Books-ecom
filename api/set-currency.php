<?php
/**
 * Set Currency Preference Endpoint
 * Sets customer's preferred currency in session
 *
 * POST /api/set-currency.php
 * JSON: { "currency_code": "EUR" }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
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

    if (!isset($data['currency_code'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Missing currency_code']));
    }

    $currency_code = strtoupper(trim($data['currency_code']));

    // Initialize currency helper
    $currency_helper = new CurrencyHelper($conn);

    // Validate currency code
    $currency = $currency_helper->getCurrencyByCode($currency_code);
    if (!$currency) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid currency code']));
    }

    // Set in session
    $_SESSION['currency'] = $currency_code;

    // If logged in, save to database
    if (isset($_SESSION['user_id'])) {
        $result = $currency_helper->setCustomerPreferredCurrency($_SESSION['user_id'], $currency_code);
        if (!$result['success']) {
            // Log but don't fail - still set in session
            error_log("Failed to save currency preference: " . $result['error']);
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'currency_code' => $currency_code,
        'currency_name' => $currency['currency_name'],
        'message' => 'Currency preference saved'
    ]);

} catch (Exception $e) {
    error_log("[Set Currency API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to set currency'
    ]);
}
?>
