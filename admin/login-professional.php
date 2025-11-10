<?php
/**
 * Professional Admin Login Page
 * Books & eBooks eCommerce Platform
 *
 * Features:
 * - Clean, minimal design
 * - Professional appearance
 * - Comprehensive validation
 * - Book-themed branding
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$success = '';
$is_setup = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    if (empty($errors)) {
        // Check if this is first-time setup
        $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $admin_count = 0;
        if ($admin_check) {
            $row = $admin_check->fetch_assoc();
            $admin_count = $row ? $row['count'] : 0;
        }

        if ($admin_count == 0) {
            // First time setup - create default admin
            $is_setup = true;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");

            if (!$stmt) {
                $errors[] = "Database error: " . $conn->error;
            } else {
                $admin_name = "Administrator";
                $stmt->bind_param("sss", $admin_name, $email, $hashed_password);

                if ($stmt->execute()) {
                    $_SESSION['admin_id'] = $conn->insert_id;
                    $_SESSION['admin_name'] = $admin_name;
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['setup_message'] = "Welcome! Your admin account has been created successfully.";
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $errors[] = "Failed to create admin account: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            // Regular login
            $stmt = $conn->prepare("SELECT id, full_name, email, password FROM users WHERE email = ? AND role = 'admin'");

            if (!$stmt) {
                $errors[] = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $email);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    if ($user && password_verify($password, $user['password'])) {
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_name'] = $user['full_name'];
                        $_SESSION['admin_email'] = $user['email'];
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $errors[] = "Invalid email or password. Please try again.";
                    }
                } else {
                    $errors[] = "Login error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Get site settings
$site_name = 'Bookory';
$site_tagline = 'Books & eBooks eCommerce Platform';
$settings_query = "SELECT setting_key, setting_value FROM site_settings LIMIT 10";
$settings_result = $conn->query($settings_query);
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        if ($row['setting_key'] === 'site_name') {
            $site_name = $row['setting_value'];
        } elseif ($row['setting_key'] === 'site_tagline') {
            $site_tagline = $row['setting_value'];
        }
    }
}

// Check if any admins exist
$admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_count = 0;
if ($admin_check) {
    $row = $admin_check->fetch_assoc();
    $admin_count = $row ? $row['count'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($admin_count == 0 ? 'Setup' : 'Admin Login'); ?> - <?php echo htmlspecialchars($site_name); ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin-theme.css">

    <style>
        body {
            background: linear-gradient(135deg, #1a3a52 0%, #2d5a7b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin: 1rem;
        }

        .login-left {
            flex: 1;
            padding: 4rem 3rem;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-right {
            flex: 1;
            background: linear-gradient(135deg, #1a3a52 0%, #2d5a7b 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 300px;
            height: 300px;
            background: rgba(212, 165, 116, 0.1);
            border-radius: 50%;
        }

        .login-right-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .brand-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #d4a574;
        }

        .login-right h2 {
            color: white;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .login-right p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .login-left h1 {
            font-size: 1.8rem;
            color: #1a3a52;
            margin-bottom: 0.5rem;
        }

        .login-left .subtitle {
            color: #6c757d;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a3a52;
            box-shadow: 0 0 0 3px rgba(26, 58, 82, 0.1);
        }

        .form-group input::placeholder {
            color: #95a5a6;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid #27ae60;
        }

        .alert i {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content p {
            margin: 0;
            font-size: 0.9rem;
        }

        .alert-content p + p {
            margin-top: 0.5rem;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .remember-forgot a {
            color: #1a3a52;
            text-decoration: none;
        }

        .remember-forgot a:hover {
            text-decoration: underline;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-wrapper input[type="checkbox"] {
            cursor: pointer;
            width: 16px;
            height: 16px;
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #1a3a52, #2d5a7b);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            box-shadow: 0 8px 16px rgba(26, 58, 82, 0.3);
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .setup-badge {
            display: inline-block;
            background: #d4a574;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .features {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .features h3 {
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .features-list {
            list-style: none;
            padding: 0;
        }

        .features-list li {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .features-list i {
            color: #d4a574;
            width: 16px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                padding: 2rem 1.5rem;
            }

            .login-right {
                padding: 2rem 1.5rem;
                min-height: 300px;
            }

            .login-left h1 {
                font-size: 1.5rem;
            }

            .brand-icon {
                font-size: 3rem;
            }

            .login-right h2 {
                font-size: 1.3rem;
            }

            .features {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                border-radius: 0;
                margin: 0;
            }

            .login-left {
                padding: 2rem 1rem;
            }

            .login-left h1 {
                font-size: 1.3rem;
            }

            .form-group input {
                padding: 0.65rem 0.75rem;
                font-size: 0.9rem;
            }

            .login-btn {
                padding: 0.65rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- LEFT SIDE - LOGIN FORM -->
        <div class="login-left">
            <?php if ($admin_count == 0): ?>
                <span class="setup-badge">Initial Setup</span>
            <?php endif; ?>

            <h1><?php echo $admin_count == 0 ? 'Create Admin Account' : 'Admin Login'; ?></h1>
            <p class="subtitle">
                <?php echo htmlspecialchars($site_name); ?> -
                <?php echo htmlspecialchars($site_tagline); ?>
            </p>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <div class="alert-content">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" onsubmit="return validateLoginForm()">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="admin@bookory.com"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="<?php echo $admin_count == 0 ? 'Create a password (min 6 chars)' : 'Enter your password'; ?>"
                        required
                        autocomplete="<?php echo $admin_count == 0 ? 'new-password' : 'current-password'; ?>"
                        minlength="6"
                    >
                </div>

                <?php if ($admin_count > 0): ?>
                    <div class="remember-forgot">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="remember" name="remember" value="1">
                            <label for="remember" style="margin-bottom: 0; cursor: pointer;">Remember me</label>
                        </div>
                        <a href="#" title="Contact administrator for password reset">Forgot password?</a>
                    </div>
                <?php endif; ?>

                <button type="submit" class="login-btn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <?php echo $admin_count == 0 ? 'Create Account' : 'Sign In'; ?>
                </button>
            </form>
        </div>

        <!-- RIGHT SIDE - BRANDING & INFO -->
        <div class="login-right">
            <div class="login-right-content">
                <div class="brand-icon">
                    <i class="bi bi-book-half"></i>
                </div>

                <h2><?php echo htmlspecialchars($site_name); ?></h2>
                <p>Professional Books & eBooks eCommerce Platform</p>

                <div class="features">
                    <h3>Features</h3>
                    <ul class="features-list">
                        <li><i class="bi bi-check2"></i> Complete Product Management</li>
                        <li><i class="bi bi-check2"></i> Order & Inventory Tracking</li>
                        <li><i class="bi bi-check2"></i> Customer Analytics</li>
                        <li><i class="bi bi-check2"></i> Sales Reports & Metrics</li>
                        <li><i class="bi bi-check2"></i> Multi-Language Support</li>
                        <li><i class="bi bi-check2"></i> Professional Design</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Validation Script -->
    <script>
        function validateLoginForm() {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            // Reset any previous errors (handled by server)
            let isValid = true;

            // Email validation
            if (!email) {
                showFieldError('email', 'Email is required');
                isValid = false;
            } else if (!isValidEmail(email)) {
                showFieldError('email', 'Please enter a valid email address');
                isValid = false;
            } else {
                clearFieldError('email');
            }

            // Password validation
            if (!password) {
                showFieldError('password', 'Password is required');
                isValid = false;
            } else if (password.length < 6) {
                showFieldError('password', 'Password must be at least 6 characters');
                isValid = false;
            } else {
                clearFieldError('password');
            }

            return isValid;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            field.style.borderColor = '#e74c3c';
            field.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
        }

        function clearFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            field.style.borderColor = '#ddd';
            field.style.boxShadow = 'none';
        }

        // Clear error styling on input
        document.getElementById('email').addEventListener('input', function() {
            clearFieldError('email');
        });

        document.getElementById('password').addEventListener('input', function() {
            clearFieldError('password');
        });
    </script>
</body>
</html>
