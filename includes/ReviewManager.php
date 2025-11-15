<?php
/**
 * Product Reviews and Ratings Management System
 * Handles customer reviews, ratings, and review moderation
 */

class ReviewManager {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->initializeTable();
    }

    /**
     * Initialize reviews table if it doesn't exist
     */
    private function initializeTable() {
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS product_reviews (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                customer_id INT NOT NULL,
                order_id INT,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                title VARCHAR(150),
                review_text LONGTEXT,
                helpful_count INT DEFAULT 0,
                unhelpful_count INT DEFAULT 0,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                verified_purchase BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX (product_id),
                INDEX (status)
            )"
        );

        // Create review moderation table
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS review_moderation_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                review_id INT NOT NULL,
                action VARCHAR(50),
                admin_id INT,
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE
            )"
        );
    }

    /**
     * Submit a product review
     * @param array $data Review details
     * @return array Result
     */
    public function submitReview($data) {
        // Validate required fields
        if (empty($data['product_id']) || empty($data['customer_id']) || empty($data['rating'])) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }

        // Validate rating
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
        }

        // Check if customer already reviewed this product
        $check = $this->conn->prepare(
            "SELECT id FROM product_reviews WHERE product_id = ? AND customer_id = ?"
        );
        $check->bind_param('ii', $data['product_id'], $data['customer_id']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'You have already reviewed this product'];
        }
        $check->close();

        // Check if it's a verified purchase
        $verified = false;
        if (!empty($data['order_id'])) {
            $order_check = $this->conn->prepare(
                "SELECT COUNT(*) as count FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.id = ? AND oi.product_id = ? AND o.user_id = ?"
            );
            $order_check->bind_param('iii', $data['order_id'], $data['product_id'], $data['customer_id']);
            $order_check->execute();
            $verified = $order_check->get_result()->fetch_assoc()['count'] > 0;
            $order_check->close();
        }

        // Insert review
        $stmt = $this->conn->prepare(
            "INSERT INTO product_reviews (product_id, customer_id, order_id, rating, title, review_text, verified_purchase)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'iiisssb',
            $data['product_id'],
            $data['customer_id'],
            $data['order_id'] ?? null,
            $data['rating'],
            $data['title'],
            $data['review_text'],
            $verified
        );

        if ($stmt->execute()) {
            $review_id = $this->conn->insert_id;
            $stmt->close();

            // Update product rating
            $this->updateProductRating($data['product_id']);

            return [
                'success' => true,
                'message' => 'Review submitted successfully. It will be visible after moderation.',
                'review_id' => $review_id
            ];
        }

        $stmt->close();
        return ['success' => false, 'message' => 'Error submitting review'];
    }

    /**
     * Get product reviews (approved only for customers)
     * @param int $product_id
     * @param int $limit
     * @param int $offset
     * @return array Reviews with customer details
     */
    public function getProductReviews($product_id, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare(
            "SELECT pr.*, u.name as customer_name, u.email
             FROM product_reviews pr
             JOIN users u ON pr.customer_id = u.id
             WHERE pr.product_id = ? AND pr.status = 'approved'
             ORDER BY pr.created_at DESC
             LIMIT ? OFFSET ?"
        );

        $stmt->bind_param('iii', $product_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];

        while ($review = $result->fetch_assoc()) {
            $reviews[] = $review;
        }
        $stmt->close();

        return $reviews;
    }

    /**
     * Get product rating statistics
     * @param int $product_id
     * @return array Rating stats
     */
    public function getProductRatingStats($product_id) {
        $stats = [];

        // Overall rating
        $avg = $this->conn->prepare(
            "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews
             FROM product_reviews
             WHERE product_id = ? AND status = 'approved'"
        );
        $avg->bind_param('i', $product_id);
        $avg->execute();
        $avg_result = $avg->get_result()->fetch_assoc();
        $stats['average_rating'] = round($avg_result['average_rating'] ?? 0, 1);
        $stats['total_reviews'] = $avg_result['total_reviews'] ?? 0;
        $avg->close();

        // Rating distribution
        $distribution = $this->conn->prepare(
            "SELECT rating, COUNT(*) as count
             FROM product_reviews
             WHERE product_id = ? AND status = 'approved'
             GROUP BY rating
             ORDER BY rating DESC"
        );
        $distribution->bind_param('i', $product_id);
        $distribution->execute();
        $dist_result = $distribution->get_result();
        $stats['distribution'] = [];

        for ($i = 5; $i >= 1; $i--) {
            $stats['distribution'][$i] = 0;
        }

        while ($row = $dist_result->fetch_assoc()) {
            $stats['distribution'][$row['rating']] = $row['count'];
        }
        $distribution->close();

        return $stats;
    }

    /**
     * Update product rating in products table
     * @param int $product_id
     */
    private function updateProductRating($product_id) {
        $rating_stmt = $this->conn->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
             FROM product_reviews
             WHERE product_id = ? AND status = 'approved'"
        );
        $rating_stmt->bind_param('i', $product_id);
        $rating_stmt->execute();
        $rating_data = $rating_stmt->get_result()->fetch_assoc();
        $rating_stmt->close();

        $avg_rating = round($rating_data['avg_rating'] ?? 0, 2);
        $review_count = $rating_data['review_count'] ?? 0;

        // Update or insert product rating
        $update = $this->conn->prepare(
            "UPDATE products SET rating = ?, review_count = ? WHERE id = ?"
        );
        $update->bind_param('dii', $avg_rating, $review_count, $product_id);
        $update->execute();
        $update->close();
    }

    /**
     * Get pending reviews for moderation
     * @return array Pending reviews
     */
    public function getPendingReviews() {
        $stmt = $this->conn->query(
            "SELECT pr.*, u.name as customer_name, p.title as product_name
             FROM product_reviews pr
             JOIN users u ON pr.customer_id = u.id
             JOIN products p ON pr.product_id = p.id
             WHERE pr.status = 'pending'
             ORDER BY pr.created_at ASC"
        );

        $reviews = [];
        while ($review = $stmt->fetch_assoc()) {
            $reviews[] = $review;
        }

        return $reviews;
    }

    /**
     * Approve a review
     * @param int $review_id
     * @param int $admin_id
     * @return array Result
     */
    public function approveReview($review_id, $admin_id) {
        $this->conn->begin_transaction();

        try {
            // Update review status
            $update = $this->conn->prepare(
                "UPDATE product_reviews SET status = 'approved' WHERE id = ?"
            );
            $update->bind_param('i', $review_id);
            $update->execute();
            $update->close();

            // Log moderation
            $log = $this->conn->prepare(
                "INSERT INTO review_moderation_log (review_id, action, admin_id)
                 VALUES (?, 'approved', ?)"
            );
            $log->bind_param('ii', $review_id, $admin_id);
            $log->execute();
            $log->close();

            // Get review to update product rating
            $get = $this->conn->prepare("SELECT product_id FROM product_reviews WHERE id = ?");
            $get->bind_param('i', $review_id);
            $get->execute();
            $product_id = $get->get_result()->fetch_assoc()['product_id'];
            $get->close();

            // Update product rating
            $this->updateProductRating($product_id);

            $this->conn->commit();
            return ['success' => true, 'message' => 'Review approved'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error approving review'];
        }
    }

    /**
     * Reject a review
     * @param int $review_id
     * @param int $admin_id
     * @param string $reason
     * @return array Result
     */
    public function rejectReview($review_id, $admin_id, $reason = '') {
        $this->conn->begin_transaction();

        try {
            // Update review status
            $update = $this->conn->prepare(
                "UPDATE product_reviews SET status = 'rejected' WHERE id = ?"
            );
            $update->bind_param('i', $review_id);
            $update->execute();
            $update->close();

            // Log moderation
            $log = $this->conn->prepare(
                "INSERT INTO review_moderation_log (review_id, action, admin_id, reason)
                 VALUES (?, 'rejected', ?, ?)"
            );
            $log->bind_param('iis', $review_id, $admin_id, $reason);
            $log->execute();
            $log->close();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Review rejected'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error rejecting review'];
        }
    }

    /**
     * Get reviews analytics
     * @return array Analytics
     */
    public function getAnalytics() {
        $analytics = [];

        // Total reviews
        $total = $this->conn->query("SELECT COUNT(*) as count FROM product_reviews");
        $analytics['total_reviews'] = $total->fetch_assoc()['count'];

        // Pending reviews
        $pending = $this->conn->query("SELECT COUNT(*) as count FROM product_reviews WHERE status = 'pending'");
        $analytics['pending_reviews'] = $pending->fetch_assoc()['count'];

        // Approved reviews
        $approved = $this->conn->query("SELECT COUNT(*) as count FROM product_reviews WHERE status = 'approved'");
        $analytics['approved_reviews'] = $approved->fetch_assoc()['count'];

        // Average rating
        $avg = $this->conn->query("SELECT AVG(rating) as avg FROM product_reviews WHERE status = 'approved'");
        $analytics['average_rating'] = round($avg->fetch_assoc()['avg'] ?? 0, 2);

        // Verified purchases count
        $verified = $this->conn->query("SELECT COUNT(*) as count FROM product_reviews WHERE verified_purchase = TRUE");
        $analytics['verified_reviews'] = $verified->fetch_assoc()['count'];

        return $analytics;
    }
}
?>
