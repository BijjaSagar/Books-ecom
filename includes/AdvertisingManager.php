<?php
/**
 * AdvertisingManager.php - Handle advertising campaigns and keywords
 */

class AdvertisingManager {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Create advertising campaign
     */
    public function createCampaign($campaignData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO advertising_campaigns (
                    campaign_name, campaign_type, budget, daily_budget,
                    status, start_date, end_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssddsss",
                $campaignData['name'],
                $campaignData['type'],
                $campaignData['budget'],
                $campaignData['daily_budget'],
                $campaignData['status'],
                $campaignData['start_date'],
                $campaignData['end_date']
            );

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating campaign: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all campaigns
     */
    public function getCampaigns($status = null, $limit = 50, $offset = 0) {
        try {
            $query = "
                SELECT
                    c.id,
                    c.campaign_name,
                    c.campaign_type,
                    c.budget,
                    c.daily_budget,
                    c.spend,
                    c.impressions,
                    c.clicks,
                    c.conversions,
                    c.orders,
                    c.sales,
                    c.acos,
                    c.roas,
                    c.cpc,
                    c.status,
                    c.start_date,
                    c.end_date,
                    CASE
                        WHEN c.budget > 0 THEN ROUND((c.spend / c.budget) * 100, 2)
                        ELSE 0
                    END as budget_utilization
                FROM advertising_campaigns c
            ";

            if ($status) {
                $query .= " WHERE c.status = '$status'";
            }

            $query .= " ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset";

            $result = $this->conn->query($query);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching campaigns: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get campaign details
     */
    public function getCampaign($campaignId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM advertising_campaigns WHERE id = ?
            ");

            $stmt->bind_param("i", $campaignId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching campaign: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update campaign
     */
    public function updateCampaign($campaignId, $data) {
        try {
            $updates = [];
            $values = [];
            $types = "";

            foreach ($data as $key => $value) {
                if (in_array($key, ['campaign_name', 'budget', 'daily_budget', 'status', 'end_date', 'impressions', 'clicks', 'conversions', 'orders', 'sales', 'acos', 'roas', 'cpc', 'spend'])) {
                    $updates[] = "$key = ?";
                    $values[] = $value;
                    $types .= is_numeric($value) ? "d" : "s";
                }
            }

            if (empty($updates)) return true;

            $values[] = $campaignId;
            $types .= "i";

            $stmt = $this->conn->prepare("
                UPDATE advertising_campaigns SET " . implode(", ", $updates) . " WHERE id = ?
            ");

            $stmt->bind_param($types, ...$values);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating campaign: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add keyword to campaign
     */
    public function addKeyword($campaignId, $keyword, $matchType, $bid) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO campaign_keywords (campaign_id, keyword, match_type, bid)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param("issd", $campaignId, $keyword, $matchType, $bid);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error adding keyword: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get campaign keywords
     */
    public function getKeywords($campaignId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    id,
                    keyword,
                    match_type,
                    bid,
                    impressions,
                    clicks,
                    conversions,
                    spend,
                    status,
                    CASE
                        WHEN clicks > 0 THEN ROUND(conversions / clicks * 100, 2)
                        ELSE 0
                    END as conversion_rate,
                    CASE
                        WHEN spend > 0 THEN ROUND(spend / clicks, 3)
                        ELSE 0
                    END as actual_cpc
                FROM campaign_keywords
                WHERE campaign_id = ?
                ORDER BY spend DESC
            ");

            $stmt->bind_param("i", $campaignId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching keywords: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update keyword bid
     */
    public function updateKeywordBid($keywordId, $newBid) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE campaign_keywords SET bid = ? WHERE id = ?
            ");

            $stmt->bind_param("di", $newBid, $keywordId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating bid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pause/Resume keyword
     */
    public function toggleKeywordStatus($keywordId, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE campaign_keywords SET status = ? WHERE id = ?
            ");

            $stmt->bind_param("si", $status, $keywordId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error toggling keyword: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get campaign performance summary
     */
    public function getCampaignPerformance($startDate = null, $endDate = null) {
        try {
            $query = "
                SELECT
                    COUNT(id) as total_campaigns,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_campaigns,
                    SUM(spend) as total_spend,
                    SUM(sales) as total_sales,
                    COUNT(impressions) as total_impressions,
                    COUNT(clicks) as total_clicks,
                    COUNT(conversions) as total_conversions,
                    AVG(acos) as avg_acos,
                    AVG(roas) as avg_roas
                FROM advertising_campaigns
            ";

            if ($startDate && $endDate) {
                $query .= " WHERE start_date >= '$startDate' AND end_date <= '$endDate'";
            }

            $result = $this->conn->query($query);
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error fetching campaign performance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Pause campaign
     */
    public function pauseCampaign($campaignId) {
        return $this->updateCampaign($campaignId, ['status' => 'paused']);
    }

    /**
     * Activate campaign
     */
    public function activateCampaign($campaignId) {
        return $this->updateCampaign($campaignId, ['status' => 'active']);
    }

    /**
     * Archive campaign
     */
    public function archiveCampaign($campaignId) {
        return $this->updateCampaign($campaignId, ['status' => 'archived']);
    }

    /**
     * Get campaign ROI analysis
     */
    public function getRoiAnalysis() {
        try {
            $result = $this->conn->query("
                SELECT
                    c.id,
                    c.campaign_name,
                    c.spend,
                    c.sales,
                    ROUND((c.sales - c.spend) / c.spend * 100, 2) as roi_percentage,
                    c.acos,
                    c.roas,
                    c.status
                FROM advertising_campaigns c
                WHERE c.status IN ('active', 'paused')
                ORDER BY c.roas DESC
            ");

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching ROI analysis: " . $e->getMessage());
            return [];
        }
    }
}
