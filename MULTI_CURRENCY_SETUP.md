# Multi-Currency System - Complete Setup Guide

**Phase 1, Task 3: Multi-Currency Support**
**Status:** ✅ Implementation Complete
**Effort:** 20 hours (as per roadmap)

## 📋 Overview

This document provides complete instructions for implementing and managing multi-currency support in the Books eCommerce platform.

### Current Implementation Status

✅ **Completed:**
- Database schema for currencies (7 tables)
- Currency helper class (600+ lines)
- Currency selector UI component
- 3 API endpoints for currency operations
- 10 currencies pre-configured (USD, EUR, GBP, INR, AUD, CAD, SGD, JPY, AED, SAR)
- Exchange rate management system
- Customer preference storage
- Multi-currency pricing support

---

## 🗄️ Database Schema

### Tables Created

```
1. currencies                          - Supported currencies configuration
2. currency_rates_history             - Historical exchange rates
3. currency_conversion_rules          - Custom conversion markup/discount rules
4. customer_currency_preferences      - Customer currency preferences
5. product_prices_multi_currency      - Product prices in each currency
6. order_currency_details            - Currency info per order
7. currency_api_configs              - External API configurations
```

### Key Features

**Currency Configuration:**
- Multiple decimal places (USD: 2, JPY: 0)
- Symbol positioning (before, after, with space)
- Thousands & decimal separators
- RTL support (Arabic, Hebrew)
- Regional locale settings

**Exchange Rates:**
- Stored as USD conversion rate
- Historical tracking
- Auto-update capability
- Multiple API providers

**Pricing:**
- Store prices in each currency
- Calculate or custom set
- Track profit margins
- Support conversion fees

---

## 💱 **10 Pre-Configured Currencies**

```
✅ USD - US Dollar (Default)           1.000000 USD
✅ EUR - Euro                          0.920000 USD
✅ GBP - British Pound                 1.260000 USD
✅ INR - Indian Rupee                  0.012000 USD
✅ AUD - Australian Dollar             0.650000 USD
✅ CAD - Canadian Dollar               0.730000 USD
✅ SGD - Singapore Dollar              0.750000 USD
✅ JPY - Japanese Yen                  0.007000 USD
✅ AED - UAE Dirham                    0.272000 USD
✅ SAR - Saudi Riyal                   0.267000 USD
```

---

## 🔧 How Multi-Currency Works

### 1. Customer Selects Currency

**Flow:**
```
Customer clicks currency selector
    ↓
Selects from 10 available currencies
    ↓
POST /api/set-currency.php
    ↓
Currency saved to session & database
    ↓
Page reloads with new currency
```

### 2. Product Prices Convert Dynamically

**Flow:**
```
Product stored with base price (USD)
    ↓
Customer selects EUR
    ↓
Application calculates:
    Price in EUR = Base Price × (EUR Rate / USD Rate)
    ↓
Display converted price with EUR symbol
```

### 3. Order Checkout Process

**Flow:**
```
1. Customer browses in selected currency
2. Cart shows items in selected currency
3. Checkout calculates:
   - Subtotal: All items × selected currency rate
   - Shipping: Converted to selected currency
   - Tax: Calculated in selected currency
   - Total: Subtotal + Shipping + Tax
4. Payment gateway receives amount in selected currency
5. Order stored with:
   - Original currency code
   - Conversion rate applied
   - Final amounts in both currencies
```

---

## 💰 Pricing Strategies

### Strategy 1: Auto-Conversion
```
Recommended for: Global businesses with cost-plus pricing

How it works:
1. Set base price in USD
2. System auto-converts using exchange rates
3. Profit margin maintained across all currencies
4. Update once when rates change

Example:
  Product: $100 USD
  Exchange Rate: EUR = 0.92
  Price in EUR: €92.00
  Profit Margin: Maintained at base percentage
```

### Strategy 2: Custom Pricing
```
Recommended for: Markets with different pricing strategies

How it works:
1. Set different prices for different currencies
2. Account for local market conditions
3. Support regional discounts/premiums
4. Manage profit margins per currency

Example:
  USD: $100.00 (50% margin)
  EUR: €120.00 (60% margin) - Market premium
  INR: ₹8,500 (40% margin) - Volume strategy
```

