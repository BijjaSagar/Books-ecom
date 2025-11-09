<?php
// verify-email.php - Email verification handler
require_once 'includes/db_connect.php';
require_once 'includes/EmailVerification.php';

// Get parameters
$token = $_GET['token'] ?? '';
$user_id = $_GET['user_id'] ?? '';

$page_title_override = "Email Verification - Bookory";

// Initialize email verification
$email_verification = new EmailVerification($conn);

$verification_result = null;
if (!empty($token) && !empty($user_id)) {
    $verification_result = $email_verification->verifyEmailToken($token, $user_id);
}

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
        .verification-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem 0;
            display: flex;
            align-items: center;
        }

        .verification-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
            overflow: hidden;
        }

        .verification-header {
            padding: 2rem;
            text-align: center;
        }

        .success-header {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .error-header {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
        }

        .status-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }

        .verification-content {
            padding: 2rem;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            margin: 0.5rem;
        }
    </style>
</head>
<body>

<div class="verification-page">
    <div class="container">
        <div class="verification-container">
            <?php if ($verification_result && $verification_result['success']): ?>
                <!-- Success Header -->
                <div class="verification-header success-header">
                    <div class="status-icon">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h1 class="h2 fw-bold mb-2">Email Verified!</h1>
                    <p class="mb-0">Your email address has been successfully verified.</p>
                </div>
                
                <div class="verification-content">
                    <p class="lead">Welcome, <?php echo htmlspecialchars($verification_result['user']['full_name']); ?>!</p>
                    <p>Your account is now active and ready to use.</p>
                    <div class="mt-4">
                        <a href="/bookshelf/login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login to Your Account
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Error Header -->
                <div class="verification-header error-header">
                    <div class="status-icon">
                        <i class="bi bi-x-lg"></i>
                    </div>
                    <h1 class="h2 fw-bold mb-2">Verification Failed</h1>
                    <p class="mb-0">Unable to verify your email address.</p>
                </div>
                
                <div class="verification-content">
                    <p><?php echo htmlspecialchars($verification_result['error'] ?? 'Invalid verification link.'); ?></p>
                    <p class="text-muted"><small>This link may have expired or already been used.</small></p>
                    <div class="mt-4">
                        <a href="/bookshelf/register.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>Create New Account
                        </a>
                        <a href="/bookshelf/resend-verification.php" class="btn btn-outline-primary">
                            <i class="bi bi-envelope me-2"></i>Resend Verification
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>