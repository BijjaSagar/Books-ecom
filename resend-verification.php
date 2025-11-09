<?php
// resend-verification.php - Resend email verification
require_once 'includes/db_connect.php';
require_once 'includes/EmailVerification.php';

$errors = [];
$success_message = '';
$email_sent = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($errors)) {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, full_name, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "No account found with this email address.";
        } else {
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Check if already verified
            if ($user['is_verified'] == 1) {
                $success_message = "This email address is already verified.";
                $email_sent = true;
            } else {
                // Resend verification email
                $email_verification = new EmailVerification($conn);
                $resend_result = $email_verification->resendVerificationEmail($user['id']);
                
                if ($resend_result['success']) {
                    $success_message = $resend_result['message'];
                    $email_sent = true;
                } else {
                    $errors[] = $resend_result['error'];
                }
            }
        }
    }
}

$page_title_override = "Resend Verification - Bookory";
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
        .resend-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem 0;
            display: flex;
            align-items: center;
        }

        .resend-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
            overflow: hidden;
        }

        .resend-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .resend-content {
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
    </style>
</head>
<body>

<div class="resend-page">
    <div class="container">
        <div class="resend-container">
            <div class="resend-header">
                <h1 class="h2 fw-bold mb-2">Resend Verification</h1>
                <p class="mb-0">Enter your email to receive a new verification link</p>
            </div>
            
            <div class="resend-content">
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
                
                <?php if (!$email_sent): ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="Enter your email address">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-envelope me-2"></i>Send Verification Email
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📧</div>
                        <p class="lead">Check your inbox for the verification email.</p>
                        <p class="text-muted">Didn't receive it? Check your spam folder or try again.</p>
                        <div class="mt-4">
                            <a href="/bookshelf/login.php" class="btn btn-outline-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <a href="/bookshelf/register.php">Create New Account</a> | 
                        <a href="/bookshelf/login.php">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>