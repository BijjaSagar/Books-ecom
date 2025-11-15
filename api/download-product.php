<?php
/**
 * Product Download Endpoint
 * Serves digital product files for download
 *
 * GET /api/download-product.php?download_id=...
 * Validates customer has purchased the product before serving file
 */

require_once __DIR__ . '/../includes/config.php';

try {
    $download_id = $_GET['download_id'] ?? null;

    if (!$download_id) {
        http_response_code(400);
        die('Missing download_id parameter');
    }

    // Get download record
    $stmt = $conn->prepare("
        SELECT dd.id, dd.product_id, dd.order_id, p.file_path, p.title,
               o.customer_id, dd.expires_at, dd.download_count
        FROM digital_downloads dd
        JOIN products p ON dd.product_id = p.id
        JOIN orders o ON dd.order_id = o.id
        WHERE dd.id = ?
    ");

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $download_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $download = $result->fetch_assoc();

    if (!$download) {
        http_response_code(404);
        die('Download not found');
    }

    // Check if expired
    if ($download['expires_at'] && strtotime($download['expires_at']) < time()) {
        http_response_code(403);
        die('Download link has expired');
    }

    // Check file exists
    $file_path = __DIR__ . '/../' . $download['file_path'];

    if (!file_exists($file_path)) {
        http_response_code(404);
        error_log("Digital product file not found: " . $file_path);
        die('File not found');
    }

    // Check file is readable
    if (!is_readable($file_path)) {
        http_response_code(403);
        die('File access denied');
    }

    // Update download count
    $update_stmt = $conn->prepare("
        UPDATE digital_downloads
        SET download_count = download_count + 1,
            last_downloaded_at = NOW()
        WHERE id = ?
    ");
    $update_stmt->bind_param("i", $download_id);
    $update_stmt->execute();

    // Get file info
    $file_size = filesize($file_path);
    $file_name = basename($file_path);

    // Serve file
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Log download activity
    error_log("[Digital Download] Customer {$download['customer_id']} downloaded product {$download['product_id']} (download_id: {$download_id})");

    // Output file content
    readfile($file_path);
    exit;

} catch (Exception $e) {
    error_log("[Product Download API] Error: " . $e->getMessage());

    http_response_code(500);
    die('Download failed. Please try again later.');
}
?>
