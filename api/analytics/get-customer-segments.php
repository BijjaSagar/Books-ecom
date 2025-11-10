<?php
/**
 * Get Customer Segments API
 * GET /api/analytics/get-customer-segments.php?segment=high_value&limit=50
 *
 * Returns customer segmentation and individual segments
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
    $segment = $_GET['segment'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);

    if ($limit < 1 || $limit > 500) {
        $limit = 50;
    }

    $analytics = new AnalyticsManager($conn);

    // Get segment summary
    $segment_summary = $analytics->getCustomerSegments();

    $response = [
        'success' => true,
        'segment_summary' => array_map(function($s) {
            return [
                'type' => $s['segment_type'],
                'count' => (int)$s['segment_count'],
                'avg_spent' => (float)$s['avg_spent'],
                'avg_orders' => (float)$s['avg_orders'],
                'avg_engagement' => (float)$s['avg_engagement'],
                'avg_churn_risk' => (float)$s['avg_churn_risk']
            ];
        }, $segment_summary)
    ];

    // If specific segment requested, get customers
    if ($segment) {
        $customers = $analytics->getSegmentCustomers($segment, $limit);
        $response['segment_customers'] = array_map(function($c) {
            return [
                'user_id' => (int)$c['user_id'],
                'name' => $c['name'],
                'email' => $c['email'],
                'total_orders' => (int)$c['total_orders'],
                'total_spent' => (float)$c['total_spent'],
                'churn_risk' => (float)$c['churn_risk'],
                'engagement_score' => (float)$c['engagement_score']
            ];
        }, $customers);
    }

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("[Customer Segments API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch segments']);
}
?>
