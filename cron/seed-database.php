<?php
/**
 * Database Seeder
 * Populate database with sample products, categories, and test data
 * 
 * Usage: php /path/to/cron/seed-database.php
 */

require_once '../includes/config.php';

echo "Starting database seed...\n";

try {
    // Sample categories
    $categories = [
        ['name' => 'Fiction', 'description' => 'Fictional stories and novels'],
        ['name' => 'Non-Fiction', 'description' => 'True stories and factual accounts'],
        ['name' => 'Technology', 'description' => 'Books about technology and programming'],
        ['name' => 'Business', 'description' => 'Business and entrepreneurship books'],
        ['name' => 'Science', 'description' => 'Scientific and educational books'],
        ['name' => 'History', 'description' => 'Historical accounts and biographies'],
        ['name' => 'Self-Help', 'description' => 'Personal development and improvement'],
        ['name' => 'Children', 'description' => 'Books for children and young readers'],
    ];
    
    echo "Inserting categories...\n";
    $category_ids = [];
    $category_stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    
    foreach ($categories as $cat) {
        $category_stmt->bind_param('ss', $cat['name'], $cat['description']);
        $category_stmt->execute();
        $category_ids[] = $conn->insert_id;
    }
    $category_stmt->close();
    echo "✓ " . count($categories) . " categories added\n";
    
    // Sample products
    $products = [
        ['title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'price' => 599, 'category_id' => $category_ids[0], 'stock' => 50],
        ['title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee', 'price' => 649, 'category_id' => $category_ids[0], 'stock' => 45],
        ['title' => '1984', 'author' => 'George Orwell', 'price' => 549, 'category_id' => $category_ids[0], 'stock' => 60],
        ['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'price' => 1299, 'category_id' => $category_ids[2], 'stock' => 30],
        ['title' => 'The Pragmatic Programmer', 'author' => 'Andrew Hunt', 'price' => 999, 'category_id' => $category_ids[2], 'stock' => 25],
        ['title' => 'Sapiens', 'author' => 'Yuval Noah Harari', 'price' => 799, 'category_id' => $category_ids[1], 'stock' => 40],
        ['title' => 'Thinking, Fast and Slow', 'author' => 'Daniel Kahneman', 'price' => 749, 'category_id' => $category_ids[1], 'stock' => 35],
        ['title' => 'Zero to One', 'author' => 'Peter Thiel', 'price' => 699, 'category_id' => $category_ids[3], 'stock' => 20],
        ['title' => 'The Lean Startup', 'author' => 'Eric Ries', 'price' => 679, 'category_id' => $category_ids[3], 'stock' => 15],
        ['title' => 'Good to Great', 'author' => 'Jim Collins', 'price' => 749, 'category_id' => $category_ids[3], 'stock' => 28],
        ['title' => 'Atomic Habits', 'author' => 'James Clear', 'price' => 599, 'category_id' => $category_ids[6], 'stock' => 100],
        ['title' => 'The 7 Habits of Highly Effective People', 'author' => 'Stephen Covey', 'price' => 549, 'category_id' => $category_ids[6], 'stock' => 80],
        ['title' => 'A Brief History of Time', 'author' => 'Stephen Hawking', 'price' => 699, 'category_id' => $category_ids[4], 'stock' => 32],
        ['title' => 'The Selfish Gene', 'author' => 'Richard Dawkins', 'price' => 649, 'category_id' => $category_ids[4], 'stock' => 25],
        ['title' => 'Cosmos', 'author' => 'Carl Sagan', 'price' => 799, 'category_id' => $category_ids[4], 'stock' => 20],
        ['title' => 'The Code Breaker', 'author' => 'Walter Isaacson', 'price' => 749, 'category_id' => $category_ids[5], 'stock' => 15],
        ['title' => 'Steve Jobs', 'author' => 'Walter Isaacson', 'price' => 699, 'category_id' => $category_ids[5], 'stock' => 18],
        ['title' => 'Where the Wild Things Are', 'author' => 'Maurice Sendak', 'price' => 399, 'category_id' => $category_ids[7], 'stock' => 70],
        ['title' => 'The Very Hungry Caterpillar', 'author' => 'Eric Carle', 'price' => 299, 'category_id' => $category_ids[7], 'stock' => 90],
        ['title' => 'Charlotte\'s Web', 'author' => 'E.B. White', 'price' => 449, 'category_id' => $category_ids[7], 'stock' => 55],
    ];
    
    echo "Inserting products...\n";
    $product_stmt = $conn->prepare("
        INSERT INTO products (title, author, price, category_id, stock_quantity, description, rating, review_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($products as $prod) {
        $desc = "A fascinating book that everyone should read.";
        $rating = 4.5 + (rand(0, 5) / 10);
        $reviews = rand(10, 100);
        
        $product_stmt->bind_param('ssiisidi', 
            $prod['title'], 
            $prod['author'], 
            $prod['price'],
            $prod['category_id'],
            $prod['stock'],
            $desc,
            $rating,
            $reviews
        );
        $product_stmt->execute();
    }
    $product_stmt->close();
    echo "✓ " . count($products) . " products added\n";
    
    // Create test users
    echo "Creating test users...\n";
    
    // Test customer account
    $customer_email = 'customer@bookstore.com';
    $customer_password = password_hash('password123', PASSWORD_BCRYPT);
    $customer_name = 'John Doe';
    
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param('s', $customer_email);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    
    if (!$exists) {
        $customer_insert = $conn->prepare("
            INSERT INTO users (email, password, name, role, phone, address, city, state, pincode, created_at)
            VALUES (?, ?, ?, 'customer', '9876543210', '123 Main St', 'Mumbai', 'Maharashtra', '400001', NOW())
        ");
        $customer_insert->bind_param('sss', $customer_email, $customer_password, $customer_name);
        $customer_insert->execute();
        $customer_insert->close();
        echo "✓ Test customer account created: $customer_email\n";
    }
    
    // Test admin account
    $admin_email = 'admin@bookstore.com';
    $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
    $admin_name = 'Admin User';
    
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param('s', $admin_email);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    
    if (!$exists) {
        $admin_insert = $conn->prepare("
            INSERT INTO users (email, password, name, role, phone, created_at)
            VALUES (?, ?, ?, 'admin', '9876543210', NOW())
        ");
        $admin_insert->bind_param('sss', $admin_email, $admin_password, $admin_name);
        $admin_insert->execute();
        $admin_insert->close();
        echo "✓ Test admin account created: $admin_email\n";
    }
    
    echo "\n✓ Database seeding completed successfully!\n";
    echo "\nTest Accounts:\n";
    echo "  Customer Email: customer@bookstore.com\n";
    echo "  Customer Password: password123\n";
    echo "  Admin Email: admin@bookstore.com\n";
    echo "  Admin Password: admin123\n";
    
} catch (Exception $e) {
    echo "✗ Error during seeding: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
exit(0);
?>
