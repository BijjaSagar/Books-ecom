<?php
/**
 * Email Queue Processor Cron Job
 * Process pending emails from the queue and send them
 * 
 * Setup cron job (every 5 minutes):
 * */5 * * * * php /path/to/cron/send-emails.php
 */

require_once '../includes/config.php';
require_once '../includes/EmailNotificationManager.php';

// Start logging
$log_file = __DIR__ . '/../logs/email_cron.log';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_message = "[" . date('Y-m-d H:i:s') . "] Email Queue Processing Started\n";

try {
    // Initialize email manager
    $email_manager = new EmailNotificationManager($conn);
    
    // Get pending email count
    $pending = $conn->prepare("
        SELECT COUNT(*) as count FROM email_queue 
        WHERE status = 'pending' AND retry_count < 3
    ");
    $pending->execute();
    $pending_count = $pending->get_result()->fetch_assoc()['count'];
    $pending->close();
    
    $log_message .= "Pending emails: {$pending_count}\n";
    
    // Process queue (send emails in batches of 10)
    if ($pending_count > 0) {
        $result = $email_manager->processQueue(10);
        $log_message .= "Processed batch of 10 emails\n";
        $log_message .= "Result: " . json_encode($result) . "\n";
    } else {
        $log_message .= "No pending emails to process\n";
    }
    
    // Clean up old logs (older than 30 days)
    $cleanup = $conn->prepare("
        DELETE FROM email_log 
        WHERE DATE(created_at) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $cleanup->execute();
    $cleanup_count = $conn->affected_rows;
    $cleanup->close();
    
    $log_message .= "Cleaned up {$cleanup_count} old email logs\n";
    $log_message .= "[" . date('Y-m-d H:i:s') . "] Email Queue Processing Completed\n";
    $log_message .= "---\n";
    
} catch (Exception $e) {
    $log_message .= "Error: " . $e->getMessage() . "\n";
    $log_message .= "---\n";
}

// Write to log file
file_put_contents($log_file, $log_message, FILE_APPEND);

// Close database connection
$conn->close();

// Exit successfully
exit(0);
?>
