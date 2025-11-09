<?php
/**
 * Payment Notification System
 * Handles payment status tracking and customer/admin notifications
 */

class PaymentNotification {
    private $conn;
    private $settings;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->settings = get_site_settings();
    }
    
    /**
     * Log payment status
     * 
     * @param int $order_id Order ID
     * @param string $order_number Order number
     * @param string $gateway Payment gateway
     * @param string $status Payment status
     * @param float $amount Payment amount
     * @param string $transaction_id Transaction ID
     * @param string $payment_method Payment method
     * @param string $notes Additional notes
     * @return bool True if logged successfully
     */
    public function logPaymentStatus($order_id, $order_number, $gateway, $status, $amount = null, $transaction_id = null, $payment_method = null, $notes = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO payment_status_log 
                (order_id, order_number, payment_gateway, status, amount, transaction_id, payment_method, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("isssdssss", 
                $order_id, 
                $order_number, 
                $gateway, 
                $status, 
                $amount, 
                $transaction_id, 
                $payment_method, 
                $notes
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Payment status logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment status history for an order
     * 
     * @param int $order_id Order ID
     * @return array Payment status history
     */
    public function getPaymentStatusHistory($order_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM payment_status_log 
                WHERE order_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            $stmt->close();
            return $history;
            
        } catch (Exception $e) {
            error_log("Payment status history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send payment notification to customer
     * 
     * @param array $order Order details
     * @param string $status Payment status
     * @param string $gateway Payment gateway
     * @return bool True if notification sent successfully
     */
    public function sendCustomerNotification($order, $status, $gateway) {
        // In a real implementation, this would send an email/SMS notification
        // For now, we'll just log it
        
        $notification_data = [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'customer_email' => $order['customer_email'],
            'status' => $status,
            'gateway' => $gateway,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log("Customer payment notification: " . json_encode($notification_data));
        
        // Here you would implement actual email sending
        // Example:
        // $this->sendEmail($order['customer_email'], 'Payment Status Update', $this->getCustomerEmailTemplate($order, $status, $gateway));
        
        return true;
    }
    
    /**
     * Send payment notification to admin
     * 
     * @param array $order Order details
     * @param string $status Payment status
     * @param string $gateway Payment gateway
     * @return bool True if notification sent successfully
     */
    public function sendAdminNotification($order, $status, $gateway) {
        // In a real implementation, this would send an email/SMS notification to admin
        // For now, we'll just log it
        
        $notification_data = [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'status' => $status,
            'gateway' => $gateway,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log("Admin payment notification: " . json_encode($notification_data));
        
        // Here you would implement actual email sending to admin
        // Example:
        // $admin_email = $this->settings['admin_email'] ?? 'admin@bookshelf.com';
        // $this->sendEmail($admin_email, 'New Payment Received', $this->getAdminEmailTemplate($order, $status, $gateway));
        
        return true;
    }
    
    /**
     * Get customer email template
     * 
     * @param array $order Order details
     * @param string $status Payment status
     * @param string $gateway Payment gateway
     * @return string Email template
     */
    private function getCustomerEmailTemplate($order, $status, $gateway) {
        $site_name = $this->settings['site_name'] ?? 'Bookstore';
        $amount = format_price($order['total_amount']);
        
        $status_messages = [
            'success' => "Your payment of {$amount} has been successfully processed.",
            'failed' => "Your payment of {$amount} has failed. Please try again or contact support.",
            'pending' => "Your payment of {$amount} is being processed.",
            'refunded' => "Your payment of {$amount} has been refunded to your original payment method."
        ];
        
        $message = $status_messages[$status] ?? "Your payment status has been updated to: " . ucfirst($status);
        
        return "
        <html>
        <body>
            <h2>Payment Status Update - {$site_name}</h2>
            <p>Dear {$order['first_name']} {$order['last_name']},</p>
            <p>{$message}</p>
            <p><strong>Order Number:</strong> {$order['order_number']}</p>
            <p><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $gateway)) . "</p>
            <p><strong>Amount:</strong> {$amount}</p>
            <p>You can view your order details in your account or contact us if you have any questions.</p>
            <p>Thank you for shopping with us!</p>
            <p>Best regards,<br>{$site_name} Team</p>
        </body>
        </html>
        ";
    }
    
    /**
     * Get admin email template
     * 
     * @param array $order Order details
     * @param string $status Payment status
     * @param string $gateway Payment gateway
     * @return string Email template
     */
    private function getAdminEmailTemplate($order, $status, $gateway) {
        $site_name = $this->settings['site_name'] ?? 'Bookstore';
        $amount = format_price($order['total_amount']);
        
        return "
        <html>
        <body>
            <h2>New Payment Notification - {$site_name}</h2>
            <p><strong>Order Number:</strong> {$order['order_number']}</p>
            <p><strong>Customer:</strong> {$order['first_name']} {$order['last_name']} ({$order['customer_email']})</p>
            <p><strong>Amount:</strong> {$amount}</p>
            <p><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $gateway)) . "</p>
            <p><strong>Status:</strong> " . ucfirst($status) . "</p>
            <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
        </body>
        </html>
        ";
    }
    
    /**
     * Send email notification
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message
     * @return bool True if email sent successfully
     */
    private function sendEmail($to, $subject, $message) {
        // In a real implementation, you would use PHPMailer or similar
        // For now, we'll use PHP's mail function as an example
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . ($this->settings['site_email'] ?? 'noreply@bookshelf.com') . "\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get recent payment activities
     * 
     * @param int $limit Number of recent activities to fetch
     * @return array Recent payment activities
     */
    public function getRecentPaymentActivities($limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT psl.*, o.order_number, o.customer_email, o.total_amount
                FROM payment_status_log psl
                JOIN orders o ON psl.order_id = o.id
                ORDER BY psl.created_at DESC
                LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            
            $stmt->close();
            return $activities;
            
        } catch (Exception $e) {
            error_log("Recent payment activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment statistics
     * 
     * @return array Payment statistics
     */
    public function getPaymentStatistics() {
        try {
            $stats = [
                'total_payments' => 0,
                'successful_payments' => 0,
                'failed_payments' => 0,
                'pending_payments' => 0,
                'refunded_payments' => 0,
                'total_amount' => 0
            ];
            
            // Total payments
            $total_result = $this->conn->query("
                SELECT COUNT(*) as count, SUM(amount) as total_amount
                FROM payment_status_log
                WHERE amount IS NOT NULL
            ");
            if ($total_row = $total_result->fetch_assoc()) {
                $stats['total_payments'] = $total_row['count'];
                $stats['total_amount'] = $total_row['total_amount'] ?? 0;
            }
            
            // Successful payments
            $success_result = $this->conn->query("
                SELECT COUNT(*) as count
                FROM payment_status_log
                WHERE status = 'success'
            ");
            if ($success_row = $success_result->fetch_assoc()) {
                $stats['successful_payments'] = $success_row['count'];
            }
            
            // Failed payments
            $failed_result = $this->conn->query("
                SELECT COUNT(*) as count
                FROM payment_status_log
                WHERE status = 'failed'
            ");
            if ($failed_row = $failed_result->fetch_assoc()) {
                $stats['failed_payments'] = $failed_row['count'];
            }
            
            // Pending payments
            $pending_result = $this->conn->query("
                SELECT COUNT(*) as count
                FROM payment_status_log
                WHERE status = 'pending'
            ");
            if ($pending_row = $pending_result->fetch_assoc()) {
                $stats['pending_payments'] = $pending_row['count'];
            }
            
            // Refunded payments
            $refunded_result = $this->conn->query("
                SELECT COUNT(*) as count
                FROM payment_status_log
                WHERE status = 'refunded'
            ");
            if ($refunded_row = $refunded_result->fetch_assoc()) {
                $stats['refunded_payments'] = $refunded_row['count'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Payment statistics error: " . $e->getMessage());
            return [];
        }
    }
}