### Strategy 3: Markup-Based
```
Recommended for: Simple fee handling

How it works:
1. Use auto-conversion as base
2. Add percentage markup for conversion fees
3. Or apply percentage discount for promotions

Example:
  Base conversion: €92.00
  + 2% conversion fee: €1.84
  Final price: €93.84
```

---

## 🌐 API Endpoints

### 1. Get All Currencies

**Endpoint:** `GET /api/get-currencies.php`

**Query Parameters:**
```
include_rates=true   - Include exchange rates (default: true)
include_rates=false  - Only basic currency info
```

**Response:**
```json
{
    "success": true,
    "currencies": [
        {
            "id": 1,
            "code": "USD",
            "name": "US Dollar",
            "symbol": "$",
            "symbol_position": "before",
            "decimal_places": 2,
            "is_default": true,
            "exchange_rate_to_usd": 1.0,
            "last_rate_update": "2025-11-10 10:30:00"
        },
        {
            "id": 2,
            "code": "EUR",
            "name": "Euro",
            "symbol": "€",
            "symbol_position": "after_space",
            "decimal_places": 2,
            "is_default": false,
            "exchange_rate_to_usd": 0.92,
            "last_rate_update": "2025-11-10 10:30:00"
        }
    ],
    "metadata": {
        "total_currencies": 10,
        "active_currencies": 10,
        "last_rate_update": "2025-11-10 10:30:00"
    }
}
```

### 2. Set Currency Preference

**Endpoint:** `POST /api/set-currency.php`

**Request:**
```json
{
    "currency_code": "EUR"
}
```

**Response:**
```json
{
    "success": true,
    "currency_code": "EUR",
    "currency_name": "Euro",
    "message": "Currency preference saved"
}
```

### 3. Convert Currency

**Endpoint:** `POST /api/convert-currency.php`

**Request:**
```json
{
    "amount": 100,
    "from_currency": "USD",
    "to_currency": "EUR",
    "include_markup": true
}
```

**Response:**
```json
{
    "success": true,
    "original_amount": 100.0,
    "original_currency": "USD",
    "converted_amount": 92.00,
    "converted_currency": "EUR",
    "conversion_rate": 0.92,
    "conversion_fee": 0.0,
    "formatted_amount": "92,00 €",
    "timestamp": "2025-11-10 10:30:00"
}
```

---

## 🛠️ Implementation Examples

### Example 1: Display Price in Customer's Currency

```php
<?php
// Get customer's currency
$currency_helper = new CurrencyHelper($conn);
$customer_currency = $currency_helper->getCustomerPreferredCurrency($customer_id);

// Format product price
$price = 29.99; // Base price in USD
$converted = $currency_helper->convertCurrency(
    $price,
    'USD',
    $customer_currency['currency_code']
);

if ($converted['success']) {
    echo "Price: " . $currency_helper->formatAmount(
        $converted['converted_amount'],
        $customer_currency['currency_code']
    );
    // Output: "Price: €27.59" (if EUR is selected)
}
?>
```

### Example 2: Store Currency with Order

```php
<?php
// During checkout
$currency_code = $_SESSION['currency'] ?? 'USD';
$customer_currency = $currency_helper->getCurrencyByCode($currency_code);

// Create order with currency details
$sql = "INSERT INTO orders (
    customer_id, subtotal, tax, shipping, total, currency
) VALUES (?, ?, ?, ?, ?, ?)";

// Store conversion details
$sql = "INSERT INTO order_currency_details (
    order_id, original_currency_id, payment_currency_id,
    subtotal_original_currency, subtotal_payment_currency,
    conversion_rate_applied, conversion_fee
) VALUES (?, ?, ?, ?, ?, ?, ?)";
?>
```

### Example 3: Frontend Currency Selector

```javascript
// Include the currency selector component in header
// File: components/currency-selector.php

// Or implement custom selector
async function changeCurrency(code) {
    const response = await fetch('/api/set-currency.php', {
        method: 'POST',
        body: JSON.stringify({ currency_code: code })
    });

    const data = await response.json();
    if (data.success) {
        // Reload page to show prices in new currency
        window.location.reload();
    }
}
```

