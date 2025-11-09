<?php
/**
 * admin/ajax/get_campaign_metrics.php - Real-time campaign metrics AJAX endpoint
 */

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once '../../includes/db_connect.php';
require_once '../../includes/AdvertisingManager.php';

$adManager = new AdvertisingManager($conn);

try {
    $performance = $adManager->getCampaignPerformance();
    $roiAnalysis = $adManager->getRoiAnalysis();

    echo json_encode([
        'success' => true,
        'performance' => $performance,
        'roi_analysis' => $roiAnalysis,
        'campaign_count' => $performance['total_campaigns'] ?? 0,
        'last_updated' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
