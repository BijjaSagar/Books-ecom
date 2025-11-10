<?php
/**
 * Dashboard Downloads Endpoint
 * Returns customer's digital product downloads
 *
 * GET /api/dashboard-downloads.php?customer_id=123&limit=20&offset=0
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

    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);

    $limit = min($limit, 100);
    $offset = max($offset, 0);

    $result = $dashboard->getDownloads($customer_id, $limit, $offset);

    http_response_code(200);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("[Dashboard Downloads API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load downloads']);
}
?>
