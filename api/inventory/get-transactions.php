<?php
/**
 * Get Transaction History API
 * GET /api/inventory/get-transactions.php?product_id=123&limit=50&offset=0
 *
 * Returns inventory transaction history for audit trail
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
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    if ($limit < 1 || $limit > 500) {
        $limit = 50;
    }
    if ($offset < 0) {
        $offset = 0;
    }

    $inventory = new InventoryManager($conn);

    // Get transactions
    $transactions = $inventory->getTransactions($product_id, $limit, $offset);

    // Get total transaction count
    if ($product_id) {
        $count_stmt = $conn->prepare("
            SELECT COUNT(*) as total FROM inventory_transactions WHERE product_id = ?
        ");
        $count_stmt->bind_param("i", $product_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
    } else {
        $count_result = $conn->query("SELECT COUNT(*) as total FROM inventory_transactions")->fetch_assoc();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $count_result['total']
        ],
        'transaction_types' => [
            'purchase' => 'Purchase from supplier',
            'sale' => 'Sale to customer',
            'adjustment' => 'Manual adjustment',
            'return' => 'Customer return',
            'damage' => 'Damaged/spoiled',
            'restock' => 'Restock received',
            'count' => 'Physical count'
        ]
    ]);

} catch (Exception $e) {
    error_log("[Get Transactions API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch transactions']);
}
?>
