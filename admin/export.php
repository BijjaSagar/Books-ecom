<?php
/**
 * admin/export.php - Export reports to CSV and PDF
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/ReportExporter.php';
require_once '../includes/AdminDashboard.php';
require_once '../includes/FinancialReports.php';
require_once '../includes/InventoryManager.php';
require_once '../includes/AdvertisingManager.php';

$exporter = new ReportExporter($conn);
$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

try {
    if ($type === 'sales') {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        if ($format === 'csv') {
            $exporter->exportSalesCSV($startDate, $endDate);
        } else {
            $dashboard = new AdminDashboard($conn);
            $data = $dashboard->getDailySalesData(30);
            $exporter->exportPDF('sales_report', $data);
        }
    } elseif ($type === 'financial') {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        if ($format === 'csv') {
            $exporter->exportFinancialCSV($startDate, $endDate);
        } else {
            $financial = new FinancialReports($conn);
            $data = $financial->getReportRange($startDate, $endDate);
            $exporter->exportPDF('financial_report', $data);
        }
    } elseif ($type === 'inventory') {
        $filter = $_GET['filter'] ?? '';

        if ($format === 'csv') {
            $exporter->exportInventoryCSV($filter);
        } else {
            $inventory = new InventoryManager($conn);
            $data = $inventory->getInventoryList(500, 0, $filter);
            $exporter->exportPDF('inventory_report', $data);
        }
    } elseif ($type === 'campaigns') {
        if ($format === 'csv') {
            $exporter->exportCampaignCSV();
        } else {
            $adManager = new AdvertisingManager($conn);
            $data = $adManager->getCampaigns(null, 500);
            $exporter->exportPDF('campaign_report', $data);
        }
    } else {
        die('Invalid report type');
    }
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('HTTP/1.1 400 Bad Request');
    die('Export failed: ' . $e->getMessage());
}
