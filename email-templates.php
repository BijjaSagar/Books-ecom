// email-templates.php
function send_order_confirmation($order_data) {
    $to = $order_data['email'];
    $subject = "Order Confirmation - " . $order_data['order_number'];
    
    $message = "
    <html>
    <body>
        <h2>Thank you for your order!</h2>
        <p>Order Number: {$order_data['order_number']}</p>
        <p>Total: $" . number_format($order_data['total'], 2) . "</p>
        <p>We'll notify you when your order ships.</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: orders@bookory.com" . "\r\n";
    
    mail($to, $subject, $message, $headers);
}