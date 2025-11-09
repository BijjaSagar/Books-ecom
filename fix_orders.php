<?php
// fix_orders.php - Fix the orders table structure and add sample data
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Orders Table</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h2>Fixing Orders Table</h2>";

try {
    // Function to check if column exists
    function columnExists($conn, $tableName, $columnName) {
        $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        return $result->num_rows > 0;
    }
    
    echo "<h4>Checking orders table structure...</h4>";
    
    // Get current orders table structure
    $structure = $conn->query("SHOW COLUMNS FROM orders");
    $existing_columns = [];
    while ($row = $structure->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "<div class='alert alert-info'>Current orders columns: " . implode(', ', $existing_columns) . "</div>";
    
    // Add missing columns to orders table
    $required_columns = [
        'order_status' => "ENUM('pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled') DEFAULT 'pending'",
        'payment_status' => "ENUM('pending', 'completed', 'failed') DEFAULT 'pending'",
        'customer_email' => "VARCHAR(255)",
        'first_name' => "VARCHAR(255)",
        'last_name' => "VARCHAR(255)",
        'total_amount' => "DECIMAL(10,2) DEFAULT 0",
        'subtotal' => "DECIMAL(10,2) DEFAULT 0",
        'tax_amount' => "DECIMAL(10,2) DEFAULT 0",
        'shipping_amount' => "DECIMAL(10,2) DEFAULT 0"
    ];
    
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $sql = "ALTER TABLE orders ADD COLUMN $column $definition";
            if ($conn->query($sql)) {
                echo "<div class='alert alert-success'>✅ Added '$column' column to orders table</div>";
            } else {
                echo "<div class='alert alert-warning'>⚠️ Could not add '$column': " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='alert alert-info'>ℹ️ Column '$column' already exists</div>";
        }
    }
    
    // Check if we have any orders
    $order_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    echo "<div class='alert alert-info'>Current order count: $order_count</div>";
    
    if ($order_count == 0) {
        echo "<h4>Adding sample orders...</h4>";
        
        // Add sample orders for dashboard
        $sample_orders = [
            [
                'customer_email' => 'john@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'total_amount' => 35.78,
                'subtotal' => 27.98,
                'tax_amount' => 2.80,
                'shipping_amount' => 5.00,
                'order_status' => 'completed',
                'payment_status' => 'completed'
            ],
            [
                'customer_email' => 'jane@example.com',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'total_amount' => 25.89,
                'subtotal' => 18.99,
                'tax_amount' => 1.90,
                'shipping_amount' => 5.00,
                'order_status' => 'pending',
                'payment_status' => 'pending'
            ],
            [
                'customer_email' => 'bob@example.com',
                'first_name' => 'Bob',
                'last_name' => 'Johnson',
                'total_amount' => 13.19,
                'subtotal' => 11.99,
                'tax_amount' => 1.20,
                'shipping_amount' => 0.00,
                'order_status' => 'completed',
                'payment_status' => 'completed'
            ],
            [
                'customer_email' => 'alice@example.com',
                'first_name' => 'Alice',
                'last_name' => 'Wilson',
                'total_amount' => 21.49,
                'subtotal' => 14.99,
                'tax_amount' => 1.50,
                'shipping_amount' => 5.00,
                'order_status' => 'delivered',
                'payment_status' => 'completed'
            ],
            [
                'customer_email' => 'mike@example.com',
                'first_name' => 'Mike',
                'last_name' => 'Brown',
                'total_amount' => 15.39,
                'subtotal' => 13.99,
                'tax_amount' => 1.40,
                'shipping_amount' => 0.00,
                'order_status' => 'completed',
                'payment_status' => 'completed'
            ]
        ];
        
        // First, get existing user IDs to use for foreign key
        $users_result = $conn->query("SELECT id FROM users LIMIT 5");
        $user_ids = [];
        while ($user = $users_result->fetch_assoc()) {
            $user_ids[] = $user['id'];
        }
        
        // If no users found, skip adding orders
        if (empty($user_ids)) {
            echo "<div class='alert alert-warning'>⚠️ No users found, cannot add orders due to foreign key constraint</div>";
        } else {
            echo "<div class='alert alert-info'>Found " . count($user_ids) . " users for foreign key reference</div>";
            
            foreach ($sample_orders as $index => $order) {
                // Create a random date within the last 30 days
                $random_days = rand(1, 30);
                $created_date = date('Y-m-d H:i:s', strtotime("-$random_days days"));
                
                // Use a random existing user_id
                $user_id = $user_ids[array_rand($user_ids)];
                
                // Check what columns exist in orders table
                $order_columns = [];
                $order_values = [];
                $bind_types = '';
                $bind_values = [];
                
                // Add user_id if column exists
                if (in_array('user_id', $existing_columns)) {
                    $order_columns[] = 'user_id';
                    $order_values[] = '?';
                    $bind_types .= 'i';
                    $bind_values[] = $user_id;
                }
                
                // Add other columns if they exist
                if (in_array('customer_email', $existing_columns)) {
                    $order_columns[] = 'customer_email';
                    $order_values[] = '?';
                    $bind_types .= 's';
                    $bind_values[] = $order['customer_email'];
                }
                
                if (in_array('first_name', $existing_columns)) {
                    $order_columns[] = 'first_name';
                    $order_values[] = '?';
                    $bind_types .= 's';
                    $bind_values[] = $order['first_name'];
                }
                
                if (in_array('last_name', $existing_columns)) {
                    $order_columns[] = 'last_name';
                    $order_values[] = '?';
                    $bind_types .= 's';
                    $bind_values[] = $order['last_name'];
                }
                
                if (in_array('total_amount', $existing_columns)) {
                    $order_columns[] = 'total_amount';
                    $order_values[] = '?';
                    $bind_types .= 'd';
                    $bind_values[] = $order['total_amount'];
                }
                
                if (in_array('subtotal', $existing_columns)) {
                    $order_columns[] = 'subtotal';
                    $order_values[] = '?';
                    $bind_types .= 'd';
                    $bind_values[] = $order['subtotal'];
                }
                
                if (in_array('tax_amount', $existing_columns)) {
                    $order_columns[] = 'tax_amount';
                    $order_values[] = '?';
                    $bind_types .= 'd';
                    $bind_values[] = $order['tax_amount'];
                }
                
                if (in_array('shipping_amount', $existing_columns)) {
                    $order_columns[] = 'shipping_amount';
                    $order_values[] = '?';
                    $bind_types .= 'd';
                    $bind_values[] = $order['shipping_amount'];
                }
                
                if (in_array('order_status', $existing_columns)) {
                    $order_columns[] = 'order_status';
                    $order_values[] = '?';
                    $bind_types .= 's';
                    $bind_values[] = $order['order_status'];
                }
                
                if (in_array('payment_status', $existing_columns)) {
                    $order_columns[] = 'payment_status';
                    $order_values[] = '?';
                    $bind_types .= 's';
                    $bind_values[] = $order['payment_status'];
                }
                
                if (in_array('created_at', $existing_columns)) {
                    $order_columns[] = 'created_at';
                    $order_values[] = '?';
                    $bind_types .= 's';
                    $bind_values[] = $created_date;
                }
                
                // Only insert if we have columns to insert
                if (!empty($order_columns)) {
                    $sql = "INSERT INTO orders (" . implode(', ', $order_columns) . ") VALUES (" . implode(', ', $order_values) . ")";
                    
                    try {
                        $stmt = $conn->prepare($sql);
                        if (!empty($bind_values)) {
                            $stmt->bind_param($bind_types, ...$bind_values);
                        }
                        
                        if ($stmt->execute()) {
                            echo "<div class='alert alert-success'>✅ Added order for {$order['first_name']} {$order['last_name']} (user_id: $user_id)</div>";
                        } else {
                            echo "<div class='alert alert-warning'>⚠️ Could not add order for {$order['first_name']}: " . $stmt->error . "</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='alert alert-danger'>❌ Error adding order for {$order['first_name']}: " . $e->getMessage() . "</div>";
                    }
                } else {
                    echo "<div class='alert alert-warning'>⚠️ No suitable columns found for order insertion</div>";
                }
            }
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Orders already exist, skipping sample data insertion</div>";
    }
    
    // Test the fixed queries
    echo "<h4>Testing Dashboard Queries...</h4>";
    
    try {
        $total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
        echo "<div class='alert alert-success'>✅ Total orders: $total_orders</div>";
        
        $total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status IN ('completed', 'delivered')")->fetch_assoc()['total'] ?? 0;
        echo "<div class='alert alert-success'>✅ Total revenue: $" . number_format($total_revenue, 2) . "</div>";
        
        $pending_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'")->fetch_assoc()['total'];
        echo "<div class='alert alert-success'>✅ Pending orders: $pending_orders</div>";
        
        $completed_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'completed'")->fetch_assoc()['total'];
        echo "<div class='alert alert-success'>✅ Completed orders: $completed_orders</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Query test failed: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
    echo "<div class='alert alert-success'>";
    echo "<h4>🎉 Orders Table Fixed!</h4>";
    echo "<p>Your orders table now has all required columns and sample data.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Delete this fix_orders.php file</li>";
    echo "<li>Go to your admin dashboard</li>";
    echo "<li>Login with: admin@example.com / admin123</li>";
    echo "</ol>";
    echo "<a href='/bookshelf/admin/dashboard.php' class='btn btn-primary me-2'>Go to Dashboard</a>";
    echo "<a href='/bookshelf/admin/' class='btn btn-outline-primary'>Admin Login</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>