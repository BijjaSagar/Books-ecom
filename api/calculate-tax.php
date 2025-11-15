<?php
/**
 * Calculate Tax API Endpoint
 * Returns tax calculation for given order
 *
 * POST /api/calculate-tax.php
 * JSON Body:
 * {
 *     "customer_id": int,
 *     "shipping_address": {
 *         "country": "US",
 *         "state_province": "CA",
 *         "postal_code": "94105",
 *         "city": "San Francisco"
 *     },
 *     "items": [
 *         { "product_id": 1, "quantity": 2, "price": 15.99, "type": "products" }
 *     ],
 *     "subtotal": 31.98
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/TaxCalculator.php';

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate input
    if (!$data || !isset($data['shipping_address']) || !isset($data['items'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing required fields']));
    }

    // Initialize tax calculator
    $tax_calc = new TaxCalculator($conn);

    // Calculate taxes
    $result = $tax_calc->calculateOrderTax($data);

    // Return result
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("[Tax API] Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Tax calculation failed',
        'message' => $e->getMessage()
    ]);
}
?>
