<?php
/**
 * Razorpay Payment Gateway Integration
 * For Indian market e-commerce transactions
 */

class RazorpayGateway {
    private $key_id;
    private $key_secret;
    private $is_test_mode;
    
    public function __construct($key_id, $key_secret, $is_test_mode = true) {
        $this->key_id = $key_id;
        $this->key_secret = $key_secret;
        $this->is_test_mode = $is_test_mode;
    }
    
    /**
     * Create a payment order
     * 
     * @param array $order_data Order details including amount, currency, etc.
     * @return array Payment order details
     */
    public function createOrder($order_data) {
        // Validate required fields
        if (!isset($order_data['amount']) || !isset($order_data['currency'])) {
            throw new Exception("Amount and currency are required");
        }
        
        // Convert amount to smallest currency unit (paise for INR)
        $amount_in_paise = $order_data['amount'] * 100;
        
        // Prepare order data for Razorpay
        $razorpay_order_data = [
            'amount' => $amount_in_paise,
            'currency' => $order_data['currency'] ?? 'INR',
            'receipt' => $order_data['receipt'] ?? uniqid('order_'),
            'payment_capture' => 1 // Auto capture
        ];
        
        // Add any additional notes
        if (isset($order_data['notes'])) {
            $razorpay_order_data['notes'] = $order_data['notes'];
        }
        
        try {
            $response = $this->makeApiCall('orders', $razorpay_order_data, 'POST');
            return $response;
        } catch (Exception $e) {
            error_log("Razorpay order creation failed: " . $e->getMessage());
            throw new Exception("Payment gateway error: " . $e->getMessage());
        }
    }
    
    /**
     * Verify payment signature
     * 
     * @param string $razorpay_payment_id Payment ID from Razorpay
     * @param string $razorpay_order_id Order ID from Razorpay
     * @param string $razorpay_signature Signature from Razorpay
     * @return bool True if signature is valid
     */
    public function verifyPayment($razorpay_payment_id, $razorpay_order_id, $razorpay_signature) {
        $payload = $razorpay_order_id . '|' . $razorpay_payment_id;
        $expected_signature = hash_hmac('sha256', $payload, $this->key_secret);
        
        return hash_equals($expected_signature, $razorpay_signature);
    }
    
    /**
     * Get payment details
     * 
     * @param string $payment_id Razorpay payment ID
     * @return array Payment details
     */
    public function getPaymentDetails($payment_id) {
        try {
            return $this->makeApiCall("payments/{$payment_id}", [], 'GET');
        } catch (Exception $e) {
            error_log("Razorpay payment details fetch failed: " . $e->getMessage());
            throw new Exception("Payment details fetch error: " . $e->getMessage());
        }
    }
    
    /**
     * Refund a payment
     * 
     * @param string $payment_id Razorpay payment ID
     * @param float $amount Amount to refund
     * @param array $notes Optional notes
     * @return array Refund details
     */
    public function refundPayment($payment_id, $amount = null, $notes = []) {
        $refund_data = [];
        
        if ($amount) {
            $refund_data['amount'] = $amount * 100; // Convert to paise
        }
        
        if (!empty($notes)) {
            $refund_data['notes'] = $notes;
        }
        
        try {
            return $this->makeApiCall("payments/{$payment_id}/refund", $refund_data, 'POST');
        } catch (Exception $e) {
            error_log("Razorpay refund failed: " . $e->getMessage());
            throw new Exception("Refund error: " . $e->getMessage());
        }
    }
    
    /**
     * Make API call to Razorpay
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string $method HTTP method
     * @return array API response
     */
    private function makeApiCall($endpoint, $data, $method = 'POST') {
        $url = $this->is_test_mode ? 
            'https://api.razorpay.com/v1/' . $endpoint : 
            'https://api.razorpay.com/v1/' . $endpoint;
            
        $auth = base64_encode($this->key_id . ':' . $this->key_secret);
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . $auth
                ],
                'method' => $method,
                'ignore_errors' => true
            ]
        ];
        
        if (!empty($data) && ($method === 'POST' || $method === 'PUT')) {
            $options['http']['content'] = json_encode($data);
        }
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("API request failed");
        }
        
        $http_response_header = $http_response_header ?? [];
        $status_line = $http_response_header[0] ?? '';
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $status = $match[1] ?? '500';
        
        if ($status >= 400) {
            $error_data = json_decode($response, true);
            $error_message = $error_data['error']['description'] ?? 'Unknown error';
            throw new Exception("API Error ({$status}): " . $error_message);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get Razorpay checkout script URL
     * 
     * @return string Checkout script URL
     */
    public function getCheckoutScriptUrl() {
        return 'https://checkout.razorpay.com/v1/checkout.js';
    }
}