<?php
/**
 * Environment Variable Loader
 *
 * Loads environment variables from .env file
 * Provides helper functions for accessing environment variables
 *
 * @package Bookory
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ENV_LOADER')) {
    define('ENV_LOADER', true);
}

/**
 * Load environment variables from .env file
 *
 * @param string $path Path to .env file
 * @return bool True if loaded successfully
 */
function load_env($path = null) {
    // Default to root directory
    if ($path === null) {
        $path = __DIR__ . '/../.env';
    }

    // Check if file exists
    if (!file_exists($path)) {
        error_log("Environment file not found: {$path}");
        return false;
    }

    // Check if file is readable
    if (!is_readable($path)) {
        error_log("Environment file not readable: {$path}");
        return false;
    }

    // Read file line by line
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        error_log("Failed to read environment file: {$path}");
        return false;
    }

    foreach ($lines as $line) {
        // Skip comments and empty lines
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            // Remove quotes from value if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    return true;
}

/**
 * Get environment variable value
 *
 * @param string $key Variable name
 * @param mixed $default Default value if not found
 * @return mixed Variable value or default
 */
function env($key, $default = null) {
    // Check $_ENV first
    if (array_key_exists($key, $_ENV)) {
        return parse_env_value($_ENV[$key]);
    }

    // Check getenv
    $value = getenv($key);
    if ($value !== false) {
        return parse_env_value($value);
    }

    // Check $_SERVER
    if (array_key_exists($key, $_SERVER)) {
        return parse_env_value($_SERVER[$key]);
    }

    return $default;
}

/**
 * Parse environment value to appropriate type
 *
 * @param string $value Raw value from .env
 * @return mixed Parsed value
 */
function parse_env_value($value) {
    if ($value === '') {
        return '';
    }

    // Convert boolean strings
    $lower = strtolower($value);
    if ($lower === 'true' || $lower === '(true)') {
        return true;
    }
    if ($lower === 'false' || $lower === '(false)') {
        return false;
    }

    // Convert null
    if ($lower === 'null' || $lower === '(null)') {
        return null;
    }

    // Convert empty
    if ($lower === 'empty' || $lower === '(empty)') {
        return '';
    }

    // Return as-is
    return $value;
}

/**
 * Check if environment variable exists
 *
 * @param string $key Variable name
 * @return bool True if exists
 */
function env_exists($key) {
    return array_key_exists($key, $_ENV)
        || getenv($key) !== false
        || array_key_exists($key, $_SERVER);
}

/**
 * Get required environment variable (throws error if missing)
 *
 * @param string $key Variable name
 * @return mixed Variable value
 * @throws Exception If variable not found
 */
function env_required($key) {
    $value = env($key);

    if ($value === null) {
        throw new Exception("Required environment variable '{$key}' is not set");
    }

    return $value;
}

/**
 * Validate environment configuration
 *
 * @return array Array of validation errors (empty if valid)
 */
function validate_env() {
    $errors = [];

    // Required variables
    $required = [
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'APP_URL',
        'MAIL_HOST',
        'MAIL_USERNAME',
        'MAIL_PASSWORD'
    ];

    foreach ($required as $key) {
        if (!env_exists($key) || env($key) === '' || env($key) === null) {
            $errors[] = "Missing required environment variable: {$key}";
        }
    }

    // Validate specific values
    $app_env = env('APP_ENV', 'production');
    if (!in_array($app_env, ['production', 'staging', 'development', 'local'])) {
        $errors[] = "Invalid APP_ENV value: {$app_env}";
    }

    // Check database port is numeric
    $db_port = env('DB_PORT', 3306);
    if (!is_numeric($db_port)) {
        $errors[] = "DB_PORT must be numeric";
    }

    // Validate email encryption
    $mail_encryption = env('MAIL_ENCRYPTION', 'ssl');
    if (!in_array(strtolower($mail_encryption), ['ssl', 'tls', ''])) {
        $errors[] = "Invalid MAIL_ENCRYPTION value: {$mail_encryption}";
    }

    // Check for default/insecure passwords
    $db_password = env('DB_PASSWORD', '');
    if (in_array($db_password, ['password', 'CHANGE_THIS_PASSWORD', 'your_password_here', ''])) {
        $errors[] = "DB_PASSWORD appears to be using default/insecure value";
    }

    return $errors;
}

/**
 * Display environment configuration errors
 *
 * @param array $errors Array of error messages
 * @return void
 */
function display_env_errors($errors) {
    if (empty($errors)) {
        return;
    }

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Configuration Error</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: #f5f5f5;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 600px;
                margin: 40px auto;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 30px;
            }
            h1 {
                color: #d9534f;
                margin-top: 0;
            }
            .error-list {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px 20px;
                margin: 20px 0;
            }
            .error-list li {
                margin: 10px 0;
                color: #856404;
            }
            .help {
                color: #666;
                font-size: 14px;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            code {
                background: #f5f5f5;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Environment Configuration Error</h1>
            <p>The application could not start due to configuration errors:</p>
            <div class="error-list">
                <ul>';

    foreach ($errors as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>';
    }

    $html .= '
                </ul>
            </div>
            <div class="help">
                <strong>How to fix:</strong>
                <ol>
                    <li>Check that <code>.env</code> file exists in the root directory</li>
                    <li>Verify all required variables are set with valid values</li>
                    <li>Ensure <code>.env</code> file has correct permissions (600)</li>
                    <li>Update any default/placeholder values with actual credentials</li>
                </ol>
                <p>For detailed setup instructions, see <code>HOSTINGER_SETUP.md</code></p>
            </div>
        </div>
    </body>
    </html>';

    echo $html;
    exit(1);
}

// Auto-load .env file when this file is included
load_env();

// Validate environment in production
if (env('APP_ENV') === 'production' && env('APP_DEBUG', false) === false) {
    $errors = validate_env();
    if (!empty($errors)) {
        error_log("Environment configuration errors: " . implode(', ', $errors));
        // Don't display errors in production, just log them
        // display_env_errors($errors);
    }
}

?>
