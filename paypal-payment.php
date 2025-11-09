<?php
// paypal-payment.php - PayPal payment processing page
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
$order_number = $_GET['order'] ?? '';

// Verify this is the correct order
if ($order_number !== $pending_payment['order_number']) {
    header("Location: /bookshelf/");
    exit();
}

$page_title_override = "PayPal Payment - Bookory";

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
$checkout_config = $payment_processor->getCheckoutConfig('paypal', [
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
        .paypal-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .paypal-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
        }

        .paypal-header {
            background: linear-gradient(135deg, #0070ba, #005b99);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .paypal-content {
            padding: 2rem;
        }

        .order-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .paypal-button-container {
            text-align: center;
            margin: 2rem 0;
        }

        .btn-paypal {
            background: #0070ba;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-paypal:hover {
            background: #005b99;
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

        .security-notice {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin-top: 2rem;
            border-radius: 0 8px 8px 0;
        }
    </style>
</head>
<body>

<div class="paypal-page">
    <div class="container">
        <div class="paypal-container">
            <!-- PayPal Header -->
            <div class="paypal-header">
                <h1 class="h2 fw-bold mb-2">Pay with PayPal</h1>
                <p class="mb-0">Secure and convenient payment processing</p>
            </div>

            <!-- PayPal Content -->
            <div class="paypal-content">
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
                        <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Tax:</span>
                        <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Shipping:</span>
                        <span>$<?php echo number_format($order['shipping_cost'], 2); ?></span>
                    </div>
                    <hr>
                    <div class="detail-row fw-bold">
                        <span>Total:</span>
                        <span class="text-success">$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <div class="paypal-button-container">
                    <button id="paypal-button" class="btn-paypal">
                        <i class="bi bi-paypal" style="font-size: 1.5rem;"></i>
                        Pay with PayPal
                    </button>
                </div>

                <div class="security-notice">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-shield-lock me-2"></i>Secure Payment</h6>
                    <p class="mb-0 small">
                        Your payment information is securely processed by PayPal. 
                        We never store your financial details on our servers.
                    </p>
                </div>
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
        <h5>Redirecting to PayPal</h5>
        <p class="mb-0">Please wait while we redirect you to PayPal for secure payment processing...</p>
    </div>
</div>

<script src="<?php echo $checkout_config['sdk_url'] ?? 'https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD'; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paypalButton = document.getElementById('paypal-button');
    
    // Render PayPal button
    paypal.Buttons({
        createOrder: function(data, actions) {
            // Show processing overlay
            document.getElementById('processingOverlay').style.display = 'flex';
            
            // Create order on your server
            return fetch('/bookshelf/create-paypal-order.php', {
                method: 'post',
                headers: {
                    'content-type': 'application/json'
                },
                body: JSON.stringify({
                    order_number: '<?php echo $order_number; ?>',
                    amount: <?php echo $order['total_amount']; ?>
                })
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (data.id) {
                    return data.id;
                } else {
                    throw new Error('Failed to create PayPal order');
                }
            });
        },
        onApprove: function(data, actions) {
            // Capture the payment
            return actions.order.capture().then(function(details) {
                // Redirect to verification page
                window.location.href = '/bookshelf/verify-payment.php?method=paypal&order=<?php echo urlencode($order_number); ?>&order_id=' + data.orderID;
            });
        },
        onError: function(err) {
            // Hide processing overlay
            document.getElementById('processingOverlay').style.display = 'none';
            
            // Show error message
            alert('An error occurred during the payment process. Please try again.');
            console.error('PayPal error:', err);
        },
        onCancel: function(data) {
            // Hide processing overlay
            document.getElementById('processingOverlay').style.display = 'none';
            
            // Show cancellation message
            alert('Payment was cancelled. You can complete your payment at any time from your order history.');
        }
    }).render('#paypal-button');
});
</script>

</body>
</html>