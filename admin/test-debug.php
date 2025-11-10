<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Information:</h2>";

// Test 1: Config file
echo "<p><strong>1. Testing config.php...</strong></p>";
try {
    require_once '../includes/config.php';
    echo "✅ Config loaded successfully<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "Host: " . DB_HOST . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 2: Functions file
echo "<p><strong>2. Testing functions.php...</strong></p>";
try {
    require_once '../includes/functions.php';
    echo "✅ Functions loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Functions error: " . $e->getMessage() . "<br>";
}

// Test 3: Database connection
echo "<p><strong>3. Testing database connection...</strong></p>";
if ($conn->connect_error) {
    echo "❌ Connection error: " . $conn->connect_error . "<br>";
} else {
    echo "✅ Connected to MySQL<br>";
}

// Test 4: Check users table
echo "<p><strong>4. Checking users table...</strong></p>";
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
if ($result) {
    $res = $result->get_result();
    $row = $res->fetch_assoc();
    echo "✅ Query successful<br>";
    echo "Admin count: " . $row['count'] . "<br>";
} else {
    echo "❌ Query error: " . $conn->error . "<br>";
}

// Test 5: Check site_settings table
echo "<p><strong>5. Checking site_settings table...</strong></p>";
$result = $conn->query("SELECT * FROM site_settings LIMIT 1");
if ($result) {
    echo "✅ site_settings table accessible<br>";
    if ($result->num_rows > 0) {
        echo "Found " . $result->num_rows . " settings<br>";
    } else {
        echo "⚠️ No settings found (creating defaults)<br>";
    }
} else {
    echo "❌ Query error: " . $conn->error . "<br>";
}

?>
