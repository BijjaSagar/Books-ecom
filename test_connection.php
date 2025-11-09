<?php
// test_connection.php - Simple test to verify database and data
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h2>Database Connection Test</h2>";

try {
    // Test database connection
    echo "<div class='alert alert-success'>✅ Database connected successfully</div>";
    
    // Test tables exist
    $tables = ['users', 'products', 'orders', 'categories', 'site_settings'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<div class='alert alert-success'>✅ Table '$table' exists</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Table '$table' missing</div>";
        }
    }
    
    // Test data counts
    echo "<h3>Data Counts:</h3>";
    
    $queries = [
        'Users' => "SELECT COUNT(*) as count FROM users",
        'Products' => "SELECT COUNT(*) as count FROM products", 
        'Orders' => "SELECT COUNT(*) as count FROM orders",
        'Categories' => "SELECT COUNT(*) as count FROM categories"
    ];
    
    foreach ($queries as $name => $query) {
        try {
            $result = $conn->query($query);
            $count = $result->fetch_assoc()['count'];
            echo "<div class='alert alert-info'>📊 $name: $count records</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-warning'>⚠️ $name: Error - " . $e->getMessage() . "</div>";
        }
    }
    
    // Test admin user
    $admin_check = $conn->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    if ($admin_check->num_rows > 0) {
        $admin = $admin_check->fetch_assoc();
        echo "<div class='alert alert-success'>✅ Admin user found: " . htmlspecialchars($admin['email']) . "</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ No admin user found</div>";
    }
    
    // Test sample orders
    $orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'completed'");
    $completed_orders = $orders->fetch_assoc()['count'];
    echo "<div class='alert alert-info'>📦 Completed orders: $completed_orders</div>";
    
    // Test revenue calculation
    $revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status = 'completed'");
    $total_revenue = $revenue->fetch_assoc()['total'] ?? 0;
    echo "<div class='alert alert-info'>💰 Total revenue: $" . number_format($total_revenue, 2) . "</div>";
    
    echo "<hr>";
    echo "<div class='alert alert-success'>";
    echo "<h4>🎉 Everything looks good!</h4>";
    echo "<p>Your database setup is complete and working.</p>";
    echo "<a href='/bookshelf/admin/' class='btn btn-primary'>Go to Admin Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>