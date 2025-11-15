<?php
/**
 * Wishlist Manager
 * Manage customer wishlists and saved items
 */

class WishlistManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->initializeTables();
    }
    
    /**
     * Initialize wishlist tables if they don't exist
     */
    private function initializeTables() {
        $create_wishlist = "
            CREATE TABLE IF NOT EXISTS wishlists (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_wishlist (user_id, product_id),
                INDEX idx_user (user_id),
                INDEX idx_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $this->conn->query($create_wishlist);
    }
    
    /**
     * Add product to wishlist
     *
     * @param int $user_id Customer ID
     * @param int $product_id Product ID
     * @return array Result array with success status and message
     */
    public function addToWishlist($user_id, $product_id) {
        // Check if product exists
        $product_check = $this->conn->prepare("SELECT id FROM products WHERE id = ?");
        $product_check->bind_param('i', $product_id);
        $product_check->execute();
        if ($product_check->get_result()->num_rows === 0) {
            $product_check->close();
            return [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        $product_check->close();
        
        // Check if already in wishlist
        $exists = $this->conn->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
        $exists->bind_param('ii', $user_id, $product_id);
        $exists->execute();
        if ($exists->get_result()->num_rows > 0) {
            $exists->close();
            return [
                'success' => false,
                'message' => 'Product already in wishlist'
            ];
        }
        $exists->close();
        
        // Add to wishlist
        $insert = $this->conn->prepare("
            INSERT INTO wishlists (user_id, product_id, added_at)
            VALUES (?, ?, NOW())
        ");
        $insert->bind_param('ii', $user_id, $product_id);
        
        if ($insert->execute()) {
            $insert->close();
            return [
                'success' => true,
                'message' => 'Added to wishlist'
            ];
        } else {
            $insert->close();
            return [
                'success' => false,
                'message' => 'Error adding to wishlist'
            ];
        }
    }
    
    /**
     * Remove product from wishlist
     *
     * @param int $user_id Customer ID
     * @param int $product_id Product ID
     * @return array Result array
     */
    public function removeFromWishlist($user_id, $product_id) {
        $delete = $this->conn->prepare("
            DELETE FROM wishlists 
            WHERE user_id = ? AND product_id = ?
        ");
        $delete->bind_param('ii', $user_id, $product_id);
        
        if ($delete->execute()) {
            $delete->close();
            return [
                'success' => true,
                'message' => 'Removed from wishlist'
            ];
        } else {
            $delete->close();
            return [
                'success' => false,
                'message' => 'Error removing from wishlist'
            ];
        }
    }
    
    /**
     * Get customer's wishlist
     *
     * @param int $user_id Customer ID
     * @return array Array of wishlist products
     */
    public function getWishlist($user_id) {
        $query = "
            SELECT 
                p.id, p.title, p.author, p.price, p.rating, p.review_count,
                p.stock_quantity, c.name as category, w.added_at
            FROM wishlists w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = ?
            ORDER BY w.added_at DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $wishlist = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $wishlist;
    }
    
    /**
     * Check if product is in customer's wishlist
     *
     * @param int $user_id Customer ID
     * @param int $product_id Product ID
     * @return bool True if in wishlist, false otherwise
     */
    public function isInWishlist($user_id, $product_id) {
        $check = $this->conn->prepare("
            SELECT id FROM wishlists 
            WHERE user_id = ? AND product_id = ?
        ");
        $check->bind_param('ii', $user_id, $product_id);
        $check->execute();
        $result = $check->get_result()->num_rows > 0;
        $check->close();
        
        return $result;
    }
    
    /**
     * Get wishlist count for customer
     *
     * @param int $user_id Customer ID
     * @return int Number of items in wishlist
     */
    public function getWishlistCount($user_id) {
        $count = $this->conn->prepare("
            SELECT COUNT(*) as count FROM wishlists 
            WHERE user_id = ?
        ");
        $count->bind_param('i', $user_id);
        $count->execute();
        $result = $count->get_result()->fetch_assoc();
        $count->close();
        
        return intval($result['count'] ?? 0);
    }
    
    /**
     * Clear entire wishlist
     *
     * @param int $user_id Customer ID
     * @return array Result array
     */
    public function clearWishlist($user_id) {
        $delete = $this->conn->prepare("DELETE FROM wishlists WHERE user_id = ?");
        $delete->bind_param('i', $user_id);
        
        if ($delete->execute()) {
            $delete->close();
            return [
                'success' => true,
                'message' => 'Wishlist cleared'
            ];
        } else {
            $delete->close();
            return [
                'success' => false,
                'message' => 'Error clearing wishlist'
            ];
        }
    }
    
    /**
     * Get popular wishlist items (trending)
     *
     * @param int $limit Number of items to return
     * @return array Array of trending products
     */
    public function getTrendingWishlistItems($limit = 10) {
        $query = "
            SELECT 
                p.id, p.title, p.author, p.price, p.rating,
                COUNT(w.id) as wishlist_count
            FROM wishlists w
            JOIN products p ON w.product_id = p.id
            GROUP BY p.id
            ORDER BY wishlist_count DESC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $items;
    }
}
?>
