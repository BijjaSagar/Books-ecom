<?php
// payment.php - Payment processing page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/payment_gateways/PaymentProcessor.php';

// Check if there's a pending payment
if (!isset($_SESSION['pending_payment'])) {
    header("Location: /bookshelf/");
    exit();
}

$pending_payment = $_SESSION['pending_payment'];
$payment_method = $_GET['method'] ?? 'razorpay';
$order_number = $_GET['order'] ?? '';

// Verify this is the correct order
if ($order_number !== $pending_payment['order_number']) {
    header("Location: /bookshelf/");
    exit();
}

$page_title_override = "Payment - Bookory";

// Get order details from database
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
$order_stmt->bind_param("s", $order_number);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header("Location: /bookshelf/");
    exit();
}

// Get site settings
$settings = get_site_settings();

// Initialize payment processor
$payment_processor = new PaymentProcessor();
$checkout_config = $payment_processor->getCheckoutConfig($payment_method, [
    'order_number' => $order_number,
    'customer_email' => $order['customer_email'],
    'first_name' => $order['first_name'],
    'last_name' => $order['last_name'],
    'phone' => $order['phone'],
    'total_amount' => $order['total_amount']
]);

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_override; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .payment-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
        }

        .payment-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .payment-methods {
            padding: 2rem;
        }

        .payment-option {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .payment-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .order-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .btn-pay {
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 1.5rem;
        }

        .btn-pay:hover {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .processing-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }

        .processing-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
    </style>
</head>
<body>

<div class="payment-page">
    <div class="container">
        <div class="payment-container">
            <!-- Payment Header -->
            <div class="payment-header">
                <h1 class="h2 fw-bold mb-2">Secure Payment</h1>
                <p class="mb-0">Complete your order securely</p>
            </div>

            <!-- Payment Content -->
            <div class="payment-methods">
                <h3 class="h4 fw-bold mb-4">Order Summary</h3>
                
                <div class="order-summary">
                    <div class="detail-row">
                        <span>Order Number:</span>
                        <strong><?php echo htmlspecialchars($order_number); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Items:</span>
                        <span><?php echo count($_SESSION['cart'] ?? []); ?> items</span>
                    </div>
                    <div class="detail-row">
                        <span>Subtotal:</span>
                        <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Tax:</span>
                        <span>₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Shipping:</span>
                        <span>₹<?php echo number_format($order['shipping_cost'], 2); ?></span>
                    </div>
                    <hr>
                    <div class="detail-row fw-bold">
                        <span>Total:</span>
                        <span class="text-success">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <h3 class="h4 fw-bold mb-4 mt-4">Select Payment Method</h3>
                
                <!-- Razorpay Option -->
                <div class="payment-option selected" data-method="razorpay">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-wallet2" style="font-size: 2rem; color: #667eea;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">Razorpay</h5>
                            <p class="mb-0 text-muted small">Pay with credit/debit card, net banking, UPI, or wallet</p>
                        </div>
                        <div>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>

                <!-- Other Payment Options -->
                <div class="payment-option" data-method="credit_card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-credit-card" style="font-size: 2rem; color: #f59e0b;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">Credit/Debit Card</h5>
                            <p class="mb-0 text-muted small">Pay with any major credit or debit card</p>
                        </div>
                    </div>
                </div>

                <div class="payment-option" data-method="paypal">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-paypal" style="font-size: 2rem; color: #0070ba;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">PayPal</h5>
                            <p class="mb-0 text-muted small">Pay with your PayPal account</p>
                        </div>
                    </div>
                </div>

                <!-- Pay Button -->
                <button id="payButton" class="btn-pay">
                    <i class="bi bi-shield-lock me-2"></i>Pay ₹<?php echo number_format($order['total_amount'], 2); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Processing Overlay -->
<div class="processing-overlay" id="processingOverlay">
    <div class="processing-content">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Processing...</span>
        </div>
        <h5>Processing Payment</h5>
        <p class="mb-0">Please wait while we securely process your payment...</p>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    const paymentOptions = document.querySelectorAll('.payment-option');
    const payButton = document.getElementById('payButton');
    
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    // Pay button click handler
    payButton.addEventListener('click', function() {
        const selectedMethod = document.querySelector('.payment-option.selected').dataset.method;
        
        // Show processing overlay
        document.getElementById('processingOverlay').style.display = 'flex';
        
        // Handle payment based on selected method
        switch(selectedMethod) {
            case 'razorpay':
                processRazorpayPayment();
                break;
            case 'credit_card':
                processCreditCardPayment();
                break;
            case 'paypal':
                processPayPalPayment();
                break;
            default:
                alert('Please select a payment method');
                document.getElementById('processingOverlay').style.display = 'none';
        }
    });
    
    // Process Razorpay payment
    function processRazorpayPayment() {
        // Razorpay checkout configuration
        var options = {
            "key": "<?php echo $checkout_config['key'] ?? ''; ?>",
            "amount": "<?php echo $checkout_config['amount'] ?? ''; ?>",
            "currency": "<?php echo $checkout_config['currency'] ?? 'INR'; ?>",
            "name": "<?php echo htmlspecialchars($settings['site_name'] ?? 'Bookstore'); ?>",
            "description": "Order #<?php echo htmlspecialchars($order_number); ?>",
            "order_id": "<?php echo $checkout_config['order_id'] ?? ''; ?>",
            "prefill": {
                "name": "<?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>",
                "email": "<?php echo htmlspecialchars($order['customer_email']); ?>",
                "contact": "<?php echo htmlspecialchars($order['phone']); ?>"
            },
            "theme": {
                "color": "<?php echo $checkout_config['theme']['color'] ?? '#667eea'; ?>"
            },
            "handler": function (response) {
                // Payment successful, redirect to confirmation
                window.location.href = '/bookshelf/verify-payment.php?method=razorpay&order=<?php echo urlencode($order_number); ?>&payment_id=' + response.razorpay_payment_id + '&order_id=' + response.razorpay_order_id + '&signature=' + response.razorpay_signature;
            },
            "modal": {
                "ondismiss": function() {
                    // Payment cancelled or closed
                    document.getElementById('processingOverlay').style.display = 'none';
                    alert('Payment was cancelled. You can complete your payment at any time from your order history.');
                }
            }
        };
        
        var rzp = new Razorpay(options);
        rzp.open();
    }
    
    // Process credit card payment
    function processCreditCardPayment() {
        // For now, redirect to a card payment page
        window.location.href = '/bookshelf/card-payment.php?order=<?php echo urlencode($order_number); ?>';
    }
    
    // Process PayPal payment
    function processPayPalPayment() {
        // For now, redirect to a PayPal payment page
        window.location.href = '/bookshelf/paypal-payment.php?order=<?php echo urlencode($order_number); ?>';
    }
});
</script>

</body>
</html>