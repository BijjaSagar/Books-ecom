<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; }
        .content { background: #f9f9f9; padding: 20px; }
        .order-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #d4a574; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table th, .items-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .items-table th { background: #f5f5f5; font-weight: bold; }
        .total-row { font-weight: bold; font-size: 16px; }
        .button { background: #d4a574; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <p>Thank you for your order! We've received your purchase and are processing it right away.</p>

            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order_number); ?></p>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order_date); ?></p>
                <p><strong>Total Amount:</strong> <?php echo htmlspecialchars($currency); ?> <?php echo number_format($order_total, 2); ?></p>
            </div>

            <h3>Items Ordered</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                        <td><?php echo intval($item['quantity']); ?></td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p><a href="<?php echo htmlspecialchars($order_url); ?>" class="button">View Order Details</a></p>

            <h3>What Happens Next?</h3>
            <ul>
                <li>We'll process your order right away</li>
                <li>You'll receive a shipping notification with tracking information</li>
                <li>Your books will be carefully packaged and shipped to your address</li>
                <li>Track your order anytime by visiting your account</li>
            </ul>

            <p>If you have any questions, please don't hesitate to <strong>contact us at <?php echo htmlspecialchars($support_email); ?></strong></p>

            <p>Thank you for choosing Books eCommerce!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