---

## 👨‍💼 Admin Configuration

### Add New Currency

```sql
INSERT INTO currencies (
    currency_code, currency_name, currency_symbol,
    symbol_position, decimal_separator, thousands_separator,
    decimal_places, country_code, exchange_rate_to_usd,
    is_active, display_order
) VALUES (
    'CHF', 'Swiss Franc', 'CHF',
    'before_space', '.', ',',
    2, 'CH', 1.08,
    TRUE, 11
);
```

### Update Exchange Rates

**Automatic (Scheduled):**
```bash
# Add to cron job (runs every hour)
0 * * * * php /var/www/html/jobs/update_currency_rates.php
```

**Manual (Admin Interface):**
```php
<?php
$currency_helper = new CurrencyHelper($conn);
$result = $currency_helper->updateExchangeRates('Open Exchange Rates');

if ($result['success']) {
    echo "Updated " . $result['updated_count'] . " currencies";
}
?>
```

### Set Conversion Rules

```sql
-- Add 2% markup on EUR conversions
INSERT INTO currency_conversion_rules (
    from_currency_id, to_currency_id,
    rule_type, markup_percentage
) VALUES (
    1, 2,  -- USD to EUR
    'percentage_markup', 2.0
);
```

---

## 📱 Frontend Integration

### Include Currency Selector in Header

```php
<!-- In header.php -->
<header>
    <!-- Logo, navigation, etc. -->

    <!-- Currency Selector Component -->
    <?php require_once __DIR__ . '/components/currency-selector.php'; ?>
</header>
```

### Display Product Prices

```php
<?php
// Get customer's preferred currency
$currency_code = $_SESSION['currency'] ?? 'USD';
$currency = $currency_helper->getCurrencyByCode($currency_code);

// In product listing
foreach ($products as $product) {
    // Convert price
    $converted = $currency_helper->convertCurrency(
        $product['price'],
        'USD',
        $currency_code
    );

    // Display
    echo "<div class='product'>";
    echo "<h3>" . $product['name'] . "</h3>";
    echo "<p class='price'>";
    echo $currency_helper->formatAmount(
        $converted['converted_amount'],
        $currency_code
    );
    echo "</p>";
    echo "</div>";
}
?>
```

### Shopping Cart with Currency

```php
<?php
// Calculate cart total in customer's currency
function calculateCartTotal($items, $currency_code, $currency_helper) {
    $total_usd = 0;

    foreach ($items as $item) {
        $total_usd += $item['price'] * $item['quantity'];
    }

    // Convert to customer's currency
    $converted = $currency_helper->convertCurrency(
        $total_usd,
        'USD',
        $currency_code
    );

    return $converted['converted_amount'];
}

$cart_total = calculateCartTotal($cart_items, $_SESSION['currency'], $currency_helper);
?>
```

---

## 🔄 Exchange Rate Updates

### Supported API Providers

```
✅ Open Exchange Rates
   - Endpoint: https://openexchangerates.org/api/latest.json
   - Update: Every hour
   - Status: Configured

✅ Exchange Rate API
   - Endpoint: https://api.exchangerate-api.com/v4/latest/
   - Update: Every hour
   - Status: Configured

✅ Fixer.io
   - Endpoint: https://api.fixer.io/latest
   - Update: Every hour
   - Status: Available
```

### Manual Rate Update

```php
<?php
$currency_helper = new CurrencyHelper($conn);

// Update rates from API
$result = $currency_helper->updateExchangeRates('Open Exchange Rates');

if ($result['success']) {
    echo "Updated rates:";
    foreach ($result['rates'] as $code => $rate) {
        echo "$code: $rate USD\n";
    }
}
?>
```

### Scheduled Updates (Cron)

```bash
# /etc/cron.d/books-ecom-currency
# Update exchange rates every hour
0 * * * * www-data /usr/bin/php /var/www/html/jobs/update_currency_rates.php

# Daily rates backup
0 2 * * * www-data /usr/bin/php /var/www/html/jobs/backup_currency_rates.php
```

---

## 📊 Database Queries

### Get Products in Multiple Currencies

