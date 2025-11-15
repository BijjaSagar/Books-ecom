<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; }
        .content { background: #f9f9f9; padding: 20px; }
        .refund-box { background: white; border: 2px solid #4caf50; padding: 20px; margin: 15px 0; border-radius: 4px; }
        .refund-amount { font-size: 24px; color: #4caf50; font-weight: bold; }
        .button { background: #d4a574; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Refund Processed ✓</h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <p>Your refund has been processed successfully!</p>

            <div class="refund-box">
                <h3>Refund Details</h3>
                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order_number); ?></p>
                <p><strong>Refund Amount:</strong> <span class="refund-amount"><?php echo htmlspecialchars($currency); ?> <?php echo number_format($refund_amount, 2); ?></span></p>
                <?php if (!empty($reason)): ?>
                <p><strong>Reason:</strong> <?php echo htmlspecialchars($reason); ?></p>
                <?php endif; ?>
            </div>

            <h3>When Will I See the Refund?</h3>
            <ul>
                <li>Your refund has been approved and initiated</li>
                <li>Depending on your bank, it may take 3-5 business days to appear in your account</li>
                <li>Some banks may take longer during weekends or holidays</li>
                <li>If you have multiple transactions, each may process separately</li>
            </ul>

            <h3>Not Received Your Refund?</h3>
            <p>If you don't see the refund within 5 business days, please contact us and reference your order number.</p>

            <p><a href="<?php echo htmlspecialchars($order_url); ?>" class="button">View Order</a></p>

            <p>We appreciate your business and hope to serve you again in the future!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
