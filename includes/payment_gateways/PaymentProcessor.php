<?php
/**
 * Payment Processor - Handles multiple payment gateways
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

class PaymentProcessor {
    private $gateway;
    private $settings;
    
    public function __construct() {
        $this->settings = get_site_settings();
    }
    
    /**
     * Initialize payment gateway based on selected method
     * 
     * @param string $method Payment method (razorpay, paypal, stripe)
     * @return void
     */
    public function initializeGateway($method) {
        switch (strtolower($method)) {
            case 'razorpay':
                if (!empty($this->settings['razorpay_key_id']) && !empty($this->settings['razorpay_key_secret'])) {
                    require_once 'includes/payment_gateways/RazorpayGateway.php';
                    $this->gateway = new RazorpayGateway(
                        $this->settings['razorpay_key_id'],
                        $this->settings['razorpay_key_secret'],
                        true // Test mode - change to false for production
                    );
                } else {
                    throw new Exception("Razorpay gateway not configured properly");
                }
                break;
                
            case 'paypal':
                if (!empty($this->settings['paypal_client_id']) && !empty($this->settings['paypal_secret'])) {
                    require_once 'includes/payment_gateways/PayPalGateway.php';
                    $this->gateway = new PayPalGateway(
                        $this->settings['paypal_client_id'],
                        $this->settings['paypal_secret'],
                        true // Sandbox mode - change to false for production
                    );
                } else {
                    throw new Exception("PayPal gateway not configured properly");
                }
                break;
                
            case 'stripe':
                // Stripe implementation would go here
                if (empty($this->settings['stripe_public_key']) || empty($this->settings['stripe_secret_key'])) {
                    throw new Exception("Stripe gateway not configured properly");
                }
                // For now, we'll implement basic Stripe handling
                break;
                
            default:
                throw new Exception("Unsupported payment method: " . $method);
        }
    }
    
    /**
     * Process payment
     * 
     * @param array $order_data Order information
     * @param string $payment_method Selected payment method
     * @return array Payment result
     */
    public function processPayment($order_data, $payment_method) {
        $this->initializeGateway($payment_method);
        
        switch (strtolower($payment_method)) {
            case 'razorpay':
                return $this->processRazorpayPayment($order_data);
                
            case 'paypal':
                return $this->processPayPalPayment($order_data);
                
            case 'stripe':
                return $this->processStripePayment($order_data);
                
            default:
                throw new Exception("Payment method not supported");
        }
    }
    
    /**
     * Process Razorpay payment
     * 
     * @param array $order_data Order information
     * @return array Payment result with order details
     */
    private function processRazorpayPayment($order_data) {
        if (!$this->gateway) {
            throw new Exception("Razorpay gateway not initialized");
        }
        
        try {
            // Create Razorpay order
            $razorpay_order = $this->gateway->createOrder([
                'amount' => $order_data['total_amount'],
                'currency' => 'INR',
                'receipt' => $order_data['order_number'],
                'notes' => [
                    'order_id' => $order_data['order_number'],
                    'customer_email' => $order_data['customer_email'],
                    'customer_name' => $order_data['first_name'] . ' ' . $order_data['last_name']
                ]
            ]);
            
            return [
                'success' => true,
                'gateway' => 'razorpay',
                'order_id' => $razorpay_order['id'],
                'amount' => $razorpay_order['amount'] / 100, // Convert back to rupees
                'currency' => $razorpay_order['currency'],
                'receipt' => $razorpay_order['receipt'],
                'status' => $razorpay_order['status'],
                'created_at' => $razorpay_order['created_at']
            ];
            
        } catch (Exception $e) {
            error_log("Razorpay payment processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process PayPal payment
     * 
     * @param array $order_data Order information
     * @return array Payment result
     */
    private function processPayPalPayment($order_data) {
        if (!$this->gateway) {
            throw new Exception("PayPal gateway not initialized");
        }
        
        try {
            // Create PayPal order
            $paypal_order = $this->gateway->createOrder([
                'amount' => $order_data['total_amount'],
                'currency' => 'USD', // PayPal typically uses USD
                'reference_id' => $order_data['order_number'],
                'description' => 'Order #' . $order_data['order_number'],
                'brand_name' => $this->settings['site_name'] ?? 'Bookstore',
                'return_url' => 'https://yoursite.com/verify-payment.php?method=paypal&order=' . urlencode($order_data['order_number']),
                'cancel_url' => 'https://yoursite.com/cart.php'
            ]);
            
            return [
                'success' => true,
                'gateway' => 'paypal',
                'order_id' => $paypal_order['id'],
                'status' => $paypal_order['status'],
                'links' => $paypal_order['links'] ?? []
            ];
            
        } catch (Exception $e) {
            error_log("PayPal payment processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process Stripe payment
     * 
     * @param array $order_data Order information
     * @return array Payment result
     */
    private function processStripePayment($order_data) {
        // For now, we'll return a basic structure
        // Actual Stripe implementation would integrate with Stripe SDK
        return [
            'success' => true,
            'gateway' => 'stripe',
            'client_secret' => 'STRIPE_CLIENT_SECRET_PLACEHOLDER',
            'message' => 'Processing payment with Stripe'
        ];
    }
    
    /**
     * Verify payment completion
     * 
     * @param string $gateway Payment gateway used
     * @param array $payment_data Payment verification data
     * @return bool True if payment is verified
     */
    public function verifyPayment($gateway, $payment_data) {
        switch (strtolower($gateway)) {
            case 'razorpay':
                if (isset($payment_data['razorpay_payment_id']) && 
                    isset($payment_data['razorpay_order_id']) && 
                    isset($payment_data['razorpay_signature'])) {
                    
                    $this->initializeGateway('razorpay');
                    return $this->gateway->verifyPayment(
                        $payment_data['razorpay_payment_id'],
                        $payment_data['razorpay_order_id'],
                        $payment_data['razorpay_signature']
                    );
                }
                return false;
                
            case 'paypal':
                // For PayPal, we need to capture the payment
                if (isset($payment_data['order_id'])) {
                    $this->initializeGateway('paypal');
                    try {
                        $capture_result = $this->gateway->capturePayment($payment_data['order_id']);
                        return $capture_result['status'] === 'COMPLETED';
                    } catch (Exception $e) {
                        error_log("PayPal payment verification error: " . $e->getMessage());
                        return false;
                    }
                }
                return false;
                
            case 'stripe':
                // Stripe verification would go here
                return true; // Simplified for now
                
            default:
                return false;
        }
    }
    
    /**
     * Get gateway-specific checkout configuration
     * 
     * @param string $gateway Payment gateway
     * @param array $order_data Order information
     * @return array Checkout configuration
     */
    public function getCheckoutConfig($gateway, $order_data) {
        switch (strtolower($gateway)) {
            case 'razorpay':
                $this->initializeGateway('razorpay');
                if ($this->gateway) {
                    // Create Razorpay order for checkout
                    $razorpay_order = $this->gateway->createOrder([
                        'amount' => $order_data['total_amount'],
                        'currency' => 'INR',
                        'receipt' => $order_data['order_number']
                    ]);
                    
                    return [
                        'key' => $this->settings['razorpay_key_id'],
                        'amount' => $razorpay_order['amount'],
                        'currency' => $razorpay_order['currency'],
                        'name' => $this->settings['site_name'] ?? 'Bookstore',
                        'description' => 'Order #' . $order_data['order_number'],
                        'order_id' => $razorpay_order['id'],
                        'prefill' => [
                            'name' => $order_data['first_name'] . ' ' . $order_data['last_name'],
                            'email' => $order_data['customer_email'],
                            'contact' => $order_data['phone']
                        ],
                        'theme' => [
                            'color' => $this->settings['primary_color'] ?? '#667eea'
                        ],
                        'script_url' => $this->gateway->getCheckoutScriptUrl()
                    ];
                }
                break;
                
            case 'paypal':
                $this->initializeGateway('paypal');
                if ($this->gateway) {
                    return [
                        'client_id' => $this->settings['paypal_client_id'],
                        'currency' => 'USD',
                        'amount' => $order_data['total_amount'],
                        'sdk_url' => $this->gateway->getSdkUrl('USD')
                    ];
                }
                break;
                
            case 'stripe':
                return [
                    'public_key' => $this->settings['stripe_public_key'],
                    'currency' => 'USD',
                    'amount' => $order_data['total_amount'] * 100 // Convert to cents
                ];
        }
        
        return [];
    }
}