<?php
/**
 * Books Bookstore REST API
 * Central API router for all endpoints
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/config.php';
session_start();

// API response helper
function json_response($success, $message = '', $data = null, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Check authentication for protected endpoints
function check_auth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        json_response(false, 'Unauthorized: Please log in', null, 401);
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        json_response(false, 'Forbidden: Insufficient permissions', null, 403);
    }
}

// Parse request
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_parts = array_filter(explode('/', $request_uri));
$endpoint = $request_parts[count($request_parts) - 1] ?? '';
$resource = $request_parts[count($request_parts) - 2] ?? '';

// Get JSON body
$body = json_decode(file_get_contents('php://input'), true);

// Route requests
try {
    switch ($resource) {
        case 'orders':
            handle_orders($request_method, $endpoint, $body);
            break;
        case 'coupons':
            handle_coupons($request_method, $endpoint, $body);
            break;
        case 'reviews':
            handle_reviews($request_method, $endpoint, $body);
            break;
        case 'products':
            handle_products($request_method, $endpoint, $body);
            break;
        case 'customers':
            handle_customers($request_method, $endpoint, $body);
            break;
        case 'exports':
            handle_exports($request_method, $endpoint, $body);
            break;
        default:
            json_response(false, 'Endpoint not found', null, 404);
    }
} catch (Exception $e) {
    json_response(false, 'Server error: ' . $e->getMessage(), null, 500);
}

// Orders API Handler
function handle_orders($method, $endpoint, $body) {
    global $conn;
    check_auth();
    
    require_once '../includes/OrderManager.php';
    $order_manager = new OrderManager($conn);
    
    switch ($method) {
        case 'GET':
            if (is_numeric($endpoint)) {
                // Get specific order
                $order = $order_manager->getOrderSummary($endpoint);
                if ($order) {
                    json_response(true, 'Order retrieved successfully', $order);
                } else {
                    json_response(false, 'Order not found', null, 404);
                }
            } else {
                // Get all orders
                $orders = $order_manager->getDashboardStats();
                json_response(true, 'Orders retrieved successfully', $orders);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'update-status') {
                check_auth('admin');
                $result = $order_manager->updateOrderStatus(
                    $body['order_id'],
                    $body['status'],
                    $body['notes'] ?? ''
                );
                json_response($result['success'], $result['message'], $result['data']);
            }
            break;
            
        default:
            json_response(false, 'Method not allowed', null, 405);
    }
}

// Coupons API Handler
function handle_coupons($method, $endpoint, $body) {
    global $conn;
    check_auth();
    
    require_once '../includes/CouponManager.php';
    $coupon_manager = new CouponManager($conn);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'validate') {
                // Validate coupon
                $code = $_GET['code'] ?? '';
                $order_total = $_GET['order_total'] ?? 0;
                $result = $coupon_manager->validateCoupon($code, $order_total);
                json_response($result['valid'], 
                    $result['message'] ?? '', 
                    ['discount' => $result['discount'] ?? 0]
                );
            } else {
                // Get all coupons
                $coupons = $coupon_manager->getAllCoupons();
                json_response(true, 'Coupons retrieved', $coupons);
            }
            break;
            
        case 'POST':
            check_auth('admin');
            if ($endpoint === 'create') {
                $result = $coupon_manager->createCoupon($body);
                json_response($result['success'], $result['message']);
            }
            break;
            
        default:
            json_response(false, 'Method not allowed', null, 405);
    }
}

// Reviews API Handler
function handle_reviews($method, $endpoint, $body) {
    global $conn;
    
    require_once '../includes/ReviewManager.php';
    $review_manager = new ReviewManager($conn);
    
    switch ($method) {
        case 'GET':
            if (is_numeric($endpoint)) {
                // Get reviews for product
                $reviews = $review_manager->getProductReviews($endpoint);
                $stats = $review_manager->getProductRatingStats($endpoint);
                json_response(true, 'Reviews retrieved', [
                    'reviews' => $reviews,
                    'stats' => $stats
                ]);
            }
            break;
            
        case 'POST':
            check_auth('customer');
            if ($endpoint === 'submit') {
                $result = $review_manager->submitReview(
                    $_SESSION['user_id'],
                    $body['product_id'],
                    $body['rating'],
                    $body['title'],
                    $body['comment']
                );
                json_response($result['success'], $result['message']);
            }
            break;
            
        default:
            json_response(false, 'Method not allowed', null, 405);
    }
}

// Products API Handler
function handle_products($method, $endpoint, $body) {
    global $conn;
    
    switch ($method) {
        case 'GET':
            if (is_numeric($endpoint)) {
                // Get specific product
                $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->bind_param('i', $endpoint);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($product) {
                    json_response(true, 'Product retrieved', $product);
                } else {
                    json_response(false, 'Product not found', null, 404);
                }
            } else {
                // Get all products with filtering
                $limit = $_GET['limit'] ?? 20;
                $offset = $_GET['offset'] ?? 0;
                $category = $_GET['category'] ?? '';
                $search = $_GET['search'] ?? '';
                
                $query = "SELECT * FROM products WHERE 1=1";
                $types = '';
                $params = [];
                
                if ($category) {
                    $query .= " AND category_id = ?";
                    $types .= 'i';
                    $params[] = $category;
                }
                
                if ($search) {
                    $search_term = '%' . $search . '%';
                    $query .= " AND (title LIKE ? OR author LIKE ?)";
                    $types .= 'ss';
                    $params[] = $search_term;
                    $params[] = $search_term;
                }
                
                $query .= " LIMIT ? OFFSET ?";
                $types .= 'ii';
                $params[] = $limit;
                $params[] = $offset;
                
                $stmt = $conn->prepare($query);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                json_response(true, 'Products retrieved', $products);
            }
            break;
            
        default:
            json_response(false, 'Method not allowed', null, 405);
    }
}

// Customers API Handler
function handle_customers($method, $endpoint, $body) {
    global $conn;
    check_auth();
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'profile') {
                // Get current user profile
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT id, name, email, phone, address, city, state, pincode, created_at FROM users WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $profile = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                json_response(true, 'Profile retrieved', $profile);
            }
            break;
            
        case 'PUT':
            if ($endpoint === 'profile') {
                check_auth('customer');
                $user_id = $_SESSION['user_id'];
                
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, city=?, state=?, pincode=? WHERE id=?");
                $stmt->bind_param('sssssssi', 
                    $body['name'], 
                    $body['email'], 
                    $body['phone'], 
                    $body['address'], 
                    $body['city'], 
                    $body['state'], 
                    $body['pincode'], 
                    $user_id
                );
                
                if ($stmt->execute()) {
                    json_response(true, 'Profile updated successfully');
                } else {
                    json_response(false, 'Failed to update profile', null, 500);
                }
                $stmt->close();
            }
            break;
            
        default:
            json_response(false, 'Method not allowed', null, 405);
    }
}

// Exports API Handler
function handle_exports($method, $endpoint, $body) {
    global $conn;
    check_auth('admin');
    
    require_once '../includes/ExportManager.php';
    $export_manager = new ExportManager($conn);
    
    if ($method === 'GET') {
        $type = $_GET['type'] ?? '';
        $format = $_GET['format'] ?? 'csv';
        
        try {
            switch ($type) {
                case 'orders':
                    $content = $export_manager->exportOrdersCSV($_GET);
                    break;
                case 'products':
                    $content = $export_manager->exportProductsCSV();
                    break;
                case 'customers':
                    $content = $export_manager->exportCustomersCSV();
                    break;
                case 'sales':
                    $start = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                    $end = $_GET['end_date'] ?? date('Y-m-d');
                    $content = $export_manager->exportSalesReportCSV($start, $end);
                    break;
                default:
                    json_response(false, 'Invalid export type', null, 400);
            }
            
            json_response(true, 'Export generated', ['content' => $content]);
        } catch (Exception $e) {
            json_response(false, 'Export failed: ' . $e->getMessage(), null, 500);
        }
    }
}
?>
