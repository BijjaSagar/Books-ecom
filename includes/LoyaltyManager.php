<?php
/**
 * LoyaltyManager.php - Complete Loyalty Program Management System
 * Phase 2, Task 2: Customer loyalty, points, tiers, rewards, and referrals
 * Created: November 10, 2025
 */

class LoyaltyManager {
    private $conn;
    const ENROLLMENT_BONUS = 100; // Points for new members
    const POINTS_PER_DOLLAR = 1.0; // Base points per $1 spent

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Initialize loyalty membership for new customer
     */
    public function initializeMembership($user_id, $referral_code = null) {
        try {
            $this->conn->begin_transaction();

            // Create loyalty member record
            $stmt = $this->conn->prepare("
                INSERT INTO loyalty_members (user_id, current_tier_id, total_points, available_points, lifetime_points)
                VALUES (?, 1, ?, ?, ?)
            ");

            $bonus_points = self::ENROLLMENT_BONUS;
            $stmt->bind_param("iiii", $user_id, $bonus_points, $bonus_points, $bonus_points);
            $stmt->execute();

            // Log enrollment bonus
            $this->logTransaction($user_id, 'signup', self::ENROLLMENT_BONUS, 'Enrollment bonus', null, 'signup');

            // Process referral if provided
            if ($referral_code) {
                $this->processReferralActivation($user_id, $referral_code);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error initializing membership: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get member loyalty information
     */
    public function getMemberInfo($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    lm.id,
                    lm.user_id,
                    u.name,
                    u.email,
                    lm.current_tier_id,
                    lt.tier_name,
                    lt.points_multiplier,
                    lt.discount_percentage,
                    lt.free_shipping,
                    lm.total_points,
                    lm.available_points,
                    lm.lifetime_points,
                    lm.join_date,
                    lm.last_activity,
                    lm.is_active
                FROM loyalty_members lm
                JOIN users u ON lm.user_id = u.id
                LEFT JOIN loyalty_tiers lt ON lm.current_tier_id = lt.id
                WHERE lm.user_id = ?
            ");

            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching member info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add points for purchase
     */
    public function addPurchasePoints($user_id, $order_id, $order_amount) {
        try {
            // Get member tier info for multiplier
            $member = $this->getMemberInfo($user_id);
            if (!$member) {
                return false;
            }

            // Calculate points
            $multiplier = $member['points_multiplier'] ?? 1.0;
            $points_earned = (int)($order_amount * self::POINTS_PER_DOLLAR * $multiplier);

            // Check max points per order
            $stmt = $this->conn->prepare("
                SELECT max_points_per_order FROM loyalty_point_rules
                WHERE rule_type = 'purchase' AND is_active = TRUE AND applies_to_tier_id IS NULL
                LIMIT 1
            ");
            $stmt->execute();
            $rule = $stmt->get_result()->fetch_assoc();
            if ($rule && $rule['max_points_per_order'] && $points_earned > $rule['max_points_per_order']) {
                $points_earned = $rule['max_points_per_order'];
            }

            // Add points
            $stmt = $this->conn->prepare("
                UPDATE loyalty_members
                SET total_points = total_points + ?,
                    available_points = available_points + ?,
                    lifetime_points = lifetime_points + ?,
                    last_activity = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");

            $stmt->bind_param("iiii", $points_earned, $points_earned, $points_earned, $user_id);
            $success = $stmt->execute();

            if ($success) {
                // Log transaction
                $this->logTransaction($user_id, 'purchase', $points_earned, "Order #$order_id", $order_id, 'order');
                // Check for tier upgrade
                $this->checkAndUpdateTier($user_id);
            }

            return $success;
        } catch (Exception $e) {
            error_log("Error adding purchase points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Redeem points for reward
     */
    public function redeemReward($user_id, $reward_id) {
        try {
            $this->conn->begin_transaction();

            // Get reward details
            $stmt = $this->conn->prepare("
                SELECT id, points_required, max_redemptions, current_redemptions, reward_code, is_active
                FROM loyalty_rewards WHERE id = ?
            ");

            $stmt->bind_param("i", $reward_id);
            $stmt->execute();
            $reward = $stmt->get_result()->fetch_assoc();

            if (!$reward || !$reward['is_active']) {
                throw new Exception("Reward not available");
            }

            // Check redemption limit
            if ($reward['max_redemptions'] && $reward['current_redemptions'] >= $reward['max_redemptions']) {
                throw new Exception("Reward limit reached");
            }

            // Get member points
            $member = $this->getMemberInfo($user_id);
            if (!$member || $member['available_points'] < $reward['points_required']) {
                throw new Exception("Insufficient points");
            }

            // Deduct points
            $stmt = $this->conn->prepare("
                UPDATE loyalty_members
                SET available_points = available_points - ?,
                    last_activity = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");

            $stmt->bind_param("ii", $reward['points_required'], $user_id);
            $stmt->execute();

            // Record redemption
            $stmt = $this->conn->prepare("
                INSERT INTO loyalty_redemptions (user_id, reward_id, points_redeemed, reward_received)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param("iis", $user_id, $reward_id, $reward['points_required'], $reward['reward_code']);
            $redemption_result = $stmt->execute();
            $redemption_id = $this->conn->insert_id;

            // Update reward redemption count
            $stmt = $this->conn->prepare("
                UPDATE loyalty_rewards SET current_redemptions = current_redemptions + 1 WHERE id = ?
            ");
            $stmt->bind_param("i", $reward_id);
            $stmt->execute();

            // Log transaction
            $this->logTransaction($user_id, 'redemption', -$reward['points_required'], "Redeemed reward", $reward_id, 'reward');

            $this->conn->commit();
            return $redemption_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error redeeming reward: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create referral link
     */
    public function createReferral($user_id) {
        try {
            // Generate unique referral code
            $referral_code = 'REF' . strtoupper(bin2hex(random_bytes(8)));
            $referral_link = 'https://' . $_SERVER['HTTP_HOST'] . '/signup?ref=' . $referral_code;

            $stmt = $this->conn->prepare("
                INSERT INTO loyalty_referrals (referrer_id, referral_code, referral_link, expires_at)
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 YEAR))
            ");

            $stmt->bind_param("iss", $user_id, $referral_code, $referral_link);
            $success = $stmt->execute();

            return $success ? [
                'referral_code' => $referral_code,
                'referral_link' => $referral_link
            ] : false;
        } catch (Exception $e) {
            error_log("Error creating referral: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process referral activation
     */
    private function processReferralActivation($referred_user_id, $referral_code) {
        try {
            // Get referral
            $stmt = $this->conn->prepare("
                SELECT referrer_id, points_offered FROM loyalty_referrals
                WHERE referral_code = ? AND status = 'pending' AND expires_at > NOW()
            ");

            $stmt->bind_param("s", $referral_code);
            $stmt->execute();
            $referral = $stmt->get_result()->fetch_assoc();

            if (!$referral) {
                return false;
            }

            $referrer_id = $referral['referrer_id'];
            $points_offered = $referral['points_offered'];

            // Update referral status
            $stmt = $this->conn->prepare("
                UPDATE loyalty_referrals
                SET referred_id = ?, status = 'activated', activation_bonus_given = TRUE
                WHERE referral_code = ?
            ");

            $stmt->bind_param("is", $referred_user_id, $referral_code);
            $stmt->execute();

            // Give referrer activation bonus
            $stmt = $this->conn->prepare("
                UPDATE loyalty_members
                SET total_points = total_points + ?,
                    available_points = available_points + ?,
                    lifetime_points = lifetime_points + ?
                WHERE user_id = ?
            ");

            $stmt->bind_param("iiii", $points_offered, $points_offered, $points_offered, $referrer_id);
            $stmt->execute();

            // Log transaction
            $this->logTransaction($referrer_id, 'referral', $points_offered, "Referral activation bonus", $referred_user_id, 'referral');

            return true;
        } catch (Exception $e) {
            error_log("Error processing referral activation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and update member tier
     */
    private function checkAndUpdateTier($user_id) {
        try {
            $member = $this->getMemberInfo($user_id);
            if (!$member) return false;

            // Get appropriate tier
            $stmt = $this->conn->prepare("
                SELECT id FROM loyalty_tiers
                WHERE ? >= min_points_required AND ? < max_points_required
                ORDER BY tier_level DESC LIMIT 1
            ");

            $points = $member['total_points'];
            $stmt->bind_param("ii", $points, $points);
            $stmt->execute();
            $tier = $stmt->get_result()->fetch_assoc();

            if ($tier && $tier['id'] != $member['current_tier_id']) {
                $stmt = $this->conn->prepare("
                    UPDATE loyalty_members SET current_tier_id = ? WHERE user_id = ?
                ");

                $stmt->bind_param("ii", $tier['id'], $user_id);
                return $stmt->execute();
            }

            return true;
        } catch (Exception $e) {
            error_log("Error checking tier: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available rewards
     */
    public function getAvailableRewards($user_id = null) {
        try {
            $query = "
                SELECT
                    id, reward_name, description, reward_type, points_required,
                    reward_value, max_redemptions, current_redemptions,
                    (max_redemptions - current_redemptions) as remaining
                FROM loyalty_rewards
                WHERE is_active = TRUE AND expiration_date IS NULL OR expiration_date > NOW()
            ";

            if ($user_id) {
                $member = $this->getMemberInfo($user_id);
                $points = $member['available_points'];
                // Get rewards user can afford
                $query = "
                    SELECT
                        id, reward_name, description, reward_type, points_required,
                        reward_value, max_redemptions, current_redemptions,
                        (max_redemptions - current_redemptions) as remaining,
                        ($points >= points_required) as can_redeem
                    FROM loyalty_rewards
                    WHERE is_active = TRUE AND (expiration_date IS NULL OR expiration_date > NOW())
                    ORDER BY points_required ASC
                ";
            }

            $result = $this->conn->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching rewards: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get member transaction history
     */
    public function getTransactionHistory($user_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    id, transaction_type, points_change, reason, reference_type,
                    balance_before, balance_after, created_at
                FROM loyalty_transactions
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->bind_param("iii", $user_id, $limit, $offset);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching transaction history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get member referrals
     */
    public function getMemberReferrals($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    lr.id,
                    lr.referral_code,
                    lr.referral_link,
                    lr.status,
                    COUNT(DISTINCT CASE WHEN lr.status = 'activated' THEN lr.referred_id END) as activated_count,
                    COUNT(DISTINCT CASE WHEN lr.status = 'converted' THEN lr.referred_id END) as converted_count,
                    lr.created_at
                FROM loyalty_referrals lr
                WHERE lr.referrer_id = ?
                GROUP BY lr.referral_code
                ORDER BY lr.created_at DESC
            ");

            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching referrals: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get loyalty program statistics
     */
    public function getProgramStats() {
        try {
            $result = $this->conn->query("
                SELECT * FROM vw_loyalty_program_stats
            ");

            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching program stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tier distribution
     */
    public function getTierDistribution() {
        try {
            $result = $this->conn->query("
                SELECT * FROM vw_member_tier_distribution
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching tier distribution: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top rewards
     */
    public function getTopRewards($limit = 10) {
        try {
            $result = $this->conn->query("
                SELECT * FROM vw_top_rewards LIMIT $limit
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching top rewards: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log loyalty transaction
     */
    private function logTransaction($user_id, $transaction_type, $points_change, $reason = '', $reference_id = null, $reference_type = 'manual', $performed_by = null) {
        try {
            $member = $this->getMemberInfo($user_id);
            $balance_before = $member['available_points'];
            $balance_after = $balance_before + $points_change;

            $stmt = $this->conn->prepare("
                INSERT INTO loyalty_transactions
                (user_id, transaction_type, points_change, reason, reference_id, reference_type, balance_before, balance_after, performed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param("isisisii", $user_id, $transaction_type, $points_change, $reason, $reference_id, $reference_type, $balance_before, $balance_after, $performed_by);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging transaction: " . $e->getMessage());
            return false;
        }
    }
}
