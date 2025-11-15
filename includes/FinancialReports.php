<?php
/**
 * FinancialReports.php - Handle financial analytics and reporting
 */

class FinancialReports {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Generate daily financial report
     */
    public function generateDailyReport($date) {
        try {
            // Get revenue from orders
            $stmt = $this->conn->prepare("
                SELECT
                    SUM(total_amount) as total_revenue,
                    COUNT(id) as order_count
                FROM orders
                WHERE DATE(created_at) = ?
            ");

            $stmt->bind_param("s", $date);
            $stmt->execute();
            $revenue = $stmt->get_result()->fetch_assoc();

            // Calculate COGS (approximation)
            $cogs = $this->calculateCOGS($date);

            // Calculate fees
            $fees = $this->calculateFees($revenue['total_revenue']);

            // Calculate expenses
            $expenses = $this->calculateExpenses($date);

            // Calculate profits
            $total_expenses = $cogs + $fees + $expenses;
            $gross_profit = ($revenue['total_revenue'] ?? 0) - $cogs;
            $net_profit = $gross_profit - $fees - $expenses;
            $profit_margin = ($revenue['total_revenue'] ?? 0) > 0 ? ($net_profit / $revenue['total_revenue']) * 100 : 0;

            // Save report
            $stmt = $this->conn->prepare("
                INSERT INTO financial_reports (
                    report_date, period_type, total_revenue, total_cogs,
                    referral_fees, fulfillment_fees, advertising_spend,
                    other_expenses, total_expenses, gross_profit, net_profit, profit_margin
                ) VALUES (?, 'daily', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_revenue = VALUES(total_revenue),
                    total_cogs = VALUES(total_cogs),
                    referral_fees = VALUES(referral_fees),
                    gross_profit = VALUES(gross_profit),
                    net_profit = VALUES(net_profit),
                    profit_margin = VALUES(profit_margin)
            ");

            $stmt->bind_param(
                "sddddddddd",
                $date,
                $revenue['total_revenue'],
                $cogs,
                $fees['referral'],
                $fees['fulfillment'],
                $expenses['advertising'],
                $expenses['other'],
                $total_expenses,
                $gross_profit,
                $net_profit,
                $profit_margin
            );

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error generating report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate COGS (Cost of Goods Sold)
     */
    private function calculateCOGS($date) {
        try {
            // Approximation: use average cost per product
            $stmt = $this->conn->prepare("
                SELECT SUM((p.price * 0.4) * oi.total) as estimated_cogs
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE DATE(o.created_at) = ?
            ");

            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['estimated_cogs'] ?? 0;
        } catch (Exception $e) {
            error_log("Error calculating COGS: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate fees (referral, fulfillment, etc)
     */
    private function calculateFees($revenue) {
        return [
            'referral' => ($revenue ?? 0) * 0.15, // 15% referral fee
            'fulfillment' => ($revenue ?? 0) * 0.10  // 10% fulfillment fee
        ];
    }

    /**
     * Calculate expenses
     */
    private function calculateExpenses($date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    SUM(spend) as advertising
                FROM advertising_campaigns
                WHERE DATE(created_at) = ?
            ");

            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            return [
                'advertising' => $result['advertising'] ?? 0,
                'other' => 0
            ];
        } catch (Exception $e) {
            error_log("Error calculating expenses: " . $e->getMessage());
            return ['advertising' => 0, 'other' => 0];
        }
    }

    /**
     * Get financial report for date range
     */
    public function getReportRange($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    report_date,
                    total_revenue,
                    total_cogs,
                    referral_fees,
                    fulfillment_fees,
                    advertising_spend,
                    total_expenses,
                    gross_profit,
                    net_profit,
                    profit_margin
                FROM financial_reports
                WHERE report_date BETWEEN ? AND ? AND period_type = 'daily'
                ORDER BY report_date DESC
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue breakdown by category
     */
    public function getRevenueByCategory($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    c.id,
                    c.name,
                    SUM(oi.total) as revenue,
                    COUNT(oi.id) as items_sold,
                    AVG(oi.price) as avg_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY c.id, c.name
                ORDER BY revenue DESC
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
     * Get profit analysis by product
     */
    public function getProfitByProduct($startDate, $endDate, $limit = 20) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    p.id,
                    p.name,
                    p.price,
                    SUM(oi.total) as total_revenue,
                    SUM(oi.total * 0.4) as estimated_cogs,
                    SUM(oi.total) * 0.15 as referral_fees,
                    (SUM(oi.total) - (SUM(oi.total) * 0.4) - (SUM(oi.total) * 0.15)) as net_profit,
                    COUNT(oi.id) as units_sold
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY p.id
                ORDER BY net_profit DESC
                LIMIT ?
            ");

            $stmt->bind_param("ssi", $startDate, $endDate, $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching profit analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary($days = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    SUM(total_revenue) as total_revenue,
                    SUM(total_cogs) as total_cogs,
                    SUM(referral_fees) as total_referral_fees,
                    SUM(fulfillment_fees) as total_fulfillment_fees,
                    SUM(advertising_spend) as total_advertising,
                    SUM(total_expenses) as total_expenses,
                    SUM(gross_profit) as total_gross_profit,
                    SUM(net_profit) as total_net_profit,
                    AVG(profit_margin) as avg_profit_margin
                FROM financial_reports
                WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND period_type = 'daily'
            ");

            $stmt->bind_param("i", $days);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching financial summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get payouts/settlements
     */
    public function getPayouts($status = null) {
        try {
            $query = "SELECT * FROM seller_payouts";

            if ($status) {
                $query .= " WHERE status = '$status'";
            }

            $query .= " ORDER BY created_at DESC LIMIT 100";

            $result = $this->conn->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching payouts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create payout record
     */
    public function createPayout($amount, $periodStart, $periodEnd) {
        try {
            $payoutId = 'PAY-' . date('YmdHis');
            $fee = $amount * 0.02; // 2% transaction fee
            $netPayout = $amount - $fee;

            $stmt = $this->conn->prepare("
                INSERT INTO seller_payouts (
                    payout_id, payout_amount, payout_period_start,
                    payout_period_end, transaction_fee, net_payout, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->bind_param("sddsdd", $payoutId, $amount, $periodStart, $periodEnd, $fee, $netPayout);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating payout: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get monthly comparison
     */
    public function getMonthlyComparison($months = 6) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    DATE_FORMAT(report_date, '%Y-%m') as month,
                    SUM(total_revenue) as revenue,
                    SUM(net_profit) as profit,
                    AVG(profit_margin) as margin
                FROM financial_reports
                WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(report_date, '%Y-%m')
                ORDER BY month DESC
            ");

            $stmt->bind_param("i", $months);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching monthly comparison: " . $e->getMessage());
            return [];
        }
    }
}
