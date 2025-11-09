<?php
// ajax/get_cart_count.php (Enhanced version)
session_start();

header('Content-Type: application/json');

$cart_count = 0;
$cart_total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
        $cart_total += $item['price'] * $item['quantity'];
    }
}

echo json_encode([
    'success' => true,
    'count' => $cart_count,
    'total' => number_format($cart_total, 2)
]);
?>