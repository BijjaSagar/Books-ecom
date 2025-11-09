<?php
/**
 * Password Reset System
 * Handles user password reset functionality
 */

class PasswordReset {
    private $conn;
    private $settings;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->settings = get_site_settings();
    }
    
    /**
     * Generate password reset token for user
     * 
     * @param int $user_id User ID
     * @return string Password reset token
     */
    public function generateResetToken($user_id) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        
        // Set expiration (1 hour)
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Insert token into database
        $stmt = $this->conn->prepare("
            INSERT OR REPLACE INTO password_reset_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $token, SQLITE3_TEXT);
        $stmt->bindValue(3, $expires_at, SQLITE3_TEXT);
        $stmt->execute();
        $stmt->close();
        
        return $token;
    }
    
    /**
     * Send password reset email to user
     * 
     * @param array $user User data
     * @param string $token Reset token
     * @return bool True if email sent successfully
     */
    public function sendResetEmail($user, $token) {
        $site_name = $this->settings['site_name'] ?? 'Bookstore';
        $site_url = 'https://yoursite.com'; // This should be configured in settings
        
        // Create reset URL
        $reset_url = $site_url . "/reset-password.php?token=" . urlencode($token) . "&user_id=" . $user['id'];
        
        // Email subject
        $subject = "Password Reset Request - " . $site_name;
        
        // Email message
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>Password Reset Request</h2>
                
                <p>Dear {$user['full_name']},</p>
                
                <p>We received a request to reset your password for your {$site_name} account. If you made this request, please click the button below to reset your password:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_url}' 
                       style='background-color: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                
                <p>If the button above doesn't work, you can also copy and paste the following link into your browser:</p>
                <p style='word-break: break-all; color: #667eea;'>{$reset_url}</p>
                
                <p><strong>Note:</strong> This reset link will expire in 1 hour.</p>
                
                <p>If you didn't request a password reset, please ignore this email. Your account remains secure.</p>
                
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
     * Validate reset token
     * 
     * @param string $token Reset token
     * @param int $user_id User ID
     * @return array Validation result
     */
    public function validateResetToken($token, $user_id) {
        try {
            // Check if token exists and is valid
            $stmt = $this->conn->prepare("
                SELECT prt.*, u.email, u.full_name 
                FROM password_reset_tokens prt
                JOIN users u ON prt.user_id = u.id
                WHERE prt.user_id = ? AND prt.token = ? AND prt.expires_at > datetime('now')
            ");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $token, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if (!$result || !$result->fetchArray(SQLITE3_ASSOC)) {
                return [
                    'success' => false,
                    'error' => 'Invalid or expired reset token.'
                ];
            }
            
            $token_data = $result->fetchArray(SQLITE3_ASSOC);
            $stmt->close();
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user_id,
                    'email' => $token_data['email'],
                    'full_name' => $token_data['full_name']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Password reset token validation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during validation. Please try again.'
            ];
        }
    }
    
    /**
     * Reset user password
     * 
     * @param int $user_id User ID
     * @param string $new_password New password
     * @param string $token Reset token
     * @return array Reset result
     */
    public function resetPassword($user_id, $new_password, $token) {
        try {
            // Validate token first
            $validation_result = $this->validateResetToken($token, $user_id);
            if (!$validation_result['success']) {
                return $validation_result;
            }
            
            // Hash new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user password
            $update_stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bindValue(1, $password_hash, SQLITE3_TEXT);
            $update_stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Delete used token
            $delete_stmt = $this->conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
            $delete_stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            return [
                'success' => true,
                'message' => 'Password has been successfully reset.'
            ];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during password reset. Please try again.'
            ];
        }
    }
    
    /**
     * Request password reset
     * 
     * @param string $email User email
     * @return array Request result
     */
    public function requestPasswordReset($email) {
        try {
            // Check if user exists
            $stmt = $this->conn->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
            $stmt->bindValue(1, $email, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if (!$result || !$result->fetchArray(SQLITE3_ASSOC)) {
                // For security, we don't reveal if email exists
                return [
                    'success' => true,
                    'message' => 'If an account exists with this email, a password reset link has been sent.'
                ];
            }
            
            $user = $result->fetchArray(SQLITE3_ASSOC);
            $stmt->close();
            
            // Generate reset token
            $token = $this->generateResetToken($user['id']);
            
            // Send reset email
            $email_sent = $this->sendResetEmail($user, $token);
            
            if ($email_sent) {
                return [
                    'success' => true,
                    'message' => 'If an account exists with this email, a password reset link has been sent.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send reset email. Please try again.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
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
        $stmt = $this->conn->prepare("DELETE FROM password_reset_tokens WHERE expires_at < datetime('now')");
        $stmt->execute();
        $stmt->close();
        
        // SQLite doesn't have affected_rows, so we can't return count
        return 0;
    }
}