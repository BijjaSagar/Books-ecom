<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; }
        .content { background: #f9f9f9; padding: 20px; }
        .success-box { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .button { background: #d4a574; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Your Order Has Arrived!</h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <div class="success-box">
                <h3>Delivery Confirmed</h3>
                <p>Your order <strong><?php echo htmlspecialchars($order_number); ?></strong> has been successfully delivered!</p>
                <p>Order Total: <?php echo htmlspecialchars($currency); ?> <?php echo number_format($order_total, 2); ?></p>
            </div>

            <h3>What's Next?</h3>
            <ul>
                <li><strong>Review Your Books:</strong> We'd love to hear what you think about your purchase</li>
                <li><strong>Share Your Experience:</strong> Write a review to help other readers</li>
                <li><strong>Browse More:</strong> Discover similar titles you might enjoy</li>
            </ul>

            <p><a href="<?php echo htmlspecialchars($review_url); ?>" class="button">Leave a Review</a></p>
            <p><a href="<?php echo htmlspecialchars($order_url); ?>" class="button">View Order</a></p>

            <h3>Questions or Issues?</h3>
            <p>If there were any problems with your delivery or the items received, please don't hesitate to contact our support team. We're here to help!</p>

            <p>Thank you for shopping with Books eCommerce!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
