<?php
/**
 * Get Inventory List API
 * GET /api/inventory/get-inventory.php?limit=50&offset=0&filter=all
 *
 * Returns list of products with inventory details
 * Filters: all, low_stock, out_of_stock, overstock
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
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $filter = $_GET['filter'] ?? 'all';

    // Validate limit
    if ($limit < 1 || $limit > 500) {
        $limit = 50;
    }
    if ($offset < 0) {
        $offset = 0;
    }

    $inventory = new InventoryManager($conn);

    // Get inventory summary
    $summary = $inventory->getInventorySummary();

    // Get inventory items based on filter
    $items = [];
    if ($filter === 'low_stock') {
        $items = $inventory->getLowStockItems($limit);
    } elseif ($filter === 'out_of_stock') {
        $items = $inventory->getOutOfStockItems($limit);
    } else {
        // Get all inventory with pagination
        $stmt = $conn->prepare("
            SELECT
                pi.id,
                pi.product_id,
                p.title,
                pi.sku,
                pi.current_stock,
                pi.reserved_stock,
                pi.available_stock,
                pi.minimum_stock,
                pi.maximum_stock,
                pi.reorder_point,
                pi.cost_price,
                pi.selling_price,
                pi.last_restock_date,
                CASE
                    WHEN pi.current_stock = 0 THEN 'out_of_stock'
                    WHEN pi.current_stock <= pi.minimum_stock THEN 'low_stock'
                    WHEN pi.current_stock > pi.maximum_stock THEN 'overstock'
                    ELSE 'in_stock'
                END as stock_status
            FROM product_inventory pi
            JOIN products p ON pi.product_id = p.id
            ORDER BY p.title ASC
            LIMIT ? OFFSET ?
        ");

        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'items' => $items,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $summary['total_products'] ?? 0
        ]
    ]);

} catch (Exception $e) {
    error_log("[Inventory API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch inventory']);
}
?>
