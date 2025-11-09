<?php
// Move all PHP logic to the top, before any HTML output.
require_once 'includes/db_connect.php';
require_once 'includes/EmailVerification.php';

$errors = [];
$show_alert = false; // This will trigger our SweetAlert
$show_verification_message = false; // Show verification message

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validation...
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (empty($password) || strlen($password) < 8) $errors[] = "Password must be at least 8 characters long.";
    if ($password !== $password_confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "An account with this email already exists.";
        $stmt->close();
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // Add is_verified field with default value 0 (not verified)
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, is_verified) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $full_name, $email, $password_hash);
        
        if ($stmt->execute()) {
            // Get the new user ID
            $user_id = $conn->insert_id;
            
            // Initialize email verification
            $email_verification = new EmailVerification($conn);
            $token = $email_verification->generateVerificationToken($user_id);
            
            // Get user data for email
            $user = [
                'id' => $user_id,
                'email' => $email,
                'full_name' => $full_name
            ];
            
            // Send verification email
            $email_sent = $email_verification->sendVerificationEmail($user, $token);
            
            if ($email_sent) {
                // Show verification message instead of immediate success
                $show_verification_message = true;
            } else {
                // If email fails, still allow registration but show warning
                $show_alert = true;
                $errors[] = "Account created but verification email could not be sent. Please contact support.";
            }
        } else {
            $errors[] = "An error occurred. Please try again.";
        }
        $stmt->close();
    }
}

// Now we can start the HTML
include 'includes/header.php';
?>

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <h1 class="card-title text-center mb-4">Create Account</h1>
            
            <!-- We no longer need the success message div -->

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <!-- Hide form on success -->
            <?php if (!$show_alert && !$show_verification_message): ?>
            <form action="register.php" method="POST">
                <div class="mb-3"><label for="full_name" class="form-label">Full Name</label><input type="text" class="form-control" id="full_name" name="full_name" required></div>
                <div class="mb-3"><label for="email" class="form-label">Email address</label><input type="email" class="form-control" id="email" name="email" required></div>
                <div class="mb-3"><label for="password" class="form-label">Password</label><input type="password" class="form-control" id="password" name="password" required></div>
                <div class="mb-3"><label for="password_confirm" class="form-label">Confirm Password</label><input type="password" class="form-control" id="password_confirm" name="password_confirm" required></div>
                <button type="submit" class="btn btn-primary w-100 mt-3">Register</button>
            </form>
            <?php elseif ($show_verification_message): ?>
                <!-- Show verification message -->
                <div class="text-center">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📧</div>
                    <h3>Check Your Email</h3>
                    <p class="lead">We've sent a verification email to <strong><?php echo htmlspecialchars($_POST['email'] ?? ''); ?></strong></p>
                    <p>Please check your inbox and click the verification link to activate your account.</p>
                    <p class="text-muted"><small>The verification link will expire in 24 hours.</small></p>
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-success">Login</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show this message after the alert is closed -->
                <div class="text-center">
                    <p class="lead">Thank you for registering!</p>
                    <a href="login.php" class="btn btn-success">Click here to Login</a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <p class="text-muted">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';

// --- This is the JavaScript part ---
// We only output this script block if the registration was successful
if ($show_alert) {
    echo "
    <script>
        // Use SweetAlert to show a nice popup
        Swal.fire({
            icon: 'success',
            title: 'Registration Successful!',
            text: 'You can now log in with your new account.',
            confirmButtonText: 'Great!'
        });
    </script>
    ";
}
?>