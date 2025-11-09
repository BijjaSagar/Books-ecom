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

// Define default values with better organization
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

// Inject professional CSS
injectProfessionalCSS();
renderProfessionalJavaScript();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">⚙️ Site Settings</h1>
                <p class="page-subtitle">Customize your store appearance, functionality, and integrations</p>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php foreach($success_messages as $message): ?>
        <?php renderProfessionalAlert('success', $message); ?>
    <?php endforeach; ?>

    <?php foreach($error_messages as $error): ?>
        <?php renderProfessionalAlert('error', $error); ?>
    <?php endforeach; ?>

    <!-- Settings Form -->
    <form action="settings.php" method="POST" enctype="multipart/form-data" id="settingsForm" class="form-professional">
        <!-- Professional Tabs Navigation -->
        <div class="professional-card mb-4">
            <div class="card-header-professional">
                <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#general">
                            <span>⚙️</span> General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#appearance">
                            <span>🎨</span> Appearance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#branding">
                            <span>🖼️</span> Branding
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#social">
                            <span>📱</span> Social Media
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#payment">
                            <span>💳</span> Payment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#analytics">
                            <span>📊</span> Analytics
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general">
                <div class="professional-card">
                    <div class="card-header-professional">
                        <h5 class="card-title"><span>ℹ️</span> Basic Information</h5>
                    </div>
                    <div class="card-body-professional">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">🏪 Site Name</label>
                                <input type="text" class="form-control-professional" name="settings[site_name]" 
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                <small class="text-muted">Displayed in header and page titles</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">🏷️ Site Tagline</label>
                                <input type="text" class="form-control-professional" name="settings[site_tagline]" 
                                       value="<?php echo htmlspecialchars($settings['site_tagline']); ?>">
                                <small class="text-muted">Brief description of your store</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📧 Contact Email</label>
                                <input type="email" class="form-control-professional" name="settings[site_email]" 
                                       value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📞 Contact Phone</label>
                                <input type="text" class="form-control-professional" name="settings[site_phone]" 
                                       value="<?php echo htmlspecialchars($settings['site_phone']); ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label-professional">📍 Address</label>
                                <input type="text" class="form-control-professional" name="settings[site_address]" 
                                       value="<?php echo htmlspecialchars($settings['site_address']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">💵 Currency</label>
                                <select class="form-control-professional form-select-professional" name="settings[site_currency]">
                                    <option value="INR" <?php echo $settings['site_currency'] == 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                                    <option value="USD" <?php echo $settings['site_currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo $settings['site_currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP" <?php echo $settings['site_currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">🌐 Timezone</label>
                                <select class="form-control-professional form-select-professional" name="settings[timezone]">
                                    <option value="America/New_York" <?php echo $settings['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (US)</option>
                                    <option value="America/Chicago" <?php echo $settings['timezone'] == 'America/Chicago' ? 'selected' : ''; ?>>Central Time (US)</option>
                                    <option value="America/Los_Angeles" <?php echo $settings['timezone'] == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time (US)</option>
                                    <option value="Europe/London" <?php echo $settings['timezone'] == 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                                    <option value="Asia/Kolkata" <?php echo $settings['timezone'] == 'Asia/Kolkata' ? 'selected' : ''; ?>>India (IST)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance Settings -->
            <div class="tab-pane fade" id="appearance">
                <div class="professional-card">
                    <div class="card-header-professional">
                        <h5 class="card-title"><span>🎨</span> Theme Colors</h5>
                    </div>
                    <div class="card-body-professional">
                        <!-- Predefined Themes -->
                        <div class="mb-4">
                            <label class="form-label-professional">🎨 Quick Theme Selection</label>
                            <div class="predefined-themes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-top: 1rem;">
                                <div class="theme-option active" onclick="applyTheme('default')" style="border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; cursor: pointer; transition: all 0.3s; text-align: center;">
                                    <div class="theme-colors" style="display: flex; justify-content: center; gap: 0.25rem; margin-bottom: 0.5rem;">
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #667eea;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #764ba2;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #f59e0b;"></span>
                                    </div>
                                    <small>Default</small>
                                </div>
                                <div class="theme-option" onclick="applyTheme('ocean')" style="border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; cursor: pointer; transition: all 0.3s; text-align: center;">
                                    <div class="theme-colors" style="display: flex; justify-content: center; gap: 0.25rem; margin-bottom: 0.5rem;">
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #0891b2;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #0e7490;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #06b6d4;"></span>
                                    </div>
                                    <small>Ocean</small>
                                </div>
                                <div class="theme-option" onclick="applyTheme('forest')" style="border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; cursor: pointer; transition: all 0.3s; text-align: center;">
                                    <div class="theme-colors" style="display: flex; justify-content: center; gap: 0.25rem; margin-bottom: 0.5rem;">
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #16a34a;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #15803d;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #84cc16;"></span>
                                    </div>
                                    <small>Forest</small>
                                </div>
                                <div class="theme-option" onclick="applyTheme('sunset')" style="border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; cursor: pointer; transition: all 0.3s; text-align: center;">
                                    <div class="theme-colors" style="display: flex; justify-content: center; gap: 0.25rem; margin-bottom: 0.5rem;">
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #f97316;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #ea580c;"></span>
                                        <span class="theme-color-dot" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); background: #fbbf24;"></span>
                                    </div>
                                    <small>Sunset</small>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Colors -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label-professional">🔵 Primary Color</label>
                                <div class="color-input-group" style="position: relative;">
                                    <input type="text" class="form-control-professional" id="primary_color" 
                                           name="settings[primary_color]" 
                                           value="<?php echo htmlspecialchars($settings['primary_color']); ?>" 
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           placeholder="#667eea">
                                    <div class="color-preview" id="primary_preview" 
                                         style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 35px; height: 35px; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.3s; background: <?php echo $settings['primary_color']; ?>;"
                                         onclick="document.getElementById('primary_picker').click()"></div>
                                    <input type="color" id="primary_picker" style="display:none;" 
                                           value="<?php echo $settings['primary_color']; ?>"
                                           onchange="updateColor('primary')">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label-professional">🟣 Secondary Color</label>
                                <div class="color-input-group" style="position: relative;">
                                    <input type="text" class="form-control-professional" id="secondary_color" 
                                           name="settings[secondary_color]" 
                                           value="<?php echo htmlspecialchars($settings['secondary_color']); ?>"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           placeholder="#764ba2">
                                    <div class="color-preview" id="secondary_preview" 
                                         style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 35px; height: 35px; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.3s; background: <?php echo $settings['secondary_color']; ?>;"
                                         onclick="document.getElementById('secondary_picker').click()"></div>
                                    <input type="color" id="secondary_picker" style="display:none;" 
                                           value="<?php echo $settings['secondary_color']; ?>"
                                           onchange="updateColor('secondary')">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label-professional">🟡 Accent Color</label>
                                <div class="color-input-group" style="position: relative;">
                                    <input type="text" class="form-control-professional" id="accent_color" 
                                           name="settings[accent_color]" 
                                           value="<?php echo htmlspecialchars($settings['accent_color']); ?>"
                                           pattern="^#[0-9A-Fa-f]{6}$"
                                           placeholder="#f59e0b">
                                    <div class="color-preview" id="accent_preview" 
                                         style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 35px; height: 35px; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.3s; background: <?php echo $settings['accent_color']; ?>;"
                                         onclick="document.getElementById('accent_picker').click()"></div>
                                    <input type="color" id="accent_picker" style="display:none;" 
                                           value="<?php echo $settings['accent_color']; ?>"
                                           onchange="updateColor('accent')">
                                </div>
                            </div>
                        </div>

                        <!-- Theme Mode -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label-professional">🌓 Theme Mode</label>
                                <select class="form-control-professional form-select-professional" name="settings[theme_mode]">
                                    <option value="light" <?php echo $settings['theme_mode'] == 'light' ? 'selected' : ''; ?>>Light Mode</option>
                                    <option value="dark" <?php echo $settings['theme_mode'] == 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                                    <option value="auto" <?php echo $settings['theme_mode'] == 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Branding Settings -->
            <div class="tab-pane fade" id="branding">
                <div class="professional-card">
                    <div class="card-header-professional">
                        <h5 class="card-title"><span>🖼️</span> Logo & Favicon</h5>
                    </div>
                    <div class="card-body-professional">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label-professional">🖼️ Site Logo</label>
                                <div class="upload-area" id="logoUploadArea" style="border: 2px dashed #d1d5db; border-radius: 10px; padding: 2rem; text-align: center; transition: all 0.3s; cursor: pointer; background: #f9fafb; position: relative; min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <?php if(!empty($settings['site_logo'])): ?>
                                        <img src="/bookshelf/public/images/<?php echo $settings['site_logo']; ?>" 
                                             alt="Current Logo" class="current-image" style="max-width: 150px; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1rem;">
                                    <?php endif; ?>
                                    <i class="bi bi-cloud-arrow-up upload-icon" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                                    <p class="mb-1">Click to upload or drag and drop</p>
                                    <small class="text-muted">PNG, JPG, SVG (Max 5MB)</small>
                                </div>
                                <input type="file" id="logo_upload" name="logo" 
                                       accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" 
                                       style="display:none;">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label-professional"> FavIcon</label>
                                <div class="upload-area" id="faviconUploadArea" style="border: 2px dashed #d1d5db; border-radius: 10px; padding: 2rem; text-align: center; transition: all 0.3s; cursor: pointer; background: #f9fafb; position: relative; min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <?php if(!empty($settings['site_favicon'])): ?>
                                        <img src="/bookshelf/public/images/<?php echo $settings['site_favicon']; ?>" 
                                             alt="Current Favicon" class="current-image" style="max-width: 64px; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1rem;">
                                    <?php endif; ?>
                                    <i class="bi bi-file-earmark-image upload-icon" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                                    <p class="mb-1">Click to upload or drag and drop</p>
                                    <small class="text-muted">ICO, PNG (16x16, 32x32, or 64x64)</small>
                                </div>
                                <input type="file" id="favicon_upload" name="favicon" 
                                       accept=".ico,image/png,image/x-icon,image/vnd.microsoft.icon" 
                                       style="display:none;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media Settings -->
            <div class="tab-pane fade" id="social">
                <div class="professional-card">
                    <div class="card-header-professional">
                        <h5 class="card-title"><span>📱</span> Social Media Links</h5>
                    </div>
                    <div class="card-body-professional">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📘 Facebook</label>
                                <div class="input-group social-input-group">
                                    <span class="input-group-text" style="background: #f9fafb; border: 2px solid #e5e7eb; border-right: none; color: #667eea;"><i class="bi bi-facebook"></i></span>
                                    <input type="url" class="form-control-professional" name="settings[facebook_url]" 
                                           value="<?php echo htmlspecialchars($settings['facebook_url']); ?>"
                                           placeholder="https://facebook.com/yourbusiness">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">🐦 Twitter</label>
                                <div class="input-group social-input-group">
                                    <span class="input-group-text" style="background: #f9fafb; border: 2px solid #e5e7eb; border-right: none; color: #667eea;"><i class="bi bi-twitter"></i></span>
                                    <input type="url" class="form-control-professional" name="settings[twitter_url]" 
                                           value="<?php echo htmlspecialchars($settings['twitter_url']); ?>"
                                           placeholder="https://twitter.com/yourbusiness">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📸 Instagram</label>
                                <div class="input-group social-input-group">
                                    <span class="input-group-text" style="background: #f9fafb; border: 2px solid #e5e7eb; border-right: none; color: #667eea;"><i class="bi bi-instagram"></i></span>
                                    <input type="url" class="form-control-professional" name="settings[instagram_url]" 
                                           value="<?php echo htmlspecialchars($settings['instagram_url']); ?>"
                                           placeholder="https://instagram.com/yourbusiness">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">👔 LinkedIn</label>
                                <div class="input-group social-input-group">
                                    <span class="input-group-text" style="background: #f9fafb; border: 2px solid #e5e7eb; border-right: none; color: #667eea;"><i class="bi bi-linkedin"></i></span>
                                    <input type="url" class="form-control-professional" name="settings[linkedin_url]" 
                                           value="<?php echo htmlspecialchars($settings['linkedin_url']); ?>"
                                           placeholder="https://linkedin.com/company/yourbusiness">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📺 YouTube</label>
                                <div class="input-group social-input-group">
                                    <span class="input-group-text" style="background: #f9fafb; border: 2px solid #e5e7eb; border-right: none; color: #667eea;"><i class="bi bi-youtube"></i></span>
                                    <input type="url" class="form-control-professional" name="settings[youtube_url]" 
                                           value="<?php echo htmlspecialchars($settings['youtube_url']); ?>"
                                           placeholder="https://youtube.com/c/yourchannel">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📌 Pinterest</label>
                                <div class="input-group social-input-group">
                                    <span class="input-group-text" style="background: #f9fafb; border: 2px solid #e5e7eb; border-right: none; color: #667eea;"><i class="bi bi-pinterest"></i></span>
                                    <input type="url" class="form-control-professional" name="settings[pinterest_url]" 
                                           value="<?php echo htmlspecialchars($settings['pinterest_url']); ?>"
                                           placeholder="https://pinterest.com/yourbusiness">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>💳</span> Stripe Configuration</h5>
                </div>
                <div class="card-body-professional">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-professional">🔑 Publishable Key</label>
                            <input type="text" class="form-control-professional" name="settings[stripe_public_key]" 
                                   value="<?php echo htmlspecialchars($settings['stripe_public_key']); ?>"
                                   placeholder="pk_test_...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-professional">🔒 Secret Key</label>
                            <input type="password" class="form-control-professional" name="settings[stripe_secret_key]" 
                                   value="<?php echo htmlspecialchars($settings['stripe_secret_key']); ?>"
                                   placeholder="sk_test_...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>💳</span> PayPal Configuration</h5>
                </div>
                <div class="card-body-professional">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-professional">🆔 Client ID</label>
                            <input type="text" class="form-control-professional" name="settings[paypal_client_id]" 
                                   value="<?php echo htmlspecialchars($settings['paypal_client_id']); ?>"
                                   placeholder="AX...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-professional">🔒 Secret</label>
                            <input type="password" class="form-control-professional" name="settings[paypal_secret]" 
                                   value="<?php echo htmlspecialchars($settings['paypal_secret']); ?>"
                                   placeholder="EK...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Razorpay Settings -->
            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>💳</span> Razorpay Configuration</h5>
                </div>
                <div class="card-body-professional">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-professional">🆔 Key ID</label>
                            <input type="text" class="form-control-professional" name="settings[razorpay_key_id]" 
                                   value="<?php echo htmlspecialchars($settings['razorpay_key_id']); ?>"
                                   placeholder="rzp_test_...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-professional">🔒 Key Secret</label>
                            <input type="password" class="form-control-professional" name="settings[razorpay_key_secret]" 
                                   value="<?php echo htmlspecialchars($settings['razorpay_key_secret']); ?>"
                                   placeholder="Secret key...">
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong>ℹ️ Note:</strong> For Indian merchants, Razorpay is the preferred payment gateway. 
                        Sign up at <a href="https://razorpay.com" target="_blank">razorpay.com</a> to get your API keys.
                    </div>
                </div>
            </div>

            <!-- Analytics Settings -->
            <div class="tab-pane fade" id="analytics">
                <div class="professional-card">
                    <div class="card-header-professional">
                        <h5 class="card-title"><span>📊</span> Analytics & Tracking</h5>
                    </div>
                    <div class="card-body-professional">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📈 Google Analytics ID</label>
                                <input type="text" class="form-control-professional" name="settings[google_analytics_id]" 
                                       value="<?php echo htmlspecialchars($settings['google_analytics_id']); ?>"
                                       placeholder="G-XXXXXXXXXX">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-professional">📊 Facebook Pixel ID</label>
                                <input type="text" class="form-control-professional" name="settings[facebook_pixel_id]" 
                                       value="<?php echo htmlspecialchars($settings['facebook_pixel_id']); ?>"
                                       placeholder="1234567890">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Save Button -->
        <div class="text-center mt-4">
            <button type="submit" class="btn-professional btn-primary-professional btn-lg">
                <span>💾</span> Save All Settings
            </button>
        </div>
    </form>
</div>

<script>
// Theme presets
const themes = {
    default: {
        primary: '#667eea',
        secondary: '#764ba2',
        accent: '#f59e0b'
    },
    ocean: {
        primary: '#0891b2',
        secondary: '#0e7490',
        accent: '#06b6d4'
    },
    forest: {
        primary: '#16a34a',
        secondary: '#15803d',
        accent: '#84cc16'
    },
    sunset: {
        primary: '#f97316',
        secondary: '#ea580c',
        accent: '#fbbf24'
    }
};

// Apply theme preset
function applyTheme(themeName) {
    const theme = themes[themeName];
    if (theme) {
        document.getElementById('primary_color').value = theme.primary;
        document.getElementById('secondary_color').value = theme.secondary;
        document.getElementById('accent_color').value = theme.accent;
        
        document.getElementById('primary_preview').style.background = theme.primary;
        document.getElementById('secondary_preview').style.background = theme.secondary;
        document.getElementById('accent_preview').style.background = theme.accent;
        
        document.getElementById('primary_picker').value = theme.primary;
        document.getElementById('secondary_picker').value = theme.secondary;
        document.getElementById('accent_picker').value = theme.accent;
        
        // Update active theme option
        document.querySelectorAll('.theme-option').forEach(el => el.classList.remove('active'));
        event.target.closest('.theme-option').classList.add('active');
    }
}

// Update color from picker
function updateColor(type) {
    const picker = document.getElementById(type + '_picker');
    const input = document.getElementById(type + '_color');
    const preview = document.getElementById(type + '_preview');
    
    input.value = picker.value;
    preview.style.background = picker.value;
}

// File upload handling with drag and drop
document.addEventListener('DOMContentLoaded', function() {
    // Logo upload
    const logoArea = document.getElementById('logoUploadArea');
    const logoInput = document.getElementById('logo_upload');
    
    logoArea.addEventListener('click', () => logoInput.click());
    
    logoArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        logoArea.classList.add('dragover');
    });
    
    logoArea.addEventListener('dragleave', () => {
        logoArea.classList.remove('dragover');
    });
    
    logoArea.addEventListener('drop', (e) => {
        e.preventDefault();
        logoArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            logoInput.files = files;
            previewImage(files[0], 'logo');
        }
    });
    
    logoInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            previewImage(e.target.files[0], 'logo');
        }
    });
    
    // Favicon upload
    const faviconArea = document.getElementById('faviconUploadArea');
    const faviconInput = document.getElementById('favicon_upload');
    
    faviconArea.addEventListener('click', () => faviconInput.click());
    
    faviconArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        faviconArea.classList.add('dragover');
    });
    
    faviconArea.addEventListener('dragleave', () => {
        faviconArea.classList.remove('dragover');
    });
    
    faviconArea.addEventListener('drop', (e) => {
        e.preventDefault();
        faviconArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            faviconInput.files = files;
            previewImage(files[0], 'favicon');
        }
    });
    
    faviconInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            previewImage(e.target.files[0], 'favicon');
        }
    });
    
    // Color validation
    document.querySelectorAll('input[id$="_color"]').forEach(input => {
        input.addEventListener('input', function() {
            const value = this.value;
            const colorRegex = /^#[0-9A-Fa-f]{6}$/;
            
            if (value && !colorRegex.test(value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                // Update preview
                const type = this.id.replace('_color', '');
                document.getElementById(type + '_preview').style.background = value;
                document.getElementById(type + '_picker').value = value;
            }
        });
    });
});

// Preview uploaded image
function previewImage(file, type) {
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const area = document.getElementById(type + 'UploadArea');
            let img = area.querySelector('.current-image');
            
            if (!img) {
                img = document.createElement('img');
                img.className = 'current-image';
                img.style.cssText = 'max-width: 150px; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1rem;';
                if (type === 'favicon') {
                    img.style.maxWidth = '64px';
                }
                area.insertBefore(img, area.firstChild);
            }
            
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert-modern').forEach(alert => {
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
});
</script>

<?php include '../includes/admin_footer.php'; ?>