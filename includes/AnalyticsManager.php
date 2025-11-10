<?php
/**
 * Analytics Manager Class
 * Handles all analytics and reporting
 *
 * @version 1.0
 * @author Books eCommerce Platform
 */

class AnalyticsManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Track page view
     */
    public function trackPageView($page_url, $page_title, $customer_id = null, $referrer = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO page_views (customer_id, page_url, page_title, referrer, user_agent, ip_address, session_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $ip_address = $this->getClientIP();
            $session_id = session_id();

            $stmt->bind_param(
                "iss ssss",
                $customer_id,
                $page_url,
                $page_title,
                $referrer,
                $user_agent,
                $ip_address,
                $session_id
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Track Page View Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Track product view
     */
    public function trackProductView($product_id, $customer_id = null, $referrer = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO product_views (product_id, customer_id, session_id, referrer)
                VALUES (?, ?, ?, ?)
            ");

            $session_id = session_id();

            $stmt->bind_param(
                "iiss",
                $product_id,
                $customer_id,
                $session_id,
                $referrer
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Track Product View Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Track search query
     */
    public function trackSearchQuery($search_term, $results_count, $customer_id = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO search_queries (customer_id, search_term, results_count, session_id)
                VALUES (?, ?, ?, ?)
            ");

            $session_id = session_id();

            $stmt->bind_param(
                "isii",
                $customer_id,
                $search_term,
                $results_count,
                $session_id
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Track Search Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Track conversion funnel step
     */
    public function trackConversionStep($customer_id, $step, $step_data = []) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO conversion_funnels (customer_id, session_id, step, step_data)
                VALUES (?, ?, ?, ?)
            ");

            $session_id = session_id();
            $step_data_json = json_encode($step_data);

            $stmt->bind_param(
                "isss",
                $customer_id,
                $session_id,
                $step,
                $step_data_json
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Track Conversion Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Track abandoned cart
     */
    public function trackAbandonedCart($customer_id, $cart_id, $items_count, $cart_value) {
        try {
            // Check if cart already tracked
            $check_stmt = $this->conn->prepare("
                SELECT id FROM cart_abandonment WHERE cart_id = ? AND recovered = FALSE
            ");
            $check_stmt->bind_param("i", $cart_id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->fetch_assoc()) {
                return false; // Already tracked
            }

            $stmt = $this->conn->prepare("
                INSERT INTO cart_abandonment (customer_id, cart_id, items_count, cart_value)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iid",
                $customer_id,
                $cart_id,
                $items_count,
                $cart_value
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Track Abandoned Cart Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark cart as recovered
     */
    public function markCartRecovered($order_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE cart_abandonment
                SET recovered = TRUE, recovered_at = NOW(), order_id = ?
                WHERE order_id IS NULL
                LIMIT 1
            ");

            $stmt->bind_param("i", $order_id);
            return $stmt->execute();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Mark Cart Recovered Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get daily metrics
     */
    public function getDailyMetrics($start_date, $end_date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM daily_metrics
                WHERE metric_date BETWEEN ? AND ?
                ORDER BY metric_date DESC
            ");

            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Daily Metrics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue summary
     */
    public function getRevenueSummary($start_date, $end_date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(total_revenue) as total_revenue,
                       AVG(average_order_value) as avg_order_value,
                       SUM(total_orders) as total_orders,
                       COUNT(*) as days
                FROM daily_metrics
                WHERE metric_date BETWEEN ? AND ?
            ");

            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();

            return $stmt->get_result()->fetch_assoc();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Revenue Summary Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get top products
     */
    public function getTopProducts($limit = 10, $start_date = null, $end_date = null) {
        try {
            $query = "
                SELECT p.id, p.title, p.sku,
                       COALESCE(SUM(pa.views_count), 0) as total_views,
                       COALESCE(SUM(pa.orders_count), 0) as total_orders,
                       COALESCE(SUM(pa.total_revenue), 0) as total_revenue,
                       COALESCE(SUM(pa.total_quantity_sold), 0) as quantity_sold
                FROM products p
                LEFT JOIN product_analytics pa ON p.id = pa.product_id
            ";

            if ($start_date && $end_date) {
                $query .= " WHERE pa.metric_date BETWEEN ? AND ?";
            }

            $query .= " GROUP BY p.id ORDER BY total_revenue DESC LIMIT ?";

            $stmt = $this->conn->prepare($query);

            if ($start_date && $end_date) {
                $stmt->bind_param("ssi", $start_date, $end_date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Top Products Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer metrics
     */
    public function getCustomerMetrics($start_date, $end_date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM customer_metrics
                WHERE metric_date BETWEEN ? AND ?
                ORDER BY metric_date DESC
            ");

            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Customer Metrics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversion funnel analysis
     */
    public function getConversionFunnel($start_date = null, $end_date = null) {
        try {
            $query = "
                SELECT step, COUNT(*) as count
                FROM conversion_funnels
            ";

            if ($start_date && $end_date) {
                $query .= " WHERE DATE(created_at) BETWEEN ? AND ?";
            }

            $query .= " GROUP BY step ORDER BY FIELD(step, 'browse', 'cart_add', 'checkout', 'payment', 'complete')";

            $stmt = $this->conn->prepare($query);

            if ($start_date && $end_date) {
                $stmt->bind_param("ss", $start_date, $end_date);
            }

            $stmt->execute();

            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Calculate conversion rates
            $total = 0;
            foreach ($result as $row) {
                $total += $row['count'];
            }

            foreach ($result as &$row) {
                $row['percentage'] = $total > 0 ? ($row['count'] / $total) * 100 : 0;
            }

            return $result;

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Conversion Funnel Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cart abandonment rate
     */
    public function getCartAbandonmentRate($start_date, $end_date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total_carts,
                       SUM(CASE WHEN recovered = TRUE THEN 1 ELSE 0 END) as recovered_carts,
                       SUM(CASE WHEN recovered = FALSE THEN 1 ELSE 0 END) as abandoned_carts,
                       ROUND((SUM(CASE WHEN recovered = FALSE THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as abandonment_rate
                FROM cart_abandonment
                WHERE DATE(abandoned_at) BETWEEN ? AND ?
            ");

            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();

            return $stmt->get_result()->fetch_assoc();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Cart Abandonment Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get traffic sources
     */
    public function getTrafficSources($start_date, $end_date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT source_type, source_name, SUM(sessions_count) as sessions,
                       SUM(users_count) as users, AVG(bounce_rate) as bounce_rate
                FROM traffic_sources
                WHERE metric_date BETWEEN ? AND ?
                GROUP BY source_type, source_name
                ORDER BY sessions DESC
            ");

            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Traffic Sources Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get popular search terms
     */
    public function getPopularSearches($limit = 20, $start_date = null, $end_date = null) {
        try {
            $query = "
                SELECT search_term, COUNT(*) as search_count,
                       SUM(CASE WHEN clicked_result = TRUE THEN 1 ELSE 0 END) as click_count,
                       AVG(results_count) as avg_results
                FROM search_queries
            ";

            if ($start_date && $end_date) {
                $query .= " WHERE DATE(created_at) BETWEEN ? AND ?";
            }

            $query .= " GROUP BY search_term ORDER BY search_count DESC LIMIT ?";

            $stmt = $this->conn->prepare($query);

            if ($start_date && $end_date) {
                $stmt->bind_param("ssi", $start_date, $end_date, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Get Popular Searches Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate daily report
     */
    public function generateDailyReport($metric_date) {
        try {
            // Count visitors
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT ip_address) as total_visitors,
                       COUNT(DISTINCT session_id) as sessions,
                       COUNT(*) as page_views
                FROM page_views
                WHERE DATE(created_at) = ?
            ");
            $stmt->bind_param("s", $metric_date);
            $stmt->execute();
            $traffic = $stmt->get_result()->fetch_assoc();

            // Get revenue data
            $rev_stmt = $this->conn->prepare("
                SELECT COUNT(*) as orders,
                       SUM(total) as revenue,
                       AVG(total) as avg_order_value
                FROM orders
                WHERE DATE(created_at) = ? AND status = 'completed'
            ");
            $rev_stmt->bind_param("s", $metric_date);
            $rev_stmt->execute();
            $revenue = $rev_stmt->get_result()->fetch_assoc();

            // Calculate conversion rate
            $conversion_rate = 0;
            if ($traffic['sessions'] > 0) {
                $conversion_rate = ($revenue['orders'] / $traffic['sessions']) * 100;
            }

            // Get top product
            $top_stmt = $this->conn->prepare("
                SELECT pa.product_id, p.title, SUM(pa.orders_count) as orders
                FROM product_analytics pa
                JOIN products p ON pa.product_id = p.id
                WHERE pa.metric_date = ?
                GROUP BY pa.product_id
                ORDER BY orders DESC
                LIMIT 1
            ");
            $top_stmt->bind_param("s", $metric_date);
            $top_stmt->execute();
            $top_product = $top_stmt->get_result()->fetch_assoc();

            // Get cart abandonment
            $abandon_stmt = $this->conn->prepare("
                SELECT COUNT(*) as abandoned,
                       SUM(cart_value) as abandoned_value
                FROM cart_abandonment
                WHERE DATE(abandoned_at) = ? AND recovered = FALSE
            ");
            $abandon_stmt->bind_param("s", $metric_date);
            $abandon_stmt->execute();
            $abandonment = $abandon_stmt->get_result()->fetch_assoc();

            // Insert into daily_metrics
            $insert_stmt = $this->conn->prepare("
                INSERT INTO daily_metrics (metric_date, total_visitors, unique_visitors, total_page_views,
                                          total_orders, total_revenue, average_order_value, conversion_rate,
                                          top_product_id, top_product_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_visitors = VALUES(total_visitors),
                    total_page_views = VALUES(total_page_views),
                    total_orders = VALUES(total_orders),
                    total_revenue = VALUES(total_revenue),
                    average_order_value = VALUES(average_order_value),
                    conversion_rate = VALUES(conversion_rate),
                    top_product_id = VALUES(top_product_id)
            ");

            $total_visitors = $traffic['total_visitors'] ?? 0;
            $sessions = $traffic['sessions'] ?? 0;
            $page_views = $traffic['page_views'] ?? 0;
            $orders = $revenue['orders'] ?? 0;
            $total_revenue = $revenue['revenue'] ?? 0;
            $avg_order = $revenue['avg_order_value'] ?? 0;
            $top_product_id = $top_product['product_id'] ?? null;
            $top_product_count = $top_product['orders'] ?? 0;

            $insert_stmt->bind_param(
                "siiiidddii",
                $metric_date,
                $total_visitors,
                $sessions,
                $page_views,
                $orders,
                $total_revenue,
                $avg_order,
                $conversion_rate,
                $top_product_id,
                $top_product_count
            );

            return $insert_stmt->execute();

        } catch (Exception $e) {
            error_log("[AnalyticsManager] Generate Daily Report Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get client IP
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return $ip;
    }
}
?>
