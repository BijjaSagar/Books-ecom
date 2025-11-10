<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } else {
        // Check if this is the first time setup
        $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $result = $admin_check->get_result();
        $admin_count = $result->fetch_assoc()['count'];

        if ($admin_count == 0) {
            // First time setup - create default admin
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
                    $_SESSION['setup_message'] = "Admin account created successfully!";
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
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $errors[] = "Invalid credentials provided.";
                    }
                } else {
                    $errors[] = "Login error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Get site settings for branding
$settings_query = "SELECT setting_key, setting_value FROM site_settings";
$settings_result = $conn->query($settings_query);
$settings = ['site_name' => 'Bookory'];
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 10px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .logo span:first-child {
            color: #ff9900;
        }

        .logo span:last-child {
            color: #ffffff;
        }

        .tagline {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }

        .login-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: #146eb4;
            box-shadow: 0 0 0 3px rgba(20, 110, 180, 0.1);
            outline: none;
        }

        .btn-login {
            background: linear-gradient(135deg, #f0c14b 0%, #e6ac00 100%);
            border: 1px solid #a88734;
            border-radius: 6px;
            padding: 0.75rem;
            color: #111;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #e6ac00 0%, #d49e00 100%);
            border-color: #8c6d2a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-login:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(240, 193, 75, 0.3);
        }

        .setup-notice {
            background: #f0f8ff;
            border: 1px solid #d0e6ff;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #0a4a7c;
            font-size: 0.9rem;
        }

        .setup-notice strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .alert {
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #ffebee;
            border: 1px solid #ffcdd2;
            color: #c62828;
        }

        .footer {
            text-align: center;
            padding: 1rem;
            border-top: 1px solid #eee;
            background: #fafafa;
            font-size: 0.85rem;
            color: #666;
        }

        .help-link {
            color: #146eb4;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 1rem;
        }

        .help-link:hover {
            text-decoration: underline;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .form-control {
            padding-left: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <span>Book</span><span>ory</span>
            </div>
            <p class="tagline">Seller Central</p>
        </div>
        
        <div class="login-body">
            <?php
            $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $result = $admin_check->get_result();
            $admin_count = $result->fetch_assoc()['count'];

            if ($admin_count == 0): ?>
                <div class="setup-notice">
                    <strong>First Time Setup:</strong>
                    Enter your email and create a password to set up your admin account.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope"></i> Email Address
                    </label>
                    <div class="input-wrapper">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <button class="btn btn-login" type="submit">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    <?php echo $admin_count == 0 ? 'Create Admin Account' : 'Sign In to Seller Central'; ?>
                </button>
                
                <a href="#" class="help-link">
                    <i class="bi bi-question-circle me-1"></i> Need help signing in?
                </a>
            </form>
        </div>
        
        <div class="footer">
            © <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved.
        </div>
    </div>
</body>
</html>