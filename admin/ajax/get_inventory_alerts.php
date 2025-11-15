<?php
/**
 * admin/ajax/get_inventory_alerts.php - Real-time inventory alerts AJAX endpoint
 */

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once '../../includes/db_connect.php';
require_once '../../includes/InventoryManager.php';

$inventory = new InventoryManager($conn);

try {
    $alerts = $inventory->getLowStockAlerts(20);
    $summary = $inventory->getInventorySummary();

    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'summary' => $summary,
        'alert_count' => count($alerts),
        'last_updated' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
