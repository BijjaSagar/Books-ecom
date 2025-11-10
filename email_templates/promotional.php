<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; text-align: center; }
        .banner { background: url('<?php echo $banner_image; ?>') center/cover; height: 200px; display: flex; align-items: center; justify-content: center; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .content { background: #f9f9f9; padding: 20px; }
        .offer-box { background: white; border: 2px dashed #d4a574; padding: 20px; margin: 15px 0; border-radius: 4px; text-align: center; }
        .discount-code { font-size: 32px; font-weight: bold; color: #d4a574; font-family: monospace; letter-spacing: 2px; background: #f0f0f0; padding: 15px; border-radius: 4px; }
        .button { background: #d4a574; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; font-size: 16px; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($promo_title); ?></h1>
        </div>

        <?php if (!empty($banner_image)): ?>
        <div class="banner"></div>
        <?php endif; ?>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <p><?php echo nl2br(htmlspecialchars($promo_description)); ?></p>

            <?php if (!empty($discount_code)): ?>
            <div class="offer-box">
                <h3>Use This Discount Code</h3>
                <div class="discount-code"><?php echo htmlspecialchars($discount_code); ?></div>
                <?php if (!empty($discount_percent)): ?>
                <p style="margin-top: 15px; font-size: 18px;"><strong>Save <?php echo htmlspecialchars($discount_percent); ?>%</strong> on your next order</p>
                <?php endif; ?>
                <?php if (!empty($offer_expires)): ?>
                <p style="color: #e74c3c;">Offer expires: <?php echo htmlspecialchars($offer_expires); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p style="text-align: center;">
                <a href="<?php echo htmlspecialchars($shop_url); ?>" class="button">Shop Now</a>
            </p>

            <p><strong>Don't miss out!</strong> This special offer is available for a limited time only.</p>

            <p>Happy reading!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p>This is a promotional email. <a href="#unsubscribe" style="color: #d4a574;">Unsubscribe from promotions</a></p>
        </div>
    </div>
</body>
</html>
