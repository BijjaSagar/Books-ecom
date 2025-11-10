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
$site_name = 'Bookory';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } else {
        // Check if this is the first time setup
        $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        if ($admin_check) {
            $result = $admin_check->get_result();
            $row = $result->fetch_assoc();
            $admin_count = $row ? $row['count'] : 0;
        } else {
            $errors[] = "Database error: " . $conn->error;
            $admin_count = 0;
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
                    $_SESSION['setup_message'] = "Admin account created successfully!";
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
$admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_count = 0;
if ($admin_check) {
    $result = $admin_check->get_result();
    $row = $result->fetch_assoc();
    $admin_count = $row ? $row['count'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($site_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
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
            display: block;
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            padding: 0.75rem 1rem;
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
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #e6ac00 0%, #d49e00 100%);
        }

        .alert {
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .setup-notice {
            background: #f0f8ff;
            border: 1px solid #d0e6ff;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #0a4a7c;
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
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <span style="color: #ff9900;">Book</span><span>ory</span>
            </div>
            <p class="tagline">Seller Central</p>
        </div>

        <div class="login-body">
            <?php if ($admin_count == 0): ?>
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

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button class="btn btn-login" type="submit">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    <?php echo $admin_count == 0 ? 'Create Admin Account' : 'Sign In to Seller Central'; ?>
                </button>
            </form>
        </div>

        <div class="footer">
            © <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. All rights reserved.
        </div>
    </div>
</body>
</html>
