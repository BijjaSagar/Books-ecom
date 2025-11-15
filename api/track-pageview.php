<?php
/**
 * Track Page View Endpoint
 * Records page view for analytics
 *
 * POST /api/track-pageview.php
 * JSON: {
 *     "page_url": "/shop",
 *     "page_title": "Shop",
 *     "referrer": "google.com"
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/AnalyticsManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $page_url = $data['page_url'] ?? null;
    $page_title = $data['page_title'] ?? null;
    $referrer = $data['referrer'] ?? null;
    $customer_id = $_SESSION['user_id'] ?? null;

    if (!$page_url) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'page_url required']));
    }

    $analytics = new AnalyticsManager($conn);
    $result = $analytics->trackPageView($page_url, $page_title, $customer_id, $referrer);

    if ($result) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Page view tracked']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to track page view']);
    }

} catch (Exception $e) {
    error_log("[Track Pageview API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Tracking failed']);
}
?>
