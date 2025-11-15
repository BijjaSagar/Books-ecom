<?php
/**
 * Dashboard Orders Endpoint
 * Returns customer's order history
 *
 * GET /api/dashboard-orders.php?customer_id=123&limit=10&offset=0
 * GET /api/dashboard-orders.php?customer_id=123&order_id=456 (for details)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/DashboardManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $customer_id = intval($_GET['customer_id'] ?? 0);
    $order_id = intval($_GET['order_id'] ?? 0);

    if ($customer_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
    }

    $dashboard = new DashboardManager($conn);

    if ($order_id > 0) {
        // Get specific order details
        $result = $dashboard->getOrderDetails($customer_id, $order_id);
    } else {
        // Get order history
        $limit = intval($_GET['limit'] ?? 10);
        $offset = intval($_GET['offset'] ?? 0);

        $limit = min($limit, 100); // Max 100 per page
        $offset = max($offset, 0);

        $result = $dashboard->getOrderHistory($customer_id, $limit, $offset);
    }

    if (!$result['success']) {
        http_response_code(404);
        echo json_encode($result);
        exit;
    }

    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("[Dashboard Orders API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load orders']);
}
?>
