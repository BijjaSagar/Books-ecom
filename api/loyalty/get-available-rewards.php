<?php
/**
 * Get Available Rewards API
 * GET /api/loyalty/get-available-rewards.php
 *
 * Returns available rewards for redemption
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

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

try {
    $user_id = $_SESSION['user_id'];
    $loyalty = new LoyaltyManager($conn);

    // Get member info
    $member = $loyalty->getMemberInfo($user_id);
    if (!$member) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Member not found']));
    }

    // Get available rewards
    $rewards = $loyalty->getAvailableRewards($user_id);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'member_points' => (int)$member['available_points'],
        'rewards' => array_map(function($reward) {
            return [
                'id' => (int)$reward['id'],
                'name' => $reward['reward_name'],
                'description' => $reward['description'],
                'type' => $reward['reward_type'],
                'points_required' => (int)$reward['points_required'],
                'value' => (float)$reward['reward_value'],
                'remaining' => (int)($reward['remaining'] ?? $reward['max_redemptions']),
                'can_redeem' => (bool)($reward['can_redeem'] ?? false)
            ];
        }, $rewards)
    ]);

} catch (Exception $e) {
    error_log("[Get Rewards API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch rewards']);
}
?>
