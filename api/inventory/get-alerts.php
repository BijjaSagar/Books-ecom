<?php
/**
 * Get Stock Alerts API
 * GET /api/inventory/get-alerts.php?limit=20&filter=active
 *
 * Returns active stock alerts (low stock, out of stock, overstock)
 * Filters: active, acknowledged, all
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
    $limit = (int)($_GET['limit'] ?? 20);
    $filter = $_GET['filter'] ?? 'active';

    if ($limit < 1 || $limit > 500) {
        $limit = 20;
    }

    $inventory = new InventoryManager($conn);

    if ($filter === 'acknowledged') {
        // Get acknowledged alerts
        $result = $conn->query("
            SELECT
                sa.id,
                p.id as product_id,
                p.title,
                pi.sku,
                sa.alert_type,
                sa.current_stock,
                sa.threshold,
                sa.created_at,
                u.name as acknowledged_by_name,
                sa.acknowledged_at
            FROM stock_alerts sa
            JOIN products p ON sa.product_id = p.id
            JOIN product_inventory pi ON p.id = pi.product_id
            LEFT JOIN users u ON sa.acknowledged_by = u.id
            WHERE sa.acknowledged_by IS NOT NULL
            ORDER BY sa.acknowledged_at DESC
            LIMIT $limit
        ");
    } else {
        // Get active (unacknowledged) alerts
        $result = $conn->query("
            SELECT
                sa.id,
                p.id as product_id,
                p.title,
                pi.sku,
                sa.alert_type,
                sa.current_stock,
                sa.threshold,
                sa.created_at,
                'unacknowledged' as status
            FROM stock_alerts sa
            JOIN products p ON sa.product_id = p.id
            JOIN product_inventory pi ON p.id = pi.product_id
            WHERE sa.is_active = TRUE AND sa.acknowledged_by IS NULL
            ORDER BY
                CASE sa.alert_type
                    WHEN 'out_of_stock' THEN 1
                    WHEN 'low_stock' THEN 2
                    WHEN 'overstock' THEN 3
                END,
                sa.created_at DESC
            LIMIT $limit
        ");
    }

    $alerts = $result->fetch_all(MYSQLI_ASSOC);

    // Get alert statistics
    $stats = $conn->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN alert_type = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_count,
            SUM(CASE WHEN alert_type = 'low_stock' THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN alert_type = 'overstock' THEN 1 ELSE 0 END) as overstock_count,
            SUM(CASE WHEN acknowledged_by IS NULL THEN 1 ELSE 0 END) as unacknowledged_count
        FROM stock_alerts
        WHERE is_active = TRUE
    ");

    $alert_stats = $stats->fetch_assoc();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'statistics' => $alert_stats,
        'total_alerts' => count($alerts)
    ]);

} catch (Exception $e) {
    error_log("[Get Alerts API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch alerts']);
}
?>
