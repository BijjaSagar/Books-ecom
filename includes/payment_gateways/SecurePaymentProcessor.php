<?php
/**
 * Secure Payment Processor - Enhanced security for payment processing
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/PaymentNotification.php';
require_once 'includes/payment_gateways/PaymentProcessor.php';

class SecurePaymentProcessor {
    private $conn;
    private $settings;
    private $payment_processor;
    private $payment_notification;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->settings = get_site_settings();
        $this->payment_processor = new PaymentProcessor();
        $this->payment_notification = new PaymentNotification($database_connection);
    }
    
    /**
     * Process secure payment with enhanced validation
     * 
     * @param array $order_data Order information
     * @param string $payment_method Selected payment method
     * @param array $customer_data Customer information
     * @return array Payment result
     */
    public function processSecurePayment($order_data, $payment_method, $customer_data) {
        // Validate inputs
        $validation_result = $this->validatePaymentData($order_data, $payment_method, $customer_data);
        if (!$validation_result['valid']) {
            return [
                'success' => false,
                'error' => $validation_result['error']
            ];
        }
        
        // Check for duplicate orders (prevent double submissions)
        if ($this->isDuplicateOrder($order_data['order_number'])) {
            return [
                'success' => false,
                'error' => 'Duplicate order detected. Please check your order history.'
            ];
        }
        
        // Log payment attempt
        $this->logPaymentAttempt($order_data, $payment_method, $customer_data);
        
        try {
            // Process payment through main processor
            $payment_result = $this->payment_processor->processPayment($order_data, $payment_method);
            
            // Log payment result in status log
            $this->payment_notification->logPaymentStatus(
                $order_data['order_id'],
                $order_data['order_number'],
                $payment_method,
                $payment_result['success'] ? 'success' : 'failed',
                $order_data['total_amount'],
                $payment_result['order_id'] ?? null,
                $payment_method,
                $payment_result['error'] ?? ''
            );
            
            // Send notifications
            if ($payment_result['success']) {
                // Get order details for notifications
                $order_stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
                $order_stmt->bind_param("i", $order_data['order_id']);
                $order_stmt->execute();
                $order_result = $order_stmt->get_result();
                $order = $order_result->fetch_assoc();
                
                if ($order) {
                    // Send customer notification
                    $this->payment_notification->sendCustomerNotification($order, 'success', $payment_method);
                    
                    // Send admin notification
                    $this->payment_notification->sendAdminNotification($order, 'success', $payment_method);
                }
            }
            
            // Log payment result
            $this->logPaymentResult($order_data['order_number'], $payment_result);
            
            return $payment_result;
            
        } catch (Exception $e) {
            error_log("Secure payment processing error: " . $e->getMessage());
            
            // Log payment failure in status log
            $this->payment_notification->logPaymentStatus(
                $order_data['order_id'],
                $order_data['order_number'],
                $payment_method,
                'failed',
                $order_data['total_amount'],
                null,
                $payment_method,
                $e->getMessage()
            );
            
            // Log payment failure
            $this->logPaymentFailure($order_data['order_number'], $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Payment processing failed. Please try again.'
            ];
        }
    }
    
    /**
     * Validate payment data
     * 
     * @param array $order_data Order information
     * @param string $payment_method Payment method
     * @param array $customer_data Customer information
     * @return array Validation result
     */
    private function validatePaymentData($order_data, $payment_method, $customer_data) {
        // Validate required fields
        $required_fields = ['order_id', 'order_number', 'customer_email', 'first_name', 'last_name', 'total_amount'];
        foreach ($required_fields as $field) {
            if (empty($order_data[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: {$field}"
                ];
            }
        }
        
        // Validate email format
        if (!filter_var($order_data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'Invalid email format'
            ];
        }
        
        // Validate amount
        if (!is_numeric($order_data['total_amount']) || $order_data['total_amount'] <= 0) {
            return [
                'valid' => false,
                'error' => 'Invalid payment amount'
            ];
        }
        
        // Validate payment method
        $valid_methods = ['razorpay', 'paypal', 'credit_card'];
        if (!in_array($payment_method, $valid_methods)) {
            return [
                'valid' => false,
                'error' => 'Invalid payment method'
            ];
        }
        
        // Validate customer data
        if (empty($customer_data['ip_address'])) {
            return [
                'valid' => false,
                'error' => 'Missing IP address'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check for duplicate orders
     * 
     * @param string $order_number Order number
     * @return bool True if duplicate order exists
     */
    private function isDuplicateOrder($order_number) {
        $stmt = $this->conn->prepare("SELECT id FROM orders WHERE order_number = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stmt->bind_param("s", $order_number);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    /**
     * Verify payment with enhanced security
     * 
     * @param string $gateway Payment gateway
     * @param array $payment_data Payment verification data
     * @param string $order_number Order number
     * @return array Verification result
     */
    public function verifySecurePayment($gateway, $payment_data, $order_number) {
        // Log verification attempt
        $this->logVerificationAttempt($gateway, $order_number, $payment_data);
        
        try {
            // Get order details for verification
            $order_stmt = $this->conn->prepare("SELECT id, total_amount, order_status FROM orders WHERE order_number = ?");
            $order_stmt->bind_param("s", $order_number);
            $order_stmt->execute();
            $order_result = $order_stmt->get_result();
            $order = $order_result->fetch_assoc();
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            // Check if order is already processed
            if ($order['order_status'] !== 'pending') {
                throw new Exception("Order already processed");
            }
            
            // Verify payment through main processor
            $verification_result = $this->payment_processor->verifyPayment($gateway, $payment_data);
            
            // Log verification result in status log
            $this->payment_notification->logPaymentStatus(
                $order['id'],
                $order_number,
                $gateway,
                $verification_result ? 'verified' : 'verification_failed',
                $order['total_amount'],
                $payment_data['razorpay_payment_id'] ?? $payment_data['order_id'] ?? null,
                $gateway,
                $verification_result ? '' : 'Payment verification failed'
            );
            
            // Send notifications for successful verification
            if ($verification_result) {
                // Get full order details for notifications
                $full_order_stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_number = ?");
                $full_order_stmt->bind_param("s", $order_number);
                $full_order_stmt->execute();
                $full_order_result = $full_order_stmt->get_result();
                $full_order = $full_order_result->fetch_assoc();
                
                if ($full_order) {
                    // Send customer notification
                    $this->payment_notification->sendCustomerNotification($full_order, 'verified', $gateway);
                    
                    // Send admin notification
                    $this->payment_notification->sendAdminNotification($full_order, 'verified', $gateway);
                }
            }
            
            // Log verification result
            $this->logVerificationResult($order_number, $verification_result);
            
            return [
                'success' => $verification_result,
                'order_number' => $order_number
            ];
            
        } catch (Exception $e) {
            error_log("Payment verification error: " . $e->getMessage());
            
            // Log verification failure
            $this->logVerificationFailure($order_number, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Log payment attempt
     * 
     * @param array $order_data Order information
     * @param string $payment_method Payment method
     * @param array $customer_data Customer information
     */
    private function logPaymentAttempt($order_data, $payment_method, $customer_data) {
        $log_data = [
            'order_number' => $order_data['order_number'],
            'customer_email' => $order_data['customer_email'],
            'amount' => $order_data['total_amount'],
            'payment_method' => $payment_method,
            'ip_address' => $customer_data['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // In a production environment, you would log this to a secure log file or database
        error_log("Payment attempt: " . json_encode($log_data));
    }
    
    /**
     * Log payment result
     * 
     * @param string $order_number Order number
     * @param array $result Payment result
     */
    private function logPaymentResult($order_number, $result) {
        error_log("Payment result for order {$order_number}: " . json_encode($result));
    }
    
    /**
     * Log payment failure
     * 
     * @param string $order_number Order number
     * @param string $error Error message
     */
    private function logPaymentFailure($order_number, $error) {
        error_log("Payment failure for order {$order_number}: {$error}");
    }
    
    /**
     * Log verification attempt
     * 
     * @param string $gateway Payment gateway
     * @param string $order_number Order number
     * @param array $payment_data Payment data
     */
    private function logVerificationAttempt($gateway, $order_number, $payment_data) {
        $log_data = [
            'gateway' => $gateway,
            'order_number' => $order_number,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR']
        ];
        
        error_log("Payment verification attempt: " . json_encode($log_data));
    }
    
    /**
     * Log verification result
     * 
     * @param string $order_number Order number
     * @param bool $result Verification result
     */
    private function logVerificationResult($order_number, $result) {
        error_log("Payment verification result for order {$order_number}: " . ($result ? 'SUCCESS' : 'FAILED'));
    }
    
    /**
     * Log verification failure
     * 
     * @param string $order_number Order number
     * @param string $error Error message
     */
    private function logVerificationFailure($order_number, $error) {
        error_log("Payment verification failure for order {$order_number}: {$error}");
    }
    
    /**
     * Sanitize input data
     * 
     * @param mixed $data Input data
     * @return mixed Sanitized data
     */
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate secure token for payment sessions
     * 
     * @param string $order_number Order number
     * @return string Secure token
     */
    public function generateSecureToken($order_number) {
        $token_data = [
            'order_number' => $order_number,
            'timestamp' => time(),
            'salt' => bin2hex(random_bytes(16))
        ];
        
        return hash_hmac('sha256', json_encode($token_data), $this->settings['site_secret'] ?? 'default_secret');
    }
    
    /**
     * Validate secure token
     * 
     * @param string $token Token to validate
     * @param string $order_number Expected order number
     * @return bool True if token is valid
     */
    public function validateSecureToken($token, $order_number) {
        $expected_token = $this->generateSecureToken($order_number);
        return hash_equals($expected_token, $token);
    }
}