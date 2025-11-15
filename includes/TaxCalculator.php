<?php
/**
 * Tax Calculator Engine
 * Calculates applicable taxes based on customer location, product type, etc.
 *
 * Features:
 * - Multi-region tax calculation
 * - Sales Tax, VAT, GST support
 * - Compound taxation
 * - Tax exemptions
 * - Historical tax rates
 */

require_once __DIR__ . '/config.php';

class TaxCalculator {
    private $conn;
    private $logger;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = function($message, $level = 'info') {
            error_log("[TaxCalculator] [$level] $message");
        };
    }

    /**
     * Calculate taxes for order
     *
     * @param array $orderData {
     *     'customer_id' => int,
     *     'shipping_address' => {
     *         'country' => string (2-char code),
     *         'state_province' => string,
     *         'postal_code' => string,
     *         'city' => string
     *     },
     *     'items' => [
     *         { 'product_id' => int, 'quantity' => int, 'price' => float, 'type' => string },
     *     ],
     *     'subtotal' => float,
     *     'customer_type' => string ('individual', 'business', 'nonprofit'),
     *     'tax_exempt_id' => string (optional)
     * }
     *
     * @return array {
     *     'success' => bool,
     *     'total_tax' => float,
     *     'tax_breakdown' => array of taxes applied,
     *     'taxable_amount' => float,
     *     'exempt_amount' => float,
     *     'effective_tax_rate' => float
     * }
     */
    public function calculateOrderTax($orderData) {
        try {
            // Extract address
            $address = $orderData['shipping_address'];
            $subtotal = floatval($orderData['subtotal']);
            $items = $orderData['items'] ?? [];
            $customer_type = $orderData['customer_type'] ?? 'individual';

            // ============================================================
            // STEP 1: Check for tax exemptions
            // ============================================================

            $is_tax_exempt = $this->checkTaxExemption(
                $orderData['customer_id'] ?? null,
                $address,
                $items,
                $orderData['tax_exempt_id'] ?? null
            );

            if ($is_tax_exempt) {
                return [
                    'success' => true,
                    'total_tax' => 0,
                    'tax_breakdown' => [],
                    'taxable_amount' => 0,
                    'exempt_amount' => $subtotal,
                    'effective_tax_rate' => 0,
                    'exemption_reason' => $is_tax_exempt['reason']
                ];
            }

            // ============================================================
            // STEP 2: Separate taxable and exempt items
            // ============================================================

            $taxable_amount = 0;
            $exempt_amount = 0;
            $item_tax_details = [];

            foreach ($items as $item) {
                $item_subtotal = floatval($item['price']) * intval($item['quantity']);

                // Check if item is tax-exempt
                $item_exempt = $this->isProductTaxExempt(
                    $item['product_id'],
                    $address,
                    $customer_type
                );

                if ($item_exempt) {
                    $exempt_amount += $item_subtotal;
                } else {
                    $taxable_amount += $item_subtotal;
                }

                $item_tax_details[] = [
                    'product_id' => $item['product_id'],
                    'amount' => $item_subtotal,
                    'is_exempt' => $item_exempt
                ];
            }

            // If no taxable amount, return zero tax
            if ($taxable_amount == 0) {
                return [
                    'success' => true,
                    'total_tax' => 0,
                    'tax_breakdown' => [],
                    'taxable_amount' => 0,
                    'exempt_amount' => $exempt_amount,
                    'effective_tax_rate' => 0
                ];
            }

            // ============================================================
            // STEP 3: Get applicable tax rates for this location
            // ============================================================

            $applicable_taxes = $this->getApplicableTaxRates(
                $address['country'],
                $address['state_province'] ?? null,
                $items
            );

            if (empty($applicable_taxes)) {
                return [
                    'success' => true,
                    'total_tax' => 0,
                    'tax_breakdown' => [],
                    'taxable_amount' => $taxable_amount,
                    'exempt_amount' => $exempt_amount,
                    'effective_tax_rate' => 0,
                    'note' => 'No applicable taxes for this location'
                ];
            }

            // ============================================================
            // STEP 4: Calculate taxes
            // ============================================================

            $total_tax = 0;
            $tax_breakdown = [];

            // Sort by priority (lower priority applies first)
            usort($applicable_taxes, function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });

            $base_amount = $taxable_amount;

            foreach ($applicable_taxes as $tax_rule) {
                // Determine tax base (compound or sequential)
                $tax_base = $tax_rule['is_compound']
                    ? ($base_amount + $total_tax)
                    : $base_amount;

                // Calculate tax
                $tax_amount = round(
                    $tax_base * ($tax_rule['tax_percentage'] / 100),
                    2
                );

                $total_tax += $tax_amount;

                $tax_breakdown[] = [
                    'tax_id' => $tax_rule['id'],
                    'tax_name' => $tax_rule['name'],
                    'tax_type' => $tax_rule['tax_type'],
                    'rate' => $tax_rule['tax_percentage'],
                    'amount' => $tax_amount,
                    'is_compound' => $tax_rule['is_compound'],
                    'region' => $tax_rule['country'] . ($tax_rule['state_province'] ? ' - ' . $tax_rule['state_province'] : '')
                ];

                // Log calculation
                call_user_func($this->logger, sprintf(
                    "Tax: %s (%.2f%%) = $%.2f (compound: %s)",
                    $tax_rule['name'],
                    $tax_rule['tax_percentage'],
                    $tax_amount,
                    $tax_rule['is_compound'] ? 'yes' : 'no'
                ));
            }

            // ============================================================
            // STEP 5: Calculate effective tax rate
            // ============================================================

            $effective_rate = $taxable_amount > 0
                ? ($total_tax / $taxable_amount) * 100
                : 0;

            return [
                'success' => true,
                'total_tax' => round($total_tax, 2),
                'tax_breakdown' => $tax_breakdown,
                'taxable_amount' => $taxable_amount,
                'exempt_amount' => $exempt_amount,
                'effective_tax_rate' => round($effective_rate, 2),
                'item_details' => $item_tax_details
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
     * Get applicable tax rates for a location
     *
     * @param string $country 2-char country code
     * @param string|null $state_province State/province name
     * @param array $items Products being purchased
     * @return array List of applicable tax rates
     */
    private function getApplicableTaxRates($country, $state_province = null, $items = []) {
        try {
            // Determine product types
            $product_types = [];
            foreach ($items as $item) {
                $product_types[] = $item['type'] ?? 'products';
            }
            $product_types = array_unique($product_types);

            // Query applicable taxes
            $sql = "SELECT * FROM tax_rates
                    WHERE is_active = TRUE
                    AND country = ?
                    AND (state_province = ? OR state_province IS NULL)
                    AND (applies_to IN ('all'";

            foreach ($product_types as $type) {
                $sql .= ", '" . $this->conn->real_escape_string($type) . "'";
            }

            $sql .= "))
                    AND (effective_from <= CURDATE())
                    AND (effective_to IS NULL OR effective_to >= CURDATE())
                    ORDER BY priority ASC";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $stmt->bind_param('ss', $country, $state_province);
            $stmt->execute();
            $result = $stmt->get_result();

            $taxes = [];
            while ($row = $result->fetch_assoc()) {
                $taxes[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'tax_type' => $row['tax_type'],
                    'country' => $row['country'],
                    'state_province' => $row['state_province'],
                    'tax_percentage' => floatval($row['tax_percentage']),
                    'is_compound' => (bool)$row['is_compound'],
                    'priority' => (int)$row['priority']
                ];
            }

            $stmt->close();
            return $taxes;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting rates: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Check if order or customer is tax exempt
     */
    private function checkTaxExemption($customer_id, $address, $items, $exempt_id) {
        try {
            // Check for customer exemption
            if ($customer_id) {
                $sql = "SELECT * FROM tax_exemptions
                        WHERE exemption_type = 'customer_type'
                        AND is_active = TRUE
                        AND (
                            (countries_applicable IS NULL OR JSON_CONTAINS(countries_applicable, ?))
                            OR countries_applicable = JSON_ARRAY('*')
                        )
                        LIMIT 1";

                $stmt = $this->conn->prepare($sql);
                $country_json = json_encode($address['country']);
                $stmt->bind_param('s', $country_json);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $exemption = $result->fetch_assoc();
                    return [
                        'is_exempt' => true,
                        'reason' => 'Customer tax exemption: ' . $exemption['tax_exempt_reason']
                    ];
                }
                $stmt->close();
            }

            // Check exemption by ID (certificate, etc.)
            if ($exempt_id) {
                $sql = "SELECT * FROM tax_exemptions
                        WHERE tax_exempt_certificate = ?
                        AND is_active = TRUE
                        AND (tax_exempt_certificate_expiry IS NULL OR tax_exempt_certificate_expiry >= CURDATE())
                        LIMIT 1";

                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('s', $exempt_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    return [
                        'is_exempt' => true,
                        'reason' => 'Valid tax exemption certificate'
                    ];
                }
                $stmt->close();
            }

            return null;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error checking exemption: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Check if a product is tax-exempt
     */
    private function isProductTaxExempt($product_id, $address, $customer_type) {
        try {
            // Check for product exemption
            $sql = "SELECT * FROM tax_exemptions
                    WHERE exemption_type = 'product'
                    AND entity_id = ?
                    AND is_active = TRUE
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $stmt->close();
            return $result->num_rows > 0;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Save tax calculation to database
     */
    public function saveTaxCalculation($order_id, $tax_result) {
        try {
            $sql = "INSERT INTO order_tax_details (
                order_id, subtotal_before_tax, subtotal_taxable,
                subtotal_exempt, total_tax, tax_applied
            ) VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);
            $tax_applied_json = json_encode($tax_result['tax_breakdown']);

            $stmt->bind_param('idddds',
                $order_id,
                $tax_result['subtotal_before_tax'] ?? $tax_result['taxable_amount'],
                $tax_result['taxable_amount'],
                $tax_result['exempt_amount'],
                $tax_result['total_tax'],
                $tax_applied_json
            );

            $stmt->execute();
            $stmt->close();

            return true;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error saving tax: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get tax summary for admin dashboard
     */
    public function getTaxSummary($startDate = null, $endDate = null, $country = null) {
        try {
            $sql = "SELECT
                        SUM(total_tax) as total_tax_collected,
                        COUNT(DISTINCT order_id) as total_orders,
                        AVG(total_tax) as average_tax_per_order,
                        SUM(subtotal_before_tax) as total_sales,
                        (SUM(total_tax) / SUM(subtotal_before_tax)) * 100 as overall_tax_rate
                    FROM order_tax_details otd
                    JOIN orders o ON otd.order_id = o.id
                    WHERE 1=1";

            $params = [];
            $types = '';

            if ($startDate) {
                $sql .= " AND o.created_at >= ?";
                $params[] = $startDate;
                $types .= 's';
            }

            if ($endDate) {
                $sql .= " AND o.created_at <= ?";
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
