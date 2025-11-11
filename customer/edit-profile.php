<?php
/**
 * Customer Profile Edit Page
 * Allows customers to edit their account information and preferences
 */

session_start();

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /login.php');
    exit;
}

require_once '../includes/config.php';

$customer_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get customer data
$customer = $conn->prepare("SELECT * FROM users WHERE id = ?");
$customer->bind_param('i', $customer_id);
$customer->execute();
$customer_data = $customer->get_result()->fetch_assoc();
$customer->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $pincode = trim($_POST['pincode'] ?? '');

            // Validate inputs
            if (empty($name) || empty($email)) {
                $message = 'Name and email are required!';
                $message_type = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Please enter a valid email address!';
                $message_type = 'error';
            } else {
                // Check if email is already in use by another user
                $email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $email_check->bind_param('si', $email, $customer_id);
                $email_check->execute();
                if ($email_check->get_result()->num_rows > 0) {
                    $message = 'This email is already in use!';
                    $message_type = 'error';
                } else {
                    // Update user profile
                    $update = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
                    $update->bind_param('sssssssi', $name, $email, $phone, $address, $city, $state, $pincode, $customer_id);
                    
                    if ($update->execute()) {
                        $message = 'Profile updated successfully!';
                        $message_type = 'success';
                        // Refresh customer data
                        $customer_data['name'] = $name;
                        $customer_data['email'] = $email;
                        $customer_data['phone'] = $phone;
                        $customer_data['address'] = $address;
                        $customer_data['city'] = $city;
                        $customer_data['state'] = $state;
                        $customer_data['pincode'] = $pincode;
                    } else {
                        $message = 'Error updating profile. Please try again!';
                        $message_type = 'error';
                    }
                    $update->close();
                }
                $email_check->close();
            }
        } elseif ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate passwords
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $message = 'All password fields are required!';
                $message_type = 'error';
            } elseif (!password_verify($current_password, $customer_data['password'])) {
                $message = 'Current password is incorrect!';
                $message_type = 'error';
            } elseif ($new_password !== $confirm_password) {
                $message = 'New passwords do not match!';
                $message_type = 'error';
            } elseif (strlen($new_password) < 6) {
                $message = 'Password must be at least 6 characters long!';
                $message_type = 'error';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update_pwd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pwd->bind_param('si', $hashed_password, $customer_id);
                
                if ($update_pwd->execute()) {
                    $message = 'Password changed successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error changing password. Please try again!';
                    $message_type = 'error';
                }
                $update_pwd->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Bookstore</title>
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

        .page-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
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

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 32px;
            border-top: 4px solid var(--primary);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            display: block;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
        }

        .btn-secondary-custom {
            background: #6b7280;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-secondary-custom:hover {
            background: #4b5563;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .success-check {
            color: var(--success);
            font-size: 1.2rem;
            margin-right: 8px;
        }

        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 32px 0;
        }

        .nav-back {
            display: inline-block;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .nav-back:hover {
            color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.75rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-primary-custom,
            .btn-secondary-custom {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <h1>👤 Edit My Profile</h1>
            <p>Manage your account information and security settings</p>
        </div>
    </div>

    <div class="container mb-5">
        <a href="dashboard.php" class="nav-back">← Back to Dashboard</a>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php if ($message_type === 'success'): ?>
                    <span class="success-check">✓</span>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Personal Information Section -->
        <div class="section-card">
            <h2 class="section-title">Personal Information</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($customer_data['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer_data['email']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer_data['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($customer_data['address'] ?? ''); ?>" placeholder="Street address">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($customer_data['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($customer_data['state'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="pincode">Postal Code</label>
                    <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($customer_data['pincode'] ?? ''); ?>">
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary-custom">Save Changes</button>
                    <button type="reset" class="btn-secondary-custom">Clear</button>
                </div>
            </form>
        </div>

        <!-- Security Section -->
        <div class="section-card">
            <h2 class="section-title">🔐 Change Password</h2>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                Keep your account secure by using a strong, unique password. We recommend changing your password periodically.
            </p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <p class="form-hint">Enter your current password for security verification</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <p class="form-hint">Minimum 6 characters</p>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <p class="form-hint">Must match new password</p>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary-custom">Update Password</button>
                    <button type="reset" class="btn-secondary-custom">Clear</button>
                </div>
            </form>
        </div>

        <!-- Account Information Section -->
        <div class="section-card">
            <h2 class="section-title">Account Information</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Account Status</label>
                    <p style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-top: 8px;">
                        <span style="color: var(--success);">●</span> Active
                    </p>
                </div>
                <div>
                    <label style="color: var(--text-secondary); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Member Since</label>
                    <p style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-top: 8px;">
                        <?php echo date('M j, Y', strtotime($customer_data['created_at'])); ?>
                    </p>
                </div>
            </div>

            <div class="divider"></div>

            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0;">
                <strong>Email address:</strong> <?php echo htmlspecialchars($customer_data['email']); ?><br>
                <strong>Account ID:</strong> #<?php echo $customer_id; ?>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
