<?php
// ============================================
// DATABASE MIGRATION FILE
// Save this as: /bookshelf/admin/migrate_settings_table.php
// Run this file ONCE to update your database
// ============================================

require_once '../includes/db_connect.php';

echo "<h2>Database Migration for Site Settings</h2>";

// Check if updated_at column exists
$check_column = "SHOW COLUMNS FROM site_settings LIKE 'updated_at'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    // Add updated_at column if it doesn't exist
    $add_column = "ALTER TABLE site_settings 
                   ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    
    if ($conn->query($add_column)) {
        echo "✅ Successfully added 'updated_at' column to site_settings table.<br>";
    } else {
        echo "❌ Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Column 'updated_at' already exists.<br>";
}

// Check if created_at column exists
$check_created = "SHOW COLUMNS FROM site_settings LIKE 'created_at'";
$result_created = $conn->query($check_created);

if ($result_created->num_rows == 0) {
    // Add created_at column if it doesn't exist
    $add_created = "ALTER TABLE site_settings 
                    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    
    if ($conn->query($add_created)) {
        echo "✅ Successfully added 'created_at' column to site_settings table.<br>";
    } else {
        echo "❌ Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Column 'created_at' already exists.<br>";
}

// Ensure setting_key is unique
$check_unique = "SHOW INDEX FROM site_settings WHERE Column_name = 'setting_key' AND Non_unique = 0";
$result_unique = $conn->query($check_unique);

if ($result_unique->num_rows == 0) {
    // Add unique constraint if it doesn't exist
    $add_unique = "ALTER TABLE site_settings ADD UNIQUE KEY unique_setting_key (setting_key)";
    
    if ($conn->query($add_unique)) {
        echo "✅ Successfully added unique constraint to 'setting_key'.<br>";
    } else {
        echo "⚠️ Could not add unique constraint (may already exist): " . $conn->error . "<br>";
    }
} else {
    echo "✅ Unique constraint on 'setting_key' already exists.<br>";
}

echo "<br><strong>Migration complete!</strong> You can now <a href='settings.php'>go to Settings page</a>.";
?>

<!-- ============================================ -->
<!-- UPDATED SETTINGS.PHP FILE WITHOUT updated_at -->
<!-- Replace your settings.php with this version  -->
<!-- ============================================ -->

<?php
// This is the FIXED settings.php file
include '../includes/admin_header.php';

// Create upload directory if it doesn't exist
$upload_dir = '../../public/images/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Initialize message arrays
$success_messages = [];
$error_messages = [];

// Handle file uploads with better error handling
function handleFileUpload($file, $type) {
    $upload_dir = '../../public/images/';
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        return ['success' => false, 'message' => 'Upload directory is not writable. Please check permissions.'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];
        return ['success' => false, 'message' => $error_messages[$file['error']] ?? 'Unknown upload error'];
    }
    
    // Validate file type
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon',
        'image/webp'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, SVG, ICO, WEBP'];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension)) {
        if ($mime_type === 'image/x-icon' || $mime_type === 'image/vnd.microsoft.icon') {
            $extension = 'ico';
        } else {
            $extension = 'png';
        }
    }
    
    $filename = $type . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Delete old file if exists
        global $conn, $settings;
        $setting_key = 'site_' . $type;
        if (!empty($settings[$setting_key]) && file_exists($upload_dir . $settings[$setting_key])) {
            @unlink($upload_dir . $settings[$setting_key]);
        }
        
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Start transaction for database operations
    $conn->begin_transaction();
    
    try {
        // Update text-based settings
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            // Modified query - removed updated_at reference
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            foreach ($_POST['settings'] as $key => $value) {
                // Validate color format if it's a color field
                if (strpos($key, 'color') !== false && !empty($value)) {
                    if (!preg_match('/^#[a-f0-9]{6}$/i', $value)) {
                        $error_messages[] = "Invalid color format for " . str_replace('_', ' ', $key) . ". Use format: #RRGGBB";
                        continue;
                    }
                }
                
                // Validate URLs for social media
                if (strpos($key, '_url') !== false && !empty($value)) {
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $error_messages[] = "Invalid URL format for " . str_replace('_', ' ', $key);
                        continue;
                    }
                }
                
                $stmt->bind_param("ss", $key, $value);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
            }
            $stmt->close();
            
            if (empty($error_messages)) {
                $success_messages[] = "Settings updated successfully";
            }
        }
        
        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = handleFileUpload($_FILES['logo'], 'logo');
            if ($result['success']) {
                $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                      VALUES ('site_logo', ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->bind_param("s", $result['filename']);
                if ($stmt->execute()) {
                    $success_messages[] = "Logo uploaded successfully";
                } else {
                    throw new Exception("Failed to save logo: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $error_messages[] = "Logo upload failed: " . $result['message'];
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = handleFileUpload($_FILES['favicon'], 'favicon');
            if ($result['success']) {
                $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                      VALUES ('site_favicon', ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->bind_param("s", $result['filename']);
                if ($stmt->execute()) {
                    $success_messages[] = "Favicon uploaded successfully";
                } else {
                    throw new Exception("Failed to save favicon: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $error_messages[] = "Favicon upload failed: " . $result['message'];
            }
        }
        
        // Commit transaction if no critical errors
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_messages[] = "Database error: " . $e->getMessage();
    }
    
    // Store messages in session for display after redirect
    if (!empty($success_messages)) {
        $_SESSION['success_messages'] = $success_messages;
    }
    if (!empty($error_messages)) {
        $_SESSION['error_messages'] = $error_messages;
    }
    
    // Redirect to prevent form resubmission
    header("Location: settings.php");
    exit();
}

// Fetch all current settings
$settings = [];
$sql = "SELECT setting_key, setting_value FROM site_settings";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Define default values
$defaults = [
    // Basic Info
    'site_name' => 'BookShelf',
    'site_tagline' => 'Your Favorite Online Bookstore',
    'site_currency' => 'INR',
    'site_email' => 'info@bookshelf.com',
    'site_phone' => '+1 840-841-2569',
    'site_address' => '123 Book Street, Reading City, RC 12345',
    'timezone' => 'America/New_York',
    
    // Theme Colors
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'accent_color' => '#f59e0b',
    'text_color' => '#1f2937',
    'bg_color' => '#ffffff',
    'theme_mode' => 'light',
    
    // Social Media
    'facebook_url' => '',
    'twitter_url' => '',
    'instagram_url' => '',
    'linkedin_url' => '',
    'youtube_url' => '',
    'pinterest_url' => '',
    
    // Payment Gateways
    'stripe_public_key' => '',
    'stripe_secret_key' => '',
    'paypal_client_id' => '',
    'paypal_secret' => '',
    'razorpay_key_id' => '',
    'razorpay_key_secret' => '',
    
    // Analytics
    'google_analytics_id' => '',
    'facebook_pixel_id' => '',
    
    // Files
    'site_logo' => '',
    'site_favicon' => ''
];

// Merge defaults with database settings
$settings = array_merge($defaults, $settings);

// Get messages from session
$success_messages = $_SESSION['success_messages'] ?? [];
$error_messages = $_SESSION['error_messages'] ?? [];
unset($_SESSION['success_messages'], $_SESSION['error_messages']);
?>

<!-- Rest of the HTML/CSS/JS code remains exactly the same as in the previous artifact -->
<!-- Include all the styles, HTML form, and JavaScript from the previous version -->

<style>
/* All the CSS from the previous version */
.settings-container {
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
    padding: 2rem 0;
}

.settings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

/* ... rest of the CSS styles ... */
</style>

<!-- Include all the HTML content from the previous version -->
<div class="settings-container">
    <!-- All the HTML content remains the same -->
</div>

<script>
// All the JavaScript from the previous version
</script>

<?php include '../includes/admin_footer.php'; ?>