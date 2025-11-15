<?php
/**
 * Stripe Payment Gateway Integration
 * Handles all Stripe payment processing
 *
 * Features:
 * - Payment Intent creation
 * - 3D Secure authentication
 * - Webhook event handling
 * - Refund processing
 * - Saved payment methods
 */

require_once __DIR__ . '/../config.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Webhook;
use Stripe\Exception\CardException;
use Stripe\Exception\RateLimitException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;

class StripeGateway {
    private $stripe_api_key;
    private $stripe_test_mode = false;
    private $webhook_secret;
    private $conn;
    private $logger;

    /**
     * Initialize Stripe Gateway
     *
     * @param mysqli $conn Database connection
     * @param bool $test_mode Use test/sandbox keys
     */
    public function __construct($conn, $test_mode = true) {
        $this->conn = $conn;
        $this->stripe_test_mode = $test_mode;

        // Load Stripe keys from gateway_keys table
        $this->loadGatewayKeys();

        if (!$this->stripe_api_key) {
            throw new Exception("Stripe API keys not configured. Please configure in admin panel.");
        }

        // Initialize Stripe SDK
        Stripe::setApiKey($this->stripe_api_key);
        Stripe::setApiVersion('2023-10-16'); // Use latest stable version

        $this->logger = $this->getLogger();
    }

