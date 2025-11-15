<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; }
        .content { background: #f9f9f9; padding: 20px; }
        .section { background: white; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .section h3 { color: #1a3a52; border-bottom: 2px solid #d4a574; padding-bottom: 10px; }
        .featured-item { background: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 4px solid #d4a574; }
        .featured-item strong { color: #1a3a52; }
        .button { background: #d4a574; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($newsletter_title); ?></h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <div class="section">
                <h3>📚 Featured In This Issue</h3>
                <p><?php echo nl2br(htmlspecialchars($newsletter_content)); ?></p>
            </div>

            <?php if (!empty($featured_products)): ?>
            <div class="section">
                <h3>🌟 Featured Books</h3>
                <?php foreach ($featured_products as $product): ?>
                <div class="featured-item">
                    <strong><?php echo htmlspecialchars($product['title']); ?></strong>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <p>Price: <?php echo htmlspecialchars($product['price']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($featured_articles)): ?>
            <div class="section">
                <h3>📖 Recent Articles</h3>
                <?php foreach ($featured_articles as $article): ?>
                <div class="featured-item">
                    <strong><?php echo htmlspecialchars($article['title']); ?></strong>
                    <p><?php echo htmlspecialchars($article['excerpt']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <p style="text-align: center;">
                <a href="<?php echo htmlspecialchars($shop_url); ?>" class="button">Browse All Books</a>
            </p>

            <p>Thank you for subscribing to our newsletter. We hope you enjoy the curated content and special offers!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p><a href="<?php echo htmlspecialchars($unsubscribe_url); ?>" style="color: #d4a574;">Unsubscribe from newsletter</a></p>
        </div>
    </div>
</body>
</html>
