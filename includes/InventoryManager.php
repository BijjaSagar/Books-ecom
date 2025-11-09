<?php
/**
 * InventoryManager.php - Handle inventory, stock levels, and alerts
 */

class InventoryManager {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Get all products with stock levels
     */
    public function getInventoryList($limit = 50, $offset = 0, $filter = '') {
        try {
            $query = "
                SELECT
                    p.id,
                    p.name,
                    p.sku,
                    p.stock_quantity,
                    p.price,
                    c.name as category,
                    p.created_at,
                    CASE
                        WHEN p.stock_quantity = 0 THEN 'out_of_stock'
                        WHEN p.stock_quantity <= 10 THEN 'low_stock'
                        WHEN p.stock_quantity > 100 THEN 'overstock'
                        ELSE 'in_stock'
                    END as stock_status
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
            ";

            if ($filter === 'low_stock') {
                $query .= " WHERE p.stock_quantity <= 10 AND p.stock_quantity > 0";
            } elseif ($filter === 'out_of_stock') {
                $query .= " WHERE p.stock_quantity = 0";
            } elseif ($filter === 'overstock') {
                $query .= " WHERE p.stock_quantity > 100";
            }

            $query .= " ORDER BY p.name ASC LIMIT ? OFFSET ?";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching inventory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get inventory count
     */
    public function getInventoryCount($filter = '') {
        try {
            $query = "SELECT COUNT(id) as count FROM products";

            if ($filter === 'low_stock') {
                $query .= " WHERE stock_quantity <= 10 AND stock_quantity > 0";
            } elseif ($filter === 'out_of_stock') {
                $query .= " WHERE stock_quantity = 0";
            }

            $result = $this->conn->query($query);
            $row = $result->fetch_assoc();
            return $row['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error counting inventory: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get low stock alerts
     */
    public function getLowStockAlerts($limit = 20) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    p.id,
                    p.name,
                    p.sku,
                    p.stock_quantity,
                    ia.alert_type,
                    ia.created_at,
                    c.name as category
                FROM inventory_alerts ia
                JOIN products p ON ia.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE ia.status = 'active'
                ORDER BY ia.created_at DESC
                LIMIT ?
            ");

            $stmt->bind_param("i", $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching alerts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create inventory alert
     */
    public function createAlert($productId, $alertType, $currentStock, $threshold) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO inventory_alerts (product_id, alert_type, current_stock, threshold, status)
                VALUES (?, ?, ?, ?, 'active')
                ON DUPLICATE KEY UPDATE status = 'active'
            ");

            $stmt->bind_param("isii", $productId, $alertType, $currentStock, $threshold);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating alert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resolve alert
     */
    public function resolveAlert($alertId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE inventory_alerts
                SET status = 'resolved', resolved_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param("i", $alertId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error resolving alert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log inventory transaction
     */
    public function logTransaction($productId, $type, $quantity, $reason = '', $reference = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO inventory_transactions (product_id, transaction_type, quantity, reason, reference_id)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("isiss", $productId, $type, $quantity, $reason, $reference);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging transaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get inventory transaction history
     */
    public function getTransactionHistory($productId = null, $limit = 100) {
        try {
            if ($productId) {
                $stmt = $this->conn->prepare("
                    SELECT
                        it.id,
                        p.name as product_name,
                        it.transaction_type,
                        it.quantity,
                        it.reason,
                        it.created_at
                    FROM inventory_transactions it
                    JOIN products p ON it.product_id = p.id
                    WHERE it.product_id = ?
                    ORDER BY it.created_at DESC
                    LIMIT ?
                ");

                $stmt->bind_param("ii", $productId, $limit);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT
                        it.id,
                        p.name as product_name,
                        it.transaction_type,
                        it.quantity,
                        it.reason,
                        it.created_at
                    FROM inventory_transactions it
                    JOIN products p ON it.product_id = p.id
                    ORDER BY it.created_at DESC
                    LIMIT ?
                ");

                $stmt->bind_param("i", $limit);
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching transactions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get inventory summary
     */
    public function getInventorySummary() {
        try {
            $result = $this->conn->query("
                SELECT
                    COUNT(id) as total_products,
                    COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
                    COUNT(CASE WHEN stock_quantity <= 10 AND stock_quantity > 0 THEN 1 END) as low_stock,
                    SUM(stock_quantity) as total_units,
                    AVG(stock_quantity) as avg_stock,
                    COUNT(CASE WHEN stock_quantity > 100 THEN 1 END) as overstock
                FROM products
            ");

            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching inventory summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get reorder recommendations
     */
    public function getReorderRecommendations() {
        try {
            $result = $this->conn->query("
                SELECT
                    p.id,
                    p.name,
                    p.sku,
                    p.stock_quantity,
                    COUNT(oi.id) as avg_monthly_sales
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE p.stock_quantity < (COUNT(oi.id) * 1.5)
                GROUP BY p.id
                HAVING avg_monthly_sales > 0
                ORDER BY p.stock_quantity ASC
                LIMIT 20
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching reorder recommendations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update product stock
     */
    public function updateStock($productId, $quantity, $type = 'adjustment') {
        try {
            $this->logTransaction($productId, $type, $quantity);

            if ($type === 'sale') {
                $stmt = $this->conn->prepare("
                    UPDATE products
                    SET stock_quantity = stock_quantity - ?
                    WHERE id = ?
                ");
            } else {
                $stmt = $this->conn->prepare("
                    UPDATE products
                    SET stock_quantity = stock_quantity + ?
                    WHERE id = ?
                ");
            }

            $stmt->bind_param("ii", $quantity, $productId);
            $success = $stmt->execute();

            if ($success) {
                $this->checkAndCreateAlerts($productId);
            }

            return $success;
        } catch (Exception $e) {
            error_log("Error updating stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and create alerts for product
     */
    private function checkAndCreateAlerts($productId) {
        try {
            $stmt = $this->conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stock = $result['stock_quantity'];

            if ($stock == 0) {
                $this->createAlert($productId, 'out_of_stock', $stock, 0);
            } elseif ($stock <= 10) {
                $this->createAlert($productId, 'low_stock', $stock, 10);
            } elseif ($stock > 100) {
                $this->createAlert($productId, 'overstock', $stock, 100);
            }
        } catch (Exception $e) {
            error_log("Error checking alerts: " . $e->getMessage());
        }
    }
}
