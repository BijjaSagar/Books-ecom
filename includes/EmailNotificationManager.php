<?php
/**
 * Email Notification Management System
 * Handles queuing, sending, and tracking of email notifications
 */

class EmailNotificationManager {
    private $conn;
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_email;
    private $from_name;

    public function __construct($database_connection, $config = []) {
        $this->conn = $database_connection;
        $this->initializeTable();

        // Set email configuration (can be from config file or env vars)
        $this->smtp_host = $config['smtp_host'] ?? $_ENV['SMTP_HOST'] ?? 'localhost';
        $this->smtp_port = $config['smtp_port'] ?? $_ENV['SMTP_PORT'] ?? 587;
        $this->smtp_user = $config['smtp_user'] ?? $_ENV['SMTP_USER'] ?? '';
        $this->smtp_pass = $config['smtp_pass'] ?? $_ENV['SMTP_PASS'] ?? '';
        $this->from_email = $config['from_email'] ?? $_ENV['FROM_EMAIL'] ?? 'noreply@bookstore.com';
        $this->from_name = $config['from_name'] ?? $_ENV['FROM_NAME'] ?? 'Bookstore';
    }

    /**
     * Initialize email queue table
     */
    private function initializeTable() {
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS email_queue (
                id INT PRIMARY KEY AUTO_INCREMENT,
                to_email VARCHAR(255) NOT NULL,
                to_name VARCHAR(255),
                subject VARCHAR(255) NOT NULL,
                body_html LONGTEXT,
                body_text LONGTEXT,
                template_name VARCHAR(50),
                template_data JSON,
                related_order_id INT,
                related_customer_id INT,
                email_type VARCHAR(50),
                status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
                attempts INT DEFAULT 0,
                max_attempts INT DEFAULT 3,
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sent_at TIMESTAMP NULL,
                INDEX (status),
                INDEX (created_at)
            )"
        );

