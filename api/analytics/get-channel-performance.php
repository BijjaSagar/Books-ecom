<?php
/**
 * Get Channel Performance API
 * GET /api/analytics/get-channel-performance.php?days=30
 *
 * Returns traffic channel performance analysis
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
    $days = (int)($_GET['days'] ?? 30);

    if ($days < 1 || $days > 365) {
        $days = 30;
    }

    $analytics = new AnalyticsManager($conn);

    // Get channel performance
    $channels = $analytics->getChannelPerformance($days);

    // Calculate totals
    $totals = [
        'total_sessions' => 0,
        'total_users' => 0,
        'total_transactions' => 0,
        'total_revenue' => 0,
        'total_cost' => 0
    ];

    foreach ($channels as $channel) {
        $totals['total_sessions'] += $channel['total_sessions'];
        $totals['total_users'] += $channel['total_users'];
        $totals['total_transactions'] += $channel['total_transactions'];
        $totals['total_revenue'] += $channel['total_revenue'];
        $totals['total_cost'] += $channel['total_cost'];
    }

    $channel_data = array_map(function($c) use ($totals) {
        return [
            'channel' => $c['channel_name'],
            'sessions' => (int)$c['total_sessions'],
            'users' => (int)$c['total_users'],
            'transactions' => (int)$c['total_transactions'],
            'revenue' => (float)$c['total_revenue'],
            'cost' => (float)$c['total_cost'],
            'bounce_rate' => (float)$c['avg_bounce_rate'],
            'conversion_rate' => (float)$c['avg_conversion_rate'],
            'roi' => (float)($c['roi'] ?? 0),
            'revenue_percentage' => $totals['total_revenue'] > 0 ? ($c['total_revenue'] / $totals['total_revenue'] * 100) : 0
        ];
    }, $channels);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'channels' => $channel_data,
        'totals' => [
            'sessions' => (int)$totals['total_sessions'],
            'users' => (int)$totals['total_users'],
            'transactions' => (int)$totals['total_transactions'],
            'revenue' => (float)$totals['total_revenue'],
            'cost' => (float)$totals['total_cost'],
            'roi' => $totals['total_cost'] > 0 ? ($totals['total_revenue'] - $totals['total_cost']) / $totals['total_cost'] * 100 : 0
        ],
        'time_period' => [
            'days' => $days,
            'start_date' => date('Y-m-d', strtotime("-$days days")),
            'end_date' => date('Y-m-d')
        ]
    ]);

} catch (Exception $e) {
    error_log("[Channel Performance API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch channel performance']);
}
?>
