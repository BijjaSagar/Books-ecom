<?php
// ajax/search_suggestions.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

try {
    $suggestions = [];
    
    // Search products
    $stmt = $conn->prepare("SELECT id, title, author FROM products WHERE status = 'active' AND (title LIKE ? OR author LIKE ?) ORDER BY sales_count DESC LIMIT 5");
    $search_term = "%$query%";
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'title' => $row['title'],
            'description' => 'by ' . $row['author'],
            'url' => "/bookshelf/product-details.php?id=" . $row['id'],
            'icon' => 'bi bi-book'
        ];
    }
    
    // Search categories
    $stmt = $conn->prepare("SELECT id, name, description FROM categories WHERE status = 'active' AND name LIKE ? LIMIT 3");
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'title' => $row['name'],
            'description' => 'Category - ' . ($row['description'] ?: 'Browse books in this category'),
            'url' => "/bookshelf/shop.php?category=" . $row['id'],
            'icon' => 'bi bi-grid'
        ];
    }
    
    // Add search all option
    if (!empty($suggestions)) {
        $suggestions[] = [
            'title' => "Search for \"$query\"",
            'description' => 'View all search results',
            'url' => "/bookshelf/shop.php?search=" . urlencode($query),
            'icon' => 'bi bi-search'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions
    ]);
    
} catch (Exception $e) {
    error_log("Search suggestions error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search failed']);
}
?>