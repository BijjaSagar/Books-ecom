<?php
/**
 * Payment Methods API Endpoint
 * Returns list of available payment methods
 *
 * GET /api/payment-methods.php
 * Returns JSON array of available payment methods
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get all active payment methods
    $sql = "SELECT
                id,
                gateway_name,
                display_name,
                description,
                gateway_type,
                icon_url,
                position,
                supports_refund,
                currencies_supported,
                countries_supported
            FROM payment_methods
            WHERE is_active = TRUE
            ORDER BY position ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }

    $methods = [];

    while ($row = $result->fetch_assoc()) {
        // Parse JSON fields
        $currencies = json_decode($row['currencies_supported'], true) ?? [];
        $countries = json_decode($row['countries_supported'], true) ?? [];

        // Check if payment method is properly configured
        $is_configured = isPaymentMethodConfigured($conn, $row['id']);

        // Only include if configured or in test mode
        if ($is_configured) {
            $methods[] = [
                'id' => (int)$row['id'],
                'gateway_name' => $row['gateway_name'],
                'display_name' => $row['display_name'],
                'description' => $row['description'],
                'gateway_type' => $row['gateway_type'],
                'icon_url' => $row['icon_url'],
                'supports_refund' => (bool)$row['supports_refund'],
                'currencies' => $currencies,
                'countries' => $countries
            ];
        }
    }

    http_response_code(200);
    echo json_encode($methods);

} catch (Exception $e) {
    error_log("[Payment Methods API] Error: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'error' => 'Failed to retrieve payment methods',
        'message' => $e->getMessage()
    ]);
}

/**
 * Check if payment method has required configuration
 */
function isPaymentMethodConfigured($conn, $payment_method_id) {
    $sql = "SELECT COUNT(*) as count FROM gateway_keys
            WHERE payment_method_id = ? AND is_active = TRUE";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $payment_method_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['count'] > 0;
}
?>
