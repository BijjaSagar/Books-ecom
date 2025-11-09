<?php
// Create this file as: ajax/add_to_cart.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

if (!$product_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Get product details
    $stmt = $conn->prepare("SELECT id, title, price, stock_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' items available.'
        ]);
        exit;
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if item already in cart
    $existing_quantity = 0;
    if (isset($_SESSION['cart'][$product_id])) {
        $existing_quantity = $_SESSION['cart'][$product_id]['quantity'];
    }
    
    $new_total_quantity = $existing_quantity + $quantity;
    
    // Check if new total exceeds stock
    if ($new_total_quantity > $product['stock_quantity']) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot add more items. Total would exceed available stock.'
        ]);
        exit;
    }
    
    // Add or update cart item
    $_SESSION['cart'][$product_id] = [
        'id' => $product['id'],
        'title' => $product['title'],
        'price' => $product['price'],
        'quantity' => $new_total_quantity
    ];
    
    // Calculate cart totals
    $cart_count = get_cart_count();
    $cart_total = get_cart_total();
    
    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart successfully',
        'cart_count' => $cart_count,
        'cart_total' => number_format($cart_total, 2)
    ]);
    
} catch (Exception $e) {
    error_log("Cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>

<?php
// Create this file as: ajax/get_cart_count.php
session_start();
require_once '../includes/functions.php';

header('Content-Type: application/json');

$cart_count = get_cart_count();
$cart_total = get_cart_total();

echo json_encode([
    'success' => true,
    'count' => $cart_count,
    'total' => number_format($cart_total, 2)
]);
?>

<?php
// Create this file as: ajax/update_cart.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$product_id || $quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$product_id])) {
        echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
        exit;
    }
    
    if ($quantity == 0) {
        // Remove item from cart
        unset($_SESSION['cart'][$product_id]);
    } else {
        // Check stock availability
        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        $product = $result->fetch_assoc();
        
        if ($quantity > $product['stock_quantity']) {
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' items available.'
            ]);
            exit;
        }
        
        // Update quantity
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }
    
    // Calculate new totals
    $cart_count = get_cart_count();
    $cart_total = get_cart_total();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'cart_count' => $cart_count,
        'cart_total' => number_format($cart_total, 2)
    ]);
    
} catch (Exception $e) {
    error_log("Cart update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>

<?php
// Create this file as: ajax/remove_from_cart.php
session_start();
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$product_id])) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit;
}

// Remove item from cart
unset($_SESSION['cart'][$product_id]);

// Calculate new totals
$cart_count = get_cart_count();
$cart_total = get_cart_total();

echo json_encode([
    'success' => true,
    'message' => 'Item removed from cart',
    'cart_count' => $cart_count,
    'cart_total' => number_format($cart_total, 2)
]);
?>