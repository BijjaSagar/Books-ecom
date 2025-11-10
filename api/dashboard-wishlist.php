<?php
/**
 * Dashboard Wishlist Endpoint
 * Manages customer's wishlist
 *
 * GET /api/dashboard-wishlist.php?customer_id=123&limit=20&offset=0
 * POST /api/dashboard-wishlist.php - Add/Remove from wishlist
 * JSON: {"customer_id": 123, "product_id": 456, "action": "add|remove"}
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/DashboardManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $dashboard = new DashboardManager($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $customer_id = intval($_GET['customer_id'] ?? 0);

        if ($customer_id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
        }

        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);

        $result = $dashboard->getWishlist($customer_id, $limit, $offset);

        http_response_code(200);
        echo json_encode($result);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $customer_id = intval($data['customer_id'] ?? 0);
        $product_id = intval($data['product_id'] ?? 0);
        $action = strtolower($data['action'] ?? 'add');

        if ($customer_id <= 0 || $product_id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid customer_id or product_id']));
        }

        if ($action === 'add') {
            $result = $dashboard->addToWishlist($customer_id, $product_id);
        } elseif ($action === 'remove') {
            $wishlist_id = intval($data['wishlist_id'] ?? 0);
            if ($wishlist_id <= 0) {
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Invalid wishlist_id']));
            }
            $result = $dashboard->removeFromWishlist($customer_id, $wishlist_id);
        } else {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid action']));
        }

        if (!$result['success']) {
            http_response_code(400);
        } else {
            http_response_code(200);
        }

        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("[Dashboard Wishlist API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to manage wishlist']);
}
?>
