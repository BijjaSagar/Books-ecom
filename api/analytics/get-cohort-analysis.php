<?php
/**
 * Get Cohort Analysis API
 * GET /api/analytics/get-cohort-analysis.php?months=12
 *
 * Returns cohort retention and analysis data
 */

session_start();
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
    $months = (int)($_GET['months'] ?? 12);
    if ($months < 1 || $months > 60) {
        $months = 12;
    }

    $start_month = date('Y-m-d', strtotime("-$months months"));
    $analytics = new AnalyticsManager($conn);

    // Get cohort analysis
    $cohorts = $analytics->getCohortAnalysis($start_month);

    // Group by cohort for better presentation
    $cohort_data = [];
    foreach ($cohorts as $row) {
        if (!isset($cohort_data[$row['cohort_name']])) {
            $cohort_data[$row['cohort_name']] = [];
        }
        $cohort_data[$row['cohort_name']][] = $row;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'cohort_data' => $cohort_data,
        'time_period' => [
            'months' => $months,
            'start_date' => $start_month,
            'end_date' => date('Y-m-d')
        ]
    ]);

} catch (Exception $e) {
    error_log("[Cohort Analysis API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch cohort analysis']);
}
?>
