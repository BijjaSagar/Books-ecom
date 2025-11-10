<?php
/**
 * Currency Helper Class
 * Handles currency conversion, formatting, and management
 *
 * Features:
 * - Multi-currency conversion
 * - Exchange rate management
 * - Currency formatting by locale
 * - Customer preference handling
 * - Real-time rate updates
 */

require_once __DIR__ . '/config.php';

class CurrencyHelper {
    private $conn;
    private $logger;
    private $currencies_cache = [];
    private $rates_cache = [];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = function($message, $level = 'info') {
            error_log("[CurrencyHelper] [$level] $message");
        };
        $this->loadCurrencies();
    }

    /**
     * Get default currency
     */
    public function getDefaultCurrency() {
        try {
            $sql = "SELECT * FROM currencies WHERE is_default = TRUE LIMIT 1";
            $result = $this->conn->query($sql);

            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }

            // Fallback to USD
            return $this->getCurrencyByCode('USD');

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting default currency: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Get currency by code
     */
    public function getCurrencyByCode($code) {
        try {
            // Check cache first
            if (isset($this->currencies_cache[$code])) {
                return $this->currencies_cache[$code];
            }

            $sql = "SELECT * FROM currencies WHERE currency_code = ? AND is_active = TRUE LIMIT 1";
            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $currency = $result->fetch_assoc();
                // Convert numeric fields
                $currency['exchange_rate_to_usd'] = floatval($currency['exchange_rate_to_usd']);
                $this->currencies_cache[$code] = $currency;
                return $currency;
            }

            $stmt->close();
            return null;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting currency: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Get all active currencies
     */
    public function getAllActiveCurrencies() {
        try {
            $sql = "SELECT * FROM currencies
                    WHERE is_active = TRUE
                    ORDER BY display_order ASC";

            $result = $this->conn->query($sql);

            if (!$result) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $currencies = [];
            while ($row = $result->fetch_assoc()) {
                $row['exchange_rate_to_usd'] = floatval($row['exchange_rate_to_usd']);
                $currencies[] = $row;
                $this->currencies_cache[$row['currency_code']] = $row;
            }

            return $currencies;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting currencies: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get customer's preferred currency
     */
    public function getCustomerPreferredCurrency($customer_id) {
        try {
            $sql = "SELECT c.* FROM currencies c
                    JOIN customer_currency_preferences ccp ON c.id = ccp.preferred_currency_id
                    WHERE ccp.customer_id = ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $currency = $result->fetch_assoc();
                $currency['exchange_rate_to_usd'] = floatval($currency['exchange_rate_to_usd']);
                return $currency;
            }

            $stmt->close();

            // Return default if no preference set
            return $this->getDefaultCurrency();

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting customer currency: " . $e->getMessage(), 'error');
            return $this->getDefaultCurrency();
        }
    }

    /**
     * Set customer's preferred currency
     */
    public function setCustomerPreferredCurrency($customer_id, $currency_code) {
        try {
            // Get currency ID
            $currency = $this->getCurrencyByCode($currency_code);
            if (!$currency) {
                return ['success' => false, 'error' => 'Invalid currency code'];
            }

            $sql = "INSERT INTO customer_currency_preferences (customer_id, preferred_currency_id)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE preferred_currency_id = ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $currency_id = $currency['id'];
            $stmt->bind_param('iii', $customer_id, $currency_id, $currency_id);
            $stmt->execute();
            $stmt->close();

            return ['success' => true, 'currency' => $currency];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error setting currency: " . $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Convert amount from one currency to another
     *
     * @param float $amount Amount to convert
     * @param string $from_code Source currency code
     * @param string $to_code Target currency code
     * @param bool $include_markup Include any conversion fee/markup
     * @return array ['success' => bool, 'converted_amount' => float, 'rate' => float, 'fee' => float]
     */
    public function convertCurrency($amount, $from_code, $to_code, $include_markup = true) {
        try {
            // If same currency, no conversion needed
            if ($from_code === $to_code) {
                return [
                    'success' => true,
                    'converted_amount' => floatval($amount),
                    'rate' => 1.0,
                    'fee' => 0,
                    'from_currency' => $from_code,
                    'to_currency' => $to_code
                ];
            }

            // Get currencies
            $from_currency = $this->getCurrencyByCode($from_code);
            $to_currency = $this->getCurrencyByCode($to_code);

            if (!$from_currency || !$to_currency) {
                return ['success' => false, 'error' => 'Invalid currency codes'];
            }

            // Get conversion rate
            $rate = $this->getConversionRate($from_code, $to_code);

            if ($rate === null) {
                return ['success' => false, 'error' => 'Conversion rate not available'];
            }

            // Calculate converted amount
            $converted_amount = $amount * $rate;

            // Check for custom rules/markup
            $fee = 0;
            if ($include_markup) {
                $markup_info = $this->getConversionMarkup($amount, $from_code, $to_code);
                if ($markup_info) {
                    $fee = $markup_info['fee'];
                    $converted_amount += $fee;
                }
            }

            return [
                'success' => true,
                'original_amount' => floatval($amount),
                'converted_amount' => round($converted_amount, $to_currency['decimal_places']),
                'rate' => $rate,
                'fee' => round($fee, $to_currency['decimal_places']),
                'from_currency' => $from_code,
                'to_currency' => $to_code,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error converting currency: " . $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get conversion rate between two currencies
     */
    private function getConversionRate($from_code, $to_code) {
        try {
            // Check cache first
            $cache_key = $from_code . '_to_' . $to_code;
            if (isset($this->rates_cache[$cache_key])) {
                return $this->rates_cache[$cache_key];
            }

            $from = $this->getCurrencyByCode($from_code);
            $to = $this->getCurrencyByCode($to_code);

            if (!$from || !$to) {
                return null;
            }

            // Calculate rate: (to_currency_rate / from_currency_rate)
            $rate = floatval($to['exchange_rate_to_usd']) / floatval($from['exchange_rate_to_usd']);

            // Cache the rate
            $this->rates_cache[$cache_key] = $rate;

            return $rate;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error getting rate: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Get conversion markup/fee for specific amount
     */
    private function getConversionMarkup($amount, $from_code, $to_code) {
        try {
            $from = $this->getCurrencyByCode($from_code);
            $to = $this->getCurrencyByCode($to_code);

            $sql = "SELECT * FROM currency_conversion_rules
                    WHERE from_currency_id = ? AND to_currency_id = ?
                    AND is_active = TRUE
                    AND (effective_from IS NULL OR effective_from <= NOW())
                    AND (effective_to IS NULL OR effective_to >= NOW())
                    AND (applies_to_amount_range_min IS NULL OR applies_to_amount_range_min <= ?)
                    AND (applies_to_amount_range_max IS NULL OR applies_to_amount_range_max >= ?)
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return null;
            }

            $stmt->bind_param('iiff', $from['id'], $to['id'], $amount, $amount);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt->close();
                return null;
            }

            $rule = $result->fetch_assoc();
            $stmt->close();

            // Calculate fee based on rule type
            $fee = 0;
            switch ($rule['rule_type']) {
                case 'fixed':
                    $fee = floatval($rule['conversion_rate']);
                    break;
                case 'percentage_markup':
                    $fee = ($amount * floatval($rule['markup_percentage'])) / 100;
                    break;
                case 'percentage_discount':
                    $fee = -1 * (($amount * floatval($rule['discount_percentage'])) / 100);
                    break;
            }

            return ['fee' => $fee, 'rule_type' => $rule['rule_type']];

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Format amount in specific currency
     *
     * @param float $amount Amount to format
     * @param string $currency_code Currency code
     * @param bool $include_symbol Include symbol in output
     * @return string Formatted amount
     */
    public function formatAmount($amount, $currency_code = 'USD', $include_symbol = true) {
        try {
            $currency = $this->getCurrencyByCode($currency_code);

            if (!$currency) {
                $currency = $this->getDefaultCurrency();
            }

            // Format number with thousands separator and decimals
            $formatted = number_format(
                floatval($amount),
                intval($currency['decimal_places']),
                $currency['decimal_separator'],
                $currency['thousands_separator']
            );

            if (!$include_symbol) {
                return $formatted;
            }

            // Add symbol based on position
            $symbol = $currency['currency_symbol'];
            switch ($currency['symbol_position']) {
                case 'before':
                    return $symbol . $formatted;
                case 'before_space':
                    return $symbol . ' ' . $formatted;
                case 'after':
                    return $formatted . $symbol;
                case 'after_space':
                    return $formatted . ' ' . $symbol;
                default:
                    return $symbol . $formatted;
            }

        } catch (Exception $e) {
            call_user_func($this->logger, "Error formatting amount: " . $e->getMessage(), 'error');
            return number_format($amount, 2);
        }
    }

    /**
     * Get currency by customer location (IP-based or browser locale)
     */
    public function detectCurrencyByLocation($country_code = null, $ip_address = null) {
        try {
            if (!$country_code && $ip_address) {
                // Use GeoIP to get country
                $country_code = $this->getCountryFromIP($ip_address);
            }

            if (!$country_code) {
                return $this->getDefaultCurrency();
            }

            // Find currency by country code
            $sql = "SELECT * FROM currencies
                    WHERE country_code = ? AND is_active = TRUE
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return $this->getDefaultCurrency();
            }

            $stmt->bind_param('s', $country_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $currency = $result->fetch_assoc();
                $currency['exchange_rate_to_usd'] = floatval($currency['exchange_rate_to_usd']);
                return $currency;
            }

            $stmt->close();
            return $this->getDefaultCurrency();

        } catch (Exception $e) {
            return $this->getDefaultCurrency();
        }
    }

    /**
     * Get country from IP address (placeholder - implement with GeoIP library)
     */
    private function getCountryFromIP($ip_address) {
        // This is a placeholder - implement with MaxMind GeoIP2 or similar
        // For now, return null to fall back to default currency
        return null;
    }

    /**
     * Update exchange rates from external API
     */
    public function updateExchangeRates($api_provider = 'Open Exchange Rates') {
        try {
            // Get API configuration
            $sql = "SELECT * FROM currency_api_configs
                    WHERE provider_name = ? AND is_active = TRUE";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error");
            }

            $stmt->bind_param('s', $api_provider);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'error' => 'API provider not configured'];
            }

            $api_config = $result->fetch_assoc();
            $stmt->close();

            // Fetch rates from API
            $rates = $this->fetchRatesFromAPI($api_config);

            if (!$rates) {
                return ['success' => false, 'error' => 'Failed to fetch rates from API'];
            }

            // Update database
            $updated_count = 0;
            foreach ($rates as $code => $rate) {
                $sql = "UPDATE currencies
                        SET exchange_rate_to_usd = ?, last_rate_update = NOW()
                        WHERE currency_code = ?";

                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('ds', $rate, $code);
                    if ($stmt->execute()) {
                        $updated_count++;
                    }
                    $stmt->close();
                }
            }

            // Log the update
            $this->logRateUpdate($api_config['id'], $rates, $updated_count);

            call_user_func($this->logger, "Updated $updated_count exchange rates");

            return [
                'success' => true,
                'updated_count' => $updated_count,
                'rates' => $rates
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error updating rates: " . $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch rates from external API
     */
    private function fetchRatesFromAPI($api_config) {
        try {
            $url = $api_config['api_endpoint'];

            // Add API key if available
            if (!empty($api_config['api_key'])) {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . 'app_id=' . $api_config['api_key'];
            }

            // Make request (using file_get_contents with stream context for simplicity)
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'header' => 'User-Agent: Books-Ecommerce/1.0'
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);

            if (!$data) {
                return null;
            }

            // Extract rates (structure varies by provider)
            if (isset($data['rates'])) {
                return $data['rates'];
            }

            return null;

        } catch (Exception $e) {
            call_user_func($this->logger, "Error fetching from API: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Log rate update to history
     */
    private function logRateUpdate($api_config_id, $rates, $count) {
        try {
            // Store historical rates for each currency
            foreach ($rates as $code => $rate) {
                $currency = $this->getCurrencyByCode($code);
                if ($currency) {
                    $sql = "INSERT INTO currency_rates_history
                            (currency_id, exchange_rate_to_usd, rate_source, data_provider, rate_timestamp)
                            VALUES (?, ?, ?, ?, NOW())";

                    $stmt = $this->conn->prepare($sql);
                    $source = 'API Update';
                    $provider = 'Rate API';

                    $stmt->bind_param('idss', $currency['id'], $rate, $source, $provider);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            // Silently fail on history logging
        }
    }

    /**
     * Load all currencies into cache
     */
    private function loadCurrencies() {
        $this->getAllActiveCurrencies();
    }

    /**
     * Get currency summary for admin dashboard
     */
    public function getCurrencySummary() {
        try {
            $sql = "SELECT
                        COUNT(*) as total_currencies,
                        SUM(CASE WHEN is_default = TRUE THEN 1 ELSE 0 END) as default_currencies,
                        SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_currencies,
                        MAX(last_rate_update) as last_rate_update
                    FROM currencies";

            $result = $this->conn->query($sql);

            if ($result) {
                return $result->fetch_assoc();
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }
}
?>
