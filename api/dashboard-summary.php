<?php
/**
 * Dashboard Summary Endpoint
 * Returns customer's complete dashboard overview
 *
 * GET /api/dashboard-summary.php?customer_id=123
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

    if ($customer_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
    }

    $dashboard = new DashboardManager($conn);

    // Get summary
    $summary = $dashboard->getCustomerSummary($customer_id);

    if (!$summary['success']) {
        http_response_code(404);
        echo json_encode($summary);
        exit;
    }

    // Get recent orders
    $orders = $dashboard->getOrderHistory($customer_id, 5, 0);
    $wishlist = $dashboard->getWishlist($customer_id, 5, 0);
    $tickets = $dashboard->getSupportTickets($customer_id, 5, 0, 'open');

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'customer' => $summary['customer'],
        'recent_orders' => $orders['orders'] ?? [],
        'recent_wishlist' => $wishlist['items'] ?? [],
        'open_tickets' => $tickets['tickets'] ?? []
    ]);

} catch (Exception $e) {
    error_log("[Dashboard Summary API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load dashboard']);
}
?>
