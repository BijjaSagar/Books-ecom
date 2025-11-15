<?php
/**
 * Email History Endpoint
 * Returns customer's email history
 *
 * GET /api/email-history.php?customer_id=123&limit=20&offset=0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $customer_id = intval($_GET['customer_id'] ?? 0);
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);

    if ($customer_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
    }

    $limit = min($limit, 100);
    $offset = max($offset, 0);

    $stmt = $conn->prepare("
        SELECT id, recipient_email, subject, template, status, created_at
        FROM email_history
        WHERE customer_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");

    $stmt->bind_param("iii", $customer_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = [
            'id' => intval($row['id']),
            'recipient' => $row['recipient_email'],
            'subject' => $row['subject'],
            'template' => $row['template'],
            'status' => $row['status'],
            'sent_at' => $row['created_at']
        ];
    }

    // Get count
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM email_history WHERE customer_id = ?");
    $count_stmt->bind_param("i", $customer_id);
    $count_stmt->execute();
    $count = $count_stmt->get_result()->fetch_assoc();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'emails' => $emails,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => intval($count['total'])
        ]
    ]);

} catch (Exception $e) {
    error_log("[Email History API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load email history']);
}
?>
