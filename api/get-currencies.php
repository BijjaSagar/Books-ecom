<?php
/**
 * Get Currencies Endpoint
 * Returns list of all active currencies with current rates
 *
 * GET /api/get-currencies.php
 * Optional query params:
 *   - format=json (default) or format=html
 *   - include_rates=true|false
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';
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
    // Initialize currency helper
    $currency_helper = new CurrencyHelper($conn);

    // Get all active currencies
    $currencies = $currency_helper->getAllActiveCurrencies();

    // Get query parameters
    $include_rates = isset($_GET['include_rates'])
        ? $_GET['include_rates'] === 'true'
        : true;

    $format = $_GET['format'] ?? 'json';

    // Prepare response
    $response = [
        'success' => true,
        'currencies' => []
    ];

    foreach ($currencies as $currency) {
        $currency_data = [
            'id' => (int)$currency['id'],
            'code' => $currency['currency_code'],
            'name' => $currency['currency_name'],
            'symbol' => $currency['currency_symbol'],
            'symbol_position' => $currency['symbol_position'],
            'decimal_places' => (int)$currency['decimal_places'],
            'is_default' => (bool)$currency['is_default'],
            'display_order' => (int)$currency['display_order']
        ];

        if ($include_rates) {
            $currency_data['exchange_rate_to_usd'] = floatval($currency['exchange_rate_to_usd']);
            $currency_data['last_rate_update'] = $currency['last_rate_update'] ?? 'Never';
        }

        $response['currencies'][] = $currency_data;
    }

    // Add metadata
    $summary = $currency_helper->getCurrencySummary();
    if ($summary) {
        $response['metadata'] = [
            'total_currencies' => (int)$summary['total_currencies'],
            'active_currencies' => (int)$summary['active_currencies'],
            'last_rate_update' => $summary['last_rate_update'] ?? 'Never'
        ];
    }

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("[Get Currencies API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve currencies'
    ]);
}
?>
