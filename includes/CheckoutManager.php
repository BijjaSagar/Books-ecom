<?php
/**
 * Checkout Manager Class
 * Orchestrates the complete checkout flow
 *
 * Integrates:
 * - Payment Gateway (Task 1)
 * - Tax & Shipping (Task 2)
 * - Multi-Currency (Task 3)
 *
 * Features:
 * - Multi-step checkout (Address → Shipping → Payment)
 * - Cart to order conversion
 * - Order processing
 * - Digital download handling
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/TaxCalculator.php';
require_once __DIR__ . '/ShippingCalculator.php';
require_once __DIR__ . '/CurrencyHelper.php';
require_once __DIR__ . '/AddressValidator.php';

class CheckoutManager {
    private $conn;
    private $tax_calc;
    private $shipping_calc;
    private $currency_helper;
    private $address_validator;
    private $logger;
    private $customer_id;

    public function __construct($conn, $customer_id) {
        $this->conn = $conn;
        $this->customer_id = $customer_id;
        $this->tax_calc = new TaxCalculator($conn);
        $this->shipping_calc = new ShippingCalculator($conn);
        $this->currency_helper = new CurrencyHelper($conn);
        $this->address_validator = new AddressValidator($conn);
        $this->logger = function($message, $level = 'info') {
            error_log("[CheckoutManager] [$level] $message");
        };
    }

    /**
     * Get checkout session for customer
     *
     * @param string $session_id
     * @return array Checkout session data
     */
    public function getCheckoutSession($session_id) {
        try {
            $sql = "SELECT * FROM checkout_sessions
                    WHERE session_id = ?
                    AND (customer_id = ? OR customer_id IS NULL)
                    AND expires_at > NOW()
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error");
            }

            $stmt->bind_param('si', $session_id, $this->customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return $this->createCheckoutSession($session_id);
            }

            $session = $result->fetch_assoc();
            $session['data'] = json_decode($session['data'], true) ?? [];
            $stmt->close();

            return $session;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting session: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Create new checkout session
     */
    public function createCheckoutSession($session_id) {
        try {
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $sql = "INSERT INTO checkout_sessions (
                session_id, customer_id, current_step, expires_at
            ) VALUES (?, ?, 'cart', ?)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('sis', $session_id, $this->customer_id, $expires_at);
            $stmt->execute();
            $stmt->close();

            return [
                'id' => $this->conn->insert_id,
                'session_id' => $session_id,
                'customer_id' => $this->customer_id,
                'current_step' => 'cart',
                'data' => []
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error creating session: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Complete Step 1: Shipping Address
     *
     * @param string $session_id
     * @param array $address Address data
     * @return array Result with validation
     */
    public function completeStep1_Address($session_id, $address) {
        try {
            // Validate address
            $validation = $this->address_validator->validateAddress($address);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            $address = $validation['standardized'];

            // Save address
            $address['type'] = 'shipping';
            $address_result = $this->address_validator->saveAddress($this->customer_id, $address);

            if (!$address_result['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to save address'
                ];
            }

            // Update checkout session
            $sql = "UPDATE checkout_sessions
                    SET current_step = 'shipping',
                        step_1_complete = TRUE,
                        shipping_address_id = ?
                    WHERE session_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('is', $address_result['address_id'], $session_id);
            $stmt->execute();
            $stmt->close();

            return [
                'success' => true,
                'address_id' => $address_result['address_id'],
                'next_step' => 'shipping'
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error in step 1: " . $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Complete Step 2: Shipping Method
     *
     * @param string $session_id
     * @param int $shipping_address_id
     * @param int $shipping_method_id
     * @param array $cart_items
     * @return array Result with calculated costs
     */
    public function completeStep2_Shipping($session_id, $shipping_address_id, $shipping_method_id, $cart_items) {
        try {
            // Get address
            $sql = "SELECT * FROM customer_addresses WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $shipping_address_id);
            $stmt->execute();
            $address_result = $stmt->get_result();

            if ($address_result->num_rows === 0) {
                return ['success' => false, 'error' => 'Invalid address'];
            }

            $address = $address_result->fetch_assoc();
            $stmt->close();

            // Calculate shipping
            $items_for_calc = [];
            $total_weight = 0;

            foreach ($cart_items as $item) {
                // Get product shipping info
                $sql = "SELECT weight_kg FROM product_shipping WHERE product_id = ?";
                $pstmt = $this->conn->prepare($sql);
                $pstmt->bind_param('i', $item['product_id']);
                $pstmt->execute();
                $presult = $pstmt->get_result();
                $product_shipping = $presult->fetch_assoc();
                $pstmt->close();

                $weight = $product_shipping['weight_kg'] ?? 0.5; // Default 500g

                $items_for_calc[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'weight_kg' => $weight
                ];

                $total_weight += $weight * $item['quantity'];
            }

            // Get subtotal
            $sql = "SELECT SUM(line_total) as subtotal FROM cart_items
                    WHERE cart_id = (
                        SELECT id FROM shopping_carts WHERE customer_id = ?
                    )";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $this->customer_id);
            $stmt->execute();
            $subtotal_result = $stmt->get_result()->fetch_assoc();
            $subtotal = floatval($subtotal_result['subtotal'] ?? 0);
            $stmt->close();

            // Calculate shipping
            $shipping_result = $this->shipping_calc->getAvailableShippingMethods([
                'destination' => [
                    'country' => $address['country'],
                    'state_province' => $address['state_province'],
                    'postal_code' => $address['postal_code'],
                    'city' => $address['city']
                ],
                'items' => $items_for_calc,
                'subtotal' => $subtotal
            ]);

            if (!$shipping_result['success']) {
                return ['success' => false, 'error' => $shipping_result['error']];
            }

            // Get selected method
            $selected_method = null;
            foreach ($shipping_result['available_methods'] as $method) {
                if ($method['id'] == $shipping_method_id) {
                    $selected_method = $method;
                    break;
                }
            }

            if (!$selected_method) {
                return ['success' => false, 'error' => 'Invalid shipping method'];
            }

            // Update session
            $sql = "UPDATE checkout_sessions
                    SET current_step = 'payment',
                        step_2_complete = TRUE,
                        shipping_method_id = ?
                    WHERE session_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('is', $shipping_method_id, $session_id);
            $stmt->execute();
            $stmt->close();

            return [
                'success' => true,
                'shipping_method' => $selected_method,
                'shipping_cost' => $selected_method['calculated_cost'],
                'estimated_delivery' => $selected_method['estimated_delivery'],
                'next_step' => 'payment'
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error in step 2: " . $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Complete Step 3: Payment & Create Order
     *
     * @param string $session_id
     * @param int $payment_method_id
     * @param array $payment_data
     * @return array Order created response
     */
    public function completeStep3_Payment($session_id, $payment_method_id, $payment_data) {
        try {
            // Get checkout session
            $sql = "SELECT * FROM checkout_sessions WHERE session_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $session_id);
            $stmt->execute();
            $session = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$session) {
                return ['success' => false, 'error' => 'Invalid checkout session'];
            }

            // Get customer currency
            $currency = $this->currency_helper->getCustomerPreferredCurrency($this->customer_id);
            $currency_code = $currency['currency_code'];

            // Get cart
            $sql = "SELECT * FROM shopping_carts
                    WHERE customer_id = ? AND status = 'active'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $this->customer_id);
            $stmt->execute();
            $cart = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$cart) {
                return ['success' => false, 'error' => 'Cart not found'];
            }

            // Get cart items
            $sql = "SELECT * FROM cart_items WHERE cart_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $cart['id']);
            $stmt->execute();
            $cart_items_result = $stmt->get_result();
            $cart_items = [];
            while ($item = $cart_items_result->fetch_assoc()) {
                $cart_items[] = $item;
            }
            $stmt->close();

            // Get address
            $sql = "SELECT * FROM customer_addresses WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $session['shipping_address_id']);
            $stmt->execute();
            $address = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Calculate tax
            $items_for_tax = [];
            foreach ($cart_items as $item) {
                $items_for_tax[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price_at_time'],
                    'type' => 'products'
                ];
            }

            $tax_result = $this->tax_calc->calculateOrderTax([
                'customer_id' => $this->customer_id,
                'shipping_address' => [
                    'country' => $address['country'],
                    'state_province' => $address['state_province'],
                    'postal_code' => $address['postal_code'],
                    'city' => $address['city']
                ],
                'items' => $items_for_tax,
                'subtotal' => $cart['subtotal']
            ]);

            // Get shipping method
            $sql = "SELECT * FROM shipping_methods WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $session['shipping_method_id']);
            $stmt->execute();
            $shipping_method = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Create order
            $order_number = $this->generateOrderNumber();
            $total = $cart['subtotal'] + ($tax_result['total_tax'] ?? 0) + ($shipping_method['base_price'] ?? 0);

            $sql = "INSERT INTO orders (
                order_number, customer_id, user_id,
                shipping_address_id,
                shipping_first_name, shipping_last_name,
                shipping_street_1, shipping_street_2,
                shipping_city, shipping_state,
                shipping_postal_code, shipping_country,
                shipping_phone, shipping_email,
                items_count, subtotal, tax_amount,
                shipping_cost, total, currency_code,
                payment_method_id, shipping_method_id,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                     ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('siiisssssssssiiiddsii',
                $order_number,
                $this->customer_id,
                $this->customer_id,
                $session['shipping_address_id'],
                $address['first_name'],
                $address['last_name'],
                $address['street_address_1'],
                $address['street_address_2'],
                $address['city'],
                $address['state_province'],
                $address['postal_code'],
                $address['country'],
                $address['phone_number'],
                $address['email'],
                count($cart_items),
                $cart['subtotal'],
                $tax_result['total_tax'],
                $shipping_method['base_price'],
                $total,
                $currency_code,
                $payment_method_id,
                $session['shipping_method_id']
            );

            $stmt->execute();
            $order_id = $this->conn->insert_id;
            $stmt->close();

            // Add items to order
            foreach ($cart_items as $item) {
                $sql = "INSERT INTO order_items (
                    order_id, product_id, product_name,
                    quantity, price_at_purchase, currency_code, line_total
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmt = $this->conn->prepare($sql);
                $product_name = "Book"; // TODO: Get actual product name

                $stmt->bind_param('iisidsd',
                    $order_id,
                    $item['product_id'],
                    $product_name,
                    $item['quantity'],
                    $item['price_at_time'],
                    $currency_code,
                    $item['line_total']
                );

                $stmt->execute();
                $stmt->close();
            }

            // Save tax details
            $this->tax_calc->saveTaxCalculation($order_id, $tax_result);

            // Save shipping
            $this->shipping_calc->saveShippingToOrder($order_id, $session['shipping_method_id'], $shipping_method['base_price']);

            // Convert cart to order
            $sql = "UPDATE shopping_carts
                    SET status = 'converted',
                        converted_to_order_id = ?
                    WHERE id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ii', $order_id, $cart['id']);
            $stmt->execute();
            $stmt->close();

            // Mark checkout complete
            $sql = "UPDATE checkout_sessions
                    SET current_step = 'complete',
                        step_3_complete = TRUE
                    WHERE session_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $session_id);
            $stmt->execute();
            $stmt->close();

            return [
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'total' => $total,
                'currency' => $currency_code,
                'message' => 'Order created successfully'
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error in step 3: " . $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber() {
        // Format: ORD-20251110-123456 (where 123456 is random)
        return 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get order summary for review
     */
    public function getOrderSummary($session_id) {
        try {
            // Get session
            $sql = "SELECT * FROM checkout_sessions WHERE session_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $session_id);
            $stmt->execute();
            $session = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$session) {
                return null;
            }

            // Get address
            $address = null;
            if ($session['shipping_address_id']) {
                $sql = "SELECT * FROM customer_addresses WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $session['shipping_address_id']);
                $stmt->execute();
                $address = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            // Get shipping method
            $shipping = null;
            if ($session['shipping_method_id']) {
                $sql = "SELECT * FROM shipping_methods WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $session['shipping_method_id']);
                $stmt->execute();
                $shipping = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            // Get cart
            $sql = "SELECT * FROM shopping_carts
                    WHERE customer_id = ? AND status = 'active'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $this->customer_id);
            $stmt->execute();
            $cart = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return [
                'session' => $session,
                'address' => $address,
                'shipping' => $shipping,
                'cart' => $cart
            ];

        } catch (Exception $e) {
            return null;
        }
    }
}
?>
