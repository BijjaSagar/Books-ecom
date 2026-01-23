<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Admin Panel Debug Information</h1>";
echo "<hr>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "✓ PHP is working<br><br>";

// Test 2: Database Connection
echo "<h2>2. Database Connection Test</h2>";
try {
    require_once '../includes/config.php';
    echo "✓ Config file loaded successfully<br>";

    if (isset($conn) && $conn) {
        echo "✓ Database connection established<br>";
        echo "Database Name: " . $conn->server_info . "<br><br>";
    } else {
        echo "❌ Database connection failed<br><br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br><br>";
    die();
}

// Test 3: Check site_settings table
echo "<h2>3. Check site_settings Table</h2>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'site_settings'");
    if ($result && $result->num_rows > 0) {
        echo "✓ site_settings table exists<br>";

        // Try to query it
        $settings_query = "SELECT setting_key, setting_value FROM site_settings LIMIT 5";
        $settings_result = $conn->query($settings_query);

        if ($settings_result) {
            echo "✓ Can query site_settings table<br>";
            echo "Rows in site_settings: " . $settings_result->num_rows . "<br>";

            if ($settings_result->num_rows > 0) {
                echo "<pre>";
                while ($row = $settings_result->fetch_assoc()) {
                    echo "  - " . htmlspecialchars($row['setting_key']) . " = " . htmlspecialchars($row['setting_value']) . "\n";
                }
                echo "</pre>";
            }
        } else {
            echo "❌ Error querying site_settings: " . $conn->error . "<br>";
        }
    } else {
        echo "❌ site_settings table does NOT exist<br>";
        echo "<strong>This is likely causing the 500 error!</strong><br>";
    }
    echo "<br>";
} catch (Exception $e) {
    echo "❌ Error checking site_settings: " . $e->getMessage() . "<br><br>";
}

// Test 4: Check users table
echo "<h2>4. Check users Table</h2>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result && $result->num_rows > 0) {
        echo "✓ users table exists<br>";

        // Check for admin users
        $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        if ($admin_check) {
            $row = $admin_check->fetch_assoc();
            echo "Admin users count: " . ($row['count'] ?? 0) . "<br>";
        }
    } else {
        echo "❌ users table does NOT exist<br>";
    }
    echo "<br>";
} catch (Exception $e) {
    echo "❌ Error checking users table: " . $e->getMessage() . "<br><br>";
}

// Test 5: Session functionality
echo "<h2>5. Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✓ Session started successfully<br>";
    } else {
        echo "✓ Session already active<br>";
    }
    echo "Session ID: " . session_id() . "<br><br>";
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br><br>";
}

// Test 6: Test admin/index.php inclusion
echo "<h2>6. Test Admin Index Page</h2>";
echo "Attempting to include admin index.php logic...<br>";
try {
    // This simulates what happens when accessing /admin/
    if (file_exists('index.php')) {
        echo "✓ admin/index.php file exists<br>";
    } else {
        echo "❌ admin/index.php file NOT found<br>";
    }

    if (file_exists('../includes/functions.php')) {
        require_once '../includes/functions.php';
        echo "✓ functions.php loaded successfully<br>";
    } else {
        echo "❌ functions.php NOT found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If you see any ❌ errors above, those are likely causing the 500 error.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If site_settings table is missing, we need to create it</li>";
echo "<li>If users table is missing, we need to run database setup</li>";
echo "<li>Check the error log for more details</li>";
echo "</ol>";
?>