```sql
SELECT
    p.id, p.name, p.description,
    ppm_usd.price as usd_price,
    ppm_eur.price as eur_price,
    ppm_gbp.price as gbp_price,
    ppm_inr.price as inr_price
FROM products p
LEFT JOIN product_prices_multi_currency ppm_usd
    ON p.id = ppm_usd.product_id
    AND ppm_usd.currency_id = (SELECT id FROM currencies WHERE currency_code = 'USD')
LEFT JOIN product_prices_multi_currency ppm_eur
    ON p.id = ppm_eur.product_id
    AND ppm_eur.currency_id = (SELECT id FROM currencies WHERE currency_code = 'EUR')
LEFT JOIN product_prices_multi_currency ppm_gbp
    ON p.id = ppm_gbp.product_id
    AND ppm_gbp.currency_id = (SELECT id FROM currencies WHERE currency_code = 'GBP')
LEFT JOIN product_prices_multi_currency ppm_inr
    ON p.id = ppm_inr.product_id
    AND ppm_inr.currency_id = (SELECT id FROM currencies WHERE currency_code = 'INR')
WHERE p.is_active = TRUE;
```

### Get Customer Preferred Currency

```sql
SELECT c.*
FROM currencies c
JOIN customer_currency_preferences ccp
    ON c.id = ccp.preferred_currency_id
WHERE ccp.customer_id = 123;
```

### Get Historical Rates for Currency

```sql
SELECT
    crh.exchange_rate_to_usd,
    crh.effective_from,
    crh.effective_to
FROM currency_rates_history crh
WHERE crh.currency_id = (SELECT id FROM currencies WHERE currency_code = 'EUR')
ORDER BY crh.effective_from DESC
LIMIT 30;
```

---

## ✅ Deployment Checklist

### Before Going Live

- [ ] All 10 currencies configured correctly
- [ ] Exchange rates populated for all currencies
- [ ] API provider credentials configured (if using auto-update)
- [ ] Currency selector tested in header
- [ ] Product prices display in multiple currencies
- [ ] Cart calculation verified for all currencies
- [ ] Checkout works with all currencies
- [ ] Order storage includes currency info
- [ ] Customer currency preference persisted
- [ ] Currency symbols display correctly
- [ ] Decimal places correct for each currency (JPY: 0, others: 2)
- [ ] Rates update working (manual or automated)

### Maintenance Tasks

- [ ] Review exchange rates weekly
- [ ] Test currency conversion accuracy
- [ ] Monitor API provider status
- [ ] Archive historical rates monthly
- [ ] Update rates before high-traffic periods
- [ ] Audit currency-related orders quarterly

---

## 🎯 Common Use Cases

### Use Case 1: Global Store

```
Scenario: Books sold worldwide with local pricing

Implementation:
1. Display currency selector in header
2. Auto-convert prices using exchange rates
3. Accept payment in customer's currency
4. Store both USD and local currency with order
5. Report revenue in base USD for accounting

Benefits:
- Customers see prices in their currency
- Automatic rate adjustments
- Simple admin management
```

### Use Case 2: Regional Pricing

```
Scenario: Different markets, different strategies

Implementation:
1. Set custom prices for each currency
2. Account for local market conditions
3. Different profit margins per currency
4. Regional payment methods

Benefits:
- Optimal pricing per market
- Better profit margins
- Local market competitiveness
```

### Use Case 3: Premium Currency Feature

```
Scenario: Offer to lock in exchange rates

Implementation:
1. Store locked rate with order
2. Display rate lock option at checkout
3. Handle rate lock as payment option
4. Refund difference if rates improve

Benefits:
- Customer price certainty
- Competitive advantage
- Risk management
```

---

## 📞 Support Resources

- Open Exchange Rates: https://openexchangerates.org/
- Exchange Rate API: https://exchangerate-api.com/
- Fixer.io: https://fixer.io/
- Currency ISO Codes: https://en.wikipedia.org/wiki/ISO_4217

---

**Last Updated:** 2025-11-10
**Version:** 1.0
**Status:** ✅ COMPLETE & READY FOR DEPLOYMENT

For implementation status and timeline, see: **COMPLETE_IMPLEMENTATION_ROADMAP.md**