        // Create email log table
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS email_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                queue_id INT,
                to_email VARCHAR(255),
                subject VARCHAR(255),
                status VARCHAR(50),
                error TEXT,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (queue_id) REFERENCES email_queue(id) ON DELETE SET NULL
            )"
        );
    }

    /**
     * Queue an email for sending
     * @param array $email_data Email details
     * @return int Queue ID
     */
    public function queueEmail($email_data) {
        $stmt = $this->conn->prepare(
            "INSERT INTO email_queue (
                to_email, to_name, subject, body_html, body_text,
                template_name, template_data, related_order_id,
                related_customer_id, email_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $template_data = isset($email_data['template_data']) ? json_encode($email_data['template_data']) : null;

        $stmt->bind_param(
            'sssssssiii',
            $email_data['to_email'],
            $email_data['to_name'] ?? null,
            $email_data['subject'],
            $email_data['body_html'] ?? null,
            $email_data['body_text'] ?? null,
            $email_data['template_name'] ?? null,
            $template_data,
            $email_data['related_order_id'] ?? null,
            $email_data['related_customer_id'] ?? null,
            $email_data['email_type'] ?? null
        );

        if ($stmt->execute()) {
            $queue_id = $this->conn->insert_id;
            $stmt->close();
            return $queue_id;
        }

        $stmt->close();
        return 0;
    }

    /**
     * Send order confirmation email
     * @param array $order Order details
     */
    public function sendOrderConfirmation($order) {
        $template_data = [
            'order_number' => $order['order_number'],
            'order_total' => $order['total_amount'],
            'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
            'order_date' => $order['created_at'],
            'items_count' => count($order['items'] ?? [])
        ];

        $this->queueEmail([
            'to_email' => $order['customer_email'] ?? $order['email'],
            'to_name' => $order['first_name'] . ' ' . $order['last_name'],
            'subject' => "Order Confirmation - #{$order['order_number']}",
            'template_name' => 'order_confirmation',
            'template_data' => $template_data,
            'related_order_id' => $order['id'],
            'related_customer_id' => $order['user_id'] ?? null,
            'email_type' => 'order_confirmation'
        ]);
    }

    /**
     * Send order status update email
     * @param array $order Order details
     * @param string $status New status
     */
    public function sendOrderStatusUpdate($order, $status) {
        $status_messages = [
            'processing' => 'Your order is being prepared',
            'shipped' => 'Your order has been shipped',
            'delivered' => 'Your order has been delivered',
            'cancelled' => 'Your order has been cancelled'
        ];

        $template_data = [
            'order_number' => $order['order_number'],
            'status' => $status,
            'status_message' => $status_messages[$status] ?? 'Order status updated',
            'customer_name' => $order['first_name'] . ' ' . $order['last_name']
        ];

        $this->queueEmail([
            'to_email' => $order['customer_email'] ?? $order['email'],
            'to_name' => $order['first_name'] . ' ' . $order['last_name'],
            'subject' => "Order #{$order['order_number']} - " . ucfirst($status),
            'template_name' => 'order_status_update',
            'template_data' => $template_data,
            'related_order_id' => $order['id'],
            'related_customer_id' => $order['user_id'] ?? null,
            'email_type' => 'order_status'
        ]);
    }

    /**
     * Send welcome email to new customer
     * @param array $customer Customer details
     */
    public function sendWelcomeEmail($customer) {
        $template_data = [
            'customer_name' => $customer['name'],
            'email' => $customer['email']
        ];

        $this->queueEmail([
            'to_email' => $customer['email'],
            'to_name' => $customer['name'],
            'subject' => 'Welcome to ' . $this->from_name,
            'template_name' => 'welcome',
            'template_data' => $template_data,
            'related_customer_id' => $customer['id'],
            'email_type' => 'welcome'
        ]);
    }

    /**
     * Send low stock alert to admin
     * @param array $product Product details
     */
    public function sendLowStockAlert($product) {
        $admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@bookstore.com';

        $template_data = [
            'product_name' => $product['title'],
            'current_stock' => $product['stock_quantity'],
            'product_id' => $product['id']
        ];

        $this->queueEmail([
            'to_email' => $admin_email,
            'to_name' => 'Admin',
            'subject' => 'Low Stock Alert - ' . $product['title'],
            'template_name' => 'low_stock_alert',
            'template_data' => $template_data,
            'email_type' => 'admin_alert'
        ]);
    }

    /**
     * Process email queue
     * @param int $batch_size Number of emails to process at once
     * @return array Results
     */
    public function processQueue($batch_size = 10) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM email_queue
             WHERE status = 'pending' AND attempts < max_attempts
             ORDER BY created_at ASC
             LIMIT ?"
        );
        $stmt->bind_param('i', $batch_size);
        $stmt->execute();
        $result = $stmt->get_result();

        $processed = 0;
        $failed = 0;

        while ($email = $result->fetch_assoc()) {
            $send_result = $this->sendEmail($email);

            if ($send_result['success']) {
                $this->updateQueueStatus($email['id'], 'sent');
                $processed++;
            } else {
                $this->incrementAttempts($email['id'], $send_result['error']);
                $failed++;
            }
        }
        $stmt->close();

        return [
            'processed' => $processed,
            'failed' => $failed,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Send email using mail() or SMTP
     * @param array $email Email details
     * @return array Result
     */
    private function sendEmail($email) {
        try {
            // Use PHP's mail() function for simplicity (can be upgraded to SMTP later)
            $headers = [
                'From: ' . $this->from_email,
                'Reply-To: ' . $this->from_email,
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8'
            ];

            $body = $email['body_html'] ?? $this->generateTemplate($email);

            $success = mail(
                $email['to_email'],
                $email['subject'],
                $body,
                implode("\r\n", $headers)
            );

            if ($success) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Mail function failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate email from template
     * @param array $email Email data
     * @return string HTML email body
     */
    private function generateTemplate($email) {
        $data = json_decode($email['template_data'], true) ?? [];
        $template_name = $email['template_name'] ?? 'default';

        // Template directory
        $template_file = __DIR__ . "/../email_templates/{$template_name}.php";

        if (!file_exists($template_file)) {
            return $this->getDefaultTemplate($email['subject'], $data);
        }

        ob_start();
        include $template_file;
        return ob_get_clean();
    }

    /**
     * Get default email template
     * @param string $subject Email subject
     * @param array $data Template data
     * @return string HTML
     */
    private function getDefaultTemplate($subject, $data) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>{$subject}</h2>
            <p>Hello " . ($data['customer_name'] ?? 'Valued Customer') . ",</p>
            <p>We appreciate your business!</p>
            <hr>
            <p>© " . date('Y') . " " . $this->from_name . ". All rights reserved.</p>
        </body>
        </html>
        ";
    }

    /**
     * Update email queue status
     * @param int $queue_id
     * @param string $status
     */
    private function updateQueueStatus($queue_id, $status) {
        $stmt = $this->conn->prepare(
            "UPDATE email_queue SET status = ?, sent_at = NOW() WHERE id = ?"
        );
        $stmt->bind_param('si', $status, $queue_id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Increment email send attempts
     * @param int $queue_id
     * @param string $error Error message
     */
    private function incrementAttempts($queue_id, $error = '') {
        $stmt = $this->conn->prepare(
            "UPDATE email_queue SET attempts = attempts + 1, error_message = ? WHERE id = ?"
        );
        $stmt->bind_param('si', $error, $queue_id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Get email statistics
     * @return array Stats
     */
    public function getStatistics() {
        $stats = [];

        // Total sent
        $sent = $this->conn->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'sent'");
        $stats['total_sent'] = $sent->fetch_assoc()['count'];

        // Pending
        $pending = $this->conn->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'");
        $stats['pending'] = $pending->fetch_assoc()['count'];

        // Failed
        $failed = $this->conn->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'");
        $stats['failed'] = $failed->fetch_assoc()['count'];

        // By type
        $by_type = $this->conn->query(
            "SELECT email_type, COUNT(*) as count FROM email_queue GROUP BY email_type"
        );
        $stats['by_type'] = [];
        while ($row = $by_type->fetch_assoc()) {
            $stats['by_type'][$row['email_type']] = $row['count'];
        }

        return $stats;
    }
}
?>
