<?php
/**
 * Email Verification System
 * Handles user email verification process
 */

class EmailVerification {
    private $conn;
    private $settings;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->settings = get_site_settings();
    }
    
    /**
     * Generate verification token for user
     * 
     * @param int $user_id User ID
     * @return string Verification token
     */
    public function generateVerificationToken($user_id) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        
        // Set expiration (24 hours)
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Insert token into database
        $stmt = $this->conn->prepare("
            INSERT INTO email_verification_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
        ");
        $stmt->bind_param("iss", $user_id, $token, $expires_at);
        $stmt->execute();
        $stmt->close();
        
        return $token;
    }
    
    /**
     * Send verification email to user
     * 
     * @param array $user User data
     * @param string $token Verification token
     * @return bool True if email sent successfully
     */
    public function sendVerificationEmail($user, $token) {
        $site_name = $this->settings['site_name'] ?? 'Bookstore';
        $site_url = 'https://yoursite.com'; // This should be configured in settings
        
        // Create verification URL
        $verification_url = $site_url . "/verify-email.php?token=" . urlencode($token) . "&user_id=" . $user['id'];
        
        // Email subject
        $subject = "Verify Your Email Address - " . $site_name;
        
        // Email message
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>Welcome to {$site_name}!</h2>
                
                <p>Dear {$user['full_name']},</p>
                
                <p>Thank you for registering with {$site_name}. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$verification_url}' 
                       style='background-color: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                        Verify Email Address
                    </a>
                </div>
                
                <p>If the button above doesn't work, you can also copy and paste the following link into your browser:</p>
                <p style='word-break: break-all; color: #667eea;'>{$verification_url}</p>
                
                <p><strong>Note:</strong> This verification link will expire in 24 hours.</p>
                
                <p>If you didn't create an account with us, please ignore this email.</p>
                
                <p>Best regards,<br>{$site_name} Team</p>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . ($this->settings['site_email'] ?? 'noreply@bookshelf.com') . "\r\n";
        $headers .= "Reply-To: " . ($this->settings['site_email'] ?? 'noreply@bookshelf.com') . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send email
        return mail($user['email'], $subject, $message, $headers);
    }
    
    /**
     * Verify email token
     * 
     * @param string $token Verification token
     * @param int $user_id User ID
     * @return array Verification result
     */
    public function verifyEmailToken($token, $user_id) {
        try {
            // Check if token exists and is valid
            $stmt = $this->conn->prepare("
                SELECT evt.*, u.email, u.full_name 
                FROM email_verification_tokens evt
                JOIN users u ON evt.user_id = u.id
                WHERE evt.user_id = ? AND evt.token = ? AND evt.expires_at > NOW()
            ");
            $stmt->bind_param("is", $user_id, $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'success' => false,
                    'error' => 'Invalid or expired verification token.'
                ];
            }
            
            $token_data = $result->fetch_assoc();
            $stmt->close();
            
            // Update user as verified
            $update_stmt = $this->conn->prepare("UPDATE users SET is_verified = 1, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Delete used token
            $delete_stmt = $this->conn->prepare("DELETE FROM email_verification_tokens WHERE user_id = ?");
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user_id,
                    'email' => $token_data['email'],
                    'full_name' => $token_data['full_name']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during verification. Please try again.'
            ];
        }
    }
    
    /**
     * Check if user's email is verified
     * 
     * @param int $user_id User ID
     * @return bool True if email is verified
     */
    public function isEmailVerified($user_id) {
        $stmt = $this->conn->prepare("SELECT is_verified FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user && $user['is_verified'] == 1;
    }
    
    /**
     * Resend verification email
     * 
     * @param int $user_id User ID
     * @return array Result
     */
    public function resendVerificationEmail($user_id) {
        try {
            // Get user data
            $stmt = $this->conn->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'success' => false,
                    'error' => 'User not found.'
                ];
            }
            
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Check if already verified
            if ($this->isEmailVerified($user_id)) {
                return [
                    'success' => false,
                    'error' => 'Email is already verified.'
                ];
            }
            
            // Generate new token
            $token = $this->generateVerificationToken($user_id);
            
            // Send verification email
            $email_sent = $this->sendVerificationEmail($user, $token);
            
            if ($email_sent) {
                return [
                    'success' => true,
                    'message' => 'Verification email has been resent successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send verification email. Please try again.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Resend verification email error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Clean expired tokens
     * 
     * @return int Number of deleted tokens
     */
    public function cleanExpiredTokens() {
        $stmt = $this->conn->prepare("DELETE FROM email_verification_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        $deleted_count = $stmt->affected_rows;
        $stmt->close();
        
        return $deleted_count;
    }
}