<?php
/**
 * admin/setup-migrations.php - Enhanced Database Migration Runner
 *
 * Run this file to set up all required database tables
 * Can be run from CLI: php admin/setup-migrations.php
 */

session_start();

// Check if running from CLI
$isCliMode = php_sapi_name() === 'cli';

// Check authentication (skip for CLI)
if (!$isCliMode && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';

if (!$isCliMode) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Database Migration Setup</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            body { background-color: #f8f9fa; padding: 20px; }
            .container { max-width: 900px; margin-top: 20px; }
            .progress-item { padding: 15px; margin-bottom: 10px; background: white; border-radius: 8px; }
            .success { border-left: 4px solid #28a745; color: #28a745; }
            .error { border-left: 4px solid #dc3545; color: #dc3545; }
            .info { border-left: 4px solid #17a2b8; color: #17a2b8; }
            code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1 class='mb-4'><i class='bi bi-database'></i> Database Migration Setup</h1>";
}

$migrationsDir = __DIR__ . '/migrations/';
$migrations = [
    '001_add_admin_dashboard_features.sql',
    '002_add_admin_dashboard_features.sql',
    '003_add_advanced_search_and_reporting.sql'
];

$totalStatements = 0;
$successStatements = 0;
$failedStatements = 0;
$errors = [];

if (!$isCliMode) {
    echo "<div class='progress-item info'><strong>🔄 Starting Migration Process...</strong></div>";
}

echo $isCliMode ? "[" . date('Y-m-d H:i:s') . "] Starting migrations...\n" : '';

// Process each migration file
foreach ($migrations as $migration) {
    $migrationPath = $migrationsDir . $migration;

    if (!file_exists($migrationPath)) {
        $error = "Migration file not found: " . $migration;
        $errors[] = $error;
        echo $isCliMode
            ? "[" . date('Y-m-d H:i:s') . "] ✗ " . $error . "\n"
            : "<div class='progress-item error'><strong>✗ $migration</strong><br/>File not found</div>";
        continue;
    }

    if (!$isCliMode) {
        echo "<div class='progress-item info'><strong>📄 Processing: $migration</strong></div>";
    }

    echo $isCliMode ? "[" . date('Y-m-d H:i:s') . "] Processing: $migration\n" : '';

    // Read and parse SQL file
    $sqlContent = file_get_contents($migrationPath);
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)), function($s) {
        return !empty($s) && !preg_match('/^--/', trim($s));
    });

    $migrationSuccess = 0;
    $migrationFailed = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;

        $totalStatements++;

        try {
            $conn->query($statement);
            $successStatements++;
            $migrationSuccess++;

            if ($isCliMode) {
                echo "  ✓ Executed statement\n";
            }
        } catch (Exception $e) {
            $failedStatements++;
            $migrationFailed++;
            $error = "Error in $migration: " . $e->getMessage();
            $errors[] = $error;

            if ($isCliMode) {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }

    if (!$isCliMode) {
        echo "<div class='progress-item success'>
            <strong>✓ $migration</strong><br/>
            Executed: <code>$migrationSuccess</code> statements
            " . ($migrationFailed > 0 ? "<br/>Failed: <code>$migrationFailed</code>" : "") . "
        </div>";
    }

    echo $isCliMode ? "  Summary: $migrationSuccess success, $migrationFailed failed\n\n" : '';
}

// Summary
if (!$isCliMode) {
    echo "<div style='margin-top: 30px;'>";
    echo "<h3>Migration Summary</h3>";
    echo "<div class='progress-item'>";
    echo "<p><strong>Total Statements:</strong> <code>$totalStatements</code></p>";
    echo "<p><strong>Successful:</strong> <code style='color: #28a745;'>$successStatements</code></p>";
    echo "<p><strong>Failed:</strong> <code style='color: #dc3545;'>$failedStatements</code></p>";

    if (!empty($errors)) {
        echo "<h5 style='margin-top: 20px; color: #dc3545;'>Errors Encountered:</h5>";
        echo "<ul>";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "<li><code>$error</code></li>";
        }
        if (count($errors) > 10) {
            echo "<li>... and " . (count($errors) - 10) . " more errors</li>";
        }
        echo "</ul>";
    }

    echo "</div>";

    echo "<div class='progress-item " . ($failedStatements === 0 ? 'success' : 'error') . "'>";
    if ($failedStatements === 0) {
        echo "<h4>✓ Migration Completed Successfully!</h4>";
        echo "<p>All " . count($migrations) . " migration files have been processed.</p>";
        echo "<p>The following tables have been created:</p>";
        echo "<ul style='columns: 2;'>";
        echo "<li>sales_analytics</li>";
        echo "<li>product_analytics</li>";
        echo "<li>inventory_alerts</li>";
        echo "<li>inventory_transactions</li>";
        echo "<li>advertising_campaigns</li>";
        echo "<li>campaign_keywords</li>";
        echo "<li>financial_reports</li>";
        echo "<li>category_performance</li>";
        echo "<li>account_health</li>";
        echo "<li>bulk_uploads</li>";
        echo "<li>listing_quality</li>";
        echo "<li>seller_payouts</li>";
        echo "<li>saved_searches</li>";
        echo "<li>payment_transactions</li>";
        echo "<li>paypal_transactions</li>";
        echo "<li>razorpay_transactions</li>";
        echo "<li>audit_log</li>";
        echo "<li>realtime_events</li>";
        echo "<li>websocket_connections</li>";
        echo "<li>widget_preferences</li>";
        echo "<li>cache_manifest</li>";
        echo "</ul>";
        echo "<p style='margin-top: 20px;'><a href='dashboard.php' class='btn btn-success'>Go to Dashboard</a></p>";
    } else {
        echo "<h4>✗ Migration Completed with Errors</h4>";
        echo "<p>Some tables may not have been created properly. Please check the errors above.</p>";
        echo "<p style='margin-top: 20px;'><a href='javascript:location.reload()' class='btn btn-warning'>Retry Migration</a></p>";
    }
    echo "</div>";

    echo "</div>";
    echo "</div></body></html>";
} else {
    echo "[" . date('Y-m-d H:i:s') . "] Migration Summary\n";
    echo "================================================\n";
    echo "Total Statements: $totalStatements\n";
    echo "Successful: $successStatements\n";
    echo "Failed: $failedStatements\n";

    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "  - $error\n";
        }
        if (count($errors) > 10) {
            echo "  ... and " . (count($errors) - 10) . " more errors\n";
        }
    }

    if ($failedStatements === 0) {
        echo "\n✓ All migrations completed successfully!\n";
        exit(0);
    } else {
        echo "\n✗ Some migrations failed. Please review the errors above.\n";
        exit(1);
    }
}
?>
