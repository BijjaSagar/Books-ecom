<?php
// reset-password.php - Reset user password
require_once 'includes/db_connect.php';
require_once 'includes/PasswordReset.php';

// Get parameters
$token = $_GET['token'] ?? '';
$user_id = $_GET['user_id'] ?? '';

$errors = [];
$success_message = '';
$password_reset = false;

// Initialize password reset system
$password_reset_system = new PasswordReset($conn);

// Validate token if parameters are provided
$token_valid = false;
$user_data = null;
if (!empty($token) && !empty($user_id)) {
    $validation_result = $password_reset_system->validateResetToken($token, $user_id);
    if ($validation_result['success']) {
        $token_valid = true;
        $user_data = $validation_result['user'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($new_password)) {
        $errors[] = "Please enter a new password.";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        // Reset password
        $reset_result = $password_reset_system->resetPassword($user_id, $new_password, $token);
        
        if ($reset_result['success']) {
            $success_message = $reset_result['message'];
            $password_reset = true;
        } else {
            $errors[] = $reset_result['error'];
        }
    }
}

$page_title_override = "Reset Password - Bookory";
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_override; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .reset-password-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem 0;
            display: flex;
            align-items: center;
        }

        .reset-password-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
            overflow: hidden;
        }

        .reset-password-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .reset-password-content {
            padding: 2rem;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
        }

        .password-requirements {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="reset-password-page">
    <div class="container">
        <div class="reset-password-container">
            <div class="reset-password-header">
                <h1 class="h2 fw-bold mb-2">Reset Password</h1>
                <?php if ($token_valid): ?>
                    <p class="mb-0">Enter your new password for <?php echo htmlspecialchars($user_data['email']); ?></p>
                <?php else: ?>
                    <p class="mb-0">Invalid or expired reset link</p>
                <?php endif; ?>
            </div>
            
            <div class="reset-password-content">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($token_valid && !$password_reset): ?>
                    <div class="password-requirements">
                        <h6 class="fw-semibold mb-2"><i class="bi bi-info-circle me-2"></i>Password Requirements</h6>
                        <ul class="mb-0 small">
                            <li>At least 8 characters long</li>
                            <li>Include both letters and numbers</li>
                            <li>Not based on personal information</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required 
                                   placeholder="Enter your new password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your new password">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-lock me-2"></i>Reset Password
                        </button>
                    </form>
                <?php elseif ($password_reset): ?>
                    <div class="text-center">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                        <p class="lead">Password Reset Successfully!</p>
                        <p>Your password has been updated. You can now login with your new password.</p>
                        <div class="mt-4">
                            <a href="/bookshelf/login.php" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">❌</div>
                        <p class="lead">Invalid Reset Link</p>
                        <p>The password reset link is invalid or has expired.</p>
                        <div class="mt-4">
                            <a href="/bookshelf/forgot-password.php" class="btn btn-primary">
                                <i class="bi bi-arrow-repeat me-2"></i>Request New Link
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <a href="/bookshelf/login.php">Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>