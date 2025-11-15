<?php
/**
 * AdminDashboard.php - Main Admin Dashboard Analytics
 */

class AdminDashboard {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Get sales metrics for date range
     */
    public function getSalesMetrics($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    SUM(total_amount) as total_revenue,
                    COUNT(DISTINCT id) as total_orders,
                    SUM(CASE WHEN order_status IN ('shipped', 'delivered') THEN 1 ELSE 0 END) as completed_orders,
                    COUNT(DISTINCT customer_id) as unique_customers
                FROM orders
                WHERE created_at BETWEEN ? AND ?
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching sales metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get daily sales data
     */
    public function getDailySalesData($days = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    DATE(created_at) as sale_date,
                    SUM(total_amount) as revenue,
                    COUNT(id) as orders,
                    COUNT(DISTINCT customer_id) as customers
                FROM orders
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY sale_date DESC
            ");

            $stmt->bind_param("i", $days);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching daily sales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by category
     */
    public function getRevenueByCategory($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    c.id,
                    c.name as category_name,
                    SUM(oi.total) as total_revenue,
                    COUNT(DISTINCT oi.id) as items_sold,
                    AVG(oi.price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY c.id, c.name
                ORDER BY total_revenue DESC
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching category revenue: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top selling products
     */
    public function getTopProducts($limit = 10, $days = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    p.id,
                    p.name,
                    COUNT(oi.id) as units_sold,
                    SUM(oi.total) as total_revenue,
                    AVG(oi.price) as avg_price,
                    p.image as product_image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY p.id
                ORDER BY units_sold DESC
                LIMIT ?
            ");

            $stmt->bind_param("ii", $days, $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching top products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer insights
     */
    public function getCustomerInsights($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    COUNT(DISTINCT u.id) as total_customers,
                    COUNT(DISTINCT CASE WHEN u.created_at BETWEEN ? AND ? THEN u.id END) as new_customers,
                    AVG(o.total_amount) as avg_order_value,
                    COUNT(o.id) as total_orders
                FROM users u
                LEFT JOIN orders o ON u.id = o.customer_id
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching customer insights: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get account health score
     */
    public function getAccountHealth() {
        try {
            $result = $this->conn->query("
                SELECT * FROM account_health
                ORDER BY check_date DESC
                LIMIT 1
            ");
            return $result->fetch_assoc() ?? [];
        } catch (Exception $e) {
            error_log("Error fetching account health: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate and update account health score
     */
    public function calculateHealthScore() {
        try {
            // Get metrics
            $stmt = $this->conn->prepare("
                SELECT
                    COALESCE(AVG(rating), 5) as seller_rating,
                    COUNT(CASE WHEN feedback_type = 'negative' THEN 1 END) as negative_feedback,
                    (COUNT(CASE WHEN order_status = 'returned' THEN 1 END) /
                     NULLIF(COUNT(id), 0) * 100) as return_rate
                FROM orders o
                LEFT JOIN seller_feedback sf ON o.id = sf.order_id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");

            $stmt->execute();
            $metrics = $stmt->get_result()->fetch_assoc();

            // Calculate health score (0-100)
            $score = 100;
            $score -= ($metrics['negative_feedback'] ?? 0) * 2;
            $score -= ($metrics['return_rate'] ?? 0) * 0.5;
            if (($metrics['seller_rating'] ?? 5) < 4) {
                $score -= (5 - $metrics['seller_rating']) * 5;
            }

            $score = max(0, min(100, $score));

            // Save to database
            $stmt = $this->conn->prepare("
                INSERT INTO account_health (check_date, health_score, seller_rating)
                VALUES (CURDATE(), ?, ?)
                ON DUPLICATE KEY UPDATE health_score = VALUES(health_score), seller_rating = VALUES(seller_rating)
            ");

            $stmt->bind_param("dd", $score, $metrics['seller_rating']);
            $stmt->execute();

            return $score;
        } catch (Exception $e) {
            error_log("Error calculating health score: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get order status breakdown
     */
    public function getOrderStatusBreakdown($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    order_status,
                    COUNT(id) as count
                FROM orders
                WHERE created_at BETWEEN ? AND ?
                GROUP BY order_status
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching order status: " . $e->getMessage());
            return [];
        }
    }
}
