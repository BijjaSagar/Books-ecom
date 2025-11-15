<?php
/**
 * Analytics Dashboard Data Endpoint
 * Returns all analytics data for admin dashboard
 *
 * GET /api/analytics-dashboard.php?start_date=2024-11-01&end_date=2024-11-30
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/AnalyticsManager.php';

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
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    // Validate dates
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid date format']));
    }

    $analytics = new AnalyticsManager($conn);

    // Get revenue summary
    $revenue = $analytics->getRevenueSummary($start_date, $end_date);

    // Get daily metrics
    $daily_metrics = $analytics->getDailyMetrics($start_date, $end_date);

    // Get top products
    $top_products = $analytics->getTopProducts(10, $start_date, $end_date);

    // Get customer metrics
    $customer_metrics = $analytics->getCustomerMetrics($start_date, $end_date);

    // Get conversion funnel
    $conversion_funnel = $analytics->getConversionFunnel($start_date, $end_date);

    // Get cart abandonment
    $abandonment = $analytics->getCartAbandonmentRate($start_date, $end_date);

    // Get traffic sources
    $traffic = $analytics->getTrafficSources($start_date, $end_date);

    // Get search queries
    $searches = $analytics->getPopularSearches(10, $start_date, $end_date);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'date_range' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'revenue_summary' => $revenue,
        'daily_metrics' => $daily_metrics,
        'top_products' => $top_products,
        'customer_metrics' => $customer_metrics,
        'conversion_funnel' => $conversion_funnel,
        'cart_abandonment' => $abandonment,
        'traffic_sources' => $traffic,
        'popular_searches' => $searches
    ]);

} catch (Exception $e) {
    error_log("[Analytics Dashboard API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load analytics']);
}
?>
