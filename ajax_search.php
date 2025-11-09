<?php
// ajax_search.php

// This file only responds to AJAX requests.
// It connects to the DB, searches, and returns JSON.

require_once 'includes/db_connect.php';

$results = [];
// Check if a search term was sent
if (isset($_GET['term']) && !empty(trim($_GET['term']))) {
    
    $searchTerm = trim($_GET['term']);
    
    // Prepare a search term for a LIKE query
    $likeTerm = '%' . $searchTerm . '%';

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, title, author, price, cover_image FROM products WHERE title LIKE ? OR author LIKE ? LIMIT 5");
    // "ss" means we are binding two string parameters
    $stmt->bind_param("ss", $likeTerm, $likeTerm);
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Build the URL for the product image
            $image_path = "/bookshelf/public/images/products/" . htmlspecialchars($row['cover_image']);
            $row['image_url'] = (empty($row['cover_image']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) ? "https://placehold.co/50x75" : $image_path;
            
            // Build the URL for the product details page
            $row['product_url'] = "/bookshelf/product-details.php?id=" . $row['id'];

            $results[] = $row;
        }
    }
    $stmt->close();
}

// Set the content type header to JSON
header('Content-Type: application/json');

// Echo the results as a JSON encoded string
echo json_encode($results);
?>