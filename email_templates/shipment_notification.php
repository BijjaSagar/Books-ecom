<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; }
        .content { background: #f9f9f9; padding: 20px; }
        .tracking-box { background: white; padding: 20px; border: 2px solid #d4a574; border-radius: 4px; margin: 15px 0; }
        .tracking-field { margin: 10px 0; }
        .tracking-field strong { display: block; color: #1a3a52; }
        .button { background: #d4a574; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚚 Your Order Has Shipped!</h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <p>Great news! Your order <strong><?php echo htmlspecialchars($order_number); ?></strong> has been shipped and is on its way to you.</p>

            <div class="tracking-box">
                <h3>Tracking Information</h3>

                <div class="tracking-field">
                    <strong>Carrier:</strong>
                    <?php echo htmlspecialchars($carrier); ?>
                </div>

                <div class="tracking-field">
                    <strong>Tracking Number:</strong>
                    <?php echo htmlspecialchars($tracking_number); ?>
                </div>

                <div class="tracking-field">
                    <strong>Estimated Delivery:</strong>
                    <?php echo htmlspecialchars($estimated_delivery); ?>
                </div>

                <?php if (!empty($tracking_url)): ?>
                <p><a href="<?php echo htmlspecialchars($tracking_url); ?>" class="button">Track Your Package</a></p>
                <?php endif; ?>
            </div>

            <h3>Shipment Details</h3>
            <ul>
                <li>Your package is now in transit</li>
                <li>Use your tracking number to get real-time updates</li>
                <li>Expected delivery date is shown above (but may vary based on your location)</li>
                <li>Click the button above to see detailed tracking information</li>
            </ul>

            <p><a href="<?php echo htmlspecialchars($order_url); ?>" class="button">View Full Order</a></p>

            <p>If your shipment doesn't arrive within the estimated timeframe, please contact us for assistance.</p>

            <p>Thank you for your patience!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
