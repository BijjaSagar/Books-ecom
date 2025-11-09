<?php
/**
 * cron-reports.php - Scheduled email reports (Run via cron job)
 *
 * Add to crontab:
 * 0 6 * * * php /path/to/Books-ecom/cron-reports.php
 * 0 6 * * 1 php /path/to/Books-ecom/cron-reports.php weekly
 */

require_once 'includes/db_connect.php';
require_once 'includes/EmailReportGenerator.php';
require_once 'includes/AdminDashboard.php';
require_once 'includes/FinancialReports.php';

$reportGenerator = new EmailReportGenerator($conn);
$dashboard = new AdminDashboard($conn);
$financial = new FinancialReports($conn);

$reportType = $argv[1] ?? 'daily';

try {
    // Get admin settings
    $result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'admin_email' LIMIT 1");
    $row = $result->fetch_assoc();
    $adminEmail = $row['setting_value'] ?? 'admin@bookory.local';

    // Get reports enabled setting
    $result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'enable_email_reports' LIMIT 1");
    $row = $result->fetch_assoc();
    $enableReports = ($row['setting_value'] ?? 'yes') === 'yes';

    if (!$enableReports) {
        echo "[" . date('Y-m-d H:i:s') . "] Email reports are disabled.\n";
        exit(0);
    }

    // Generate and send reports
    if ($reportType === 'daily') {
        echo "[" . date('Y-m-d H:i:s') . "] Generating daily report...\n";
        $today = date('Y-m-d');

        // Generate daily financial report
        $financial->generateDailyReport($today);

        // Send email report
        if ($reportGenerator->generateDailyReport($adminEmail)) {
            echo "[" . date('Y-m-d H:i:s') . "] Daily report sent successfully to $adminEmail\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] Failed to send daily report\n";
        }
    } elseif ($reportType === 'weekly') {
        echo "[" . date('Y-m-d H:i:s') . "] Generating weekly report...\n";
        $startDate = date('Y-m-d', strtotime('-7 days'));

        if ($reportGenerator->generateWeeklyReport($adminEmail, $startDate)) {
            echo "[" . date('Y-m-d H:i:s') . "] Weekly report sent successfully to $adminEmail\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] Failed to send weekly report\n";
        }
    }

    // Log the execution
    $stmt = $conn->prepare("
        INSERT INTO audit_log (action, details, timestamp)
        VALUES (?, ?, NOW())
    ");
    $action = strtoupper($reportType) . '_REPORT';
    $details = 'Auto-generated ' . $reportType . ' report sent to ' . $adminEmail;
    $stmt->bind_param("ss", $action, $details);
    $stmt->execute();

} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Cron report error: " . $e->getMessage());
    echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Report generation completed.\n";
exit(0);
