<?php
/**
 * Database Migration Runner
 * Applies SQL migrations from database/migrations/ directory
 */

// Include database configuration
require_once __DIR__ . '/../includes/config.php';

// Get all migration files
$migrations_dir = __DIR__ . '/migrations';
$migration_files = glob($migrations_dir . '/*.sql');
sort($migration_files);

if (empty($migration_files)) {
    echo "❌ No migration files found in $migrations_dir\n";
    exit(1);
}

echo "🔄 Starting database migration...\n";
echo "Database: {$GLOBALS['db_name']}\n";
echo "Found " . count($migration_files) . " migration file(s)\n\n";

$total_statements = 0;
$errors = 0;

foreach ($migration_files as $file) {
    $filename = basename($file);
    echo "📄 Processing: $filename\n";

    // Read SQL file
    $sql_content = file_get_contents($file);

    // Split by semicolon (simple approach)
    $statements = array_filter(
        array_map('trim', preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', trim($stmt));
        }
    );

    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;

        // Skip comments
        if (preg_match('/^--/', $statement)) continue;

        $stmt_number = $index + 1;

        try {
            // Execute statement
            if (!$conn->query($statement)) {
                echo "   ❌ Statement $stmt_number failed: " . $conn->error . "\n";
                echo "   SQL: " . substr($statement, 0, 100) . "...\n";
                $errors++;
            } else {
                echo "   ✓ Statement $stmt_number executed\n";
                $total_statements++;
            }
        } catch (Exception $e) {
            echo "   ❌ Statement $stmt_number error: " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    echo "\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 MIGRATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Statements executed: $total_statements\n";
echo "❌ Statements failed: $errors\n";

if ($errors === 0) {
    echo "\n✅ Migration completed successfully!\n";

    // List created tables
    echo "\n📋 Created/Updated tables:\n";
    $tables = $conn->query("SHOW TABLES FROM `{$GLOBALS['db_name']}`");
    while ($row = $tables->fetch_row()) {
        echo "   • {$row[0]}\n";
    }

    exit(0);
} else {
    echo "\n❌ Migration completed with errors.\n";
    exit(1);
}
?>
