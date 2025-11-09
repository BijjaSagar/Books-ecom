<?php
// setup_sqlite.php - Setup script for SQLite database

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>BookShelf SQLite Database Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-10'>
            <div class='card'>
                <div class='card-header'>
                    <h3 class='text-center'>BookShelf SQLite Database Setup</h3>
                </div>
                <div class='card-body'>";

try {
    echo "<h4>Creating Tables...</h4>";
    
    // 1. Users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'customer',
        phone TEXT,
        address TEXT,
        city TEXT,
        state TEXT,
        postal_code TEXT,
        country TEXT DEFAULT 'India',
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Users table created</div>";
    }
    
    // 2. Categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        slug TEXT UNIQUE,
        description TEXT,
        sort_order INTEGER DEFAULT 0,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Categories table created</div>";
    }
    
    // 3. Products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        sale_price REAL,
        sku TEXT UNIQUE,
        stock_quantity INTEGER DEFAULT 0,
        category_id INTEGER,
        author TEXT,
        publisher TEXT,
        isbn TEXT,
        featured INTEGER DEFAULT 0,
        status TEXT DEFAULT 'published',
        image TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Products table created</div>";
    }
    
    // 4. Orders table
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT UNIQUE,
        customer_id INTEGER,
        customer_email TEXT NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        phone TEXT,
        address_line_1 TEXT NOT NULL,
        city TEXT NOT NULL,
        state TEXT NOT NULL,
        postal_code TEXT NOT NULL,
        country TEXT DEFAULT 'India',
        subtotal REAL NOT NULL,
        tax_amount REAL DEFAULT 0,
        shipping_amount REAL DEFAULT 0,
        total_amount REAL NOT NULL,
        payment_method TEXT,
        payment_status TEXT DEFAULT 'pending',
        order_status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Orders table created</div>";
    }
    
    // 5. Order items table
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        product_name TEXT NOT NULL,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        total REAL NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Order items table created</div>";
    }
    
    // 6. Site settings table
    $sql = "CREATE TABLE IF NOT EXISTS site_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Site settings table created</div>";
    }
    
    // 7. Navigation menus table
    $sql = "CREATE TABLE IF NOT EXISTS navigation_menus (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        location TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Navigation menus table created</div>";
    }
    
    // 8. Menu items table
    $sql = "CREATE TABLE IF NOT EXISTS menu_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        menu_id INTEGER NOT NULL,
        parent_id INTEGER,
        title TEXT NOT NULL,
        url TEXT,
        target TEXT DEFAULT '_self',
        icon_class TEXT,
        css_class TEXT,
        description TEXT,
        is_category_link INTEGER DEFAULT 0,
        category_id INTEGER,
        sort_order INTEGER DEFAULT 0,
        status TEXT DEFAULT 'active',
        visibility TEXT DEFAULT 'public',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Menu items table created</div>";
    }
    
    // 9. Menu cache table
    $sql = "CREATE TABLE IF NOT EXISTS menu_cache (
        cache_key TEXT PRIMARY KEY,
        cached_html TEXT,
        expires_at DATETIME,
        menu_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Menu cache table created</div>";
    }
    
    echo "<hr><h4>Inserting Default Data...</h4>";
    
    // Check if admin user exists
    $admin_check = $conn->query("SELECT id FROM users WHERE email = 'admin@bookshelf.com'");
    if (!$admin_check || !$admin_check->fetchArray(SQLITE3_ASSOC)) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bindValue(1, 'Admin User', SQLITE3_TEXT);
        $stmt->bindValue(2, 'admin@bookshelf.com', SQLITE3_TEXT);
        $stmt->bindValue(3, $admin_password, SQLITE3_TEXT);
        $stmt->bindValue(4, 'admin', SQLITE3_TEXT);
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
        $check->bindValue(1, $setting[0], SQLITE3_TEXT);
        $result = $check->execute();
        if (!$result || !$result->fetchArray(SQLITE3_ASSOC)) {
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bindValue(1, $setting[0], SQLITE3_TEXT);
            $stmt->bindValue(2, $setting[1], SQLITE3_TEXT);
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
        $check->bindValue(1, $cat[0], SQLITE3_TEXT);
        $result = $check->execute();
        if (!$result || !$result->fetchArray(SQLITE3_ASSOC)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bindValue(1, $cat[0], SQLITE3_TEXT);
            $stmt->bindValue(2, $cat[1], SQLITE3_TEXT);
            $stmt->execute();
        }
    }
    echo "<div class='alert alert-success'>✅ Sample categories inserted</div>";
    
    // Sample products
    $products = [
        ['The Great Gatsby', 'A classic American novel', 12.99, 'BOOK001', 50, 1, 'F. Scott Fitzgerald'],
        ['To Kill a Mockingbird', 'Harper Lee\'s timeless novel', 14.99, 'BOOK002', 30, 1, 'Harper Lee'],
        ['Dune', 'Frank Herbert\'s epic sci-fi', 18.99, 'BOOK003', 40, 5, 'Frank Herbert'],
        ['Pride and Prejudice', 'Jane Austen\'s romance', 11.99, 'BOOK004', 35, 3, 'Jane Austen'],
        ['1984', 'George Orwell\'s dystopian classic', 13.99, 'BOOK005', 45, 1, 'George Orwell']
    ];
    
    foreach ($products as $prod) {
        $check = $conn->prepare("SELECT id FROM products WHERE name = ?");
        $check->bindValue(1, $prod[0], SQLITE3_TEXT);
        $result = $check->execute();
        if (!$result || !$result->fetchArray(SQLITE3_ASSOC)) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, sku, stock_quantity, category_id, author) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $prod[0], SQLITE3_TEXT);
            $stmt->bindValue(2, $prod[1], SQLITE3_TEXT);
            $stmt->bindValue(3, $prod[2], SQLITE3_TEXT);
            $stmt->bindValue(4, $prod[3], SQLITE3_TEXT);
            $stmt->bindValue(5, $prod[4], SQLITE3_INTEGER);
            $stmt->bindValue(6, $prod[5], SQLITE3_INTEGER);
            $stmt->bindValue(7, $prod[6], SQLITE3_TEXT);
            $stmt->execute();
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
    $order_count = $order_check->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($order_count == 0) {
        foreach ($sample_orders as $order) {
            $stmt = $conn->prepare("INSERT INTO orders (customer_email, first_name, last_name, address_line_1, city, state, postal_code, country, subtotal, tax_amount, shipping_amount, total_amount, order_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', '-" . rand(1, 30) . " days'))");
            $stmt->bindValue(1, $order[0], SQLITE3_TEXT);
            $stmt->bindValue(2, $order[1], SQLITE3_TEXT);
            $stmt->bindValue(3, $order[2], SQLITE3_TEXT);
            $stmt->bindValue(4, $order[3], SQLITE3_TEXT);
            $stmt->bindValue(5, $order[4], SQLITE3_TEXT);
            $stmt->bindValue(6, $order[5], SQLITE3_TEXT);
            $stmt->bindValue(7, $order[6], SQLITE3_TEXT);
            $stmt->bindValue(8, $order[7], SQLITE3_TEXT);
            $stmt->bindValue(9, $order[8], SQLITE3_TEXT);
            $stmt->bindValue(10, $order[9], SQLITE3_TEXT);
            $stmt->bindValue(11, $order[10], SQLITE3_TEXT);
            $stmt->bindValue(12, $order[11], SQLITE3_TEXT);
            $stmt->bindValue(13, $order[12], SQLITE3_TEXT);
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
    echo "<strong>⚠️ Security Notice:</strong> Please delete this setup_sqlite.php file after setup is complete!";
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