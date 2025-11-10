<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a52; color: white; padding: 20px; border-radius: 4px 4px 0 0; }
        .content { background: #f9f9f9; padding: 20px; }
        .ticket-info { background: white; padding: 15px; border-left: 4px solid #d4a574; margin: 15px 0; border-radius: 4px; }
        .response-box { background: white; padding: 15px; border: 1px solid #ddd; margin: 15px 0; border-radius: 4px; }
        .button { background: #d4a574; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
        .footer { background: #1a3a52; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 4px 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Support Ticket Response</h1>
        </div>

        <div class="content">
            <p>Hello <?php echo htmlspecialchars($customer_name); ?>,</p>

            <p>Our support team has responded to your support ticket. Please see the response below:</p>

            <div class="ticket-info">
                <h3>Ticket Number: <?php echo htmlspecialchars($ticket_number); ?></h3>
                <p>Status: <strong>Awaiting Your Response</strong></p>
            </div>

            <h3>Support Response</h3>
            <div class="response-box">
                <?php echo nl2br(htmlspecialchars($response_message)); ?>
            </div>

            <h3>Next Steps</h3>
            <ul>
                <li>Read the response from our support team carefully</li>
                <li>If you need further assistance, you can reply to this ticket</li>
                <li>If the issue is resolved, you can close the ticket</li>
            </ul>

            <p><a href="<?php echo htmlspecialchars($ticket_url); ?>" class="button">View Full Ticket</a></p>

            <p>Thank you for contacting our support team. We're here to help!</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Books eCommerce. All rights reserved.</p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
