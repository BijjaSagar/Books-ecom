<?php
/**
 * Data Export Manager
 * Handles exporting reports to CSV, PDF, and Excel formats
 */

class ExportManager {
    private $conn;
    private $currency_symbol = '₹';

    public function __construct($database_connection, $currency_symbol = '₹') {
        $this->conn = $database_connection;
        $this->currency_symbol = $currency_symbol;
    }

    /**
     * Export orders to CSV
     * @param array $filters Filter criteria
     * @return string CSV content
     */
    public function exportOrdersCSV($filters = []) {
        $orders = $this->getOrders($filters);

        // Create CSV header
        $csv = "Order ID,Order Number,Customer Name,Email,Total Amount,Status,Created Date\n";

        foreach ($orders as $order) {
            $csv .= implode(',', [
                $order['id'],
                $order['order_number'],
                $order['first_name'] . ' ' . $order['last_name'],
                $order['customer_email'] ?? $order['email'],
                number_format($order['total_amount'], 2),
                ucfirst($order['order_status']),
                date('Y-m-d H:i:s', strtotime($order['created_at']))
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export orders to Excel format (CSV that opens in Excel)
     * @param array $filters Filter criteria
     * @return string Excel-compatible CSV
     */
    public function exportOrdersExcel($filters = []) {
        $orders = $this->getOrders($filters);

        // Excel CSV format with BOM
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= "Order ID,Order Number,Customer,Email,Phone,Address,Total Amount,Status,Order Date\n";

        foreach ($orders as $order) {
            $csv .= implode(',', [
                '"' . $order['id'] . '"',
                '"' . $order['order_number'] . '"',
                '"' . $order['first_name'] . ' ' . $order['last_name'] . '"',
                '"' . ($order['customer_email'] ?? $order['email']) . '"',
                '"' . ($order['phone'] ?? '') . '"',
                '"' . ($order['address_line1'] ?? '') . ', ' . ($order['city'] ?? '') . '"',
                number_format($order['total_amount'], 2),
                ucfirst($order['order_status']),
                date('Y-m-d', strtotime($order['created_at']))
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export sales report
     * @param string $start_date
     * @param string $end_date
     * @return string CSV content
     */
    public function exportSalesReportCSV($start_date, $end_date) {
        $stmt = $this->conn->prepare(
            "SELECT
                DATE(created_at) as sale_date,
                COUNT(*) as orders_count,
                SUM(total_amount) as daily_sales,
                AVG(total_amount) as avg_order_value,
                COUNT(DISTINCT user_id) as unique_customers
             FROM orders
             WHERE order_status IN ('completed', 'delivered')
             AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY sale_date ASC"
        );

        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $csv = "Date,Orders,Total Sales,Average Order Value,Unique Customers\n";

        while ($row = $result->fetch_assoc()) {
            $csv .= implode(',', [
                $row['sale_date'],
                $row['orders_count'],
                $this->currency_symbol . number_format($row['daily_sales'], 2),
                $this->currency_symbol . number_format($row['avg_order_value'], 2),
                $row['unique_customers']
            ]) . "\n";
        }

        $stmt->close();
        return $csv;
    }

    /**
     * Export products with inventory
     * @return string CSV content
     */
    public function exportProductsCSV() {
        $stmt = $this->conn->query(
            "SELECT id, title, author, price, stock_quantity, status, rating, review_count
             FROM products
             ORDER BY title ASC"
        );

        $csv = "Product ID,Title,Author,Price,Stock,Status,Rating,Reviews\n";

        while ($row = $stmt->fetch_assoc()) {
            $csv .= implode(',', [
                $row['id'],
                '"' . $row['title'] . '"',
                '"' . $row['author'] . '"',
                $this->currency_symbol . number_format($row['price'], 2),
                $row['stock_quantity'],
                ucfirst($row['status']),
                $row['rating'] ?? '0',
                $row['review_count'] ?? '0'
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export customer list
     * @return string CSV content
     */
    public function exportCustomersCSV() {
        $stmt = $this->conn->query(
            "SELECT id, name, email, phone, created_at,
                    (SELECT COUNT(*) FROM orders WHERE user_id = users.id) as total_orders,
                    (SELECT SUM(total_amount) FROM orders WHERE user_id = users.id) as total_spent
             FROM users
             WHERE role = 'customer'
             ORDER BY created_at DESC"
        );

        $csv = "Customer ID,Name,Email,Phone,Registered,Total Orders,Total Spent\n";

        while ($row = $stmt->fetch_assoc()) {
            $csv .= implode(',', [
                $row['id'],
                '"' . $row['name'] . '"',
                '"' . $row['email'] . '"',
                '"' . ($row['phone'] ?? '') . '"',
                date('Y-m-d', strtotime($row['created_at'])),
                $row['total_orders'] ?? 0,
                $this->currency_symbol . number_format($row['total_spent'] ?? 0, 2)
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Export specific order details with items
     * @param int $order_id
     * @return string CSV content
     */
    public function exportOrderDetailCSV($order_id) {
        $order = $this->conn->prepare(
            "SELECT * FROM orders WHERE id = ?"
        );
        $order->bind_param('i', $order_id);
        $order->execute();
        $order_data = $order->get_result()->fetch_assoc();
        $order->close();

        if (!$order_data) {
            return '';
        }

        // Order header
        $csv = "Order Details - #{$order_data['order_number']}\n";
        $csv .= "Export Date," . date('Y-m-d H:i:s') . "\n\n";
        $csv .= "Customer Information\n";
        $csv .= "Name,{$order_data['first_name']} {$order_data['last_name']}\n";
        $csv .= "Email,{$order_data['customer_email']}\n";
        $csv .= "Phone,{$order_data['phone']}\n";
        $csv .= "Address,\"{$order_data['address_line1']}, {$order_data['city']}\"\n\n";
        $csv .= "Order Summary\n";
        $csv .= "Order Date," . date('Y-m-d', strtotime($order_data['created_at'])) . "\n";
        $csv .= "Status," . ucfirst($order_data['order_status']) . "\n";
        $csv .= "Total Amount," . $this->currency_symbol . number_format($order_data['total_amount'], 2) . "\n\n";
        $csv .= "Order Items\n";
        $csv .= "Product,Quantity,Price,Total\n";

        // Get order items
        $items = $this->conn->prepare(
            "SELECT oi.*, p.title FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $items->bind_param('i', $order_id);
        $items->execute();
        $items_result = $items->get_result();

        while ($item = $items_result->fetch_assoc()) {
            $csv .= implode(',', [
                '"' . $item['title'] . '"',
                $item['quantity'],
                $this->currency_symbol . number_format($item['price'], 2),
                $this->currency_symbol . number_format($item['total'], 2)
            ]) . "\n";
        }
        $items->close();

        return $csv;
    }

    /**
     * Generate PDF (requires external library - returning HTML for now)
     * @param string $html HTML content
     * @param string $filename Output filename
     * @return string PDF or HTML
     */
    public function generatePDF($html, $filename) {
        // This would require a PDF library like TCPDF or mPDF
        // For now, returning the HTML that could be printed to PDF
        return $html;
    }

    /**
     * Get orders with filters
     * @param array $filters Filter criteria
     * @return array Orders
     */
    private function getOrders($filters = []) {
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['status'])) {
            $where[] = "order_status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['start_date'])) {
            $where[] = "DATE(created_at) >= ?";
            $params[] = $filters['start_date'];
            $types .= 's';
        }

        if (!empty($filters['end_date'])) {
            $where[] = "DATE(created_at) <= ?";
            $params[] = $filters['end_date'];
            $types .= 's';
        }

        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT * FROM orders $where_clause ORDER BY created_at DESC";

        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conn->query($sql);
        }

        $orders = [];
        while ($order = $result->fetch_assoc()) {
            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * Generate HTML report for PDF conversion
     * @param array $data Report data
     * @param string $title Report title
     * @return string HTML
     */
    public function generateHTMLReport($data, $title) {
        $html = "<html><head><title>{$title}</title>";
        $html .= "<style>";
        $html .= "body { font-family: Arial, sans-serif; margin: 20px; }";
        $html .= "h1 { color: #1e40af; }";
        $html .= "table { width: 100%; border-collapse: collapse; margin: 20px 0; }";
        $html .= "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }";
        $html .= "th { background-color: #1e40af; color: white; }";
        $html .= "tr:hover { background-color: #f5f5f5; }";
        $html .= ".total { font-weight: bold; background-color: #f0f0f0; }";
        $html .= "</style></head><body>";
        $html .= "<h1>{$title}</h1>";
        $html .= "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";
        $html .= $this->arrayToHtmlTable($data);
        $html .= "</body></html>";

        return $html;
    }

    /**
     * Convert array to HTML table
     * @param array $data Array data
     * @return string HTML table
     */
    private function arrayToHtmlTable($data) {
        if (empty($data)) {
            return "<p>No data available</p>";
        }

        $html = "<table>";
        $headers = array_keys($data[0]);

        // Headers
        $html .= "<thead><tr>";
        foreach ($headers as $header) {
            $html .= "<th>" . ucfirst(str_replace('_', ' ', $header)) . "</th>";
        }
        $html .= "</tr></thead>";

        // Rows
        $html .= "<tbody>";
        foreach ($data as $row) {
            $html .= "<tr>";
            foreach ($row as $value) {
                $html .= "<td>" . htmlspecialchars($value) . "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        return $html;
    }
}
?>
