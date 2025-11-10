<?php
/**
 * InventoryManager.php - Complete Inventory Management System
 * Phase 2, Task 1: Comprehensive inventory tracking, stock management, and forecasting
 * Created: November 10, 2025
 */

class InventoryManager {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Get product inventory details
     */
    public function getProductInventory($product_id) {
        try {
            $stmt = $this->conn->prepare("
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
                    pi.reorder_quantity,
                    pi.cost_price,
                    pi.selling_price,
                    pi.last_counted_at,
                    pi.last_restock_date,
                    pi.created_at,
                    pi.updated_at
                FROM product_inventory pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.product_id = ?
            ");

            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching product inventory: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update stock with transaction logging
     */
    public function updateStock($product_id, $quantity_change, $transaction_type = 'adjustment', $reason = '', $reference_id = null, $reference_type = 'manual', $performed_by = null, $notes = '') {
        try {
            $this->conn->begin_transaction();

            // Get current stock
            $current = $this->getProductInventory($product_id);
            if (!$current) {
                throw new Exception("Product inventory not found");
            }

            $stock_before = $current['current_stock'];
            $stock_after = $stock_before + $quantity_change;

            // Update stock
            $stmt = $this->conn->prepare("
                UPDATE product_inventory
                SET current_stock = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE product_id = ?
            ");

            $stmt->bind_param("ii", $stock_after, $product_id);
            $stmt->execute();

            // Log transaction
            $stmt = $this->conn->prepare("
                INSERT INTO inventory_transactions
                (product_id, transaction_type, quantity_change, reason, reference_id, reference_type, notes, performed_by, stock_before, stock_after, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");

            $stmt->bind_param("isssissii", $product_id, $transaction_type, $quantity_change, $reason, $reference_id, $reference_type, $notes, $performed_by, $stock_before, $stock_after);
            $stmt->execute();

            // Check for alerts
            $this->checkAndCreateAlerts($product_id);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error updating stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reserve stock for pending orders
     */
    public function reserveStock($product_id, $quantity, $order_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE product_inventory
                SET reserved_stock = reserved_stock + ?
                WHERE product_id = ?
            ");

            $stmt->bind_param("ii", $quantity, $product_id);
            $success = $stmt->execute();

            if ($success) {
                $this->logTransaction($product_id, 'reserve', $quantity, "Order #$order_id", $order_id, 'order');
            }

            return $success;
        } catch (Exception $e) {
            error_log("Error reserving stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Release reserved stock
     */
    public function releaseReservedStock($product_id, $quantity, $order_id, $reason = '') {
        try {
            $stmt = $this->conn->prepare("
                UPDATE product_inventory
                SET reserved_stock = GREATEST(0, reserved_stock - ?)
                WHERE product_id = ?
            ");

            $stmt->bind_param("ii", $quantity, $product_id);
            $success = $stmt->execute();

            if ($success) {
                $this->logTransaction($product_id, 'release', $quantity, $reason ?: "Order #$order_id cancelled", $order_id, 'order');
            }

            return $success;
        } catch (Exception $e) {
            error_log("Error releasing stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and create stock alerts
     */
    private function checkAndCreateAlerts($product_id) {
        try {
            $inventory = $this->getProductInventory($product_id);
            if (!$inventory) return false;

            $current_stock = $inventory['current_stock'];
            $minimum_stock = $inventory['minimum_stock'];
            $maximum_stock = $inventory['maximum_stock'];

            // Out of stock alert
            if ($current_stock == 0) {
                $this->createAlert($product_id, 'out_of_stock', $current_stock, 0);
            }
            // Low stock alert
            elseif ($current_stock <= $minimum_stock) {
                $this->createAlert($product_id, 'low_stock', $current_stock, $minimum_stock);
            }
            // Overstock alert
            elseif ($current_stock > $maximum_stock) {
                $this->createAlert($product_id, 'overstock', $current_stock, $maximum_stock);
            } else {
                // Clear active alerts if stock is healthy
                $this->clearAlerts($product_id);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error checking alerts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create stock alert
     */
    public function createAlert($product_id, $alert_type, $current_stock, $threshold) {
        try {
            // Check if alert already exists
            $stmt = $this->conn->prepare("
                SELECT id FROM stock_alerts
                WHERE product_id = ? AND alert_type = ? AND is_active = TRUE
            ");

            $stmt->bind_param("is", $product_id, $alert_type);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if (!$existing) {
                $stmt = $this->conn->prepare("
                    INSERT INTO stock_alerts
                    (product_id, alert_type, current_stock, threshold, is_active, created_at)
                    VALUES (?, ?, ?, ?, TRUE, CURRENT_TIMESTAMP)
                ");

                $stmt->bind_param("isii", $product_id, $alert_type, $current_stock, $threshold);
                return $stmt->execute();
            }

            return true;
        } catch (Exception $e) {
            error_log("Error creating alert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear alerts for product
     */
    private function clearAlerts($product_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE stock_alerts
                SET is_active = FALSE
                WHERE product_id = ? AND is_active = TRUE
            ");

            $stmt->bind_param("i", $product_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error clearing alerts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems($limit = 20) {
        try {
            $result = $this->conn->query("
                SELECT
                    p.id,
                    p.title,
                    pi.sku,
                    pi.current_stock,
                    pi.minimum_stock,
                    pi.reorder_point,
                    (pi.minimum_stock - pi.current_stock) as shortage,
                    pi.cost_price,
                    (pi.cost_price * pi.current_stock) as total_cost
                FROM product_inventory pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.current_stock <= pi.minimum_stock AND pi.current_stock > 0
                ORDER BY shortage DESC
                LIMIT $limit
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching low stock items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems($limit = 20) {
        try {
            $result = $this->conn->query("
                SELECT
                    p.id,
                    p.title,
                    pi.sku,
                    pi.current_stock,
                    pi.minimum_stock,
                    COUNT(DISTINCT od.order_id) as pending_orders
                FROM product_inventory pi
                JOIN products p ON pi.product_id = p.id
                LEFT JOIN order_items od ON p.id = od.product_id
                WHERE pi.current_stock = 0
                GROUP BY p.id
                ORDER BY pending_orders DESC
                LIMIT $limit
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching out of stock items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get inventory value summary
     */
    public function getInventoryValue() {
        try {
            $result = $this->conn->query("
                SELECT
                    SUM(pi.current_stock * ict.cost_price) as total_inventory_cost,
                    SUM(pi.current_stock * ict.selling_price) as total_inventory_value,
                    COUNT(DISTINCT pi.product_id) as total_products,
                    AVG(ict.markup_percentage) as avg_markup
                FROM product_inventory pi
                LEFT JOIN inventory_cost_tracking ict ON pi.product_id = ict.product_id
            ");

            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching inventory value: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get inventory transactions
     */
    public function getTransactions($product_id = null, $limit = 100, $offset = 0) {
        try {
            if ($product_id) {
                $stmt = $this->conn->prepare("
                    SELECT
                        it.id,
                        it.product_id,
                        p.title,
                        it.transaction_type,
                        it.quantity_change,
                        it.reason,
                        it.reference_type,
                        it.stock_before,
                        it.stock_after,
                        it.created_at
                    FROM inventory_transactions it
                    JOIN products p ON it.product_id = p.id
                    WHERE it.product_id = ?
                    ORDER BY it.created_at DESC
                    LIMIT ? OFFSET ?
                ");

                $stmt->bind_param("iii", $product_id, $limit, $offset);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT
                        it.id,
                        it.product_id,
                        p.title,
                        it.transaction_type,
                        it.quantity_change,
                        it.reason,
                        it.reference_type,
                        it.stock_before,
                        it.stock_after,
                        it.created_at
                    FROM inventory_transactions it
                    JOIN products p ON it.product_id = p.id
                    ORDER BY it.created_at DESC
                    LIMIT ? OFFSET ?
                ");

                $stmt->bind_param("ii", $limit, $offset);
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching transactions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get warehouse inventory
     */
    public function getWarehouseInventory($warehouse_id = null, $limit = 50, $offset = 0) {
        try {
            if ($warehouse_id) {
                $stmt = $this->conn->prepare("
                    SELECT
                        wi.id,
                        w.warehouse_name,
                        p.title,
                        pi.sku,
                        wi.quantity_on_hand,
                        wi.quantity_reserved,
                        wi.quantity_damaged,
                        (wi.quantity_on_hand - wi.quantity_reserved - wi.quantity_damaged) as available,
                        wi.last_updated
                    FROM warehouse_inventory wi
                    JOIN warehouses w ON wi.warehouse_id = w.id
                    JOIN products p ON wi.product_id = p.id
                    JOIN product_inventory pi ON p.id = pi.product_id
                    WHERE wi.warehouse_id = ?
                    LIMIT ? OFFSET ?
                ");

                $stmt->bind_param("iii", $warehouse_id, $limit, $offset);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT
                        wi.id,
                        w.warehouse_name,
                        p.title,
                        pi.sku,
                        wi.quantity_on_hand,
                        wi.quantity_reserved,
                        wi.quantity_damaged,
                        (wi.quantity_on_hand - wi.quantity_reserved - wi.quantity_damaged) as available,
                        wi.last_updated
                    FROM warehouse_inventory wi
                    JOIN warehouses w ON wi.warehouse_id = w.id
                    JOIN products p ON wi.product_id = p.id
                    JOIN product_inventory pi ON p.id = pi.product_id
                    ORDER BY w.warehouse_name, p.title
                    LIMIT ? OFFSET ?
                ");

                $stmt->bind_param("ii", $limit, $offset);
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching warehouse inventory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active stock alerts
     */
    public function getActiveAlerts($limit = 20) {
        try {
            $result = $this->conn->query("
                SELECT
                    sa.id,
                    p.id as product_id,
                    p.title,
                    pi.sku,
                    sa.alert_type,
                    sa.current_stock,
                    sa.threshold,
                    sa.created_at,
                    sa.acknowledged_by,
                    sa.acknowledged_at
                FROM stock_alerts sa
                JOIN products p ON sa.product_id = p.id
                JOIN product_inventory pi ON p.id = pi.product_id
                WHERE sa.is_active = TRUE
                ORDER BY sa.created_at DESC
                LIMIT $limit
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching active alerts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert($alert_id, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE stock_alerts
                SET acknowledged_by = ?,
                    acknowledged_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->bind_param("ii", $user_id, $alert_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error acknowledging alert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate stock forecast
     */
    public function generateForecast($product_id, $days_ahead = 30) {
        try {
            // Get average daily sales from last 90 days
            $stmt = $this->conn->prepare("
                SELECT
                    AVG(daily_sales) as avg_daily_sales,
                    STDDEV(daily_sales) as stddev_sales
                FROM (
                    SELECT
                        DATE(o.created_at) as sale_date,
                        SUM(oi.quantity) as daily_sales
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE oi.product_id = ? AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                    GROUP BY DATE(o.created_at)
                ) daily
            ");

            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $sales_data = $stmt->get_result()->fetch_assoc();

            $avg_daily_sales = $sales_data['avg_daily_sales'] ?? 0;
            $inventory = $this->getProductInventory($product_id);
            $current_stock = $inventory['current_stock'];

            // Generate forecast for each day
            $forecast_type = 'simple'; // Can be extended with weighted/exponential
            $confidence = 85.0; // Base confidence level

            for ($i = 1; $i <= $days_ahead; $i++) {
                $forecast_date = date('Y-m-d', strtotime("+$i days"));
                $predicted_demand = round($avg_daily_sales * $i);
                $predicted_stock = max(0, $current_stock - $predicted_demand);

                $stmt = $this->conn->prepare("
                    INSERT INTO stock_forecasts
                    (product_id, forecast_date, predicted_demand, predicted_stock, confidence_level, forecast_type, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE
                        predicted_demand = VALUES(predicted_demand),
                        predicted_stock = VALUES(predicted_stock)
                ");

                $stmt->bind_param("isiids", $product_id, $forecast_date, $predicted_demand, $predicted_stock, $confidence, $forecast_type);
                $stmt->execute();
            }

            return true;
        } catch (Exception $e) {
            error_log("Error generating forecast: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reorder recommendations
     */
    public function getReorderRecommendations($limit = 20) {
        try {
            $result = $this->conn->query("
                SELECT
                    p.id,
                    p.title,
                    pi.sku,
                    pi.current_stock,
                    pi.reorder_point,
                    pi.reorder_quantity,
                    rp.lead_time_days,
                    rp.supplier_id,
                    rp.auto_reorder_enabled,
                    COUNT(oi.id) as avg_monthly_sales
                FROM product_inventory pi
                JOIN products p ON pi.product_id = p.id
                LEFT JOIN reorder_points rp ON pi.product_id = rp.product_id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE pi.current_stock <= pi.reorder_point
                GROUP BY p.id
                ORDER BY pi.current_stock ASC
                LIMIT $limit
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching reorder recommendations: " . $e->getMessage());
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
                    COUNT(DISTINCT pi.product_id) as total_products,
                    SUM(pi.current_stock) as total_units,
                    AVG(pi.current_stock) as avg_units_per_product,
                    COUNT(DISTINCT CASE WHEN pi.current_stock = 0 THEN pi.product_id END) as out_of_stock,
                    COUNT(DISTINCT CASE WHEN pi.current_stock <= pi.minimum_stock AND pi.current_stock > 0 THEN pi.product_id END) as low_stock,
                    COUNT(DISTINCT CASE WHEN pi.current_stock > pi.maximum_stock THEN pi.product_id END) as overstock
                FROM product_inventory pi
            ");

            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching inventory summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log transaction helper
     */
    private function logTransaction($product_id, $type, $quantity, $reason = '', $reference_id = null, $reference_type = 'manual') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO inventory_transactions
                (product_id, transaction_type, quantity_change, reason, reference_id, reference_type, created_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");

            $stmt->bind_param("issisi", $product_id, $type, $quantity, $reason, $reference_id, $reference_type);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging transaction: " . $e->getMessage());
            return false;
        }
    }
}
