<?php
/**
 * Get Reorder Recommendations API
 * GET /api/inventory/get-reorder-recommendations.php?limit=20
 *
 * Returns products that should be reordered based on reorder point settings
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

    if ($limit < 1 || $limit > 500) {
        $limit = 20;
    }

    $inventory = new InventoryManager($conn);

    // Get reorder recommendations
    $recommendations = $inventory->getReorderRecommendations($limit);

    // Calculate total reorder value
    $total_reorder_cost = 0;
    foreach ($recommendations as &$item) {
        $item['reorder_cost'] = (float)($item['reorder_quantity'] * $item['cost_price']);
        $total_reorder_cost += $item['reorder_cost'];
    }

    // Get supplier information if available
    $stmt = $conn->prepare("
        SELECT DISTINCT
            s.id,
            s.supplier_name,
            s.email,
            s.phone,
            s.lead_time_days
        FROM reorder_points rp
        JOIN suppliers s ON rp.supplier_id = s.id
        LIMIT 10
    ");
    $stmt->execute();
    $suppliers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'summary' => [
            'total_products' => count($recommendations),
            'total_units_to_order' => array_sum(array_column($recommendations, 'reorder_quantity')),
            'total_reorder_cost' => $total_reorder_cost,
            'average_lead_time_days' => round(array_sum(array_column($recommendations, 'lead_time_days', null)) / max(count($recommendations), 1))
        ],
        'suppliers' => $suppliers
    ]);

} catch (Exception $e) {
    error_log("[Reorder Recommendations API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch recommendations']);
}
?>
