<?php
/**
 * Dashboard Support Tickets Endpoint
 * Manages customer support tickets
 *
 * GET /api/dashboard-tickets.php?customer_id=123&limit=10&offset=0&status=open
 * GET /api/dashboard-tickets.php?customer_id=123&ticket_id=456 (get details)
 * POST /api/dashboard-tickets.php - Create or reply to ticket
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/DashboardManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $dashboard = new DashboardManager($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $customer_id = intval($_GET['customer_id'] ?? 0);
        $ticket_id = intval($_GET['ticket_id'] ?? 0);

        if ($customer_id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
        }

        if ($ticket_id > 0) {
            // Get ticket details
            $result = $dashboard->getTicketDetails($customer_id, $ticket_id);
        } else {
            // Get ticket list
            $limit = intval($_GET['limit'] ?? 10);
            $offset = intval($_GET['offset'] ?? 0);
            $status = $_GET['status'] ?? null;

            $result = $dashboard->getSupportTickets($customer_id, $limit, $offset, $status);
        }

        if (!$result['success']) {
            http_response_code(404);
        } else {
            http_response_code(200);
        }

        echo json_encode($result);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $customer_id = intval($data['customer_id'] ?? 0);

        if ($customer_id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
        }

        if (!empty($data['ticket_id'])) {
            // Add reply to existing ticket
            $ticket_id = intval($data['ticket_id']);
            $reply = trim($data['reply'] ?? '');

            if (empty($reply)) {
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Reply cannot be empty']));
            }

            $result = $dashboard->addTicketReply($customer_id, $ticket_id, $reply);
        } else {
            // Create new ticket
            if (empty($data['subject']) || empty($data['description'])) {
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'Subject and description are required']));
            }

            $result = $dashboard->createTicket($customer_id, $data);
        }

        if (!$result['success']) {
            http_response_code(400);
        } else {
            http_response_code(201);
        }

        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("[Dashboard Tickets API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to manage support tickets']);
}
?>
