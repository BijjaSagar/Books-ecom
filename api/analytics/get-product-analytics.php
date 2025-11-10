<?php
/**
 * Get Product Analytics API
 * GET /api/analytics/get-product-analytics.php?product_id=123&days=30
 *
 * Returns detailed product performance analytics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/AnalyticsManager.php';

// Check admin auth
if (($_SESSION['user_id'] ?? null) === null || ($_SESSION['is_admin'] ?? false) === false) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $days = (int)($_GET['days'] ?? 30);

    if ($days < 1 || $days > 365) {
        $days = 30;
    }

    $analytics = new AnalyticsManager($conn);

    // Get product analytics
    $products = $analytics->getProductAnalytics($product_id, $days);

    if ($product_id) {
        $product_info = $conn->query("SELECT id, title FROM products WHERE id = $product_id")->fetch_assoc();
        $response = [
            'success' => true,
            'product' => [
                'id' => (int)$product_info['id'],
                'title' => $product_info['title']
            ],
            'analytics' => array_map(function($a) {
                return [
                    'date' => $a['analysis_date'],
                    'views' => (int)$a['views'],
                    'add_to_cart' => (int)$a['add_to_cart'],
                    'purchases' => (int)$a['purchases'],
                    'revenue' => (float)$a['revenue'],
                    'returns' => (int)$a['returns'],
                    'conversion_rate' => (float)$a['conversion_rate'],
                    'view_to_cart_rate' => (float)$a['view_to_cart_rate']
                ];
            }, $products)
        ];
    } else {
        $response = [
            'success' => true,
            'products' => array_map(function($p) {
                return [
                    'product_id' => (int)$p['id'],
                    'title' => $p['title'],
                    'total_views' => (int)$p['total_views'],
                    'total_purchases' => (int)$p['total_purchases'],
                    'total_revenue' => (float)$p['total_revenue'],
                    'avg_conversion_rate' => (float)$p['avg_conversion'],
                    'avg_view_to_cart_rate' => (float)$p['avg_view_to_cart']
                ];
            }, $products)
        ];
    }

    $response['time_period'] = [
        'days' => $days,
        'start_date' => date('Y-m-d', strtotime("-$days days")),
        'end_date' => date('Y-m-d')
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("[Product Analytics API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch product analytics']);
}
?>
