<?php
/**
 * Dashboard Account Settings Endpoint
 * Manages customer account settings and preferences
 *
 * GET /api/dashboard-account.php?customer_id=123&section=settings|preferences|all
 * POST /api/dashboard-account.php - Update settings or preferences
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
        $section = strtolower($_GET['section'] ?? 'all');

        if ($customer_id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
        }

        $response = ['success' => true];

        if (in_array($section, ['settings', 'all'])) {
            $settings = $dashboard->getAccountSettings($customer_id);
            if ($settings['success']) {
                $response['account_settings'] = $settings['settings'];
            }
        }

        if (in_array($section, ['preferences', 'all'])) {
            $prefs = $dashboard->getNotificationPreferences($customer_id);
            if ($prefs['success']) {
                $response['notification_preferences'] = $prefs['preferences'];
            }
        }

        http_response_code(200);
        echo json_encode($response);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $customer_id = intval($data['customer_id'] ?? 0);

        if ($customer_id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid customer_id']));
        }

        $result = ['success' => true];

        // Update account settings if provided
        if (!empty($data['account_settings'])) {
            $settings_result = $dashboard->updateAccountSettings($customer_id, $data['account_settings']);
            if (!$settings_result['success']) {
                $result['success'] = false;
                $result['errors'] = $result['errors'] ?? [];
                $result['errors']['account_settings'] = $settings_result['error'];
            } else {
                $result['message'] = $settings_result['message'];
            }
        }

        // Update notification preferences if provided
        if (!empty($data['notification_preferences'])) {
            $prefs_result = $dashboard->updateNotificationPreferences($customer_id, $data['notification_preferences']);
            if (!$prefs_result['success']) {
                $result['success'] = false;
                $result['errors'] = $result['errors'] ?? [];
                $result['errors']['notification_preferences'] = $prefs_result['error'];
            } else {
                if (!isset($result['message'])) {
                    $result['message'] = $prefs_result['message'];
                }
            }
        }

        $status_code = $result['success'] ? 200 : 400;
        http_response_code($status_code);
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("[Dashboard Account API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to manage account settings']);
}
?>
