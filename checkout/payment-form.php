<?php
/**
 * Payment Form - Step 3 of Checkout Flow
 * Collects payment method selection and card details
 *
 * Uses:
 * - Stripe Elements for secure card entry
 * - PayPal Express Checkout button
 * - Alternative payment methods
 */

// Session already started in header
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Get order data from session
$order_id = $_SESSION['current_order_id'] ?? null;
$cart_total = $_SESSION['cart_total'] ?? 0;
$currency = $_SESSION['currency'] ?? 'USD';

if (!$order_id) {
    die("No order in progress. Please start checkout again.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Books Ecommerce</title>
    <link rel="stylesheet" href="/Books-ecom/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="/Books-ecom/public/css/style-optimized.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .payment-header {
            background: linear-gradient(135deg, #1a3a52 0%, #2d5a7b 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
        }

        .payment-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .order-summary {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: bold;
            color: #1a3a52;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #eee;
        }

        .payment-methods {
            padding: 30px;
        }

        .method-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }

        .method-tab {
            padding: 15px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .method-tab.active {
            color: #1a3a52;
            border-bottom-color: #d4a574;
        }

        .method-tab:hover {
            color: #1a3a52;
        }

        .method-content {
            display: none;
        }

        .method-content.active {
            display: block;
        }

        /* Stripe Elements Styles */
        .StripeElement {
            box-sizing: border-box;
            height: 50px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            transition: box-shadow 150ms ease;
        }

        .StripeElement--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;
            border: 1px solid #d4a574;
        }

        .StripeElement--invalid {
            border-color: #fa755a;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a3a52;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d4a574;
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }

        .billing-address {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-group label {
            margin: 0;
            color: #666;
            font-weight: 400;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1a3a52 0%, #2d5a7b 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 58, 82, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #eee;
            color: #1a3a52;
        }

        .btn-secondary:hover {
            background: #ddd;
        }

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 8px;
            padding: 10px;
            background: #fadbd8;
            border-radius: 4px;
            border-left: 4px solid #e74c3c;
        }

        .success-message {
            color: #27ae60;
            font-size: 14px;
            margin-top: 8px;
            padding: 10px;
            background: #d5f4e6;
            border-radius: 4px;
            border-left: 4px solid #27ae60;
        }

        .gateway-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .gateway-option {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            background: white;
        }

        .gateway-option:hover {
            border-color: #d4a574;
            background: #fafaf8;
        }

        .gateway-option.selected {
            border-color: #d4a574;
            background: #fffcf7;
        }

        .gateway-option img {
            height: 40px;
            margin-bottom: 10px;
        }

        .gateway-option p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #d4a574;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a3a52;
            margin-bottom: 20px;
            margin-top: 30px;
        }

        .three-d-secure-notice {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- Header -->
        <div class="payment-header">
            <h1>💳 Complete Payment</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Order #<?php echo htmlspecialchars($order_id); ?></p>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>₹<?php echo number_format($cart_total * 0.95, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping:</span>
                <span>₹<?php echo number_format($cart_total * 0.05, 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($cart_total, 2); ?></span>
            </div>
        </div>

        <!-- Payment Form -->
        <form id="paymentForm" class="payment-methods">
            <!-- Payment Method Selection -->
            <h3 class="section-title">Select Payment Method</h3>

            <div class="gateway-grid" id="gatewayGrid">
                <!-- Populated by JavaScript from available payment methods -->
            </div>

            <!-- Stripe Tab -->
            <div id="stripeContent" class="method-content active">
                <div class="three-d-secure-notice">
                    ✓ This transaction is protected with 3D Secure (when required)
                </div>

                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" id="cardholderName" name="cardholder_name" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label>Card Details</label>
                    <div id="cardElement" class="StripeElement"></div>
                    <div id="cardError" class="error-message" style="display: none;"></div>
                </div>

                <div class="billing-address">
                    <div class="checkbox-group">
                        <input type="checkbox" id="billingSameAsShipping" name="billing_same_as_shipping" checked>
                        <label for="billingSameAsShipping">Billing address same as shipping</label>
                    </div>

                    <div id="billingAddressFields" style="display: none;">
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="billing_address" placeholder="Street address">
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="billing_city" placeholder="City">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>State/Province</label>
                                <input type="text" name="billing_state" placeholder="State">
                            </div>
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="billing_postal" placeholder="12345">
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="savePaymentMethod" name="save_payment" value="1">
                        <label for="savePaymentMethod">Save this card for future purchases</label>
                    </div>
                </div>
            </div>

            <!-- PayPal Tab -->
            <div id="paypalContent" class="method-content" style="text-align: center;">
                <p style="color: #666; margin-bottom: 20px;">You will be redirected to PayPal to complete your payment securely.</p>
                <div id="paypal-button-container"></div>
            </div>

            <!-- Alternative Methods (Future) -->
            <div id="otherContent" class="method-content">
                <p style="color: #666;">Other payment methods coming soon.</p>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" id="orderId" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
            <input type="hidden" id="paymentMethodId" name="payment_method_id" value="">
            <input type="hidden" id="paymentMethodToken" name="payment_method_token" value="">
            <input type="hidden" id="cartTotal" name="amount" value="<?php echo $cart_total; ?>">
            <input type="hidden" id="cartCurrency" name="currency" value="<?php echo strtoupper($currency); ?>">

            <!-- Loading Indicator -->
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner"></div>
                <p>Processing payment...</p>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="goBack()">← Back</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Pay ₹<?php echo number_format($cart_total, 2); ?></button>
            </div>

            <p style="text-align: center; color: #999; font-size: 12px; margin-top: 20px;">
                ✓ Your payment information is secure and encrypted
            </p>
        </form>
    </div>

    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>

    <!-- PayPal -->
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&vault=true"></script>

    <script>
        // ============================================================
        // STRIPE INITIALIZATION
        // ============================================================

        const stripe = Stripe('<?php echo getStripePublishableKey(); ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            iconStyle: 'solid',
            style: {
                base: {
                    iconColor: '#d4a574',
                    color: '#1a3a52',
                    fontWeight: '500',
                    fontFamily: 'Segoe UI, Roboto',
                    fontSize: '14px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    iconColor: '#e74c3c',
                    color: '#e74c3c'
                }
            }
        });

        cardElement.mount('#cardElement');

        // Handle real-time validation errors
        cardElement.addEventListener('change', function(event) {
            const displayError = document.getElementById('cardError');
            if (event.error) {
                displayError.textContent = event.error.message;
                displayError.style.display = 'block';
            } else {
                displayError.style.display = 'none';
            }
        });

        // ============================================================
        // PAYMENT METHOD SELECTION
        // ============================================================

        let selectedPaymentMethod = 'stripe';

        document.querySelectorAll('.gateway-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.gateway-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');

                selectedPaymentMethod = this.dataset.gateway;

                // Show/hide appropriate content
                document.querySelectorAll('.method-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(selectedPaymentMethod + 'Content').classList.add('active');

                // Update hidden field
                document.getElementById('paymentMethodId').value = this.dataset.methodId;
            });
        });

        // Handle billing address checkbox
        document.getElementById('billingSameAsShipping').addEventListener('change', function() {
            document.getElementById('billingAddressFields').style.display = this.checked ? 'none' : 'block';
        });

        // ============================================================
        // FORM SUBMISSION
        // ============================================================

        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('loadingSpinner');

            // Disable button and show loading
            submitBtn.disabled = true;
            spinner.classList.add('active');

            try {
                if (selectedPaymentMethod === 'stripe') {
                    await handleStripePayment();
                } else if (selectedPaymentMethod === 'paypal') {
                    // PayPal button will handle this
                    return;
                }
            } catch (error) {
                console.error('Payment error:', error);
                showError(error.message);
            } finally {
                submitBtn.disabled = false;
                spinner.classList.remove('active');
            }
        });

        // ============================================================
        // STRIPE PAYMENT HANDLER
        // ============================================================

        async function handleStripePayment() {
            // Create payment method from card
            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    name: document.getElementById('cardholderName').value,
                    email: '<?php echo $_SESSION['user_email'] ?? ''; ?>'
                }
            });

            if (error) {
                throw new Error(error.message);
            }

            // Send payment to backend
            const response = await fetch('/checkout/process-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: document.getElementById('orderId').value,
                    payment_method_id: document.getElementById('paymentMethodId').value,
                    payment_method_token: paymentMethod.id,
                    amount: document.getElementById('cartTotal').value,
                    currency: document.getElementById('cartCurrency').value,
                    customer_name: document.getElementById('cardholderName').value,
                    customer_email: '<?php echo $_SESSION['user_email'] ?? ''; ?>'
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Payment processing failed');
            }

            // Handle 3D Secure if required
            if (data.requires_action) {
                const confirmResult = await stripe.confirmCardPayment(data.client_secret);
                if (confirmResult.error) {
                    throw new Error(confirmResult.error.message);
                }
            }

            // Payment successful
            showSuccess('Payment processed successfully!');
            setTimeout(() => {
                window.location.href = '/checkout/success.php?order=' + document.getElementById('orderId').value;
            }, 2000);
        }

        // ============================================================
        // UTILITY FUNCTIONS
        // ============================================================

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            document.querySelector('.payment-methods').insertBefore(errorDiv, document.getElementById('paymentForm'));

            setTimeout(() => errorDiv.remove(), 5000);
        }

        function showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'success-message';
            successDiv.textContent = message;
            document.querySelector('.payment-methods').insertBefore(successDiv, document.getElementById('paymentForm'));
        }

        function goBack() {
            window.history.back();
        }

        // ============================================================
        // LOAD PAYMENT METHODS ON PAGE LOAD
        // ============================================================

        document.addEventListener('DOMContentLoaded', async function() {
            // Fetch available payment methods from backend
            const response = await fetch('/api/payment-methods.php');
            const methods = await response.json();

            const gatewayGrid = document.getElementById('gatewayGrid');
            methods.forEach(method => {
                const option = document.createElement('div');
                option.className = `gateway-option ${method.gateway_name === 'stripe' ? 'selected' : ''}`;
                option.dataset.gateway = method.gateway_name;
                option.dataset.methodId = method.id;
                option.innerHTML = `
                    <img src="/images/payment-icons/${method.gateway_name}.svg" alt="${method.display_name}">
                    <p>${method.display_name}</p>
                `;

                option.addEventListener('click', function() {
                    document.querySelectorAll('.gateway-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedPaymentMethod = this.dataset.gateway;
                    document.getElementById('paymentMethodId').value = this.dataset.methodId;
                });

                gatewayGrid.appendChild(option);
            });

            // Set Stripe as default
            if (methods.some(m => m.gateway_name === 'stripe')) {
                document.getElementById('paymentMethodId').value = methods.find(m => m.gateway_name === 'stripe').id;
            }
        });
    </script>
</body>
</html>

<?php
/**
 * Get Stripe publishable key from database
 */
function getStripePublishableKey() {
    global $conn;

    $sql = "SELECT api_key FROM gateway_keys
            WHERE gateway_keys.payment_method_id = (
                SELECT id FROM payment_methods WHERE gateway_name = 'stripe'
            ) AND is_test_mode = TRUE
            LIMIT 1";

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return htmlspecialchars($row['api_key']);
    }

    return 'pk_test_xxx'; // Fallback
}
?>
