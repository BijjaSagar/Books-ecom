<?php
// Migration 001: Add reviews and ratings system
// This script adds the reviews table and modifies the products table to include rating fields

require_once '../includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Migration 001: Add Reviews System</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-10'>
            <div class='card'>
                <div class='card-header'>
                    <h3 class='text-center'>Migration 001: Add Reviews and Ratings System</h3>
                </div>
                <div class='card-body'>";

try {
    echo "<h4>Creating Reviews Table...</h4>";
    
    // 1. Create reviews table
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        user_id INTEGER,
        rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
        title TEXT,
        comment TEXT,
        is_verified_purchase INTEGER DEFAULT 0,
        status TEXT DEFAULT 'approved',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Reviews table created successfully</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Failed to create reviews table</div>";
    }
    
    echo "<h4>Adding Rating Columns to Products Table...</h4>";
    
    // 2. Add rating columns to products table (if they don't exist)
    // Check if columns exist
    $columns = [];
    $result = $conn->query("PRAGMA table_info(products)");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }
    
    // Add rating column if it doesn't exist
    if (!in_array('rating', $columns)) {
        $sql = "ALTER TABLE products ADD COLUMN rating REAL DEFAULT 0";
        if ($conn->exec($sql)) {
            echo "<div class='alert alert-success'>✅ Rating column added to products table</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Failed to add rating column to products table</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Rating column already exists in products table</div>";
    }
    
    // Add reviews_count column if it doesn't exist
    if (!in_array('reviews_count', $columns)) {
        $sql = "ALTER TABLE products ADD COLUMN reviews_count INTEGER DEFAULT 0";
        if ($conn->exec($sql)) {
            echo "<div class='alert alert-success'>✅ Reviews count column added to products table</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Failed to add reviews count column to products table</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Reviews count column already exists in products table</div>";
    }
    
    echo "<h4>Creating Indexes...</h4>";
    
    // 3. Create indexes for better performance
    $sql = "CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id)";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Reviews product_id index created</div>";
    }
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_reviews_user_id ON reviews(user_id)";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Reviews user_id index created</div>";
    }
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(rating)";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Reviews rating index created</div>";
    }
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_products_rating ON products(rating)";
    if ($conn->exec($sql)) {
        echo "<div class='alert alert-success'>✅ Products rating index created</div>";
    }
    
    echo "<hr>";
    echo "<div class='alert alert-success'>";
    echo "<h4>🎉 Migration 001 Completed Successfully!</h4>";
    echo "<p><strong>Changes made:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Created reviews table with proper relationships</li>";
    echo "<li>✅ Added rating column to products table</li>";
    echo "<li>✅ Added reviews_count column to products table</li>";
    echo "<li>✅ Created performance indexes</li>";
    echo "</ul>";
    echo "<p class='mt-3'>";
    echo "<a href='../dashboard.php' class='btn btn-primary'>Go to Admin Dashboard</a>";
    echo "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error during migration:</h4>";
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