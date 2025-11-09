<?php
// ajax/get_wishlist_count.php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'count' => (int)$row['count']
    ]);
    
} catch (Exception $e) {
    error_log("Wishlist count error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get wishlist count']);
}
?>