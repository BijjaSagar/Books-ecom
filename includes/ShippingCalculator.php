<?php
/**
 * Shipping Calculator Engine
 * Calculates applicable shipping methods and costs
 *
 * Features:
 * - Zone-based shipping calculation
 * - Multiple shipping methods per zone
 * - Weight-based, quantity-based, order-total-based rates
 * - Shipping rules & restrictions
 * - Real-time availability checking
 */

require_once __DIR__ . '/config.php';

class ShippingCalculator {
    private $conn;
    private $logger;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = function($message, $level = 'info') {
            error_log("[ShippingCalculator] [$level] $message");
        };
    }

    /**
     * Get available shipping methods for order
     *
     * @param array $shippingData {
     *     'destination' => {
     *         'country' => string (2-char code),
     *         'state_province' => string,
     *         'postal_code' => string,
     *         'city' => string
     *     },
     *     'items' => [
     *         { 'product_id' => int, 'quantity' => int, 'weight_kg' => float }
     *     ],
     *     'subtotal' => float,
     *     'customer_id' => int (optional)
     * }
     *
     * @return array {
     *     'success' => bool,
     *     'available_methods' => array of shipping methods,
     *     'zone_id' => int,
     *     'zone_name' => string,
     *     'default_method' => array
     * }
     */
    public function getAvailableShippingMethods($shippingData) {
        try {
            $destination = $shippingData['destination'];
            $items = $shippingData['items'] ?? [];
            $subtotal = floatval($shippingData['subtotal']);

            // ============================================================
            // STEP 1: Find applicable shipping zone
            // ============================================================

            $zone = $this->findShippingZone($destination);

            if (!$zone) {
                return [
                    'success' => false,
                    'error' => 'No shipping available to this location'
                ];
            }

            // ============================================================
            // STEP 2: Get shipping methods for this zone
            // ============================================================

            $available_methods = $this->getMethodsForZone(
                $zone['id'],
                $items,
                $subtotal
            );

            if (empty($available_methods)) {
                return [
                    'success' => false,
                    'error' => 'No shipping methods available for this location and items'
                ];
            }

            // ============================================================
            // STEP 3: Calculate costs for each method
            // ============================================================

            $calculated_methods = [];
            $default_method = null;

            foreach ($available_methods as $method) {
                $cost_info = $this->calculateShippingCost($method, $items, $subtotal);

                $method['base_price'] = floatval($method['base_price']);
                $method['calculated_cost'] = round($cost_info['total_cost'], 2);
                $method['cost_breakdown'] = $cost_info['breakdown'];
                $method['estimated_delivery'] = $cost_info['estimated_delivery'];

                $calculated_methods[] = $method;

                // Mark first active method as default
                if (!$default_method) {
                    $default_method = $method;
                }
            }

            // Sort by cost (cheaper first)
            usort($calculated_methods, function($a, $b) {
                return $a['calculated_cost'] <=> $b['calculated_cost'];
            });

            return [
                'success' => true,
                'zone_id' => $zone['id'],
                'zone_name' => $zone['name'],
                'available_methods' => $calculated_methods,
                'default_method' => $default_method,
                'shipping_to' => $destination['city'] . ', ' . $destination['state_province'] . ' ' . $destination['postal_code'] . ', ' . $destination['country']
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Find the applicable shipping zone for a destination
     */
    private function findShippingZone($destination) {
        try {
            $country = $destination['country'];
            $state = $destination['state_province'] ?? null;
            $postal = $destination['postal_code'] ?? null;

            // Query zones in priority order
            $sql = "SELECT * FROM shipping_zones
                    WHERE is_active = TRUE
                    ORDER BY priority ASC";

            $result = $this->conn->query($sql);

            if (!$result) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            while ($zone = $result->fetch_assoc()) {
                // Check if destination matches zone
                if ($this->matchesZone($destination, $zone)) {
                    return $zone;
                }
            }

            // Return default zone if exists
            $default_zone = $this->conn->query(
                "SELECT * FROM shipping_zones WHERE is_default_zone = TRUE LIMIT 1"
            );

            if ($default_zone && $default_zone->num_rows > 0) {
                return $default_zone->fetch_assoc();
            }

            return null;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error finding zone: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Check if destination matches zone criteria
     */
    private function matchesZone($destination, $zone) {
        $country = $destination['country'];
        $state = $destination['state_province'] ?? null;
        $postal = $destination['postal_code'] ?? null;
        $city = $destination['city'] ?? null;

        // Parse zone criteria
        $zone_countries = json_decode($zone['countries'], true) ?? [];
        $zone_states = json_decode($zone['states'], true) ?? [];
        $zone_cities = json_decode($zone['cities'], true) ?? [];
        $exclude_areas = json_decode($zone['exclude_areas'], true) ?? [];

        // Check excluded areas first
        if (in_array($country, $exclude_areas)) {
            return false;
        }

        // Check zone type
        switch ($zone['zone_type']) {
            case 'country':
                return in_array($country, $zone_countries) || in_array('*', $zone_countries);

            case 'state':
                if (!in_array($country, $zone_countries)) {
                    return false;
                }
                return empty($zone_states) || in_array($state, $zone_states) || in_array('*', $zone_states);

            case 'city':
                return empty($zone_cities) || in_array($city, $zone_cities) || in_array('*', $zone_cities);

            case 'postal_code':
                if ($zone['postal_codes_pattern']) {
                    return preg_match('/' . $zone['postal_codes_pattern'] . '/i', $postal);
                }
                return true;

            case 'custom':
                return in_array('*', $zone_countries);

            default:
                return false;
        }
    }

    /**
     * Get shipping methods available for zone
     */
    private function getMethodsForZone($zone_id, $items, $subtotal) {
        try {
            $sql = "SELECT sm.*, sr.* FROM shipping_methods sm
                    LEFT JOIN shipping_rates sr ON sm.id = sr.shipping_method_id
                    WHERE sm.shipping_zone_id = ?
                    AND sm.is_active = TRUE
                    ORDER BY sm.display_order ASC";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $stmt->bind_param('i', $zone_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $methods = [];
            $seen_methods = [];

            while ($row = $result->fetch_assoc()) {
                $method_id = $row['id'];

                // Avoid duplicates
                if (in_array($method_id, $seen_methods)) {
                    continue;
                }
                $seen_methods[] = $method_id;

                // Check if method meets restrictions
                if (!$this->meetsShippingRules($method_id, $items, $subtotal)) {
                    continue;
                }

                $methods[] = [
                    'id' => $method_id,
                    'name' => $row['name'],
                    'shipping_type' => $row['shipping_type'],
                    'provider' => $row['provider'],
                    'base_price' => $row['base_price'],
                    'min_days' => $row['min_days'],
                    'max_days' => $row['max_days'],
                    'provides_tracking' => (bool)$row['provides_tracking']
                ];
            }

            $stmt->close();
            return $methods;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting methods: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Check if order meets shipping rules
     */
    private function meetsShippingRules($shipping_method_id, $items, $subtotal) {
        try {
            $sql = "SELECT * FROM shipping_rules
                    WHERE shipping_method_id = ?
                    AND is_active = TRUE";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $shipping_method_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($rule = $result->fetch_assoc()) {
                // Check order value
                if ($rule['min_order_value'] && $subtotal < floatval($rule['min_order_value'])) {
                    $stmt->close();
                    return false;
                }
                if ($rule['max_order_value'] && $subtotal > floatval($rule['max_order_value'])) {
                    $stmt->close();
                    return false;
                }

                // Check product types
                if ($rule['applicable_product_types']) {
                    $applicable_types = json_decode($rule['applicable_product_types'], true);
                    // For now, allow all (can be enhanced)
                }
            }

            $stmt->close();
            return true;

        } catch (Exception $e) {
            return true; // Allow if can't check
        }
    }

    /**
     * Calculate shipping cost for a method
     */
    private function calculateShippingCost($method, $items, $subtotal) {
        try {
            $total_weight = 0;
            $total_quantity = 0;
            $handling_fee = 0;
            $cod_fee = 0;

            // Calculate totals from items
            foreach ($items as $item) {
                $total_weight += floatval($item['weight_kg'] ?? 0) * intval($item['quantity'] ?? 1);
                $total_quantity += intval($item['quantity'] ?? 1);
            }

            // Get rates for this method
            $sql = "SELECT * FROM shipping_rates
                    WHERE shipping_method_id = ?
                    AND is_active = TRUE
                    ORDER BY priority ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $method['id']);
            $stmt->execute();
            $result = $stmt->get_result();

            $base_cost = floatval($method['base_price'] ?? 0);
            $rate_cost = 0;
            $breakdown = [];

            // Check for free shipping
            if ($method['free_shipping_threshold'] && $subtotal >= floatval($method['free_shipping_threshold'])) {
                return [
                    'total_cost' => 0,
                    'breakdown' => [['type' => 'Free Shipping (threshold met)', 'amount' => 0]],
                    'estimated_delivery' => $this->getEstimatedDelivery($method)
                ];
            }

            while ($rate = $result->fetch_assoc()) {
                // Calculate based on rate type
                switch ($rate['rate_type']) {
                    case 'weight_based':
                        if ($total_weight > 0) {
                            $rate_cost += $total_weight * floatval($rate['weight_rate_per_kg'] ?? 0);
                        }
                        break;

                    case 'quantity_based':
                        $rate_cost += $total_quantity * floatval($rate['quantity_rate_per_item'] ?? 0);
                        break;

                    case 'order_total':
                        if ($subtotal >= floatval($rate['min_order_total'] ?? 0)) {
                            $rate_cost += floatval($rate['order_total_rate'] ?? 0);
                        }
                        break;

                    case 'tiered':
                        // For tiered rates
                        if ($total_quantity >= intval($rate['min_quantity'] ?? 0) &&
                            ($rate['max_quantity'] === null || $total_quantity <= intval($rate['max_quantity']))) {
                            $rate_cost += floatval($rate['rate_value'] ?? 0);
                        }
                        break;

                    case 'flat':
                    default:
                        $rate_cost += floatval($rate['rate_value'] ?? 0);
                }

                // Add additional charges
                $cod_fee += floatval($rate['cod_fee'] ?? 0);
                $handling_fee += floatval($rate['handling_fee'] ?? 0);
            }

            $stmt->close();

            $total_cost = $base_cost + $rate_cost + $handling_fee + $cod_fee;

            // Build breakdown
            if ($base_cost > 0) {
                $breakdown[] = ['type' => 'Base Shipping', 'amount' => round($base_cost, 2)];
            }
            if ($rate_cost > 0) {
                $breakdown[] = ['type' => 'Rate Adjustment', 'amount' => round($rate_cost, 2)];
            }
            if ($handling_fee > 0) {
                $breakdown[] = ['type' => 'Handling Fee', 'amount' => round($handling_fee, 2)];
            }
            if ($cod_fee > 0) {
                $breakdown[] = ['type' => 'COD Fee', 'amount' => round($cod_fee, 2)];
            }

            return [
                'total_cost' => round($total_cost, 2),
                'breakdown' => $breakdown,
                'estimated_delivery' => $this->getEstimatedDelivery($method)
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error calculating cost: " . $e->getMessage(), 'error');
            return [
                'total_cost' => floatval($method['base_price'] ?? 0),
                'breakdown' => [['type' => 'Base Shipping', 'amount' => $method['base_price']]],
                'estimated_delivery' => $this->getEstimatedDelivery($method)
            ];
        }
    }

    /**
     * Get estimated delivery date for method
     */
    private function getEstimatedDelivery($method) {
        try {
            $min_days = intval($method['min_days'] ?? 1);
            $max_days = intval($method['max_days'] ?? 5);

            $from_date = new DateTime('now');
            $to_date = new DateTime('now');

            // Add business days (assuming Mon-Fri)
            $business_days = 0;
            $current = clone $from_date;

            while ($business_days < $max_days) {
                $current->modify('+1 day');
                $day = $current->format('N');
                if ($day < 6) {
                    $business_days++;
                }
            }

            return [
                'min_date' => (new DateTime('now'))->modify("+$min_days days")->format('Y-m-d'),
                'max_date' => (new DateTime('now'))->modify("+$max_days days")->format('Y-m-d'),
                'min_days' => $min_days,
                'max_days' => $max_days
            ];

        } catch (Exception $e) {
            return [
                'min_date' => (new DateTime('now'))->modify("+1 days")->format('Y-m-d'),
                'max_date' => (new DateTime('now'))->modify("+5 days")->format('Y-m-d'),
                'min_days' => 1,
                'max_days' => 5
            ];
        }
    }

    /**
     * Save shipping selection to order
     */
    public function saveShippingToOrder($order_id, $shipping_method_id, $shipping_cost) {
        try {
            $sql = "INSERT INTO order_shipping_details (
                order_id, shipping_method_id, total_shipping_cost
            ) VALUES (?, ?, ?)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('iid', $order_id, $shipping_method_id, $shipping_cost);
            $stmt->execute();
            $stmt->close();

            return true;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error saving shipping: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get shipping summary for admin dashboard
     */
    public function getShippingSummary($startDate = null, $endDate = null) {
        try {
            $sql = "SELECT
                        COUNT(DISTINCT order_id) as total_orders,
                        SUM(total_shipping_cost) as total_shipping_revenue,
                        AVG(total_shipping_cost) as average_shipping_cost,
                        MAX(total_shipping_cost) as highest_shipping_cost,
                        COUNT(DISTINCT shipping_method_id) as methods_used
                    FROM order_shipping_details
                    WHERE 1=1";

            $params = [];
            $types = '';

            if ($startDate) {
                $sql .= " AND created_at >= ?";
                $params[] = $startDate;
                $types .= 's';
            }

            if ($endDate) {
                $sql .= " AND created_at <= ?";
                $params[] = $endDate;
                $types .= 's';
            }

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $summary = $result->fetch_assoc();
            $stmt->close();

            return $summary;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting summary: " . $e->getMessage(), 'error');
            return null;
        }
    }
}
?>
