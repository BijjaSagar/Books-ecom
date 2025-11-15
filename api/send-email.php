<?php
/**
 * Send Email Endpoint
 * Triggers email notifications
 *
 * POST /api/send-email.php
 * JSON: {
 *     "customer_id": 123,
 *     "email_type": "order_confirmation|shipment|delivery|refund|review|promotion",
 *     "data": {...}
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/EmailManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $customer_id = intval($data['customer_id'] ?? 0);
    $email_type = strtolower($data['email_type'] ?? '');

    if ($customer_id <= 0 || empty($email_type)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Missing customer_id or email_type']));
    }

    // Get customer email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Customer not found']));
    }

    $email_manager = new EmailManager($conn);
    $result = [];

    switch ($email_type) {
        case 'order_confirmation':
            $result = $email_manager->sendOrderConfirmation(
                intval($data['order_id'] ?? 0),
                $customer_id,
                $user['email']
            );
            break;

        case 'shipment':
            $result = $email_manager->sendShipmentNotification(
                intval($data['order_id'] ?? 0),
                $customer_id,
                $user['email'],
                $data['tracking_info'] ?? []
            );
            break;

        case 'delivery':
            $result = $email_manager->sendDeliveryConfirmation(
                intval($data['order_id'] ?? 0),
                $customer_id,
                $user['email']
            );
            break;

        case 'refund':
            $result = $email_manager->sendRefundNotification(
                intval($data['order_id'] ?? 0),
                $customer_id,
                $user['email'],
                floatval($data['refund_amount'] ?? 0),
                $data['reason'] ?? ''
            );
            break;

        case 'review':
            $result = $email_manager->sendReviewRequest(
                intval($data['order_id'] ?? 0),
                $customer_id,
                $user['email'],
                $data['product_ids'] ?? []
            );
            break;

        case 'promotion':
            $result = $email_manager->sendPromotion(
                $customer_id,
                $user['email'],
                $data['promo_data'] ?? []
            );
            break;

        case 'newsletter':
            $result = $email_manager->sendNewsletter(
                $customer_id,
                $user['email'],
                $data['newsletter_data'] ?? []
            );
            break;

        case 'ticket_response':
            $result = $email_manager->sendTicketResponse(
                $customer_id,
                $user['email'],
                $data['ticket_number'] ?? '',
                $data['response_message'] ?? ''
            );
            break;

        default:
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid email_type']));
    }

    if (!$result['success']) {
        http_response_code(400);
    } else {
        http_response_code(200);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("[Send Email API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to send email']);
}
?>
