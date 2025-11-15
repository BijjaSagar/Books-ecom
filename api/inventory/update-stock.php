<?php
/**
 * Update Stock API
 * POST /api/inventory/update-stock.php
 *
 * Request body (JSON):
 * {
 *     "product_id": 123,
 *     "quantity_change": 10,
 *     "transaction_type": "restock",
 *     "reason": "Restock from supplier",
 *     "reference_id": null,
 *     "reference_type": "manual",
 *     "notes": "Order #12345 received"
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required fields
    $product_id = (int)($data['product_id'] ?? 0);
    $quantity_change = (int)($data['quantity_change'] ?? 0);
    $transaction_type = $data['transaction_type'] ?? 'adjustment';
    $reason = $data['reason'] ?? '';
    $reference_id = isset($data['reference_id']) ? (int)$data['reference_id'] : null;
    $reference_type = $data['reference_type'] ?? 'manual';
    $notes = $data['notes'] ?? '';
    $performed_by = $_SESSION['user_id'];

    if ($product_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'product_id required']));
    }

    if ($quantity_change == 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'quantity_change must be non-zero']));
    }

    // Validate transaction type
    $valid_types = ['purchase', 'sale', 'adjustment', 'return', 'damage', 'restock', 'count'];
    if (!in_array($transaction_type, $valid_types)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid transaction_type']));
    }

    $inventory = new InventoryManager($conn);

    // Get current inventory before update
    $before = $inventory->getProductInventory($product_id);
    if (!$before) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Product inventory not found']));
    }

    // Update stock
    $success = $inventory->updateStock(
        $product_id,
        $quantity_change,
        $transaction_type,
        $reason,
        $reference_id,
        $reference_type,
        $performed_by,
        $notes
    );

    if (!$success) {
        http_response_code(500);
        die(json_encode(['success' => false, 'error' => 'Failed to update stock']));
    }

    // Get updated inventory
    $after = $inventory->getProductInventory($product_id);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'before' => [
            'current_stock' => $before['current_stock'],
            'reserved_stock' => $before['reserved_stock'],
            'available_stock' => $before['available_stock']
        ],
        'after' => [
            'current_stock' => $after['current_stock'],
            'reserved_stock' => $after['reserved_stock'],
            'available_stock' => $after['available_stock']
        ]
    ]);

} catch (Exception $e) {
    error_log("[Update Stock API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Stock update failed']);
}
?>
