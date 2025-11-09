<?php
include '../includes/admin_header.php';
include 'includes/professional-components.php';

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
        // For favicon.ico files
        if ($mime_type === 'image/x-icon' || $mime_type === 'image/vnd.microsoft.icon') {
            $extension = 'ico';
        } else {
            $extension = 'png'; // Default
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
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            
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
                $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
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
                $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('site_favicon', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
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
    header("Location: settings-professional.php");
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

// Get system information
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => $conn->server_info,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">⚙️ System Settings</h1>
                <p class="page-subtitle">Configure your bookstore and manage system preferences</p>
            </div>
            <button class="btn-professional btn-primary-professional" onclick="saveSettings()">
                <span>💾</span> Save Settings
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_messages'])): ?>
        <?php foreach($_SESSION['success_messages'] as $message): ?>
            <?php renderProfessionalAlert('success', $message); ?>
        <?php endforeach; ?>
        <?php unset($_SESSION['success_messages']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_messages'])): ?>
        <?php foreach($_SESSION['error_messages'] as $message): ?>
            <?php renderProfessionalAlert('error', $message); ?>
        <?php endforeach; ?>
        <?php unset($_SESSION['error_messages']); ?>
    <?php endif; ?>

    <!-- Settings Navigation -->
    <div style="display: flex; gap: var(--spacing-3); margin-bottom: var(--spacing-6); flex-wrap: wrap;">
        <button class="btn-professional btn-outline-professional active-setting-tab" onclick="switchTab('general')">
            <span>🏢</span> General
        </button>
        <button class="btn-professional btn-outline-professional" onclick="switchTab('appearance')">
            <span>🎨</span> Appearance
        </button>
        <button class="btn-professional btn-outline-professional" onclick="switchTab('social')">
            <span>📱</span> Social Media
        </button>
        <button class="btn-professional btn-outline-professional" onclick="switchTab('contact')">
            <span>📞</span> Contact
        </button>
        <button class="btn-professional btn-outline-professional" onclick="switchTab('system')">
            <span>💻</span> System Info
        </button>
    </div>

    <form method="POST" enctype="multipart/form-data" id="settingsForm">
        <!-- General Settings Tab -->
        <div class="settings-tab active" id="general-tab">
            <div class="professional-card" style="margin-bottom: var(--spacing-6);">
                <div class="card-header-professional">
                    <h3 class="card-title">
                        <span>🏢</span> General Information
                    </h3>
                </div>
                <div class="card-body-professional">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6);">
                        <div>
                            <div class="form-group-professional">
                                <label class="form-label-professional">🏪 Store Name</label>
                                <input type="text" class="form-control-professional" name="settings[site_name]" 
                                       value="<?php echo htmlspecialchars($settings['site_name'] ?? 'BookShelf'); ?>"
                                       placeholder="Enter store name...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📝 Tagline</label>
                                <input type="text" class="form-control-professional" name="settings[site_tagline]" 
                                       value="<?php echo htmlspecialchars($settings['site_tagline'] ?? 'Your Favorite Online Bookstore'); ?>"
                                       placeholder="Enter tagline...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📧 Admin Email</label>
                                <input type="email" class="form-control-professional" name="settings[admin_email]" 
                                       value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>"
                                       placeholder="admin@yoursite.com">
                            </div>
                        </div>
                        
                        <div>
                            <div class="form-group-professional">
                                <label class="form-label-professional">💱 Currency Symbol</label>
                                <input type="text" class="form-control-professional" name="settings[currency_symbol]" 
                                       value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? '₹'); ?>"
                                       placeholder="₹" maxlength="3">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📦 Default Shipping Cost</label>
                                <input type="number" class="form-control-professional" name="settings[default_shipping_cost]" 
                                       value="<?php echo htmlspecialchars($settings['default_shipping_cost'] ?? '0'); ?>"
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📋 Items Per Page</label>
                                <select class="form-control-professional form-select-professional" name="settings[items_per_page]">
                                    <option value="12" <?php echo ($settings['items_per_page'] ?? '12') == '12' ? 'selected' : ''; ?>>12 items</option>
                                    <option value="16" <?php echo ($settings['items_per_page'] ?? '12') == '16' ? 'selected' : ''; ?>>16 items</option>
                                    <option value="20" <?php echo ($settings['items_per_page'] ?? '12') == '20' ? 'selected' : ''; ?>>20 items</option>
                                    <option value="24" <?php echo ($settings['items_per_page'] ?? '12') == '24' ? 'selected' : ''; ?>>24 items</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appearance Settings Tab -->
        <div class="settings-tab" id="appearance-tab" style="display: none;">
            <div class="professional-card" style="margin-bottom: var(--spacing-6);">
                <div class="card-header-professional">
                    <h3 class="card-title">
                        <span>🎨</span> Branding & Appearance
                    </h3>
                </div>
                <div class="card-body-professional">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6);">
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>🖼️</span> Logo & Favicon
                            </h4>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🏢 Store Logo</label>
                                <input type="file" class="form-control-professional" name="logo" accept="image/*">
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <div style="margin-top: var(--spacing-2);">
                                        <img src="/bookshelf/public/images/<?php echo htmlspecialchars($settings['site_logo']); ?>" 
                                             alt="Current Logo" style="max-width: 200px; max-height: 100px; border: 1px solid var(--gray-300); border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                                <div style="font-size: var(--font-size-xs); color: var(--gray-500); margin-top: var(--spacing-1);">
                                    Recommended: 300x100px, Max: 2MB
                                </div>
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">⭐ Favicon</label>
                                <input type="file" class="form-control-professional" name="favicon" accept="image/*">
                                <?php if (!empty($settings['site_favicon'])): ?>
                                    <div style="margin-top: var(--spacing-2);">
                                        <img src="/bookshelf/public/images/<?php echo htmlspecialchars($settings['site_favicon']); ?>" 
                                             alt="Current Favicon" style="width: 32px; height: 32px;">
                                    </div>
                                <?php endif; ?>
                                <div style="font-size: var(--font-size-xs); color: var(--gray-500); margin-top: var(--spacing-1);">
                                    Recommended: 32x32px, Max: 1MB
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>🎨</span> Color Scheme
                            </h4>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🔵 Primary Color</label>
                                <input type="color" class="form-control-professional" name="settings[primary_color]" 
                                       value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#667eea'); ?>">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🟣 Secondary Color</label>
                                <input type="color" class="form-control-professional" name="settings[secondary_color]" 
                                       value="<?php echo htmlspecialchars($settings['secondary_color'] ?? '#764ba2'); ?>">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🟢 Success Color</label>
                                <input type="color" class="form-control-professional" name="settings[success_color]" 
                                       value="<?php echo htmlspecialchars($settings['success_color'] ?? '#28a745'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Settings Tab -->
        <div class="settings-tab" id="social-tab" style="display: none;">
            <div class="professional-card" style="margin-bottom: var(--spacing-6);">
                <div class="card-header-professional">
                    <h3 class="card-title">
                        <span>📱</span> Social Media Links
                    </h3>
                </div>
                <div class="card-body-professional">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6);">
                        <div>
                            <div class="form-group-professional">
                                <label class="form-label-professional">📘 Facebook</label>
                                <input type="url" class="form-control-professional" name="settings[facebook_url]" 
                                       value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>"
                                       placeholder="https://facebook.com/yourpage">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🐦 Twitter</label>
                                <input type="url" class="form-control-professional" name="settings[twitter_url]" 
                                       value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>"
                                       placeholder="https://twitter.com/yourhandle">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📸 Instagram</label>
                                <input type="url" class="form-control-professional" name="settings[instagram_url]" 
                                       value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>"
                                       placeholder="https://instagram.com/yourhandle">
                            </div>
                        </div>
                        
                        <div>
                            <div class="form-group-professional">
                                <label class="form-label-professional">💼 LinkedIn</label>
                                <input type="url" class="form-control-professional" name="settings[linkedin_url]" 
                                       value="<?php echo htmlspecialchars($settings['linkedin_url'] ?? ''); ?>"
                                       placeholder="https://linkedin.com/company/yourcompany">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🎥 YouTube</label>
                                <input type="url" class="form-control-professional" name="settings[youtube_url]" 
                                       value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>"
                                       placeholder="https://youtube.com/yourchannel">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📱 Pinterest</label>
                                <input type="url" class="form-control-professional" name="settings[pinterest_url]" 
                                       value="<?php echo htmlspecialchars($settings['pinterest_url'] ?? ''); ?>"
                                       placeholder="https://pinterest.com/yourhandle">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Settings Tab -->
        <div class="settings-tab" id="contact-tab" style="display: none;">
            <div class="professional-card" style="margin-bottom: var(--spacing-6);">
                <div class="card-header-professional">
                    <h3 class="card-title">
                        <span>📞</span> Contact Information
                    </h3>
                </div>
                <div class="card-body-professional">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6);">
                        <div>
                            <div class="form-group-professional">
                                <label class="form-label-professional">📍 Address</label>
                                <textarea class="form-control-professional" name="settings[contact_address]" rows="3" 
                                          placeholder="Enter full address..."><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📞 Phone Number</label>
                                <input type="tel" class="form-control-professional" name="settings[contact_phone]" 
                                       value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>"
                                       placeholder="+1 (555) 123-4567">
                            </div>
                        </div>
                        
                        <div>
                            <div class="form-group-professional">
                                <label class="form-label-professional">📧 Contact Email</label>
                                <input type="email" class="form-control-professional" name="settings[contact_email]" 
                                       value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>"
                                       placeholder="contact@yoursite.com">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🕒 Business Hours</label>
                                <textarea class="form-control-professional" name="settings[business_hours]" rows="2" 
                                          placeholder="Mon-Fri: 9AM-6PM&#10;Sat: 10AM-4PM&#10;Sun: Closed"><?php echo htmlspecialchars($settings['business_hours'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information Tab -->
        <div class="settings-tab" id="system-tab" style="display: none;">
            <div class="professional-card" style="margin-bottom: var(--spacing-6);">
                <div class="card-header-professional">
                    <h3 class="card-title">
                        <span>💻</span> System Information
                    </h3>
                </div>
                <div class="card-body-professional">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-6);">
                        <div class="stat-card-professional">
                            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">🐘</div>
                            <h3 class="stat-value" style="font-size: 1.5rem; margin: var(--spacing-2) 0 0 0;"><?php echo $system_info['php_version']; ?></h3>
                            <p class="stat-label" style="font-size: var(--font-size-sm); margin: var(--spacing-1) 0 0 0;">PHP Version</p>
                        </div>
                        
                        <div class="stat-card-professional">
                            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">🗄️</div>
                            <h3 class="stat-value" style="font-size: 1.5rem; margin: var(--spacing-2) 0 0 0;"><?php echo $system_info['mysql_version']; ?></h3>
                            <p class="stat-label" style="font-size: var(--font-size-sm); margin: var(--spacing-1) 0 0 0;">MySQL Version</p>
                        </div>
                        
                        <div class="stat-card-professional">
                            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">🌐</div>
                            <h3 class="stat-value" style="font-size: 1rem; margin: var(--spacing-2) 0 0 0;"><?php echo htmlspecialchars($system_info['server_software']); ?></h3>
                            <p class="stat-label" style="font-size: var(--font-size-sm); margin: var(--spacing-1) 0 0 0;">Server Software</p>
                        </div>
                        
                        <div class="stat-card-professional">
                            <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);">💾</div>
                            <h3 class="stat-value" style="font-size: 1.5rem; margin: var(--spacing-2) 0 0 0;"><?php echo $system_info['upload_max_filesize']; ?></h3>
                            <p class="stat-label" style="font-size: var(--font-size-sm); margin: var(--spacing-1) 0 0 0;">Max Upload Size</p>
                        </div>
                    </div>
                    
                    <div style="margin-top: var(--spacing-6); padding-top: var(--spacing-6); border-top: 1px solid var(--gray-200);">
                        <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800);">🔧 Advanced Settings</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-4);">
                            <div class="form-group-professional">
                                <label class="form-label-professional">⏱️ Max Execution Time</label>
                                <div style="padding: var(--spacing-3); background: var(--gray-100); border-radius: 8px; font-weight: 600;">
                                    <?php echo $system_info['max_execution_time']; ?> seconds
                                </div>
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🧠 Memory Limit</label>
                                <div style="padding: var(--spacing-3); background: var(--gray-100); border-radius: 8px; font-weight: 600;">
                                    <?php echo $system_info['memory_limit']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button (Hidden but functional) -->
        <button type="submit" id="saveSettingsBtn" style="display: none;"></button>
    </form>

</div>

<!-- Professional JavaScript -->
<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.btn-outline-professional').forEach(btn => {
        btn.classList.remove('active-setting-tab');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').style.display = 'block';
    
    // Add active class to clicked button
    event.target.classList.add('active-setting-tab');
}

function saveSettings() {
    document.getElementById('saveSettingsBtn').click();
}

// Initialize professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add form enhancements
});
</script>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>