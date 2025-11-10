<?php
/**
 * Security Manager Class
 * Handles CSRF protection, input sanitization, output escaping
 * XSS prevention, and security best practices
 *
 * @version 1.0
 * @author Books eCommerce Platform
 */

class SecurityManager {
    private $conn;
    private $session_timeout = 3600; // 1 hour

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Initialize security headers
     * Call this at the beginning of every request
     */
    public static function setSecurityHeaders() {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');

        // Clickjacking protection
        header('X-Frame-Options: SAMEORIGIN');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self';");

        // Feature policy
        header('Feature-Policy: geolocation none; microphone none; camera none;');

        // HTTPS only
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Prevent caching sensitive pages
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get CSRF token for HTML forms
     */
    public function getCSRFTokenInput() {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        // Check if token exists
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }

        // Check token match
        if ($token !== $_SESSION['csrf_token']) {
            return false;
        }

        // Check token age (1 hour)
        $token_age = time() - ($_SESSION['csrf_token_time'] ?? 0);
        if ($token_age > $this->session_timeout) {
            return false;
        }

        return true;
    }

    /**
     * Sanitize input - removes potentially dangerous characters
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInput($item, $type);
            }, $input);
        }

        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);

            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);

            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);

            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT);

            case 'string':
                return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));

            case 'text':
                // Allow some HTML tags for rich text
                $allowed = '<p><br><strong><em><u><h1><h2><h3><ul><ol><li>';
                return trim(strip_tags($input, $allowed));

            default:
                return trim($input);
        }
    }

    /**
     * Escape output for HTML context
     */
    public static function escapeHTML($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape output for JavaScript context
     */
    public static function escapeJS($string) {
        return addslashes(str_replace(["\r", "\n"], ['\\r', '\\n'], $string));
    }

    /**
     * Escape output for URL context
     */
    public static function escapeURL($string) {
        return urlencode($string);
    }

    /**
     * Escape output for attribute context
     */
    public static function escapeAttribute($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email address
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    public function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate IP address
     */
    public function validateIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate password strength
     * Requirements: 8+ chars, uppercase, lowercase, number, special char
     */
    public function validatePasswordStrength($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain number';
        }

        if (!preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\\/]/', $password)) {
            $errors[] = 'Password must contain special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Check for SQL injection patterns
     */
    public function detectSQLInjection($input) {
        $sql_keywords = ['DROP', 'DELETE', 'INSERT', 'UPDATE', 'SELECT', 'UNION', 'ALTER', 'CREATE', 'EXEC', 'EXECUTE'];

        $input_upper = strtoupper($input);

        foreach ($sql_keywords as $keyword) {
            // Check for keyword followed by dangerous patterns
            if (preg_match('/\b' . $keyword . '\b\s*(--|;|\/\*|\*\/)/i', $input)) {
                return true;
            }
        }

        // Check for common SQL injection patterns
        if (preg_match("/('|\")\s*(OR|AND)\s*('|\")?.*?('|\")?=/i", $input)) {
            return true;
        }

        return false;
    }

    /**
     * Check for XSS patterns
     */
    public function detectXSS($input) {
        // Check for script tags
        if (preg_match('/<script[^>]*>.*?<\/script>/is', $input)) {
            return true;
        }

        // Check for event handlers
        if (preg_match('/on\w+\s*=/i', $input)) {
            return true;
        }

        // Check for iframe
        if (preg_match('/<iframe/i', $input)) {
            return true;
        }

        // Check for object/embed
        if (preg_match('/<(object|embed)[^>]*>/i', $input)) {
            return true;
        }

        // Check for data: protocol
        if (preg_match('/data:text\/html/i', $input)) {
            return true;
        }

        return false;
    }

    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
        $errors = [];

        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file size
        if ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds maximum limit';
        }

        // Check file type
        if (!empty($allowed_types)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $errors[] = 'File type not allowed';
            }
        }

        // Check for double extensions
        if (preg_match('/\.php\./i', $file['name'])) {
            $errors[] = 'Invalid file extension';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mime_type ?? null
        ];
    }

    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Rate limiting - check if action is allowed
     */
    public function checkRateLimit($action, $user_id, $limit = 10, $window = 60) {
        $key = "ratelimit:{$action}:{$user_id}";
        $count = $_SESSION[$key] ?? 0;
        $last_reset = $_SESSION[$key . ':time'] ?? time();

        // Reset if window has passed
        if (time() - $last_reset > $window) {
            $_SESSION[$key] = 0;
            $_SESSION[$key . ':time'] = time();
            $count = 0;
        }

        // Check limit
        if ($count >= $limit) {
            return ['allowed' => false, 'retry_after' => $window - (time() - $last_reset)];
        }

        // Increment counter
        $_SESSION[$key] = $count + 1;

        return ['allowed' => true];
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($event_type, $user_id, $description, $ip_address = null) {
        try {
            if ($ip_address === null) {
                $ip_address = $this->getClientIP();
            }

            $stmt = $this->conn->prepare("
                INSERT INTO security_logs (event_type, user_id, description, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $stmt->bind_param(
                "ssss",
                $event_type,
                $user_id,
                $description,
                $ip_address,
                $user_agent
            );

            $stmt->execute();

            return true;
        } catch (Exception $e) {
            error_log("[SecurityManager] Log Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get client IP address
     */
    public function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs in X-Forwarded-For header
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }

        // Validate IP
        if ($this->validateIP($ip)) {
            return $ip;
        }

        return 'Unknown';
    }

    /**
     * Create security log table if not exists
     */
    public function ensureSecurityLogTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS security_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                event_type VARCHAR(100) NOT NULL,
                user_id INT,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_event_type (event_type),
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->conn->query($sql);
    }

    /**
     * Get security logs for admin
     */
    public function getSecurityLogs($limit = 100, $offset = 0, $event_type = null) {
        try {
            $query = "SELECT * FROM security_logs WHERE 1=1";
            $params = [];
            $types = "";

            if ($event_type) {
                $query .= " AND event_type = ?";
                $params[] = $event_type;
                $types .= "s";
            }

            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";

            $stmt = $this->conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("[SecurityManager] Get Logs Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
