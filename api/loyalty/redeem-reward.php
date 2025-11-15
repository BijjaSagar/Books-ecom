<?php
/**
 * Redeem Reward API
 * POST /api/loyalty/redeem-reward.php
 *
 * Request body (JSON):
 * {
 *     "reward_id": 1
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/LoyaltyManager.php';

// Check authentication
if (($_SESSION['user_id'] ?? null) === null) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $user_id = $_SESSION['user_id'];
    $reward_id = (int)($data['reward_id'] ?? 0);

    if ($reward_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'reward_id required']));
    }

    $loyalty = new LoyaltyManager($conn);

    // Try to redeem
    $redemption_id = $loyalty->redeemReward($user_id, $reward_id);

    if (!$redemption_id) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Unable to redeem reward']));
    }

    // Get updated member info
    $member = $loyalty->getMemberInfo($user_id);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Reward redeemed successfully',
        'redemption_id' => $redemption_id,
        'updated_points' => (int)$member['available_points']
    ]);

} catch (Exception $e) {
    error_log("[Redeem Reward API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
