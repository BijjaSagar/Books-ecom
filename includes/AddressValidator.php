<?php
/**
 * Address Validator Class
 * Validates shipping and billing addresses
 *
 * Features:
 * - Format validation
 * - Country/state verification
 * - Postal code validation
 * - Address standardization
 * - Duplicate detection
 */

require_once __DIR__ . '/config.php';

class AddressValidator {
    private $conn;
    private $logger;
    private $valid_countries = [
        'US' => 'United States',
        'CA' => 'Canada',
        'UK' => 'United Kingdom',
        'AU' => 'Australia',
        'DE' => 'Germany',
        'FR' => 'France',
        'IT' => 'Italy',
        'ES' => 'Spain',
        'NL' => 'Netherlands',
        'BE' => 'Belgium',
        'IN' => 'India',
        'AE' => 'United Arab Emirates',
        'SA' => 'Saudi Arabia',
        'SG' => 'Singapore',
        'JP' => 'Japan',
        // Add more as needed
    ];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->logger = function($message, $level = 'info') {
            error_log("[AddressValidator] [$level] $message");
        };
    }

    /**
     * Validate address
     *
     * @param array $address {
     *     'first_name' => string,
     *     'last_name' => string,
     *     'company_name' => string (optional),
     *     'street_address_1' => string,
     *     'street_address_2' => string (optional),
     *     'city' => string,
     *     'state_province' => string,
     *     'postal_code' => string,
     *     'country' => string (2-char code),
     *     'phone_number' => string (optional),
     *     'email' => string (optional)
     * }
     *
     * @return array {
     *     'valid' => bool,
     *     'errors' => array,
     *     'warnings' => array,
     *     'standardized' => array
     * }
     */
    public function validateAddress($address) {
        $errors = [];
        $warnings = [];
        $standardized = $address;

        // Required fields
        $required_fields = ['first_name', 'last_name', 'street_address_1', 'city', 'state_province', 'postal_code', 'country'];
        foreach ($required_fields as $field) {
            if (empty($address[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }

        // Validate individual fields
        if (!empty($address['first_name'])) {
            if (strlen($address['first_name']) < 2) {
                $errors[] = "First name must be at least 2 characters";
            }
            if (strlen($address['first_name']) > 100) {
                $errors[] = "First name is too long (max 100 characters)";
            }
        }

        if (!empty($address['last_name'])) {
            if (strlen($address['last_name']) < 2) {
                $errors[] = "Last name must be at least 2 characters";
            }
            if (strlen($address['last_name']) > 100) {
                $errors[] = "Last name is too long (max 100 characters)";
            }
        }

        // Email validation
        if (!empty($address['email'])) {
            if (!filter_var($address['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email address";
            }
        }

        // Phone validation
        if (!empty($address['phone_number'])) {
            // Remove common formatting characters
            $phone = preg_replace('/[^0-9+\-\s]/', '', $address['phone_number']);
            if (strlen($phone) < 10) {
                $errors[] = "Phone number is too short";
            }
            if (strlen($phone) > 20) {
                $errors[] = "Phone number is too long";
            }
            $standardized['phone_number'] = $phone;
        }

        // Country validation
        if (!empty($address['country'])) {
            $country_code = strtoupper($address['country']);
            if (!isset($this->valid_countries[$country_code])) {
                $errors[] = "Invalid country code: $country_code";
            } else {
                $standardized['country'] = $country_code;
            }
        }

        // Postal code validation
        if (!empty($address['postal_code']) && !empty($address['country'])) {
            $postal_valid = $this->validatePostalCode(
                $address['postal_code'],
                strtoupper($address['country'])
            );

            if (!$postal_valid['valid']) {
                $errors[] = $postal_valid['error'];
            }
        }

        // Standardize state/province
        if (!empty($address['state_province']) && !empty($address['country'])) {
            $standardized['state_province'] = $this->standardizeState(
                $address['state_province'],
                strtoupper($address['country'])
            );
        }

        // Address length validation
        if (!empty($address['street_address_1'])) {
            if (strlen($address['street_address_1']) < 5) {
                $errors[] = "Street address is too short";
            }
            if (strlen($address['street_address_1']) > 255) {
                $errors[] = "Street address is too long";
            }
        }

        // City validation
        if (!empty($address['city'])) {
            if (strlen($address['city']) < 2) {
                $errors[] = "City name is too short";
            }
            if (strlen($address['city']) > 100) {
                $errors[] = "City name is too long";
            }
        }

        // Duplicate address detection
        if (!empty($address['customer_id'])) {
            $duplicate = $this->findDuplicateAddress($address);
            if ($duplicate) {
                $warnings[] = "Similar address already exists in your account";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'standardized' => $standardized
        ];
    }

    /**
     * Validate postal code format for country
     */
    private function validatePostalCode($postal_code, $country) {
        $postal_code = trim(strtoupper($postal_code));

        $patterns = [
            'US' => '/^\d{5}(-\d{4})?$/', // 12345 or 12345-6789
            'CA' => '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/', // A1A 1A1
            'UK' => '/^[A-Z]{1,2}[0-9]{1,2}\s?[0-9][A-Z]{2}$/', // SW1A 1AA
            'DE' => '/^\d{5}$/', // 10115
            'FR' => '/^\d{5}$/', // 75001
            'IT' => '/^\d{5}$/', // 00100
            'ES' => '/^\d{5}$/', // 28001
            'NL' => '/^\d{4}\s?[A-Z]{2}$/', // 1012 AB
            'AU' => '/^\d{4}$/', // 2000
            'IN' => '/^\d{6}$/', // 110001
            'SG' => '/^\d{6}$/', // 139645
            'JP' => '/^\d{3}-\d{4}$/', // 100-0001
            'AE' => '/^\d{5}$/', // 10001
        ];

        if (!isset($patterns[$country])) {
            // Default: allow alphanumeric up to 20 chars
            return [
                'valid' => strlen($postal_code) <= 20,
                'error' => 'Invalid postal code'
            ];
        }

        if (!preg_match($patterns[$country], $postal_code)) {
            return [
                'valid' => false,
                'error' => "Invalid postal code format for $country"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Standardize state/province names
     */
    private function standardizeState($state, $country) {
        $state = trim($state);

        // US state abbreviations
        $us_states = [
            'AL' => 'AL', 'ALABAMA' => 'AL',
            'AK' => 'AK', 'ALASKA' => 'AK',
            'AZ' => 'AZ', 'ARIZONA' => 'AZ',
            'AR' => 'AR', 'ARKANSAS' => 'AR',
            'CA' => 'CA', 'CALIFORNIA' => 'CA',
            'CO' => 'CO', 'COLORADO' => 'CO',
            // Add more as needed
        ];

        if ($country === 'US' && isset($us_states[strtoupper($state)])) {
            return $us_states[strtoupper($state)];
        }

        return $state;
    }

    /**
     * Find duplicate address
     */
    private function findDuplicateAddress($address) {
        try {
            if (empty($address['customer_id'])) {
                return null;
            }

            $sql = "SELECT id FROM customer_addresses
                    WHERE customer_id = ?
                    AND LEVENSHTEIN(
                        CONCAT(first_name, ' ', last_name, ' ', street_address_1, ' ', city),
                        CONCAT(?, ' ', ?, ' ', ?, ' ', ?)
                    ) < 5
                    LIMIT 1";

            // Note: LEVENSHTEIN might not be available, use simple comparison
            $sql = "SELECT id FROM customer_addresses
                    WHERE customer_id = ?
                    AND first_name = ?
                    AND last_name = ?
                    AND street_address_1 = ?
                    AND city = ?
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return null;
            }

            $stmt->bind_param('issss',
                $address['customer_id'],
                $address['first_name'],
                $address['last_name'],
                $address['street_address_1'],
                $address['city']
            );

            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            return $result->num_rows > 0 ? $result->fetch_assoc() : null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Save address to database
     */
    public function saveAddress($customer_id, $address) {
        try {
            $sql = "INSERT INTO customer_addresses (
                customer_id, type, first_name, last_name,
                company_name, street_address_1, street_address_2,
                city, state_province, postal_code, country,
                phone_number, email, is_validated, validated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $type = $address['type'] ?? 'shipping';
            $is_validated = TRUE;

            $stmt->bind_param('issssssssssssi',
                $customer_id,
                $type,
                $address['first_name'],
                $address['last_name'],
                $address['company_name'] ?? null,
                $address['street_address_1'],
                $address['street_address_2'] ?? null,
                $address['city'],
                $address['state_province'],
                $address['postal_code'],
                $address['country'],
                $address['phone_number'] ?? null,
                $address['email'] ?? null,
                $is_validated
            );

            $stmt->execute();
            $address_id = $this->conn->insert_id;
            $stmt->close();

            return [
                'success' => true,
                'address_id' => $address_id,
                'message' => 'Address saved successfully'
            ];

        } catch (Exception $e) {
            call_user_func($this->logger, "Error saving address: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer addresses
     */
    public function getCustomerAddresses($customer_id) {
        try {
            $sql = "SELECT * FROM customer_addresses
                    WHERE customer_id = ? AND is_active = TRUE
                    ORDER BY is_default DESC, updated_at DESC";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error");
            }

            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $addresses = [];
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row;
            }

            $stmt->close();
            return $addresses;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get default address
     */
    public function getDefaultAddress($customer_id, $type = 'shipping') {
        try {
            $sql = "SELECT * FROM customer_addresses
                    WHERE customer_id = ?
                    AND is_active = TRUE
                    AND is_default = TRUE
                    AND type IN (?, 'both')
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Database error");
            }

            $stmt->bind_param('iss', $customer_id, $type);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }

            $stmt->close();
            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Set default address
     */
    public function setDefaultAddress($customer_id, $address_id) {
        try {
            // Remove other defaults
            $sql = "UPDATE customer_addresses
                    SET is_default = FALSE
                    WHERE customer_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $stmt->close();

            // Set new default
            $sql = "UPDATE customer_addresses
                    SET is_default = TRUE
                    WHERE id = ? AND customer_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ii', $address_id, $customer_id);
            $stmt->execute();
            $stmt->close();

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete address
     */
    public function deleteAddress($customer_id, $address_id) {
        try {
            $sql = "UPDATE customer_addresses
                    SET is_active = FALSE
                    WHERE id = ? AND customer_id = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ii', $address_id, $customer_id);
            $stmt->execute();
            $stmt->close();

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
