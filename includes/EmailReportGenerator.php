<?php
/**
 * EmailReportGenerator.php - Generate and send automated reports via email
 */

class EmailReportGenerator {
    private $conn;
    private $smtpConfig = [
        'host' => 'smtp.mailtrap.io', // Or use your own SMTP
        'port' => 465,
        'username' => 'admin@bookory.local',
        'password' => 'admin_pass'
    ];

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Generate and send daily report
     */
    public function generateDailyReport($recipientEmail, $reportDate = null) {
        try {
            if (!$reportDate) {
                $reportDate = date('Y-m-d', strtotime('-1 day'));
            }

            // Get metrics for the day
            $metrics = $this->getDailyMetrics($reportDate);
            $topProducts = $this->getTopProducts($reportDate);
            $alerts = $this->getAlerts($reportDate);

            // Generate HTML email
            $html = $this->generateEmailHTML($metrics, $topProducts, $alerts, $reportDate);

            // Send email
            return $this->sendEmail(
                $recipientEmail,
                'Daily Sales Report - ' . date('M d, Y', strtotime($reportDate)),
                $html
            );
        } catch (Exception $e) {
            error_log("Report generation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate weekly report
     */
    public function generateWeeklyReport($recipientEmail, $startDate = null) {
        try {
            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-7 days'));
            }
            $endDate = date('Y-m-d');

            // Get metrics
            $metrics = $this->getWeeklyMetrics($startDate, $endDate);
            $topProducts = $this->getTopProductsRange($startDate, $endDate);
            $trends = $this->getWeeklyTrends($startDate, $endDate);

            // Generate HTML
            $html = $this->generateWeeklyEmailHTML($metrics, $topProducts, $trends, $startDate, $endDate);

            return $this->sendEmail(
                $recipientEmail,
                'Weekly Sales Report - ' . date('M d', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate)),
                $html
            );
        } catch (Exception $e) {
            error_log("Weekly report error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get daily metrics
     */
    private function getDailyMetrics($date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    SUM(total_amount) as total_revenue,
                    COUNT(id) as total_orders,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    AVG(total_amount) as avg_order_value
                FROM orders
                WHERE DATE(created_at) = ?
            ");

            $stmt->bind_param("s", $date);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get weekly metrics
     */
    private function getWeeklyMetrics($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    SUM(total_amount) as total_revenue,
                    COUNT(id) as total_orders,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    AVG(total_amount) as avg_order_value
                FROM orders
                WHERE DATE(created_at) BETWEEN ? AND ?
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get top products for date
     */
    private function getTopProducts($date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    p.name,
                    COUNT(oi.id) as units_sold,
                    SUM(oi.total) as revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE DATE(o.created_at) = ?
                GROUP BY p.id
                ORDER BY units_sold DESC
                LIMIT 5
            ");

            $stmt->bind_param("s", $date);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get top products for range
     */
    private function getTopProductsRange($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    p.name,
                    COUNT(oi.id) as units_sold,
                    SUM(oi.total) as revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE DATE(o.created_at) BETWEEN ? AND ?
                GROUP BY p.id
                ORDER BY units_sold DESC
                LIMIT 10
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get alerts
     */
    private function getAlerts($date) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, product_id, alert_type FROM inventory_alerts
                WHERE status = 'active' AND DATE(created_at) >= ?
                LIMIT 5
            ");

            $stmt->bind_param("s", $date);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get weekly trends
     */
    private function getWeeklyTrends($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    DATE(created_at) as sale_date,
                    SUM(total_amount) as daily_revenue,
                    COUNT(id) as daily_orders
                FROM orders
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY sale_date DESC
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Generate HTML email for daily report
     */
    private function generateEmailHTML($metrics, $topProducts, $alerts, $date) {
        $dateFormatted = date('F d, Y', strtotime($date));

        $html = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; }
                h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
                .metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
                .metric-box { background: #f9f9f9; padding: 15px; border-left: 4px solid #007bff; border-radius: 4px; }
                .metric-value { font-size: 24px; font-weight: bold; color: #007bff; }
                .metric-label { color: #666; font-size: 12px; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background-color: #007bff; color: white; padding: 10px; text-align: left; }
                td { border: 1px solid #ddd; padding: 8px; }
                .alert { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>📊 Daily Sales Report</h1>
                <p><strong>Date:</strong> $dateFormatted</p>

                <div class='metrics'>
                    <div class='metric-box'>
                        <div class='metric-value'>\$" . number_format($metrics['total_revenue'] ?? 0, 2) . "</div>
                        <div class='metric-label'>Total Revenue</div>
                    </div>
                    <div class='metric-box'>
                        <div class='metric-value'>" . ($metrics['total_orders'] ?? 0) . "</div>
                        <div class='metric-label'>Total Orders</div>
                    </div>
                    <div class='metric-box'>
                        <div class='metric-value'>" . ($metrics['unique_customers'] ?? 0) . "</div>
                        <div class='metric-label'>Unique Customers</div>
                    </div>
                    <div class='metric-box'>
                        <div class='metric-value'>\$" . number_format($metrics['avg_order_value'] ?? 0, 2) . "</div>
                        <div class='metric-label'>Avg Order Value</div>
                    </div>
                </div>

                <h3 style='margin-top: 30px;'>Top Selling Products</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($topProducts as $product) {
            $html .= "
                        <tr>
                            <td>" . htmlspecialchars($product['name']) . "</td>
                            <td>" . $product['units_sold'] . "</td>
                            <td>\$" . number_format($product['revenue'], 2) . "</td>
                        </tr>";
        }

        $html .= "
                    </tbody>
                </table>";

        if (!empty($alerts)) {
            $html .= "<h3 style='margin-top: 30px;'>⚠️ Alerts</h3>";
            foreach ($alerts as $alert) {
                $html .= "<div class='alert'><strong>" . ucfirst(str_replace('_', ' ', $alert['alert_type'])) . "</strong> - Product ID: " . $alert['product_id'] . "</div>";
            }
        }

        $html .= "
                <div class='footer'>
                    <p>This is an automated report from Bookory Admin Dashboard.</p>
                    <p>Do not reply to this email. <a href='https://bookory.local/admin/dashboard.php'>View Full Dashboard</a></p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Generate HTML email for weekly report
     */
    private function generateWeeklyEmailHTML($metrics, $topProducts, $trends, $startDate, $endDate) {
        $periodStr = date('M d', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));

        $html = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; }
                h1 { color: #333; border-bottom: 3px solid #28a745; padding-bottom: 10px; }
                .metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
                .metric-box { background: #f9f9f9; padding: 15px; border-left: 4px solid #28a745; border-radius: 4px; }
                .metric-value { font-size: 24px; font-weight: bold; color: #28a745; }
                .metric-label { color: #666; font-size: 12px; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background-color: #28a745; color: white; padding: 10px; text-align: left; }
                td { border: 1px solid #ddd; padding: 8px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>📈 Weekly Sales Report</h1>
                <p><strong>Period:</strong> $periodStr</p>

                <div class='metrics'>
                    <div class='metric-box'>
                        <div class='metric-value'>\$" . number_format($metrics['total_revenue'] ?? 0, 2) . "</div>
                        <div class='metric-label'>Weekly Revenue</div>
                    </div>
                    <div class='metric-box'>
                        <div class='metric-value'>" . ($metrics['total_orders'] ?? 0) . "</div>
                        <div class='metric-label'>Total Orders</div>
                    </div>
                </div>

                <h3>Daily Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Revenue</th>
                            <th>Orders</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($trends as $trend) {
            $html .= "
                        <tr>
                            <td>" . date('M d', strtotime($trend['sale_date'])) . "</td>
                            <td>\$" . number_format($trend['daily_revenue'], 2) . "</td>
                            <td>" . $trend['daily_orders'] . "</td>
                        </tr>";
        }

        $html .= "
                    </tbody>
                </table>

                <h3>Top Selling Products</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($topProducts as $product) {
            $html .= "
                        <tr>
                            <td>" . htmlspecialchars($product['name']) . "</td>
                            <td>" . $product['units_sold'] . "</td>
                            <td>\$" . number_format($product['revenue'], 2) . "</td>
                        </tr>";
        }

        $html .= "
                    </tbody>
                </table>

                <div class='footer'>
                    <p>This is an automated report from Bookory Admin Dashboard.</p>
                    <p>Do not reply to this email. <a href='https://bookory.local/admin/dashboard.php'>View Full Dashboard</a></p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Send email
     */
    private function sendEmail($to, $subject, $htmlContent) {
        try {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: reports@bookory.local\r\n";
            $headers .= "Reply-To: noreply@bookory.local\r\n";

            return mail($to, $subject, $htmlContent, $headers);
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Schedule email report (call this in cron job)
     */
    public function scheduleReports() {
        try {
            // Get admin email settings
            $stmt = $this->conn->prepare("
                SELECT setting_value FROM site_settings
                WHERE setting_key IN ('admin_email', 'report_frequency')
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            $adminEmail = $settings['admin_email'] ?? 'admin@bookory.local';
            $frequency = $settings['report_frequency'] ?? 'daily';

            if ($frequency === 'daily') {
                return $this->generateDailyReport($adminEmail);
            } elseif ($frequency === 'weekly') {
                $dayOfWeek = date('w');
                if ($dayOfWeek == 1) { // Monday
                    return $this->generateWeeklyReport($adminEmail);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Schedule reports error: " . $e->getMessage());
            return false;
        }
    }
}
