<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../includes/config.php';

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$admin_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } else {
        // Check if this is the first time setup
        $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        if ($admin_check) {
            $row = $admin_check->fetch_assoc();
            $admin_count = $row ? $row['count'] : 0;
        }

        if (empty($errors) && $admin_count == 0) {
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
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $errors[] = "Failed to create admin account: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif (empty($errors)) {
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

// Get admin count for display
$admin_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
if ($admin_result) {
    $row = $admin_result->fetch_assoc();
    $admin_count = $row ? $row['count'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bookory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .login-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .logo-text .orange {
            color: #ff9900;
        }
        .tagline {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        .login-form {
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #146eb4;
            box-shadow: 0 0 0 3px rgba(20, 110, 180, 0.1);
            outline: none;
        }
        .btn-submit {
            background: linear-gradient(135deg, #f0c14b 0%, #e6ac00 100%);
            border: 1px solid #a88734;
            color: #111;
            font-weight: 600;
            padding: 0.75rem;
            width: 100%;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #e6ac00 0%, #d49e00 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .setup-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .footer {
            text-align: center;
            padding: 1rem;
            border-top: 1px solid #eee;
            background: #fafafa;
            font-size: 0.85rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <div class="logo-text">
                <span class="orange">Book</span><span>ory</span>
            </div>
            <p class="tagline">Seller Central</p>
        </div>

        <div class="login-form">
            <?php if ($admin_count == 0): ?>
                <div class="setup-box">
                    <strong>First Time Setup:</strong><br>
                    Create your admin account by entering an email and password.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <?php echo $admin_count == 0 ? 'Create Admin Account' : 'Sign In'; ?>
                </button>
            </form>
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> Bookory. All rights reserved.
        </div>
    </div>
</body>
</html>
