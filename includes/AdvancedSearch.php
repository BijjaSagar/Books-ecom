<?php
/**
 * AdvancedSearch.php - Advanced search and filtering across dashboards
 */

class AdvancedSearch {
    private $conn;
    private $filters = [];
    private $searchQuery = '';

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Search products with advanced filters
     */
    public function searchProducts($query = '', $filters = []) {
        try {
            $where = [];
            $params = [];
            $types = '';

            // Search query
            if (!empty($query)) {
                $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.author LIKE ?)";
                $searchTerm = '%' . $query . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= 'sss';
            }

            // Category filter
            if (!empty($filters['category_id'])) {
                $where[] = "p.category_id = ?";
                $params[] = $filters['category_id'];
                $types .= 'i';
            }

            // Price range
            if (!empty($filters['min_price'])) {
                $where[] = "p.price >= ?";
                $params[] = $filters['min_price'];
                $types .= 'd';
            }
            if (!empty($filters['max_price'])) {
                $where[] = "p.price <= ?";
                $params[] = $filters['max_price'];
                $types .= 'd';
            }

            // Stock status
            if (!empty($filters['stock_status'])) {
                if ($filters['stock_status'] === 'in_stock') {
                    $where[] = "p.stock_quantity > 0";
                } elseif ($filters['stock_status'] === 'low_stock') {
                    $where[] = "p.stock_quantity <= 10 AND p.stock_quantity > 0";
                } elseif ($filters['stock_status'] === 'out_of_stock') {
                    $where[] = "p.stock_quantity = 0";
                }
            }

            // Featured only
            if (!empty($filters['featured'])) {
                $where[] = "p.featured = 1";
            }

            // Rating filter
            if (!empty($filters['min_rating'])) {
                $where[] = "p.rating >= ?";
                $params[] = $filters['min_rating'];
                $types .= 'd';
            }

            // Status filter
            if (!empty($filters['status'])) {
                $where[] = "p.status = ?";
                $params[] = $filters['status'];
                $types .= 's';
            }

            // Build query
            $sql = "
                SELECT p.*, c.name as category_name, COUNT(r.id) as review_count
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN reviews r ON p.id = r.product_id
            ";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " GROUP BY p.id";

            // Sorting
            $sort = $filters['sort'] ?? 'name';
            $order = $filters['order'] ?? 'ASC';
            switch ($sort) {
                case 'price_low':
                    $sql .= " ORDER BY p.price ASC";
                    break;
                case 'price_high':
                    $sql .= " ORDER BY p.price DESC";
                    break;
                case 'rating':
                    $sql .= " ORDER BY p.rating DESC";
                    break;
                case 'newest':
                    $sql .= " ORDER BY p.created_at DESC";
                    break;
                case 'bestseller':
                    $sql .= " ORDER BY p.sales_count DESC";
                    break;
                default:
                    $sql .= " ORDER BY p.name ASC";
            }

            // Pagination
            $page = $filters['page'] ?? 1;
            $limit = $filters['limit'] ?? 20;
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search orders with filters
     */
    public function searchOrders($query = '', $filters = []) {
        try {
            $where = [];
            $params = [];
            $types = '';

            // Search query
            if (!empty($query)) {
                $where[] = "(o.order_number LIKE ? OR o.customer_email LIKE ? OR o.customer_name LIKE ?)";
                $searchTerm = '%' . $query . '%';
                $params = [$searchTerm, $searchTerm, $searchTerm];
                $types = 'sss';
            }

            // Date range
            if (!empty($filters['date_from'])) {
                $where[] = "DATE(o.created_at) >= ?";
                $params[] = $filters['date_from'];
                $types .= 's';
            }
            if (!empty($filters['date_to'])) {
                $where[] = "DATE(o.created_at) <= ?";
                $params[] = $filters['date_to'];
                $types .= 's';
            }

            // Order status
            if (!empty($filters['order_status'])) {
                $where[] = "o.order_status = ?";
                $params[] = $filters['order_status'];
                $types .= 's';
            }

            // Payment status
            if (!empty($filters['payment_status'])) {
                $where[] = "o.payment_status = ?";
                $params[] = $filters['payment_status'];
                $types .= 's';
            }

            // Price range
            if (!empty($filters['min_amount'])) {
                $where[] = "o.total_amount >= ?";
                $params[] = $filters['min_amount'];
                $types .= 'd';
            }
            if (!empty($filters['max_amount'])) {
                $where[] = "o.total_amount <= ?";
                $params[] = $filters['max_amount'];
                $types .= 'd';
            }

            // Customer filter
            if (!empty($filters['customer_id'])) {
                $where[] = "o.customer_id = ?";
                $params[] = $filters['customer_id'];
                $types .= 'i';
            }

            $sql = "SELECT * FROM orders";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            // Sorting
            $sort = $filters['sort'] ?? 'created_at';
            $order = $filters['order'] ?? 'DESC';
            $sql .= " ORDER BY " . $sort . " " . $order;

            // Pagination
            $page = $filters['page'] ?? 1;
            $limit = $filters['limit'] ?? 20;
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Order search error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get search suggestions
     */
    public function getSearchSuggestions($query, $type = 'products') {
        try {
            if (strlen($query) < 2) {
                return [];
            }

            $searchTerm = '%' . $query . '%';

            if ($type === 'products') {
                $stmt = $this->conn->prepare("
                    SELECT id, name as label, 'product' as type
                    FROM products
                    WHERE name LIKE ? OR author LIKE ?
                    LIMIT 8
                ");
                $stmt->bind_param("ss", $searchTerm, $searchTerm);
            } elseif ($type === 'orders') {
                $stmt = $this->conn->prepare("
                    SELECT id, order_number as label, 'order' as type
                    FROM orders
                    WHERE order_number LIKE ? OR customer_email LIKE ?
                    LIMIT 8
                ");
                $stmt->bind_param("ss", $searchTerm, $searchTerm);
            } else {
                return [];
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Save search filter
     */
    public function saveSearchFilter($userId, $name, $filters) {
        try {
            $filterJson = json_encode($filters);
            $stmt = $this->conn->prepare("
                INSERT INTO saved_searches (user_id, filter_name, filters)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE filters = VALUES(filters)
            ");

            $stmt->bind_param("iss", $userId, $name, $filterJson);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get saved filters
     */
    public function getSavedFilters($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, filter_name, filters FROM saved_searches
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($results as &$row) {
                $row['filters'] = json_decode($row['filters'], true);
            }

            return $results;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Delete saved filter
     */
    public function deleteSavedFilter($filterId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM saved_searches WHERE id = ?");
            $stmt->bind_param("i", $filterId);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get filter options (for dropdowns)
     */
    public function getFilterOptions($optionType) {
        try {
            if ($optionType === 'categories') {
                $result = $this->conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
                return $result->fetch_all(MYSQLI_ASSOC);
            } elseif ($optionType === 'order_status') {
                return [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'processing', 'label' => 'Processing'],
                    ['value' => 'shipped', 'label' => 'Shipped'],
                    ['value' => 'delivered', 'label' => 'Delivered'],
                    ['value' => 'cancelled', 'label' => 'Cancelled']
                ];
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }
}
