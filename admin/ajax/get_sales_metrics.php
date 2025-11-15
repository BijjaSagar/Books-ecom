<?php
/**
 * admin/ajax/get_sales_metrics.php - Real-time sales metrics AJAX endpoint
 */

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once '../../includes/db_connect.php';
require_once '../../includes/AdminDashboard.php';

$dashboard = new AdminDashboard($conn);

$period = $_GET['period'] ?? 'daily';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

try {
    $metrics = $dashboard->getSalesMetrics($startDate, $endDate);
    $dailySales = $dashboard->getDailySalesData(30);
    $topProducts = $dashboard->getTopProducts(10);

    echo json_encode([
        'success' => true,
        'metrics' => $metrics,
        'daily_sales' => $dailySales,
        'top_products' => $topProducts
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
