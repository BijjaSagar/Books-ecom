<?php
/**
 * admin/run-migrations.php - Run database migrations for admin dashboard features
 */

// Only allow admin to run migrations
if (php_sapi_name() !== 'cli' && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    die('Unauthorized');
}

require_once '../includes/db_connect.php';

$migrationFile = __DIR__ . '/migrations/002_add_admin_dashboard_features.sql';

if (!file_exists($migrationFile)) {
    die('Migration file not found: ' . $migrationFile);
}

echo "Running migration: 002_add_admin_dashboard_features.sql\n";

$sql = file_get_contents($migrationFile);
$statements = explode(';', $sql);

$count = 0;
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;

    try {
        $conn->query($statement);
        $count++;
        echo "✓ Executed statement $count\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\nMigration completed! $count statements executed.\n";
echo "New tables created:\n";
echo "- sales_analytics\n";
echo "- product_analytics\n";
echo "- inventory_alerts\n";
echo "- inventory_transactions\n";
echo "- advertising_campaigns\n";
echo "- campaign_keywords\n";
echo "- financial_reports\n";
echo "- category_performance\n";
echo "- account_health\n";
echo "- bulk_uploads\n";
echo "- listing_quality\n";
echo "- seller_payouts\n";
echo "\nYou can now use the new admin dashboard features!\n";
