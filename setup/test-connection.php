<?php
/**
 * Database Connection Test for Hostinger
 * Run this to verify your database credentials are correct
 * 
 * USAGE: Visit https://yourdomain.com/setup/test-connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Read config directly to test it
$config_file = '../includes/config.php';

if (!file_exists($config_file)) {
    die('ERROR: config.php not found!');
}

// Get config values
$config_content = file_get_contents($config_file);
preg_match('/\$db_host\s*=\s*[\'"]([^\'"]+)/', $config_content, $matches);
$db_host = $matches[1] ?? 'localhost';

preg_match('/\$db_username\s*=\s*[\'"]([^\'"]+)/', $config_content, $matches);
$db_username = $matches[1] ?? '';

preg_match('/\$db_password\s*=\s*[\'"]([^\'"]+)/', $config_content, $matches);
$db_password = $matches[1] ?? '';

preg_match('/\$db_name\s*=\s*[\'"]([^\'"]+)/', $config_content, $matches);
$db_name = $matches[1] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
            background: #f3f4f6;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e40af;
            margin: 0 0 20px 0;
        }
        .status {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border-left-color: #ef4444;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            border-left-color: #f59e0b;
        }
        code {
            background: #f3f4f6;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        .credentials {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .credentials h3 {
            margin: 0 0 10px 0;
            color: #374151;
        }
        .credentials p {
            margin: 5px 0;
            font-size: 0.9em;
            font-family: monospace;
        }
        .next-steps {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border-left: 4px solid #1e40af;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 Database Connection Test</h1>

        <h2 style="color: #374151; margin-top: 20px;">Current Configuration:</h2>
        <div class="credentials">
            <h3>Credentials from config.php:</h3>
            <p><strong>Host:</strong> <?php echo htmlspecialchars($db_host); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($db_username); ?></p>
            <p><strong>Password:</strong> <?php echo htmlspecialchars(str_repeat('*', strlen($db_password))); ?></p>
            <p><strong>Database:</strong> <?php echo htmlspecialchars($db_name); ?></p>
        </div>

        <?php
        // Test connection
        $mysqli = @new mysqli($db_host, $db_username, $db_password, $db_name);
        
        if ($mysqli->connect_error) {
            echo '<div class="status error">';
            echo '<strong>✗ Connection Failed!</strong><br>';
            echo 'Error: ' . htmlspecialchars($mysqli->connect_error);
            echo '</div>';
            
            echo '<div class="status warning">';
            echo '<strong>Troubleshooting Tips:</strong><br>';
            echo '1. Check Host: Is it <code>localhost</code> or an IP address?<br>';
            echo '2. Check Username: Should be like <code>u618910819_xxxxx</code><br>';
            echo '3. Check Password: Did you copy it exactly?<br>';
            echo '4. Check Database Name: Should be like <code>u618910819_xxxxx</code><br>';
            echo '5. Get correct values from: hPanel → Hosting → MySQL Databases<br>';
            echo '</div>';
            
        } else {
            echo '<div class="status success">';
            echo '<strong>✓ Connection Successful!</strong><br>';
            echo 'Connected to database: <code>' . htmlspecialchars($db_name) . '</code>';
            echo '</div>';
            
            // Get database info
            $result = $mysqli->query("SELECT VERSION() as version");
            $row = $result->fetch_assoc();
            
            echo '<div class="status success">';
            echo '<strong>Database Information:</strong><br>';
            echo 'MySQL Version: ' . htmlspecialchars($row['version']) . '<br>';
            echo 'Character Set: ' . htmlspecialchars($mysqli->character_set_name()) . '<br>';
            echo '</div>';
            
            // List existing tables
            $tables_result = $mysqli->query("SHOW TABLES");
            $table_count = $tables_result->num_rows;
            
            if ($table_count > 0) {
                echo '<div class="status success">';
                echo '<strong>✓ Database has ' . $table_count . ' tables</strong><br>';
                echo 'Your tables: ';
                while ($table = $tables_result->fetch_row()) {
                    echo '<code>' . htmlspecialchars($table[0]) . '</code> ';
                }
                echo '</div>';
            } else {
                echo '<div class="status warning">';
                echo '<strong>⚠ Database is empty!</strong><br>';
                echo 'You need to run the database setup.<br>';
                echo 'Visit: <code>/setup/database-init.php</code>';
                echo '</div>';
            }
            
            echo '<div class="next-steps">';
            echo '<strong>✓ Next Steps:</strong><br>';
            echo '1. Run database setup: <code>/setup/database-init.php</code><br>';
            echo '2. Run database seeder: <code>/cron/seed-database.php</code><br>';
            echo '3. Access admin: <code>/admin/</code><br>';
            echo '</div>';
            
            $mysqli->close();
        }
        ?>

    </div>
</body>
</html>
