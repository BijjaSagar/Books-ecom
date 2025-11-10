<?php
/**
 * Convert Currency Endpoint
 * Converts amount from one currency to another
 *
 * POST /api/convert-currency.php
 * JSON: {
 *     "amount": 100,
 *     "from_currency": "USD",
 *     "to_currency": "EUR",
 *     "include_markup": true
 * }
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

    // Validate required fields
    if (!isset($data['amount']) || !isset($data['from_currency']) || !isset($data['to_currency'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Missing required fields']));
    }

    // Initialize currency helper
    $currency_helper = new CurrencyHelper($conn);

    // Convert currency
    $result = $currency_helper->convertCurrency(
        floatval($data['amount']),
        strtoupper($data['from_currency']),
        strtoupper($data['to_currency']),
        $data['include_markup'] ?? true
    );

    if (!$result['success']) {
        http_response_code(400);
        echo json_encode($result);
        exit;
    }

    // Format the output
    $to_currency = $currency_helper->getCurrencyByCode($result['to_currency']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'original_amount' => $result['original_amount'],
        'original_currency' => $result['from_currency'],
        'converted_amount' => $result['converted_amount'],
        'converted_currency' => $result['to_currency'],
        'conversion_rate' => $result['rate'],
        'conversion_fee' => $result['fee'],
        'formatted_amount' => $currency_helper->formatAmount(
            $result['converted_amount'],
            $result['to_currency'],
            true
        ),
        'timestamp' => $result['timestamp']
    ]);

} catch (Exception $e) {
    error_log("[Convert Currency API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Currency conversion failed'
    ]);
}
?>
