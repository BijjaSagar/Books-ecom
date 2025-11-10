<?php
/**
 * Digital Downloads Endpoint
 * Manages digital product download links and tracking
 *
 * GET /api/digital-downloads.php?order_id=...
 * GET /api/digital-downloads.php?customer_id=...
 * POST /api/digital-downloads.php (to log download)
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: application/json');

        $order_id = $_GET['order_id'] ?? null;
        $customer_id = $_GET['customer_id'] ?? null;

        if (!$order_id && !$customer_id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Missing order_id or customer_id']));
        }

        if ($order_id) {
            // Get downloads for specific order
            $stmt = $conn->prepare("
                SELECT dd.id, dd.product_id, p.title, p.file_path, dd.download_count,
                       dd.created_at, dd.expires_at
                FROM digital_downloads dd
                JOIN products p ON dd.product_id = p.id
                WHERE dd.order_id = ? AND (dd.expires_at IS NULL OR dd.expires_at > NOW())
                ORDER BY dd.created_at DESC
            ");
            $stmt->bind_param("i", $order_id);
        } else {
            // Get all downloads for customer
            $stmt = $conn->prepare("
                SELECT dd.id, dd.product_id, dd.order_id, p.title, p.file_path,
                       dd.download_count, dd.created_at, dd.expires_at
                FROM digital_downloads dd
                JOIN products p ON dd.product_id = p.id
                JOIN orders o ON dd.order_id = o.id
                WHERE o.customer_id = ? AND (dd.expires_at IS NULL OR dd.expires_at > NOW())
                ORDER BY dd.created_at DESC
            ");
            $stmt->bind_param("i", $customer_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $downloads = [];
        while ($row = $result->fetch_assoc()) {
            $downloads[] = [
                'id' => intval($row['id']),
                'product_id' => intval($row['product_id']),
                'order_id' => isset($row['order_id']) ? intval($row['order_id']) : null,
                'title' => $row['title'],
                'download_url' => '/api/download-product.php?download_id=' . $row['id'],
                'download_count' => intval($row['download_count']),
                'created_at' => $row['created_at'],
                'expires_at' => $row['expires_at']
            ];
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'downloads' => $downloads,
            'count' => count($downloads)
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!isset($data['download_id'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Missing download_id']));
        }

        // Log download
        $stmt = $conn->prepare("
            UPDATE digital_downloads
            SET download_count = download_count + 1,
                last_downloaded_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $data['download_id']);

        if (!$stmt->execute()) {
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'Failed to log download']));
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Download logged successfully'
        ]);

    } else {
        http_response_code(405);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Method not allowed']));
    }

} catch (Exception $e) {
    error_log("[Digital Downloads API] Error: " . $e->getMessage());

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process digital downloads'
    ]);
}
?>
