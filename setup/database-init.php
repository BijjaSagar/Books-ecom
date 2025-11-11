<?php
/**
 * Database Initialization Script for Hostinger
 * Run this ONCE to create all necessary tables on your Hostinger database
 * 
 * USAGE:
 * 1. Upload this file to your Hostinger server (/setup/ folder)
 * 2. Visit: https://yourdomain.com/setup/database-init.php
 * 3. Follow the instructions on screen
 * 4. DELETE this file after setup completes for security
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Bookstore - Database Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f3f4f6;
        }
        .container {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 { color: #1e40af; margin-bottom: 20px; }
        h2 { color: #374151; margin-top: 24px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; }
        .status {
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            border-left: 4px solid;
        }
        .status.success { background: #d1fae5; color: #065f46; border-left-color: #10b981; }
        .status.error { background: #fee2e2; color: #991b1b; border-left-color: #ef4444; }
        .status.warning { background: #fef3c7; color: #92400e; border-left-color: #f59e0b; }
        .status.info { background: #dbeafe; color: #1e40af; border-left-color: #1e40af; }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        button {
            background: #1e40af;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 20px;
        }
        button:hover { background: #1e3a8a; }
        button:disabled { background: #9ca3af; cursor: not-allowed; }
        .checklist { list-style: none; padding: 0; }
        .checklist li { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .checklist li:before { content: "✓ "; color: #10b981; font-weight: 700; margin-right: 8px; }
        .footer {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 0.9rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 Books Bookstore - Database Setup</h1>

        <?php
        $connection_ok = !$conn->connect_error;
        
        if ($connection_ok) {
            echo '<div class="status success">✓ <strong>Database Connection Successful!</strong></div>';
            echo '<p>Connected to: <code>' . htmlspecialchars($db_name) . '</code></p>';
        } else {
            echo '<div class="status error">✗ <strong>Database Connection Failed!</strong></div>';
            echo '<p>Error: ' . htmlspecialchars($conn->connect_error) . '</p>';
            echo '<p>Please verify your credentials in <code>includes/config.php</code></p>';
            die();
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_database'])) {
            echo '<h2>Setting Up Database Tables...</h2>';
            
            $tables_created = 0;
            $sql_tables = [
                'users' => "CREATE TABLE IF NOT EXISTS users (id INT PRIMARY KEY AUTO_INCREMENT, email VARCHAR(255) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, role ENUM('customer','admin','seller') DEFAULT 'customer', status VARCHAR(50) DEFAULT 'active', phone VARCHAR(20), address TEXT, city VARCHAR(100), state VARCHAR(100), pincode VARCHAR(10), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'categories' => "CREATE TABLE IF NOT EXISTS categories (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255) NOT NULL UNIQUE, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'products' => "CREATE TABLE IF NOT EXISTS products (id INT PRIMARY KEY AUTO_INCREMENT, title VARCHAR(255) NOT NULL, author VARCHAR(255), description LONGTEXT, price DECIMAL(10,2) NOT NULL, category_id INT, stock_quantity INT DEFAULT 0, rating DECIMAL(3,1) DEFAULT 0, review_count INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (category_id) REFERENCES categories(id), INDEX idx_category (category_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'orders' => "CREATE TABLE IF NOT EXISTS orders (id INT PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, total_amount DECIMAL(10,2) NOT NULL, order_status VARCHAR(50) DEFAULT 'pending', payment_method VARCHAR(50), delivery_address TEXT, city VARCHAR(100), state VARCHAR(100), pincode VARCHAR(10), tracking_number VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id), INDEX idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'order_items' => "CREATE TABLE IF NOT EXISTS order_items (id INT PRIMARY KEY AUTO_INCREMENT, order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, price DECIMAL(10,2) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id), INDEX idx_order (order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'product_reviews' => "CREATE TABLE IF NOT EXISTS product_reviews (id INT PRIMARY KEY AUTO_INCREMENT, product_id INT NOT NULL, user_id INT NOT NULL, rating INT NOT NULL, title VARCHAR(255), comment LONGTEXT, verified_purchase TINYINT(1) DEFAULT 0, is_approved TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (product_id) REFERENCES products(id), FOREIGN KEY (user_id) REFERENCES users(id), INDEX idx_product (product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'coupons' => "CREATE TABLE IF NOT EXISTS coupons (id INT PRIMARY KEY AUTO_INCREMENT, code VARCHAR(50) UNIQUE NOT NULL, discount_type VARCHAR(20), discount_value DECIMAL(10,2), min_order_value DECIMAL(10,2), max_uses INT, per_customer_limit INT, valid_from DATETIME, valid_until DATETIME, status VARCHAR(20) DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_code (code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'wishlists' => "CREATE TABLE IF NOT EXISTS wishlists (id INT PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, product_id INT NOT NULL, added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id), UNIQUE KEY unique_wishlist (user_id, product_id), INDEX idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'email_queue' => "CREATE TABLE IF NOT EXISTS email_queue (id INT PRIMARY KEY AUTO_INCREMENT, to_email VARCHAR(255) NOT NULL, type VARCHAR(50), subject VARCHAR(255), body LONGTEXT, status VARCHAR(20) DEFAULT 'pending', retry_count INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'support_tickets' => "CREATE TABLE IF NOT EXISTS support_tickets (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20), subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, category VARCHAR(50), status VARCHAR(20) DEFAULT 'open', response LONGTEXT, responded_by INT, responded_at DATETIME, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'system_settings' => "CREATE TABLE IF NOT EXISTS system_settings (id INT PRIMARY KEY AUTO_INCREMENT, site_name VARCHAR(255), site_email VARCHAR(255), support_phone VARCHAR(20), low_stock_threshold INT DEFAULT 10, order_confirmation_email TINYINT(1) DEFAULT 1, status_update_email TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            ];

            foreach ($sql_tables as $table_name => $sql) {
                if ($conn->query($sql)) {
                    echo '<div class="status success">✓ Table created: <code>' . htmlspecialchars($table_name) . '</code></div>';
                    $tables_created++;
                } else {
                    echo '<div class="status error">✗ Error: ' . htmlspecialchars($conn->error) . '</div>';
                }
            }

            echo '<h2>Summary</h2>';
            echo '<div class="status success"><strong>✓ ' . $tables_created . ' tables successfully created!</strong></div>';
            echo '<p><strong>Next Steps:</strong></p><ol>';
            echo '<li>Run the seeder: <code>php /cron/seed-database.php</code></li>';
            echo '<li>Delete this setup file for security</li>';
            echo '<li>Visit: <code>https://yourdomain.com/admin/</code></li>';
            echo '</ol>';
        } else {
            ?>
            <h2>Database Setup</h2>
            <p>Click the button below to create all necessary tables:</p>
            
            <h3>Tables to be created:</h3>
            <ul class="checklist">
                <li>Users (customers and admins)</li>
                <li>Products and Categories</li>
                <li>Orders and Order Items</li>
                <li>Reviews, Wishlists, Coupons</li>
                <li>Email Queue and Support Tickets</li>
                <li>System Settings</li>
            </ul>

            <form method="POST">
                <button type="submit" name="setup_database" value="1">✓ Create Tables</button>
            </form>

            <div class="status warning">⚠️ Delete this file after setup!</div>
            <?php
        }
        ?>

        <div class="footer">
            <p><strong>Connection Info:</strong><br>
            Host: <code><?php echo htmlspecialchars($db_host); ?></code><br>
            DB: <code><?php echo htmlspecialchars($db_name); ?></code></p>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
