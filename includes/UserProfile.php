<?php
/**
 * User Profile Management System
 * Handles user profile updates and management
 */

class UserProfile {
    private $conn;
    private $settings;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->settings = get_site_settings();
    }
    
    /**
     * Get user profile data
     * 
     * @param int $user_id User ID
     * @return array User profile data
     */
    public function getUserProfile($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, full_name, email, phone, address, city, state, zip_code, country, created_at, updated_at FROM users WHERE id = ?");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result) {
                $user = $result->fetchArray(SQLITE3_ASSOC);
                $stmt->close();
                return $user;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Get user profile error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user profile
     * 
     * @param int $user_id User ID
     * @param array $profile_data Profile data to update
     * @return array Update result
     */
    public function updateProfile($user_id, $profile_data) {
        try {
            // Build dynamic update query
            $fields = [];
            $values = [];
            $types = '';
            
            // Allowed fields for update
            $allowed_fields = ['full_name', 'phone', 'address', 'city', 'state', 'zip_code', 'country'];
            
            foreach ($allowed_fields as $field) {
                if (isset($profile_data[$field]) && $profile_data[$field] !== null) {
                    $fields[] = "{$field} = ?";
                    $values[] = $profile_data[$field];
                    $types .= 's'; // All fields are strings in SQLite
                }
            }
            
            // Only proceed if there are fields to update
            if (empty($fields)) {
                return [
                    'success' => false,
                    'error' => 'No valid fields to update.'
                ];
            }
            
            // Add user_id to values for WHERE clause
            $values[] = $user_id;
            $types .= 'i';
            
            // Build query
            $query = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = datetime('now') WHERE id = ?";
            
            // Prepare and execute
            $stmt = $this->conn->prepare($query);
            for ($i = 0; $i < count($values); $i++) {
                $stmt->bindValue($i + 1, $values[$i], ($i === count($values) - 1) ? SQLITE3_INTEGER : SQLITE3_TEXT);
            }
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to update profile. Please try again.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Update user password
     * 
     * @param int $user_id User ID
     * @param string $current_password Current password
     * @param string $new_password New password
     * @return array Update result
     */
    public function updatePassword($user_id, $current_password, $new_password) {
        try {
            // Get current password hash
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'User not found.'
                ];
            }
            
            $user = $result->fetchArray(SQLITE3_ASSOC);
            $stmt->close();
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                return [
                    'success' => false,
                    'error' => 'Current password is incorrect.'
                ];
            }
            
            // Validate new password
            if (strlen($new_password) < 8) {
                return [
                    'success' => false,
                    'error' => 'New password must be at least 8 characters long.'
                ];
            }
            
            // Hash new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_stmt = $this->conn->prepare("UPDATE users SET password = ?, updated_at = datetime('now') WHERE id = ?");
            $update_stmt->bindValue(1, $new_password_hash, SQLITE3_TEXT);
            $update_stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
            $update_result = $update_stmt->execute();
            $update_stmt->close();
            
            if ($update_result) {
                return [
                    'success' => true,
                    'message' => 'Password updated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to update password. Please try again.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Update password error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Get user's order history
     * 
     * @param int $user_id User ID
     * @param int $limit Number of orders to fetch
     * @return array Order history
     */
    public function getOrderHistory($user_id, $limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.*, COUNT(oi.id) as item_count
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            $orders = [];
            while ($order = $result->fetchArray(SQLITE3_ASSOC)) {
                $orders[] = $order;
            }
            
            $stmt->close();
            return $orders;
            
        } catch (Exception $e) {
            error_log("Get order history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user's wishlist
     * 
     * @param int $user_id User ID
     * @return array Wishlist items
     */
    public function getWishlist($user_id) {
        try {
            // First check if wishlist table exists
            $check_sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='wishlist'";
            $check_result = $this->conn->query($check_sql);
            
            if (!$check_result || !$check_result->fetchArray(SQLITE3_ASSOC)) {
                // Wishlist table doesn't exist, return empty array
                return [];
            }
            
            $stmt = $this->conn->prepare("
                SELECT w.*, p.title, p.price, p.cover_image
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC
            ");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            $wishlist = [];
            while ($item = $result->fetchArray(SQLITE3_ASSOC)) {
                $wishlist[] = $item;
            }
            
            $stmt->close();
            return $wishlist;
            
        } catch (Exception $e) {
            error_log("Get wishlist error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add item to wishlist
     * 
     * @param int $user_id User ID
     * @param int $product_id Product ID
     * @return array Result
     */
    public function addToWishlist($user_id, $product_id) {
        try {
            // Check if item already in wishlist
            $check_stmt = $this->conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $check_stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $check_stmt->bindValue(2, $product_id, SQLITE3_INTEGER);
            $check_result = $check_stmt->execute();
            
            if ($check_result && $check_result->fetchArray(SQLITE3_ASSOC)) {
                $check_stmt->close();
                return [
                    'success' => false,
                    'error' => 'Item already in wishlist.'
                ];
            }
            $check_stmt->close();
            
            // Add to wishlist
            $stmt = $this->conn->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, datetime('now'))");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $product_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Item added to wishlist.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to add item to wishlist.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Add to wishlist error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Remove item from wishlist
     * 
     * @param int $user_id User ID
     * @param int $product_id Product ID
     * @return array Result
     */
    public function removeFromWishlist($user_id, $product_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $product_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Item removed from wishlist.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to remove item from wishlist.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Remove from wishlist error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred. Please try again.'
            ];
        }
    }
}