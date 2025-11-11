<?php
/**
 * Coupon and Discount Management System
 * Handles coupon creation, validation, application, and analytics
 */

class CouponManager {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->initializeTable();
    }

    /**
     * Initialize coupons table if it doesn't exist
     */
    private function initializeTable() {
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS coupons (
                id INT PRIMARY KEY AUTO_INCREMENT,
                code VARCHAR(50) UNIQUE NOT NULL,
                description VARCHAR(255),
                discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
                discount_value DECIMAL(10, 2) NOT NULL,
                min_order_value DECIMAL(10, 2) DEFAULT 0,
                max_uses INT DEFAULT NULL,
                current_uses INT DEFAULT 0,
                per_customer_limit INT DEFAULT 1,
                valid_from DATETIME NOT NULL,
                valid_until DATETIME NOT NULL,
                applicable_products TEXT COMMENT 'JSON array of product IDs',
                applicable_categories TEXT COMMENT 'JSON array of category IDs',
                status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );

        // Create coupon usage history table
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS coupon_usage (
                id INT PRIMARY KEY AUTO_INCREMENT,
                coupon_id INT NOT NULL,
                customer_id INT,
                order_id INT,
                discount_amount DECIMAL(10, 2),
                used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
            )"
        );
    }

    /**
     * Create a new coupon
     * @param array $data Coupon details
     * @return array Result
     */
    public function createCoupon($data) {
        $required_fields = ['code', 'discount_type', 'discount_value', 'valid_from', 'valid_until'];

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        // Validate code format
        if (!preg_match('/^[A-Z0-9\-_]{3,50}$/', $data['code'])) {
            return ['success' => false, 'message' => 'Invalid coupon code format'];
        }

        // Check if code already exists
        $check = $this->conn->prepare("SELECT id FROM coupons WHERE code = ?");
        $check->bind_param('s', $data['code']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Coupon code already exists'];
        }
        $check->close();

        // Insert coupon
        $stmt = $this->conn->prepare(
            "INSERT INTO coupons (code, description, discount_type, discount_value, min_order_value,
             max_uses, per_customer_limit, valid_from, valid_until, applicable_products,
             applicable_categories, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $products_json = isset($data['applicable_products']) ? json_encode($data['applicable_products']) : null;
        $categories_json = isset($data['applicable_categories']) ? json_encode($data['applicable_categories']) : null;

        $stmt->bind_param(
            'sssddiisss',
            $data['code'],
            $data['description'] ?? null,
            $data['discount_type'],
            $data['discount_value'],
            $data['min_order_value'] ?? 0,
            $data['max_uses'] ?? null,
            $data['per_customer_limit'] ?? 1,
            $data['valid_from'],
            $data['valid_until'],
            $products_json,
            $categories_json,
            $data['created_by'] ?? null
        );

        if ($stmt->execute()) {
            $coupon_id = $this->conn->insert_id;
            $stmt->close();
            return ['success' => true, 'message' => 'Coupon created successfully', 'coupon_id' => $coupon_id];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Error creating coupon'];
    }

    /**
     * Validate and apply coupon
     * @param string $code Coupon code
     * @param float $cart_total Cart total amount
     * @param int $customer_id Customer ID
     * @param array $cart_items Items in cart
     * @return array Result with discount amount
     */
    public function validateCoupon($code, $cart_total, $customer_id = null, $cart_items = []) {
        $code = strtoupper(trim($code));

        // Get coupon
        $stmt = $this->conn->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$coupon) {
            return ['success' => false, 'message' => 'Coupon not found'];
        }

        // Check if active
        if ($coupon['status'] !== 'active') {
            return ['success' => false, 'message' => 'Coupon is not active'];
        }

        // Check validity dates
        $now = date('Y-m-d H:i:s');
        if ($now < $coupon['valid_from'] || $now > $coupon['valid_until']) {
            return ['success' => false, 'message' => 'Coupon has expired'];
        }

        // Check minimum order value
        if ($cart_total < $coupon['min_order_value']) {
            return [
                'success' => false,
                'message' => "Minimum order value of ₹{$coupon['min_order_value']} required"
            ];
        }

        // Check max uses
        if ($coupon['max_uses'] && $coupon['current_uses'] >= $coupon['max_uses']) {
            return ['success' => false, 'message' => 'Coupon usage limit exceeded'];
        }

        // Check per-customer limit
        if ($customer_id && $coupon['per_customer_limit'] > 0) {
            $usage_stmt = $this->conn->prepare(
                "SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = ? AND customer_id = ?"
            );
            $usage_stmt->bind_param('ii', $coupon['id'], $customer_id);
            $usage_stmt->execute();
            $usage_count = $usage_stmt->get_result()->fetch_assoc()['count'];
            $usage_stmt->close();

            if ($usage_count >= $coupon['per_customer_limit']) {
                return ['success' => false, 'message' => 'You have already used this coupon'];
            }
        }

        // Check applicable products/categories
        if (!empty($cart_items)) {
            $applicable_products = json_decode($coupon['applicable_products'], true) ?? [];
            $applicable_categories = json_decode($coupon['applicable_categories'], true) ?? [];

            // If restrictions exist, check if any item matches
            if (!empty($applicable_products) || !empty($applicable_categories)) {
                $item_matches = false;
                // This would be implemented based on cart item structure
            }
        }

        // Calculate discount
        $discount = 0;
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($cart_total * $coupon['discount_value']) / 100;
        } else {
            $discount = $coupon['discount_value'];
        }

        return [
            'success' => true,
            'message' => 'Coupon applied successfully',
            'coupon_id' => $coupon['id'],
            'code' => $coupon['code'],
            'discount_amount' => round($discount, 2),
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value']
        ];
    }

    /**
     * Record coupon usage
     * @param int $coupon_id
     * @param int $order_id
     * @param float $discount_amount
     * @param int $customer_id
     */
    public function recordUsage($coupon_id, $order_id, $discount_amount, $customer_id = null) {
        $this->conn->begin_transaction();

        try {
            // Record usage
            $usage_stmt = $this->conn->prepare(
                "INSERT INTO coupon_usage (coupon_id, customer_id, order_id, discount_amount)
                 VALUES (?, ?, ?, ?)"
            );
            $usage_stmt->bind_param('iiii', $coupon_id, $customer_id, $order_id, $discount_amount);
            $usage_stmt->execute();
            $usage_stmt->close();

            // Increment coupon usage count
            $update_stmt = $this->conn->prepare(
                "UPDATE coupons SET current_uses = current_uses + 1 WHERE id = ?"
            );
            $update_stmt->bind_param('i', $coupon_id);
            $update_stmt->execute();
            $update_stmt->close();

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error recording usage'];
        }
    }

    /**
     * Get all coupons with statistics
     * @return array Coupons with usage stats
     */
    public function getAllCoupons() {
        $stmt = $this->conn->query(
            "SELECT c.*,
                    COUNT(cu.id) as total_used,
                    COALESCE(SUM(cu.discount_amount), 0) as total_discounts
             FROM coupons c
             LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
             GROUP BY c.id
             ORDER BY c.created_at DESC"
        );

        $coupons = [];
        while ($coupon = $stmt->fetch_assoc()) {
            $coupon['remaining_uses'] = $coupon['max_uses'] ? ($coupon['max_uses'] - $coupon['current_uses']) : 'Unlimited';
            $coupon['usage_percentage'] = $coupon['max_uses'] ? round(($coupon['current_uses'] / $coupon['max_uses']) * 100) : 0;
            $coupons[] = $coupon;
        }

        return $coupons;
    }

    /**
     * Update coupon
     * @param int $coupon_id
     * @param array $data
     * @return array Result
     */
    public function updateCoupon($coupon_id, $data) {
        $stmt = $this->conn->prepare(
            "UPDATE coupons SET description = ?, discount_type = ?, discount_value = ?,
             min_order_value = ?, valid_until = ?, status = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            'ssddsti',
            $data['description'],
            $data['discount_type'],
            $data['discount_value'],
            $data['min_order_value'],
            $data['valid_until'],
            $data['status'],
            $coupon_id
        );

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Coupon updated successfully'];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Error updating coupon'];
    }

    /**
     * Delete coupon
     * @param int $coupon_id
     * @return array Result
     */
    public function deleteCoupon($coupon_id) {
        $stmt = $this->conn->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->bind_param('i', $coupon_id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Coupon deleted successfully'];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Error deleting coupon'];
    }

    /**
     * Get coupon analytics
     * @return array Analytics data
     */
    public function getAnalytics() {
        $analytics = [];

        // Total coupons
        $total = $this->conn->query("SELECT COUNT(*) as count FROM coupons");
        $analytics['total_coupons'] = $total->fetch_assoc()['count'];

        // Active coupons
        $active = $this->conn->query("SELECT COUNT(*) as count FROM coupons WHERE status = 'active'");
        $analytics['active_coupons'] = $active->fetch_assoc()['count'];

        // Total discounts given
        $discounts = $this->conn->query("SELECT COALESCE(SUM(discount_amount), 0) as total FROM coupon_usage");
        $analytics['total_discounts'] = $discounts->fetch_assoc()['total'];

        // Most used coupons
        $most_used = $this->conn->query(
            "SELECT c.code, COUNT(cu.id) as usage_count, SUM(cu.discount_amount) as discount_total
             FROM coupons c
             JOIN coupon_usage cu ON c.id = cu.coupon_id
             GROUP BY c.id
             ORDER BY usage_count DESC
             LIMIT 5"
        );
        $analytics['most_used'] = [];
        while ($row = $most_used->fetch_assoc()) {
            $analytics['most_used'][] = $row;
        }

        return $analytics;
    }
}
?>
