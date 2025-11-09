<?php
/**
 * PayPal Payment Gateway Integration
 * For international customers and transactions
 */

class PayPalGateway {
    private $client_id;
    private $secret;
    private $is_sandbox;
    private $access_token;
    
    public function __construct($client_id, $secret, $is_sandbox = true) {
        $this->client_id = $client_id;
        $this->secret = $secret;
        $this->is_sandbox = $is_sandbox;
    }
    
    /**
     * Get PayPal API base URL
     * 
     * @return string API base URL
     */
    private function getApiBaseUrl() {
        return $this->is_sandbox ? 
            'https://api.sandbox.paypal.com' : 
            'https://api.paypal.com';
    }
    
    /**
     * Get access token for API calls
     * 
     * @return string Access token
     */
    private function getAccessToken() {
        if ($this->access_token) {
            return $this->access_token;
        }
        
        $url = $this->getApiBaseUrl() . '/v1/oauth2/token';
        $auth = base64_encode($this->client_id . ':' . $this->secret);
        
        $options = [
            'http' => [
                'header' => [
                    'Accept: application/json',
                    'Accept-Language: en_US',
                    'Authorization: Basic ' . $auth,
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                'method' => 'POST',
                'content' => 'grant_type=client_credentials',
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to get PayPal access token");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception("PayPal authentication error: " . $data['error_description']);
        }
        
        $this->access_token = $data['access_token'];
        return $this->access_token;
    }
    
    /**
     * Create PayPal order
     * 
     * @param array $order_data Order details
     * @return array Order creation response
     */
    public function createOrder($order_data) {
        $access_token = $this->getAccessToken();
        $url = $this->getApiBaseUrl() . '/v2/checkout/orders';
        
        // Convert amount to proper format
        $amount = number_format($order_data['amount'], 2, '.', '');
        
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $order_data['reference_id'] ?? uniqid('order_'),
                    'amount' => [
                        'currency_code' => $order_data['currency'] ?? 'USD',
                        'value' => $amount
                    ],
                    'description' => $order_data['description'] ?? 'Bookstore Purchase'
                ]
            ],
            'application_context' => [
                'brand_name' => $order_data['brand_name'] ?? 'Bookstore',
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
                'return_url' => $order_data['return_url'] ?? '',
                'cancel_url' => $order_data['cancel_url'] ?? ''
            ]
        ];
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ],
                'method' => 'POST',
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to create PayPal order");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception("PayPal order creation error: " . $data['error_description']);
        }
        
        return $data;
    }
    
    /**
     * Capture PayPal payment
     * 
     * @param string $order_id PayPal order ID
     * @return array Capture response
     */
    public function capturePayment($order_id) {
        $access_token = $this->getAccessToken();
        $url = $this->getApiBaseUrl() . '/v2/checkout/orders/' . $order_id . '/capture';
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ],
                'method' => 'POST',
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to capture PayPal payment");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception("PayPal payment capture error: " . $data['error_description']);
        }
        
        return $data;
    }
    
    /**
     * Get order details
     * 
     * @param string $order_id PayPal order ID
     * @return array Order details
     */
    public function getOrderDetails($order_id) {
        $access_token = $this->getAccessToken();
        $url = $this->getApiBaseUrl() . '/v2/checkout/orders/' . $order_id;
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ],
                'method' => 'GET',
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to get PayPal order details");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception("PayPal order details error: " . $data['error_description']);
        }
        
        return $data;
    }
    
    /**
     * Refund a captured payment
     * 
     * @param string $capture_id Capture ID
     * @param float $amount Amount to refund
     * @param string $currency Currency code
     * @return array Refund response
     */
    public function refundPayment($capture_id, $amount = null, $currency = 'USD') {
        $access_token = $this->getAccessToken();
        $url = $this->getApiBaseUrl() . '/v2/payments/captures/' . $capture_id . '/refund';
        
        $payload = [];
        if ($amount) {
            $payload['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => $currency
            ];
        }
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ],
                'method' => 'POST',
                'content' => json_encode($payload),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Failed to refund PayPal payment");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception("PayPal refund error: " . $data['error_description']);
        }
        
        return $data;
    }
    
    /**
     * Get PayPal JavaScript SDK URL
     * 
     * @param string $currency Currency code
     * @return string SDK URL
     */
    public function getSdkUrl($currency = 'USD') {
        $base_url = $this->is_sandbox ? 
            'https://www.sandbox.paypal.com/sdk/js' : 
            'https://www.paypal.com/sdk/js';
            
        return $base_url . '?client-id=' . $this->client_id . '&currency=' . $currency;
    }
}