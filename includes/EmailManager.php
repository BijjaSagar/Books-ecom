<?php
/**
 * Email Manager Class
 * Manages all customer notification emails
 * - Email queue system
 * - Template rendering
 * - SMTP delivery
 * - Email history tracking
 *
 * @version 1.0
 * @author Books eCommerce Platform
 */

class EmailManager {
    private $conn;
    private $smtp_host = SMTP_HOST ?? 'localhost';
    private $smtp_port = SMTP_PORT ?? 587;
    private $smtp_user = SMTP_USER ?? '';
    private $smtp_pass = SMTP_PASS ?? '';
    private $from_email = FROM_EMAIL ?? 'noreply@booksecom.com';
    private $from_name = FROM_NAME ?? 'Books eCommerce';
    private $template_dir = '';

    public function __construct($db) {
        $this->conn = $db;
        $this->template_dir = __DIR__ . '/../email_templates';
    }

    /**
     * Queue email for sending
     */
    public function queueEmail($customer_id, $email_address, $subject, $template_name, $template_vars = []) {
        try {
            // Ensure queue table exists
            $this->ensureQueueTableExists();

            $stmt = $this->conn->prepare("
                INSERT INTO email_queue (
                    customer_id, recipient_email, subject,
                    template_name, template_vars, status
                ) VALUES (?, ?, ?, ?, ?, 'pending')
            ");

            $template_vars_json = json_encode($template_vars);

            $stmt->bind_param(
                "issss",
                $customer_id,
                $email_address,
                $subject,
                $template_name,
                $template_vars_json
            );

            if (!$stmt->execute()) {
                return ['success' => false, 'error' => 'Failed to queue email'];
            }

            return ['success' => true, 'queue_id' => $stmt->insert_id];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email immediately
     */
    public function sendEmail($to_email, $subject, $template_name, $template_vars = [], $customer_id = null) {
        try {
            // Render template
            $body_html = $this->renderTemplate($template_name, $template_vars);

            if (!$body_html) {
                return ['success' => false, 'error' => 'Template not found'];
            }

            // Create email headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "Reply-To: {$this->from_email}\r\n";
            $headers .= "X-Mailer: Books eCommerce\r\n";

            // Send using PHP mail function (can be replaced with SMTP class)
            $result = mail($to_email, $subject, $body_html, $headers);

            if ($result) {
                // Log email sent
                $this->logEmailSent($customer_id, $to_email, $subject, $template_name, 'sent');

                return ['success' => true, 'message' => 'Email sent successfully'];
            } else {
                $this->logEmailSent($customer_id, $to_email, $subject, $template_name, 'failed');

                return ['success' => false, 'error' => 'Failed to send email'];
            }

        } catch (Exception $e) {
            error_log("[EmailManager] Send Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Render email template with variables
     */
    private function renderTemplate($template_name, $template_vars = []) {
        try {
            $template_file = $this->template_dir . '/' . basename($template_name) . '.php';

            if (!file_exists($template_file)) {
                error_log("Email template not found: " . $template_file);
                return null;
            }

            // Extract variables for use in template
            extract($template_vars);

            // Capture template output
            ob_start();
            include $template_file;
            $html = ob_get_clean();

            return $html;

        } catch (Exception $e) {
            error_log("[EmailManager] Template Render Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($order_id, $customer_id, $customer_email) {
        try {
            // Get order details
            $stmt = $this->conn->prepare("
                SELECT o.order_number, o.total, o.currency, o.created_at,
                       u.name as customer_name
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ? AND o.customer_id = ?
            ");

            $stmt->bind_param("ii", $order_id, $customer_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            // Get order items
            $items_stmt = $this->conn->prepare("
                SELECT p.title, oi.quantity, oi.price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");

            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();

            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }

            $template_vars = [
                'customer_name' => $order['customer_name'],
                'order_number' => $order['order_number'],
                'order_total' => $order['total'],
                'currency' => $order['currency'],
                'order_date' => date('F j, Y', strtotime($order['created_at'])),
                'items' => $items,
                'order_url' => 'https://yourdomain.com/order/' . $order_id,
                'support_email' => 'support@booksecom.com'
            ];

            return $this->sendEmail(
                $customer_email,
                'Order Confirmation: ' . $order['order_number'],
                'order_confirmation',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send shipment notification
     */
    public function sendShipmentNotification($order_id, $customer_id, $customer_email, $tracking_info) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.order_number, u.name as customer_name
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ? AND o.customer_id = ?
            ");

            $stmt->bind_param("ii", $order_id, $customer_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $template_vars = [
                'customer_name' => $order['customer_name'],
                'order_number' => $order['order_number'],
                'tracking_number' => $tracking_info['tracking_number'] ?? '',
                'carrier' => $tracking_info['carrier'] ?? '',
                'estimated_delivery' => $tracking_info['estimated_delivery'] ?? '',
                'tracking_url' => $tracking_info['tracking_url'] ?? '',
                'order_url' => 'https://yourdomain.com/order/' . $order_id
            ];

            return $this->sendEmail(
                $customer_email,
                'Your Order Has Shipped: ' . $order['order_number'],
                'shipment_notification',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send delivery confirmation
     */
    public function sendDeliveryConfirmation($order_id, $customer_id, $customer_email) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.order_number, o.total, o.currency, u.name as customer_name
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ? AND o.customer_id = ?
            ");

            $stmt->bind_param("ii", $order_id, $customer_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $template_vars = [
                'customer_name' => $order['customer_name'],
                'order_number' => $order['order_number'],
                'order_total' => $order['total'],
                'currency' => $order['currency'],
                'order_url' => 'https://yourdomain.com/order/' . $order_id,
                'review_url' => 'https://yourdomain.com/review/' . $order_id
            ];

            return $this->sendEmail(
                $customer_email,
                'Your Order Has Arrived: ' . $order['order_number'],
                'delivery_confirmation',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send refund notification
     */
    public function sendRefundNotification($order_id, $customer_id, $customer_email, $refund_amount, $reason = '') {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.order_number, o.currency, u.name as customer_name
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ? AND o.customer_id = ?
            ");

            $stmt->bind_param("ii", $order_id, $customer_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            $template_vars = [
                'customer_name' => $order['customer_name'],
                'order_number' => $order['order_number'],
                'refund_amount' => $refund_amount,
                'currency' => $order['currency'],
                'reason' => $reason,
                'order_url' => 'https://yourdomain.com/order/' . $order_id
            ];

            return $this->sendEmail(
                $customer_email,
                'Refund Processed: ' . $order['order_number'],
                'refund_notification',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send review request email
     */
    public function sendReviewRequest($order_id, $customer_id, $customer_email, $product_ids) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.order_number, u.name as customer_name
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ? AND o.customer_id = ?
            ");

            $stmt->bind_param("ii", $order_id, $customer_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                return ['success' => false, 'error' => 'Order not found'];
            }

            // Get products
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $types = str_repeat('i', count($product_ids));

            $products_stmt = $this->conn->prepare("
                SELECT id, title FROM products WHERE id IN ($placeholders)
            ");

            $products_stmt->bind_param($types, ...$product_ids);
            $products_stmt->execute();
            $products_result = $products_stmt->get_result();

            $products = [];
            while ($product = $products_result->fetch_assoc()) {
                $products[] = $product;
            }

            $template_vars = [
                'customer_name' => $order['customer_name'],
                'order_number' => $order['order_number'],
                'products' => $products,
                'review_url' => 'https://yourdomain.com/review/' . $order_id
            ];

            return $this->sendEmail(
                $customer_email,
                'Share Your Experience - Review Your Purchase',
                'review_request',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send promotional email
     */
    public function sendPromotion($customer_id, $customer_email, $promo_data) {
        try {
            $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            $template_vars = [
                'customer_name' => $user['name'],
                'promo_title' => $promo_data['title'] ?? 'Special Offer',
                'promo_description' => $promo_data['description'] ?? '',
                'discount_code' => $promo_data['discount_code'] ?? '',
                'discount_percent' => $promo_data['discount_percent'] ?? '',
                'offer_expires' => $promo_data['expires'] ?? '',
                'shop_url' => 'https://yourdomain.com/shop',
                'banner_image' => $promo_data['banner_image'] ?? ''
            ];

            return $this->sendEmail(
                $customer_email,
                $promo_data['subject'] ?? 'Special Offer Just for You!',
                'promotional',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send newsletter
     */
    public function sendNewsletter($customer_id, $customer_email, $newsletter_data) {
        try {
            $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            $template_vars = [
                'customer_name' => $user['name'],
                'newsletter_title' => $newsletter_data['title'] ?? 'Monthly Newsletter',
                'newsletter_content' => $newsletter_data['content'] ?? '',
                'featured_products' => $newsletter_data['featured_products'] ?? [],
                'featured_articles' => $newsletter_data['featured_articles'] ?? [],
                'shop_url' => 'https://yourdomain.com/shop',
                'unsubscribe_url' => 'https://yourdomain.com/unsubscribe/' . $customer_id
            ];

            return $this->sendEmail(
                $customer_email,
                $newsletter_data['subject'] ?? 'Our Monthly Newsletter',
                'newsletter',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send support ticket response
     */
    public function sendTicketResponse($customer_id, $customer_email, $ticket_number, $response) {
        try {
            $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                return ['success' => false, 'error' => 'Customer not found'];
            }

            $template_vars = [
                'customer_name' => $user['name'],
                'ticket_number' => $ticket_number,
                'response_message' => $response,
                'ticket_url' => 'https://yourdomain.com/support/ticket/' . $ticket_number
            ];

            return $this->sendEmail(
                $customer_email,
                'Support Response: ' . $ticket_number,
                'ticket_response',
                $template_vars,
                $customer_id
            );

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log email sent/failed
     */
    private function logEmailSent($customer_id, $email, $subject, $template, $status) {
        try {
            $this->ensureHistoryTableExists();

            $stmt = $this->conn->prepare("
                INSERT INTO email_history (customer_id, recipient_email, subject, template, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("issss", $customer_id, $email, $subject, $template, $status);
            $stmt->execute();

        } catch (Exception $e) {
            error_log("[EmailManager] Log Error: " . $e->getMessage());
        }
    }

    /**
     * Ensure email queue table exists
     */
    private function ensureQueueTableExists() {
        $sql = "
            CREATE TABLE IF NOT EXISTS email_queue (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_id INT,
                recipient_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                template_name VARCHAR(100) NOT NULL,
                template_vars LONGTEXT,
                status ENUM('pending', 'sending', 'sent', 'failed') DEFAULT 'pending',
                attempts INT DEFAULT 0,
                last_error TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->conn->query($sql);
    }

    /**
     * Ensure email history table exists
     */
    private function ensureHistoryTableExists() {
        $sql = "
            CREATE TABLE IF NOT EXISTS email_history (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_id INT,
                recipient_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                template VARCHAR(100) NOT NULL,
                status ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_customer (customer_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->conn->query($sql);
    }
}
?>
