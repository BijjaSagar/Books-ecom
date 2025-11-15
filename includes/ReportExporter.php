<?php
/**
 * ReportExporter.php - Export reports to PDF and CSV formats
 */

class ReportExporter {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Export sales report to CSV
     */
    public function exportSalesCSV($startDate, $endDate, $filename = null) {
        try {
            if (!$filename) {
                $filename = 'sales_report_' . date('Y-m-d_His') . '.csv';
            }

            $stmt = $this->conn->prepare("
                SELECT
                    DATE(created_at) as date,
                    COUNT(id) as orders,
                    SUM(total_amount) as revenue,
                    COUNT(DISTINCT customer_id) as customers
                FROM orders
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY created_at DESC
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $csv = $this->generateCSV(['Date', 'Orders', 'Revenue', 'Customers'], $results);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $csv;
            exit;
        } catch (Exception $e) {
            error_log("Error exporting CSV: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export financial report to CSV
     */
    public function exportFinancialCSV($startDate, $endDate, $filename = null) {
        try {
            if (!$filename) {
                $filename = 'financial_report_' . date('Y-m-d_His') . '.csv';
            }

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
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $headers = ['Date', 'Revenue', 'COGS', 'Referral Fees', 'Fulfillment Fees', 'Ad Spend', 'Total Expenses', 'Gross Profit', 'Net Profit', 'Margin %'];
            $csv = $this->generateCSV($headers, $results);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $csv;
            exit;
        } catch (Exception $e) {
            error_log("Error exporting financial CSV: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export inventory report to CSV
     */
    public function exportInventoryCSV($filter = '', $filename = null) {
        try {
            if (!$filename) {
                $filename = 'inventory_report_' . date('Y-m-d_His') . '.csv';
            }

            $query = "
                SELECT
                    p.id,
                    p.name,
                    p.sku,
                    c.name as category,
                    p.stock_quantity,
                    p.price,
                    CASE
                        WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                        WHEN p.stock_quantity <= 10 THEN 'Low Stock'
                        WHEN p.stock_quantity > 100 THEN 'Overstock'
                        ELSE 'In Stock'
                    END as status
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
            ";

            if ($filter === 'low_stock') {
                $query .= " WHERE p.stock_quantity <= 10 AND p.stock_quantity > 0";
            } elseif ($filter === 'out_of_stock') {
                $query .= " WHERE p.stock_quantity = 0";
            }

            $result = $this->conn->query($query);
            $data = $result->fetch_all(MYSQLI_ASSOC);

            $headers = ['ID', 'Product Name', 'SKU', 'Category', 'Stock Quantity', 'Price', 'Status'];
            $csv = $this->generateCSV($headers, $data);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $csv;
            exit;
        } catch (Exception $e) {
            error_log("Error exporting inventory CSV: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export campaign report to CSV
     */
    public function exportCampaignCSV($filename = null) {
        try {
            if (!$filename) {
                $filename = 'campaign_report_' . date('Y-m-d_His') . '.csv';
            }

            $result = $this->conn->query("
                SELECT
                    campaign_name,
                    campaign_type,
                    status,
                    spend,
                    sales,
                    impressions,
                    clicks,
                    conversions,
                    acos,
                    roas
                FROM advertising_campaigns
                ORDER BY created_at DESC
            ");

            $data = $result->fetch_all(MYSQLI_ASSOC);

            $headers = ['Campaign Name', 'Type', 'Status', 'Spend', 'Sales', 'Impressions', 'Clicks', 'Conversions', 'ACOS', 'ROAS'];
            $csv = $this->generateCSV($headers, $data);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $csv;
            exit;
        } catch (Exception $e) {
            error_log("Error exporting campaign CSV: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate CSV content
     */
    private function generateCSV($headers, $data) {
        $csv = implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $values = [];
            foreach ($row as $value) {
                // Escape quotes and wrap in quotes if contains comma
                $value = str_replace('"', '""', $value);
                if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
                    $value = '"' . $value . '"';
                }
                $values[] = $value;
            }
            $csv .= implode(',', $values) . "\n";
        }

        return $csv;
    }

    /**
     * Generate simple HTML report (for PDF conversion)
     */
    public function generateHTMLReport($reportType, $data, $title) {
        $html = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>$title</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #007bff; color: white; padding: 10px; text-align: left; }
                td { border: 1px solid #ddd; padding: 8px; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .summary { background-color: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .metric { display: inline-block; margin-right: 20px; }
                .metric-value { font-size: 24px; font-weight: bold; color: #007bff; }
                .metric-label { color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <h1>$title</h1>
            <div class='summary'>
                <p>Report Generated: " . date('Y-m-d H:i:s') . "</p>
            </div>
            <table>
                <thead>";

        // Add headers
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $header) {
                $html .= "<th>" . ucfirst(str_replace('_', ' ', $header)) . "</th>";
            }
        }

        $html .= "</thead><tbody>";

        // Add data
        foreach ($data as $row) {
            $html .= "<tr>";
            foreach ($row as $value) {
                $html .= "<td>" . htmlspecialchars($value) . "</td>";
            }
            $html .= "</tr>";
        }

        $html .= "
            </tbody>
            </table>
        </body>
        </html>";

        return $html;
    }

    /**
     * Output PDF (requires html2pdf or similar)
     * For now, return HTML that can be printed to PDF
     */
    public function exportPDF($reportType, $data, $filename = null) {
        if (!$filename) {
            $filename = $reportType . '_report_' . date('Y-m-d_His') . '.html';
        }

        $title = ucfirst(str_replace('_', ' ', $reportType)) . ' Report';
        $html = $this->generateHTMLReport($reportType, $data, $title);

        // For actual PDF, you would use a library like TCPDF or html2pdf
        // This returns HTML that users can print to PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $html;
        exit;
    }
}
