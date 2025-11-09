<?php
// setup_database.php - Fixed version that handles existing tables

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>BookShelf Database Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-10'>
            <div class='card'>
                <div class='card-header'>
                    <h3 class='text-center'>BookShelf Database Setup</h3>
                </div>
                <div class='card-body'>";

try {
    echo "<h4>Checking and Creating Tables...</h4>";
    
    // Function to check if table exists
    function tableExists($conn, $tableName) {
        $result = $conn->query("SHOW TABLES LIKE '$tableName'");
        return $result->num_rows > 0;
    }
    
    // Function to check if column exists
    function columnExists($conn, $tableName, $columnName) {
        $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        return $result->num_rows > 0;
    }
    
    // 1. Users table
    if (!tableExists($conn, 'users')) {
        $sql = "CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'customer') DEFAULT 'customer',
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            postal_code VARCHAR(20),
            country VARCHAR(100) DEFAULT 'India',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Users table created</div>";
        }
    } else {
        // Add missing columns to existing users table
        if (!columnExists($conn, 'users', 'status')) {
            $conn->query("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
            echo "<div class='alert alert-info'>ℹ️ Added status column to users table</div>";
        }
        if (!columnExists($conn, 'users', 'role')) {
            $conn->query("ALTER TABLE users ADD COLUMN role ENUM('admin', 'customer') DEFAULT 'customer'");
            echo "<div class='alert alert-info'>ℹ️ Added role column to users table</div>";
        }
        echo "<div class='alert alert-info'>ℹ️ Users table already exists</div>";
    }
    
    // 2. Categories table
    if (!tableExists($conn, 'categories')) {
        $sql = "CREATE TABLE categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL UNIQUE,
            slug VARCHAR(255) UNIQUE,
            description TEXT,
            sort_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Categories table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Categories table already exists</div>";
    }
    
    // 3. Products table
    if (!tableExists($conn, 'products')) {
        $sql = "CREATE TABLE products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            sale_price DECIMAL(10,2) NULL,
            sku VARCHAR(100) UNIQUE,
            stock_quantity INT DEFAULT 0,
            category_id INT,
            author VARCHAR(255),
            publisher VARCHAR(255),
            isbn VARCHAR(20),
            featured BOOLEAN DEFAULT 0,
            status ENUM('published', 'draft') DEFAULT 'published',
            image VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Products table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Products table already exists</div>";
    }
    
    // 4. Orders table
    if (!tableExists($conn, 'orders')) {
        $sql = "CREATE TABLE orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_number VARCHAR(50) UNIQUE,
            customer_id INT NULL,
            customer_email VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address_line_1 VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) DEFAULT 'India',
            subtotal DECIMAL(10,2) NOT NULL,
            tax_amount DECIMAL(10,2) DEFAULT 0,
            shipping_amount DECIMAL(10,2) DEFAULT 0,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Orders table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Orders table already exists</div>";
    }
    
    // 5. Order items table
    if (!tableExists($conn, 'order_items')) {
        $sql = "CREATE TABLE order_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Order items table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Order items table already exists</div>";
    }
    
    // 6. Site settings table
    if (!tableExists($conn, 'site_settings')) {
        $sql = "CREATE TABLE site_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Site settings table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Site settings table already exists</div>";
    }
    
    // 7. Navigation menus table
    if (!tableExists($conn, 'navigation_menus')) {
        $sql = "CREATE TABLE navigation_menus (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(100) NOT NULL,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Navigation menus table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Navigation menus table already exists</div>";
    }
    
    // 8. Menu items table
    if (!tableExists($conn, 'menu_items')) {
        $sql = "CREATE TABLE menu_items (
            id INT PRIMARY KEY AUTO_INCREMENT,
            menu_id INT NOT NULL,
            parent_id INT NULL,
            title VARCHAR(255) NOT NULL,
            url VARCHAR(500),
            target VARCHAR(20) DEFAULT '_self',
            icon_class VARCHAR(100),
            css_class VARCHAR(100),
            description TEXT,
            is_category_link BOOLEAN DEFAULT 0,
            category_id INT NULL,
            sort_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            visibility ENUM('public', 'logged_in', 'logged_out', 'admin') DEFAULT 'public',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Menu items table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Menu items table already exists</div>";
    }
    
    // 9. Menu cache table
    if (!tableExists($conn, 'menu_cache')) {
        $sql = "CREATE TABLE menu_cache (
            cache_key VARCHAR(255) PRIMARY KEY,
            cached_html LONGTEXT,
            expires_at DATETIME,
            menu_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Menu cache table created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Menu cache table already exists</div>";
    }
    
    echo "<hr><h4>Inserting Default Data...</h4>";
    
    // Check if admin user exists
    $admin_check = $conn->query("SELECT id FROM users WHERE email = 'admin@bookshelf.com'");
    if ($admin_check->num_rows == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($admin_sql);
        $full_name = 'Admin User';
        $email = 'admin@bookshelf.com';
        $role = 'admin';
        $stmt->bind_param("ssss", $full_name, $email, $admin_password, $role);
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ Default admin user created</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Admin user already exists</div>";
    }
    
    // Default settings
    $settings = [
        ['site_name', 'BookShelf'],
        ['currency_symbol', '₹'],
        ['currency_code', 'INR'],
        ['tax_rate', '10'],
        ['shipping_cost', '5.00'],
        ['enable_menu_cache', '1'],
        ['menu_cache_duration', '3600']
    ];
    
    foreach ($settings as $setting) {
        $check = $conn->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
        $check->bind_param("s", $setting[0]);
        $check->execute();
        if ($check->get_result()->num_rows == 0) {
            $setting_sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)";
            $stmt = $conn->prepare($setting_sql);
            $stmt->bind_param("ss", $setting[0], $setting[1]);
            $stmt->execute();
        }
    }
    echo "<div class='alert alert-success'>✅ Default settings inserted</div>";
    
    // Sample categories
    $categories = [
        ['Fiction', 'Fictional books and novels'],
        ['Non-Fiction', 'Non-fictional books'],
        ['Romance', 'Romance novels'],
        ['Mystery', 'Mystery and thriller books'],
        ['Science Fiction', 'Science fiction books']
    ];
    
    foreach ($categories as $cat) {
        $check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $check->bind_param("s", $cat[0]);
        $check->execute();
        if ($check->get_result()->num_rows == 0) {
            $cat_sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($cat_sql);
            $stmt->bind_param("ss", $cat[0], $cat[1]);
            $stmt->execute();
        }
    }
    echo "<div class='alert alert-success'>✅ Sample categories inserted</div>";
    
    // Check existing products table structure
    if (tableExists($conn, 'products')) {
        echo "<div class='alert alert-info'>📋 Checking products table structure...</div>";
        
        // Get current table structure
        $structure = $conn->query("SHOW COLUMNS FROM products");
        $existing_columns = [];
        while ($row = $structure->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
        
        echo "<div class='alert alert-info'>Current columns: " . implode(', ', $existing_columns) . "</div>";
        
        // Add missing essential columns
        $required_columns = [
            'name' => 'VARCHAR(255) NOT NULL',
            'description' => 'TEXT',
            'price' => 'DECIMAL(10,2) NOT NULL',
            'sku' => 'VARCHAR(100) UNIQUE',
            'stock_quantity' => 'INT DEFAULT 0',
            'author' => 'VARCHAR(255)',
            'category_id' => 'INT'
        ];
        
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                $conn->query("ALTER TABLE products ADD COLUMN $column $definition");
                echo "<div class='alert alert-info'>ℹ️ Added $column column to products table</div>";
                $existing_columns[] = $column;
            }
        }
    }

    // Sample products - only insert if we have a proper products table
    if (tableExists($conn, 'products') && in_array('name', $existing_columns)) {
        $products = [
            ['The Great Gatsby', 'A classic American novel', 12.99, 'BOOK001', 50, 1, 'F. Scott Fitzgerald'],
            ['To Kill a Mockingbird', 'Harper Lee\'s timeless novel', 14.99, 'BOOK002', 30, 1, 'Harper Lee'],
            ['Dune', 'Frank Herbert\'s epic sci-fi', 18.99, 'BOOK003', 40, 5, 'Frank Herbert'],
            ['Pride and Prejudice', 'Jane Austen\'s romance', 11.99, 'BOOK004', 35, 3, 'Jane Austen'],
            ['1984', 'George Orwell\'s dystopian classic', 13.99, 'BOOK005', 45, 1, 'George Orwell']
        ];
        
        foreach ($products as $prod) {
            // Check if product exists by name (safely)
            try {
                $check = $conn->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
                $check->bind_param("s", $prod[0]);
                $check->execute();
                
                if ($check->get_result()->num_rows == 0) {
                    // Build dynamic SQL based on available columns
                    $columns = [];
                    $values = [];
                    $bind_types = '';
                    $bind_values = [];
                    
                    // Add columns that exist in table
                    if (in_array('name', $existing_columns)) {
                        $columns[] = 'name'; $values[] = '?'; $bind_types .= 's'; $bind_values[] = $prod[0];
                    }
                    if (in_array('description', $existing_columns)) {
                        $columns[] = 'description'; $values[] = '?'; $bind_types .= 's'; $bind_values[] = $prod[1];
                    }
                    if (in_array('price', $existing_columns)) {
                        $columns[] = 'price'; $values[] = '?'; $bind_types .= 'd'; $bind_values[] = $prod[2];
                    }
                    if (in_array('sku', $existing_columns)) {
                        $columns[] = 'sku'; $values[] = '?'; $bind_types .= 's'; $bind_values[] = $prod[3];
                    }
                    if (in_array('stock_quantity', $existing_columns)) {
                        $columns[] = 'stock_quantity'; $values[] = '?'; $bind_types .= 'i'; $bind_values[] = $prod[4];
                    }
                    if (in_array('category_id', $existing_columns)) {
                        $columns[] = 'category_id'; $values[] = '?'; $bind_types .= 'i'; $bind_values[] = $prod[5];
                    }
                    if (in_array('author', $existing_columns)) {
                        $columns[] = 'author'; $values[] = '?'; $bind_types .= 's'; $bind_values[] = $prod[6];
                    }
                    
                    if (!empty($columns)) {
                        $prod_sql = "INSERT INTO products (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
                        $stmt = $conn->prepare($prod_sql);
                        if (!empty($bind_values)) {
                            $stmt->bind_param($bind_types, ...$bind_values);
                        }
                        $stmt->execute();
                    }
                }
            } catch (Exception $e) {
                echo "<div class='alert alert-warning'>⚠️ Could not insert product '{$prod[0]}': " . $e->getMessage() . "</div>";
            }
        }
    }
    echo "<div class='alert alert-success'>✅ Sample products inserted</div>";
    
    // Sample orders for dashboard data
    $sample_orders = [
        ['customer@example.com', 'John', 'Doe', '123 Main St', 'New York', 'NY', '10001', 'USA', 27.98, 2.80, 5.00, 35.78, 'completed'],
        ['jane@example.com', 'Jane', 'Smith', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 'USA', 18.99, 1.90, 5.00, 25.89, 'pending'],
        ['bob@example.com', 'Bob', 'Johnson', '789 Pine Rd', 'Chicago', 'IL', '60601', 'USA', 11.99, 1.20, 0.00, 13.19, 'completed'],
        ['alice@example.com', 'Alice', 'Wilson', '321 Elm St', 'Miami', 'FL', '33101', 'USA', 14.99, 1.50, 5.00, 21.49, 'delivered'],
        ['mike@example.com', 'Mike', 'Brown', '654 Maple Ave', 'Seattle', 'WA', '98101', 'USA', 13.99, 1.40, 0.00, 15.39, 'completed']
    ];
    
    // Check if we already have orders
    $order_check = $conn->query("SELECT COUNT(*) as count FROM orders");
    $order_count = $order_check->fetch_assoc()['count'];
    
    if ($order_count == 0) {
        foreach ($sample_orders as $order) {
            $order_sql = "INSERT INTO orders (customer_email, first_name, last_name, address_line_1, city, state, postal_code, country, subtotal, tax_amount, shipping_amount, total_amount, order_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 30) DAY)";
            $stmt = $conn->prepare($order_sql);
            $stmt->bind_param("ssssssssdddds", ...$order);
            $stmt->execute();
        }
        echo "<div class='alert alert-success'>✅ Sample orders inserted</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ Orders already exist ({$order_count} orders)</div>";
    }
    
    echo "<hr>";
    echo "<div class='alert alert-success'>";
    echo "<h4>🎉 Database Setup Complete!</h4>";
    echo "<p><strong>Admin Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@bookshelf.com</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "<p class='mt-3'>";
    echo "<a href='/bookshelf/admin/' class='btn btn-primary me-2'>Go to Admin Panel</a>";
    echo "<a href='/bookshelf/admin/dashboard.php' class='btn btn-success me-2'>View Dashboard</a>";
    echo "<a href='/bookshelf/' class='btn btn-outline-primary'>View Website</a>";
    echo "</p>";
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<strong>⚠️ Security Notice:</strong> Please delete this setup_database.php file after setup is complete!";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h5>📊 What's been set up:</h5>";
    echo "<ul class='mb-0'>";
    echo "<li>✅ All database tables created</li>";
    echo "<li>✅ Admin user account ready</li>";
    echo "<li>✅ Sample products and categories</li>";
    echo "<li>✅ Sample orders for dashboard statistics</li>";
    echo "<li>✅ Default site settings configured</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error during setup:</h4>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "            </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>";
?>