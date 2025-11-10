<?php
/**
 * Get Member Loyalty Info API
 * GET /api/loyalty/get-member-info.php
 *
 * Returns current member's loyalty information, points, and tier
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

    // Get referral link
    $stmt = $conn->prepare("
        SELECT referral_code, referral_link FROM loyalty_referrals
        WHERE referrer_id = ? ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $referral = $stmt->get_result()->fetch_assoc();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'member' => [
            'id' => (int)$member['id'],
            'name' => $member['name'],
            'email' => $member['email'],
            'tier' => [
                'id' => (int)$member['current_tier_id'],
                'name' => $member['tier_name'],
                'discount' => (float)$member['discount_percentage'],
                'free_shipping' => (bool)$member['free_shipping']
            ],
            'points' => [
                'total' => (int)$member['total_points'],
                'available' => (int)$member['available_points'],
                'lifetime' => (int)$member['lifetime_points']
            ],
            'join_date' => $member['join_date'],
            'last_activity' => $member['last_activity'],
            'is_active' => (bool)$member['is_active']
        ],
        'referral' => $referral ? [
            'code' => $referral['referral_code'],
            'link' => $referral['referral_link']
        ] : null
    ]);

} catch (Exception $e) {
    error_log("[Get Member Info API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch member info']);
}
?>
