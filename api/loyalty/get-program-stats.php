<?php
/**
 * Get Program Statistics API
 * GET /api/loyalty/get-program-stats.php
 *
 * Returns loyalty program statistics for admins
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/LoyaltyManager.php';

// Check admin auth
if (($_SESSION['user_id'] ?? null) === null || ($_SESSION['is_admin'] ?? false) === false) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $loyalty = new LoyaltyManager($conn);

    // Get program stats
    $stats = $loyalty->getProgramStats();

    // Get tier distribution
    $tiers = $loyalty->getTierDistribution();

    // Get top rewards
    $top_rewards = $loyalty->getTopRewards(5);

    // Get recent referrals
    $referrals_result = $conn->query("
        SELECT
            u.name,
            COUNT(lr.id) as referral_count,
            SUM(CASE WHEN lr.status = 'converted' THEN 1 ELSE 0 END) as conversions
        FROM users u
        LEFT JOIN loyalty_referrals lr ON u.id = lr.referrer_id
        WHERE u.id IN (SELECT DISTINCT referrer_id FROM loyalty_referrals)
        GROUP BY u.id
        ORDER BY conversions DESC
        LIMIT 10
    ");
    $top_referrers = $referrals_result->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'program_overview' => [
            'total_members' => (int)($stats['total_members'] ?? 0),
            'active_members_30d' => (int)($stats['active_members_30d'] ?? 0),
            'total_points_circulation' => (int)($stats['total_points_in_circulation'] ?? 0),
            'avg_points_per_member' => (float)($stats['avg_points_per_member'] ?? 0),
            'members_with_referrals' => (int)($stats['members_with_referrals'] ?? 0),
            'conversions_from_referrals' => (int)($stats['conversions_from_referrals'] ?? 0)
        ],
        'tier_distribution' => array_map(function($tier) {
            return [
                'tier_id' => (int)$tier['id'],
                'tier_name' => $tier['tier_name'],
                'member_count' => (int)$tier['member_count'],
                'avg_points' => (float)$tier['avg_points'],
                'total_tier_points' => (int)$tier['total_tier_points']
            ];
        }, $tiers),
        'top_rewards' => array_map(function($reward) {
            return [
                'reward_id' => (int)$reward['id'],
                'reward_name' => $reward['reward_name'],
                'type' => $reward['reward_type'],
                'times_redeemed' => (int)$reward['times_redeemed'],
                'remaining' => (int)$reward['remaining']
            ];
        }, $top_rewards),
        'top_referrers' => array_map(function($referrer) {
            return [
                'name' => $referrer['name'],
                'total_referrals' => (int)$referrer['referral_count'],
                'successful_conversions' => (int)$referrer['conversions']
            ];
        }, $top_referrers)
    ]);

} catch (Exception $e) {
    error_log("[Program Stats API] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch statistics']);
}
?>
