<?php
// verify-payment.php - Verify payment and update order status
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/payment_gateways/SecurePaymentProcessor.php';

// Get parameters
$payment_method = $_GET['method'] ?? '';
$order_number = $_GET['order'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';
$razorpay_order_id = $_GET['order_id'] ?? '';
$signature = $_GET['signature'] ?? '';

// Validate required parameters
if (empty($order_number) || empty($payment_method)) {
    header("Location: /bookshelf/");
    exit();
}

// Get order details
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
$order_stmt->bind_param("s", $order_number);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header("Location: /bookshelf/");
    exit();
}

// Initialize secure payment processor
$secure_payment_processor = new SecurePaymentProcessor($conn);
$payment_verified = false;
$error_message = '';

// Verify payment based on method
switch ($payment_method) {
    case 'razorpay':
        if (!empty($payment_id) && !empty($razorpay_order_id) && !empty($signature)) {
            $payment_data = [
                'razorpay_payment_id' => $payment_id,
                'razorpay_order_id' => $razorpay_order_id,
                'razorpay_signature' => $signature
            ];
            
            $verification_result = $secure_payment_processor->verifySecurePayment('razorpay', $payment_data, $order_number);
            $payment_verified = $verification_result['success'];
            
            if ($payment_verified) {
                // Update order status to processing
                $update_stmt = $conn->prepare("UPDATE orders SET order_status = 'processing', updated_at = NOW() WHERE order_number = ?");
                $update_stmt->bind_param("s", $order_number);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Store success data
                $_SESSION['order_success'] = [
                    'order_id' => $order['id'],
                    'order_number' => $order_number,
                    'email' => $order['customer_email'],
                    'first_name' => $order['first_name'],
                    'last_name' => $order['last_name'],
                    'total' => $order['total_amount'],
                    'payment_method' => $payment_method,
                    'state' => $order['state_name'],
                    'country' => $order['country_name']
                ];
                
                // Clear pending payment
                unset($_SESSION['pending_payment']);
                
                // Redirect to confirmation
                header("Location: /bookshelf/order-confirmation.php");
                exit();
            } else {
                $error_message = $verification_result['error'] ?? "Payment verification failed. Please contact support.";
            }
        } else {
            $error_message = "Missing payment verification data.";
        }
        break;
        
    case 'paypal':
        if (!empty($razorpay_order_id)) { // Using razorpay_order_id for PayPal order ID
            $payment_data = [
                'order_id' => $razorpay_order_id
            ];
            
            $verification_result = $secure_payment_processor->verifySecurePayment('paypal', $payment_data, $order_number);
            $payment_verified = $verification_result['success'];
            
            if ($payment_verified) {
                // Update order status to processing
                $update_stmt = $conn->prepare("UPDATE orders SET order_status = 'processing', updated_at = NOW() WHERE order_number = ?");
                $update_stmt->bind_param("s", $order_number);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Store success data
                $_SESSION['order_success'] = [
                    'order_id' => $order['id'],
                    'order_number' => $order_number,
                    'email' => $order['customer_email'],
                    'first_name' => $order['first_name'],
                    'last_name' => $order['last_name'],
                    'total' => $order['total_amount'],
                    'payment_method' => $payment_method,
                    'state' => $order['state_name'],
                    'country' => $order['country_name']
                ];
                
                // Clear pending payment
                unset($_SESSION['pending_payment']);
                
                // Redirect to confirmation
                header("Location: /bookshelf/order-confirmation.php");
                exit();
            } else {
                $error_message = $verification_result['error'] ?? "Payment verification failed. Please contact support.";
            }
        } else {
            $error_message = "Missing payment verification data.";
        }
        break;
        
    default:
        $error_message = "Unsupported payment method.";
}

$page_title_override = "Payment Verification - Bookory";
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
        .verification-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .verification-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
            overflow: hidden;
        }

        .verification-header {
            padding: 2rem;
            text-align: center;
        }

        .success-header {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .error-header {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
        }

        .status-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }

        .verification-content {
            padding: 2rem;
        }

        .detail-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .action-buttons {
            text-align: center;
            padding: 1rem 2rem 2rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            margin: 0.5rem;
        }

        .btn-outline-secondary {
            border: 2px solid #e5e7eb;
            color: #6b7280;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            margin: 0.5rem;
        }
    </style>
</head>
<body>

<div class="verification-page">
    <div class="container">
        <div class="verification-container">
            <?php if ($payment_verified): ?>
                <!-- Success Header -->
                <div class="verification-header success-header">
                    <div class="status-icon">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h1 class="h2 fw-bold mb-2">Payment Successful!</h1>
                    <p class="mb-0">Your payment has been verified and processed.</p>
                </div>
            <?php else: ?>
                <!-- Error Header -->
                <div class="verification-header error-header">
                    <div class="status-icon">
                        <i class="bi bi-x-lg"></i>
                    </div>
                    <h1 class="h2 fw-bold mb-2">Payment Verification Failed</h1>
                    <p class="mb-0"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Verification Content -->
            <div class="verification-content">
                <div class="detail-card">
                    <h5 class="fw-semibold mb-3">Order Information</h5>
                    <div class="detail-row">
                        <span>Order Number:</span>
                        <strong><?php echo htmlspecialchars($order_number); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Amount:</span>
                        <strong class="text-success">₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Payment Method:</span>
                        <span><?php echo ucfirst(str_replace('_', ' ', $payment_method)); ?></span>
                    </div>
                    <?php if (!$payment_verified): ?>
                        <div class="detail-row">
                            <span>Status:</span>
                            <span class="badge bg-warning">Verification Failed</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="action-buttons">
                    <?php if ($payment_verified): ?>
                        <a href="/bookshelf/order-confirmation.php" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>View Order Confirmation
                        </a>
                    <?php else: ?>
                        <a href="/bookshelf/contact.php" class="btn btn-primary">
                            <i class="bi bi-headset me-2"></i>Contact Support
                        </a>
                        <a href="/bookshelf/my-account.php" class="btn btn-outline-secondary">
                            <i class="bi bi-person me-2"></i>My Account
                        </a>
                    <?php endif; ?>
                    <a href="/bookshelf/shop.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>