<?php
/**
 * Get Inventory Summary API
 * GET /api/inventory/get-summary.php
 *
 * Returns high-level inventory metrics and statistics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/InventoryManager.php';

// Check admin auth
if (($_SESSION['user_id'] ?? null) === null || ($_SESSION['is_admin'] ?? false) === false) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $inventory = new InventoryManager($conn);

    // Get summary
    $summary = $inventory->getInventorySummary();

    // Get inventory value
    $value = $inventory->getInventoryValue();

    // Get alerts statistics
    $alerts_result = $conn->query("
        SELECT
            COUNT(*) as total_alerts,
            SUM(CASE WHEN alert_type = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_alerts,
            SUM(CASE WHEN alert_type = 'low_stock' THEN 1 ELSE 0 END) as low_stock_alerts,
            SUM(CASE WHEN alert_type = 'overstock' THEN 1 ELSE 0 END) as overstock_alerts,
            SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_alerts
        FROM stock_alerts
    ");
    $alert_stats = $alerts_result->fetch_assoc();

    // Get warehouse statistics
    $warehouse_result = $conn->query("
        SELECT
            COUNT(DISTINCT w.id) as total_warehouses,
            SUM(wi.quantity_on_hand) as total_warehouse_stock,
            SUM(wi.quantity_reserved) as total_reserved,
            SUM(wi.quantity_damaged) as total_damaged
        FROM warehouses w
        LEFT JOIN warehouse_inventory wi ON w.id = wi.warehouse_id
    ");
    $warehouse_stats = $warehouse_result->fetch_assoc();

    // Get transaction statistics (last 30 days)
    $transactions_result = $conn->query("
        SELECT
            COUNT(*) as total_transactions,
            SUM(CASE WHEN transaction_type = 'sale' THEN quantity_change ELSE 0 END) as units_sold,
            SUM(CASE WHEN transaction_type = 'restock' OR transaction_type = 'purchase' THEN quantity_change ELSE 0 END) as units_restocked,
            SUM(CASE WHEN transaction_type = 'return' THEN quantity_change ELSE 0 END) as units_returned
        FROM inventory_transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $transaction_stats = $transactions_result->fetch_assoc();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'inventory' => [
            'total_products' => (int)($summary['total_products'] ?? 0),
            'total_units' => (int)($summary['total_units'] ?? 0),
            'avg_units_per_product' => (float)($summary['avg_units_per_product'] ?? 0),
            'out_of_stock' => (int)($summary['out_of_stock'] ?? 0),
            'low_stock' => (int)($summary['low_stock'] ?? 0),
            'overstock' => (int)($summary['overstock'] ?? 0)
        ],
        'value' => [
            'total_inventory_cost' => (float)($value['total_inventory_cost'] ?? 0),
            'total_inventory_value' => (float)($value['total_inventory_value'] ?? 0),
            'avg_markup' => (float)($value['avg_markup'] ?? 0)
        ],
        'alerts' => [
            'total' => (int)($alert_stats['total_alerts'] ?? 0),
            'out_of_stock' => (int)($alert_stats['out_of_stock_alerts'] ?? 0),
            'low_stock' => (int)($alert_stats['low_stock_alerts'] ?? 0),
            'overstock' => (int)($alert_stats['overstock_alerts'] ?? 0),
            'active' => (int)($alert_stats['active_alerts'] ?? 0)
        ],
        'warehouses' => [
            'total' => (int)($warehouse_stats['total_warehouses'] ?? 0),
            'total_stock' => (int)($warehouse_stats['total_warehouse_stock'] ?? 0),
            'total_reserved' => (int)($warehouse_stats['total_reserved'] ?? 0),
            'total_damaged' => (int)($warehouse_stats['total_damaged'] ?? 0)
        ],
        'transactions_30d' => [
            'total' => (int)($transaction_stats['total_transactions'] ?? 0),
            'units_sold' => (int)($transaction_stats['units_sold'] ?? 0),
            'units_restocked' => (int)($transaction_stats['units_restocked'] ?? 0),
            'units_returned' => (int)($transaction_stats['units_returned'] ?? 0)
        ]
    ]);

} catch (Exception $e) {
    error_log("[Get Summary API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch summary']);
}
?>
