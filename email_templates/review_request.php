<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; }
        .content { background: #f9f9f9; padding: 20px; }
        .product-list { background: white; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .product-item { padding: 10px; border-bottom: 1px solid #eee; }
        .product-item:last-child { border-bottom: none; }
        .button { background: #d4a574; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Share Your Experience ⭐</h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <p>We hope you're enjoying your recent purchase! Your feedback helps other readers make informed decisions and helps us improve our service.</p>

            <h3>Books You Recently Received:</h3>
            <div class="product-list">
                <?php foreach ($products as $product): ?>
                <div class="product-item">
                    <strong><?php echo htmlspecialchars($product['title']); ?></strong>
                </div>
                <?php endforeach; ?>
            </div>

            <h3>Leave Your Reviews</h3>
            <p>Your honest reviews are incredibly valuable! Please share:</p>
            <ul>
                <li>How would you rate the books? (1-5 stars)</li>
                <li>What did you like about them?</li>
                <li>Any suggestions for improvement?</li>
                <li>Would you recommend these books to others?</li>
            </ul>

            <p><a href="<?php echo htmlspecialchars($review_url); ?>" class="button">Write Your Reviews</a></p>

            <p>Reviews typically appear on the product pages within 24 hours of submission.</p>

            <p>Thank you for being a valued customer!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
