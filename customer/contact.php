<?php
/**
 * Contact Us Page
 * Customer support contact form
 */

session_start();
require_once '../includes/config.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    $category = trim($_POST['category']);
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($message_text) || strlen($message_text) < 10) $errors[] = 'Message must be at least 10 characters';
    
    if (empty($errors)) {
        // Create support ticket
        $insert = $conn->prepare("
            INSERT INTO support_tickets (name, email, phone, subject, message, category, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())
        ");
        
        $status_val = 'open';
        $insert->bind_param('ssssss', $name, $email, $phone, $subject, $message_text, $category);
        
        if ($insert->execute()) {
            $message = 'Thank you! Your message has been received. We will respond soon.';
            $message_type = 'success';
            
            // Clear form
            $name = $email = $phone = $subject = $message_text = $category = '';
        } else {
            $message = 'Error sending message. Please try again.';
            $message_type = 'error';
        }
        $insert->close();
    } else {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    }
}

// Get contact info from settings
$settings = $conn->prepare("SELECT * FROM system_settings LIMIT 1");
$settings->execute();
$settings_data = $settings->get_result()->fetch_assoc();
$settings->close();

$site_email = $settings_data['site_email'] ?? 'support@bookstore.com';
$support_phone = $settings_data['support_phone'] ?? '+91-1800-123-4567';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --border-color: #e5e7eb;
            --text-primary: #374151;
            --text-secondary: #6b7280;
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            background: #f3f4f6;
        }

        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .page-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
        }

        .container-custom {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            margin-bottom: 40px;
        }

        .contact-form-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .contact-info-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .alert {
            border: none;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .contact-item {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .contact-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .contact-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 4px 0;
        }

        .contact-details p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.95rem;
        }

        .contact-details a {
            color: var(--primary);
            text-decoration: none;
        }

        .contact-details a:hover {
            text-decoration: underline;
        }

        .hours-box {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            margin-top: 24px;
            border-left: 4px solid var(--primary);
        }

        .hours-box h4 {
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .hours-box p {
            margin: 4px 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container-custom {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .contact-form-card,
            .contact-info-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php" style="color: var(--primary); font-weight: 700;">📚 Books</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="edit-profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>📞 Contact Us</h1>
            <p>Get in touch with our support team</p>
        </div>
    </div>

    <!-- Contact Content -->
    <div class="container">
        <div class="container-custom">
            <!-- Contact Form -->
            <div class="contact-form-card">
                <h2 class="card-title">Send us a Message</h2>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="general">General Inquiry</option>
                            <option value="order">Order Related</option>
                            <option value="delivery">Delivery Issue</option>
                            <option value="product">Product Quality</option>
                            <option value="refund">Refund/Return</option>
                            <option value="feedback">Feedback</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($message_text ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="contact-info-card">
                <h2 class="card-title">Contact Information</h2>

                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div class="contact-details">
                        <h3>Email</h3>
                        <p>
                            <a href="mailto:<?php echo htmlspecialchars($site_email); ?>">
                                <?php echo htmlspecialchars($site_email); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">📱</div>
                    <div class="contact-details">
                        <h3>Phone</h3>
                        <p>
                            <a href="tel:<?php echo htmlspecialchars($support_phone); ?>">
                                <?php echo htmlspecialchars($support_phone); ?>
                            </a>
                        </p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">📍</div>
                    <div class="contact-details">
                        <h3>Address</h3>
                        <p>
                            Books Bookstore<br>
                            Mumbai, Maharashtra<br>
                            India
                        </p>
                    </div>
                </div>

                <div class="hours-box">
                    <h4>🕐 Business Hours</h4>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                    <p>Saturday: 10:00 AM - 4:00 PM</p>
                    <p>Sunday: Closed</p>
                </div>

                <div style="margin-top: 24px; padding: 16px; background: #f9fafb; border-radius: 8px; border-left: 4px solid var(--primary);">
                    <h4 style="color: var(--primary); margin: 0 0 8px 0;">Response Time</h4>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.9rem;">
                        We aim to respond to all inquiries within 24 hours during business days.
                    </p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="contact-form-card">
            <h2 class="card-title">❓ Frequently Asked Questions</h2>
            
            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <h4 style="color: var(--primary); margin: 0 0 8px 0;">What is your return policy?</h4>
                <p style="color: var(--text-secondary); margin: 0;">We offer hassle-free returns within 30 days of purchase. Items must be in original condition.</p>
            </div>

            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <h4 style="color: var(--primary); margin: 0 0 8px 0;">How long does delivery take?</h4>
                <p style="color: var(--text-secondary); margin: 0;">Standard delivery takes 3-5 business days. Express delivery is available for urgent orders.</p>
            </div>

            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <h4 style="color: var(--primary); margin: 0 0 8px 0;">Do you ship internationally?</h4>
                <p style="color: var(--text-secondary); margin: 0;">Currently, we ship within India only. International shipping coming soon!</p>
            </div>

            <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                <h4 style="color: var(--primary); margin: 0 0 8px 0;">What payment methods do you accept?</h4>
                <p style="color: var(--text-secondary); margin: 0;">We accept credit cards, debit cards, net banking, UPI, and digital wallets.</p>
            </div>

            <div>
                <h4 style="color: var(--primary); margin: 0 0 8px 0;">Can I cancel my order?</h4>
                <p style="color: var(--text-secondary); margin: 0;">Orders can be cancelled within 2 hours of placement. Contact support immediately for faster processing.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