    /**
     * Load Stripe keys from database
     */
    private function loadGatewayKeys() {
        $sql = "SELECT * FROM gateway_keys
                WHERE gateway_keys.payment_method_id = (
                    SELECT id FROM payment_methods WHERE gateway_name = 'stripe'
                ) AND is_test_mode = ? AND is_active = TRUE
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $this->conn->error);
        }

        $stmt->bind_param('i', $this->stripe_test_mode ? 1 : 0);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->stripe_api_key = $row['api_secret'];
            $this->webhook_secret = $row['webhook_secret'];
        }

        $stmt->close();
    }

    /**
     * Create Payment Intent for transaction
     *
     * @param array $paymentData {
     *     'order_id' => int,
     *     'customer_id' => int,
     *     'amount' => float (in cents),
     *     'currency' => string ('usd', 'eur', etc.),
     *     'payment_method_token' => string (optional),
     *     'customer_email' => string,
     *     'customer_name' => string,
     *     'description' => string,
     *     'metadata' => array
     * }
     *
     * @return array Payment intent data with client_secret for frontend
     */
    public function createPaymentIntent($paymentData) {
        try {
            // Validate input
            if (!isset($paymentData['order_id']) || !isset($paymentData['amount'])) {
                throw new InvalidRequestException("Missing required fields: order_id, amount");
            }

            // Create Stripe customer if needed
            $customer_id = $this->getOrCreateStripeCustomer(
                $paymentData['customer_id'],
                $paymentData['customer_email'],
                $paymentData['customer_name']
            );

            // Build payment intent data
            $intent_data = [
                'amount' => intval($paymentData['amount']), // Must be in cents
                'currency' => strtolower($paymentData['currency'] ?? 'usd'),
                'customer' => $customer_id,
                'description' => $paymentData['description'] ?? 'Book Purchase',
                'metadata' => [
                    'order_id' => $paymentData['order_id'],
                    'customer_id' => $paymentData['customer_id'],
                    'timestamp' => date('Y-m-d H:i:s')
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'if_required'
                ]
            ];

            // Add payment method if provided
            if (isset($paymentData['payment_method_token'])) {
                $intent_data['payment_method'] = $paymentData['payment_method_token'];
                $intent_data['confirm'] = true;
                $intent_data['return_url'] = $paymentData['return_url'] ?? '';
            }

            // Add receipt email
            if (isset($paymentData['customer_email'])) {
                $intent_data['receipt_email'] = $paymentData['customer_email'];
            }

            // Create the payment intent
            $payment_intent = PaymentIntent::create($intent_data);

            // Log transaction to database
            $this->logTransaction([
                'order_id' => $paymentData['order_id'],
                'customer_id' => $paymentData['customer_id'],
                'gateway_name' => 'stripe',
                'gateway_transaction_id' => $payment_intent->id,
                'amount' => $paymentData['amount'] / 100, // Convert back to dollars
                'currency' => strtoupper($paymentData['currency'] ?? 'USD'),
                'status' => $this->mapStripeStatus($payment_intent->status),
                'gateway_response_json' => json_encode($payment_intent)
            ]);

            return [
                'success' => true,
                'client_secret' => $payment_intent->client_secret,
                'payment_intent_id' => $payment_intent->id,
                'status' => $payment_intent->status,
                'amount' => $payment_intent->amount,
                'currency' => strtoupper($payment_intent->currency),
                'requires_action' => $payment_intent->status === 'requires_action'
            ];

        } catch (CardException $e) {
            return $this->handlePaymentError($paymentData, $e, 'card_error');
        } catch (RateLimitException $e) {
            return $this->handlePaymentError($paymentData, $e, 'rate_limit');
        } catch (InvalidRequestException $e) {
            return $this->handlePaymentError($paymentData, $e, 'invalid_request');
        } catch (AuthenticationException $e) {
            return $this->handlePaymentError($paymentData, $e, 'auth_error');
        } catch (ApiConnectionException $e) {
            return $this->handlePaymentError($paymentData, $e, 'connection_error');
        } catch (ApiErrorException $e) {
            return $this->handlePaymentError($paymentData, $e, 'api_error');
        }
    }

    /**
     * Retrieve and verify payment intent status
     *
     * @param string $payment_intent_id Stripe payment intent ID
     * @return array Payment intent status
     */
    public function getPaymentIntentStatus($payment_intent_id) {
        try {
            $payment_intent = PaymentIntent::retrieve($payment_intent_id);

            $transaction_data = [
                'success' => true,
                'status' => $payment_intent->status,
                'amount' => $payment_intent->amount,
                'currency' => strtoupper($payment_intent->currency),
                'client_secret' => $payment_intent->client_secret,
                'charges' => $payment_intent->charges
            ];

            // Update transaction status in database
            if ($payment_intent->status === 'succeeded') {
                $this->updateTransactionStatus($payment_intent_id, 'completed');
            } elseif ($payment_intent->status === 'requires_payment_method') {
                $this->updateTransactionStatus($payment_intent_id, 'failed');
            }

            return $transaction_data;

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode()
            ];
        }
    }

    /**
     * Process refund for a transaction
     *
     * @param string $stripe_charge_id The charge ID to refund
     * @param array $refundData Refund information
     * @return array Refund result
     */
    public function processRefund($stripe_charge_id, $refundData) {
        try {
            $refund_amount = isset($refundData['amount']) ? intval($refundData['amount'] * 100) : null;

            $refund_params = [
                'reason' => $refundData['reason'] ?? 'requested_by_customer'
            ];

            if ($refund_amount) {
                $refund_params['amount'] = $refund_amount;
            }

            $refund = Refund::create([
                'charge' => $stripe_charge_id,
                ...$refund_params
            ]);

            // Log refund to database
            $this->logRefund($refundData['transaction_id'], $refund, $refundData);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'currency' => strtoupper($refund->currency),
                'status' => $refund->status
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getStripeCode()
            ];
        }
    }

    /**
     * Create/Store payment method for future use
     *
     * @param int $customer_id Books-ecom customer ID
     * @param string $payment_method_token Stripe payment method token
     * @param array $paymentMethodData Metadata for this payment method
     * @return array Save result
     */
    public function savePaymentMethod($customer_id, $payment_method_token, $paymentMethodData) {
        try {
            // Get Stripe customer ID
            $stripe_customer_id = $this->getOrCreateStripeCustomer(
                $customer_id,
                $paymentMethodData['email'],
                $paymentMethodData['name']
            );

            // Retrieve payment method to get details
            $payment_method = PaymentMethod::retrieve($payment_method_token);

            // Attach payment method to customer
            $payment_method->attach(['customer' => $stripe_customer_id]);

            // Save to our database
            $sql = "INSERT INTO saved_payment_methods (
                customer_id, payment_method_id, gateway_payment_method_token,
                nickname, card_brand, card_last_four, card_expiry_month,
                card_expiry_year, is_default, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);
            $card = $payment_method->card;
            $nickname = $paymentMethodData['nickname'] ?? $card->brand . ' •••• ' . $card->last4;
            $is_default = $paymentMethodData['is_default'] ?? false;

            $stmt->bind_param('iissssssii',
                $customer_id,
                $this->getPaymentMethodId('stripe'),
                $payment_method_token,
                $nickname,
                $card->brand,
                $card->last4,
                $card->exp_month,
                $card->exp_year,
                $is_default,
                $true
            );

            $stmt->execute();
            $stmt->close();

            return [
                'success' => true,
                'payment_method_id' => $this->conn->insert_id,
                'message' => 'Payment method saved successfully'
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle webhook events from Stripe
     *
     * @param string $payload Raw request body
     * @param string $sig_header Stripe signature header
     * @return array Webhook processing result
     */
    public function handleWebhook($payload, $sig_header) {
        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $this->webhook_secret
            );

            // Log webhook event
            $this->logWebhookEvent('stripe', $event->type, $event);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handlePaymentIntentSucceeded($event->data->object);

                case 'payment_intent.payment_failed':
                    return $this->handlePaymentIntentFailed($event->data->object);

                case 'payment_intent.canceled':
                    return $this->handlePaymentIntentCanceled($event->data->object);

                case 'charge.refunded':
                    return $this->handleChargeRefunded($event->data->object);

                case 'charge.dispute.created':
                    return $this->handleChargeDispute($event->data->object);

                default:
                    // Unhandled event type
                    return ['success' => true, 'message' => 'Webhook received'];
            }

        } catch (\UnexpectedValueException $e) {
            return ['success' => false, 'error' => 'Invalid signature'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle successful payment intent webhook
     */
    private function handlePaymentIntentSucceeded($payment_intent) {
        $this->updateTransactionStatus(
            $payment_intent->id,
            'completed',
            [
                'processed_at' => date('Y-m-d H:i:s'),
                'gateway_response_code' => 'succeeded'
            ]
        );

        // Trigger order completion logic
        // TODO: Update order status, send confirmation email, etc.

        return ['success' => true, 'message' => 'Payment succeeded'];
    }

    /**
     * Handle failed payment intent webhook
     */
    private function handlePaymentIntentFailed($payment_intent) {
        $error_message = $payment_intent->last_payment_error->message ?? 'Unknown error';

        $this->updateTransactionStatus(
            $payment_intent->id,
            'failed',
            [
                'failed_at' => date('Y-m-d H:i:s'),
                'gateway_response_message' => $error_message,
                'payment_error_details' => json_encode($payment_intent->last_payment_error)
            ]
        );

        return ['success' => true, 'message' => 'Payment failed processed'];
    }

    /**
     * Handle canceled payment intent webhook
     */
    private function handlePaymentIntentCanceled($payment_intent) {
        $this->updateTransactionStatus(
            $payment_intent->id,
            'cancelled',
            ['processed_at' => date('Y-m-d H:i:s')]
        );

        return ['success' => true, 'message' => 'Payment cancelled'];
    }

    /**
     * Handle charge refunded webhook
     */
    private function handleChargeRefunded($charge) {
        $this->updateTransactionStatus(
            $charge->payment_intent,
            'refunded'
        );

        return ['success' => true, 'message' => 'Refund processed'];
    }

    /**
     * Handle charge dispute/chargeback webhook
     */
    private function handleChargeDispute($dispute) {
        // Update transaction with fraud flag
        $this->updateTransactionStatus(
            $dispute->payment_intent ?? 'unknown',
            'disputed',
            ['risk_level' => 'high']
        );

        // TODO: Send admin alert about dispute
        return ['success' => true, 'message' => 'Dispute noted'];
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get or create Stripe customer
     */
    private function getOrCreateStripeCustomer($customer_id, $email, $name) {
        try {
            // Check if customer has a Stripe ID already
            $sql = "SELECT stripe_customer_id FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['stripe_customer_id']) {
                    return $row['stripe_customer_id'];
                }
            }
            $stmt->close();

            // Create new Stripe customer
            $customer = Customer::create([
                'email' => $email,
                'name' => $name,
                'metadata' => ['customer_id' => $customer_id]
            ]);

            // Save Stripe customer ID to user record
            $sql = "UPDATE users SET stripe_customer_id = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('si', $customer->id, $customer_id);
            $stmt->execute();
            $stmt->close();

            return $customer->id;

        } catch (ApiErrorException $e) {
            throw new Exception("Failed to create Stripe customer: " . $e->getMessage());
        }
    }

    /**
     * Map Stripe status to our transaction status
     */
    private function mapStripeStatus($stripe_status) {
        $status_map = [
            'requires_payment_method' => 'pending',
            'requires_action' => 'pending_3d',
            'requires_capture' => 'authorized',
            'processing' => 'processing',
            'succeeded' => 'completed',
            'requires_confirmation' => 'pending'
        ];

        return $status_map[$stripe_status] ?? 'processing';
    }

    /**
     * Log transaction to database
     */
    private function logTransaction($data) {
        $sql = "INSERT INTO transactions (
            transaction_id, order_id, customer_id, payment_method_id,
            gateway_name, gateway_transaction_id, currency, amount, status,
            gateway_response_json, initiated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $transaction_id = 'TXN-' . time() . '-' . bin2hex(random_bytes(4));
        $payment_method_id = $this->getPaymentMethodId('stripe');

        $stmt->bind_param('siiiissdss',
            $transaction_id,
            $data['order_id'],
            $data['customer_id'],
            $payment_method_id,
            $data['gateway_name'],
            $data['gateway_transaction_id'],
            $data['currency'],
            $data['amount'],
            $data['status'],
            $data['gateway_response_json']
        );

        $stmt->execute();
        $stmt->close();

        return $transaction_id;
    }

    /**
     * Update transaction status
     */
    private function updateTransactionStatus($gateway_tx_id, $status, $additional_data = []) {
        $update_fields = ['status' => $status];
        $update_fields = array_merge($update_fields, $additional_data);

        $set_clause = '';
        $values = [];

        foreach ($update_fields as $field => $value) {
            $set_clause .= "`$field` = ?, ";
            $values[] = $value;
        }

        $set_clause = rtrim($set_clause, ', ');
        $values[] = $gateway_tx_id;

        $sql = "UPDATE transactions SET $set_clause WHERE gateway_transaction_id = ?";
        $stmt = $this->conn->prepare($sql);

        if (!empty($values)) {
            $types = str_repeat('s', count($values) - 1) . 's';
            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        $stmt->close();
    }

    /**
     * Log refund to database
     */
    private function logRefund($transaction_id, $stripe_refund, $refundData) {
        $sql = "INSERT INTO refunds (
            transaction_id, refund_id, gateway_refund_id, amount,
            currency, reason, status, gateway_response_code, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $refund_id = 'REF-' . time() . '-' . bin2hex(random_bytes(4));

        $stmt->bind_param('issdssss',
            $transaction_id,
            $refund_id,
            $stripe_refund->id,
            $stripe_refund->amount / 100,
            strtoupper($stripe_refund->currency),
            $refundData['reason'],
            $stripe_refund->status,
            'refund_processed'
        );

        $stmt->execute();
        $stmt->close();
    }

    /**
     * Log webhook event
     */
    private function logWebhookEvent($gateway, $event_type, $event) {
        $sql = "INSERT INTO payment_webhooks (
            gateway_name, event_type, webhook_id, raw_payload,
            ip_address, created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $payload = json_encode($event);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $stmt->bind_param('sssss',
            $gateway,
            $event_type,
            $event->id,
            $payload,
            $ip
        );

        $stmt->execute();
        $stmt->close();
    }

    /**
     * Handle payment errors
     */
    private function handlePaymentError($paymentData, $exception, $error_type) {
        $error_message = $exception->getMessage();
        $error_code = method_exists($exception, 'getStripeCode') ? $exception->getStripeCode() : 'unknown';

        // Log to database
        if (isset($paymentData['order_id'])) {
            $this->logTransaction([
                'order_id' => $paymentData['order_id'],
                'customer_id' => $paymentData['customer_id'],
                'gateway_name' => 'stripe',
                'gateway_transaction_id' => 'error-' . time(),
                'amount' => $paymentData['amount'] / 100,
                'currency' => $paymentData['currency'] ?? 'USD',
                'status' => 'failed',
                'gateway_response_json' => json_encode([
                    'error_type' => $error_type,
                    'error_code' => $error_code,
                    'error_message' => $error_message
                ])
            ]);
        }

        return [
            'success' => false,
            'error' => $error_message,
            'error_code' => $error_code,
            'error_type' => $error_type
        ];
    }

    /**
     * Get payment method ID from database
     */
    private function getPaymentMethodId($gateway_name) {
        $sql = "SELECT id FROM payment_methods WHERE gateway_name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $gateway_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }

        $stmt->close();
        return null;
    }

    /**
     * Get logger instance
     */
    private function getLogger() {
        return function($message, $level = 'info') {
            error_log("[Stripe] [$level] $message");
        };
    }
}
?>
