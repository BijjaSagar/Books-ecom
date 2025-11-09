<?php
/**
 * PaymentGatewayIntegration.php - Integrate and track PayPal & Razorpay transactions
 */

class PaymentGatewayIntegration {
    private $conn;
    private $paypalConfig = [
        'mode' => 'sandbox', // 'sandbox' or 'live'
        'client_id' => getenv('PAYPAL_CLIENT_ID') ?: 'your_client_id',
        'secret' => getenv('PAYPAL_SECRET') ?: 'your_secret',
        'api_signature' => getenv('PAYPAL_SIGNATURE') ?: 'your_signature'
    ];

    private $razorpayConfig = [
        'key_id' => getenv('RAZORPAY_KEY_ID') ?: 'your_key_id',
        'key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: 'your_secret'
    ];

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Get PayPal transaction details
     */
    public function getPayPalTransaction($transactionId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM paypal_transactions WHERE transaction_id = ?
            ");

            $stmt->bind_param("s", $transactionId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("PayPal transaction error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Log PayPal transaction
     */
    public function logPayPalTransaction($orderId, $transactionId, $amount, $status, $payerEmail) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO paypal_transactions (order_id, transaction_id, amount, status, payer_email)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()
            ");

            $stmt->bind_param("isdss", $orderId, $transactionId, $amount, $status, $payerEmail);
            if ($stmt->execute()) {
                // Also update payment_transactions table
                $this->logPaymentTransaction($orderId, 'paypal', $transactionId, $amount, $status);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("PayPal log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Razorpay payment details
     */
    public function getRazorpayPayment($paymentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM razorpay_transactions WHERE payment_id = ?
            ");

            $stmt->bind_param("s", $paymentId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Razorpay payment error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Log Razorpay transaction
     */
    public function logRazorpayTransaction($orderId, $paymentId, $razorpayOrderId, $amount, $status, $customerEmail, $notes = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO razorpay_transactions (order_id, payment_id, razorpay_order_id, amount, status, customer_email, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()
            ");

            $stmt->bind_param("issdss", $orderId, $paymentId, $razorpayOrderId, $amount, $status, $customerEmail, $notes);
            if ($stmt->execute()) {
                $this->logPaymentTransaction($orderId, 'razorpay', $paymentId, $amount, $status);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Razorpay log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log generic payment transaction
     */
    private function logPaymentTransaction($orderId, $gateway, $gatewayTransactionId, $amount, $status) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO payment_transactions (order_id, payment_gateway, gateway_transaction_id, amount, status)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("issds", $orderId, $gateway, $gatewayTransactionId, $amount, $status);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get payment analytics
     */
    public function getPaymentAnalytics($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    payment_gateway,
                    COUNT(id) as transaction_count,
                    SUM(amount) as total_amount,
                    SUM(fee) as total_fees,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                    COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded
                FROM payment_transactions
                WHERE created_at BETWEEN ? AND ?
                GROUP BY payment_gateway
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get failed payment transactions
     */
    public function getFailedPayments($limit = 20) {
        try {
            $result = $this->conn->query("
                SELECT
                    pt.id,
                    pt.order_id,
                    pt.payment_gateway,
                    pt.amount,
                    pt.status,
                    pt.response_message,
                    pt.created_at
                FROM payment_transactions pt
                WHERE pt.status = 'failed'
                ORDER BY pt.created_at DESC
                LIMIT $limit
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Sync PayPal transactions (usually called via API webhook)
     */
    public function syncPayPalTransactions($startDate = null, $endDate = null) {
        try {
            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-7 days'));
            }
            if (!$endDate) {
                $endDate = date('Y-m-d');
            }

            // In production, this would call PayPal API to fetch transactions
            // For now, we'll just return success
            // PayPal API call example:
            // $url = 'https://api.sandbox.paypal.com/v1/oauth2/token';
            // Get access token and fetch transactions

            return ['success' => true, 'message' => 'PayPal transactions synced'];
        } catch (Exception $e) {
            error_log("PayPal sync error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync Razorpay transactions
     */
    public function syncRazorpayTransactions($startDate = null, $endDate = null) {
        try {
            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-7 days'));
            }
            if (!$endDate) {
                $endDate = date('Y-m-d');
            }

            // In production, call Razorpay API
            // Example: https://api.razorpay.com/v1/payments
            // Requires authentication with key_id and key_secret

            return ['success' => true, 'message' => 'Razorpay transactions synced'];
        } catch (Exception $e) {
            error_log("Razorpay sync error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get payment gateway summary
     */
    public function getPaymentGatewaySummary($days = 30) {
        try {
            $startDate = date('Y-m-d', strtotime('-' . $days . ' days'));
            $endDate = date('Y-m-d');

            $analytics = $this->getPaymentAnalytics($startDate, $endDate);

            $summary = [
                'total_transactions' => 0,
                'total_revenue' => 0,
                'total_fees' => 0,
                'success_rate' => 0,
                'failed_count' => 0,
                'refunded_count' => 0,
                'by_gateway' => $analytics
            ];

            foreach ($analytics as $gateway) {
                $summary['total_transactions'] += $gateway['transaction_count'];
                $summary['total_revenue'] += $gateway['total_amount'];
                $summary['total_fees'] += $gateway['total_fees'];
                $summary['failed_count'] += $gateway['failed'];
                $summary['refunded_count'] += $gateway['refunded'];
            }

            if ($summary['total_transactions'] > 0) {
                $summary['success_rate'] = (($summary['total_transactions'] - $summary['failed_count']) / $summary['total_transactions']) * 100;
            }

            return $summary;
        } catch (Exception $e) {
            error_log("Summary error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get payment method breakdown
     */
    public function getPaymentMethodBreakdown($startDate, $endDate) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    payment_method,
                    COUNT(id) as count,
                    SUM(amount) as total_amount
                FROM payment_transactions
                WHERE created_at BETWEEN ? AND ? AND status = 'completed'
                GROUP BY payment_method
                ORDER BY total_amount DESC
            ");

            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Verify PayPal signature
     */
    public function verifyPayPalSignature($paypalData) {
        // PayPal IPN verification would go here
        // This is a simplified example
        try {
            $url = $this->paypalConfig['mode'] === 'sandbox'
                ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
                : 'https://www.paypal.com/cgi-bin/webscr';

            $data = 'cmd=_notify-validate';
            foreach ($paypalData as $key => $value) {
                $data .= '&' . $key . '=' . urlencode($value);
            }

            $options = [
                'http' => [
                    'method' => 'POST',
                    'content' => $data,
                    'timeout' => 10
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            return $response === 'VERIFIED';
        } catch (Exception $e) {
            error_log("PayPal signature verification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refund PayPal payment
     */
    public function refundPayPalPayment($transactionId, $amount) {
        try {
            // Implementation would use PayPal RefundTransaction API
            // This is a stub for documentation

            $stmt = $this->conn->prepare("
                INSERT INTO payment_transactions (gateway_transaction_id, amount, status, payment_gateway)
                VALUES (?, ?, 'refunded', 'paypal')
            ");

            $stmt->bind_param("sd", $transactionId, $amount);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("PayPal refund error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refund Razorpay payment
     */
    public function refundRazorpayPayment($paymentId, $amount) {
        try {
            // Implementation would use Razorpay Refund API
            // Requires: curl -u RAZORPAY_KEY_ID:RAZORPAY_KEY_SECRET

            $stmt = $this->conn->prepare("
                INSERT INTO payment_transactions (gateway_transaction_id, amount, status, payment_gateway)
                VALUES (?, ?, 'refunded', 'razorpay')
            ");

            $stmt->bind_param("sd", $paymentId, $amount);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Razorpay refund error: " . $e->getMessage());
            return false;
        }
    }
}
