# Tax & Shipping System - Complete Setup Guide

**Phase 1, Task 2: Tax & Shipping Configuration**
**Status:** ✅ Implementation Complete
**Effort:** 20 hours (as per roadmap)

## 📋 Overview

This document provides complete instructions for setting up and configuring tax and shipping in the Books eCommerce platform.

### Current Implementation Status

✅ **Completed:**
- Database schema for tax & shipping (10 tables)
- Tax calculation engine (multiple tax types support)
- Shipping calculator (zone-based, weight-based rates)
- API endpoints for real-time calculations
- Admin data seeding (15 tax rates, 5 zones, 6 methods)
- International tax support (US, EU, India, UK)

🔄 **Ready to Use:**
- Tax rates for 15 regions
- 5 shipping zones (US, Canada, EU, Asia, Global)
- 6 shipping methods (Standard, Express, Overnight, etc.)

---

## 🗄️ Database Schema

### Tables Created

```
1. tax_rates              - Tax rate configuration
2. tax_exemptions         - Products/customers exempt from tax
3. shipping_zones         - Geographic delivery areas
4. shipping_methods       - Shipping options per zone
5. shipping_rates         - Complex rate calculation rules
6. shipping_rules         - Eligibility rules for shipping
7. product_shipping       - Product-specific shipping properties
8. order_tax_details      - Tax breakdown per order
9. order_shipping_details - Shipping info per order
10. shipping_zone_postcodes - Detailed postal code mappings
```

### Key Features

**Tax Rates:**
- Multiple tax types (Sales Tax, VAT, GST)
- Compound taxation support
- Date-based effectiveness
- Product type filtering
- Priority-based calculation

**Shipping:**
- Zone-based routing (country, state, city, postal code)
- Multiple methods per zone
- Weight-based rates ($/kg)
- Quantity-based rates ($/item)
- Order total-based rates
- Flat rates
- Free shipping thresholds
- Handling & COD fees

---

## 💰 Tax System Guide

### Supported Tax Types

```
Sales Tax     - US, Canada (% of order subtotal)
VAT          - UK, EU countries (% with exemptions)
GST          - India, Australia (% + exemptions)
Custom       - Any other tax type
```

### Tax Calculation Methods

#### 1. Simple Tax
```
Taxable Amount × Tax Rate = Tax
$100 × 7.25% = $7.25
```

#### 2. Compound Tax
```
(Base Amount + Previous Tax) × Rate = Tax
Base: $100
Tax 1 (7%): $100 × 7% = $7.00
Tax 2 (2%, compound): ($100 + $7) × 2% = $2.14
Total Tax: $9.14
```

#### 3. Sequential Tax
```
Each tax calculated on base only
Base: $100
Tax 1 (7%): $100 × 7% = $7.00
Tax 2 (2%): $100 × 2% = $2.00
Total Tax: $9.00
```

### Configuring Tax Rates

#### Example 1: US Sales Tax (California)

```sql
INSERT INTO tax_rates (
    name, country, state_province, tax_percentage,
    effective_from, applies_to, tax_type, is_active
) VALUES (
    'US - California Sales Tax',
    'US',
    'CA',
    7.25,
    '2025-01-01',
    'all',
    'sales_tax',
    TRUE
);
```

#### Example 2: India GST (Books)

```sql
INSERT INTO tax_rates (
    name, country, state_province, tax_percentage,
    effective_from, applies_to, tax_type, is_active
) VALUES (
    'India - GST on Books',
    'IN',
    NULL,
    5.00,
    '2025-01-01',
    'products',
    'gst',
    TRUE
);
```

#### Example 3: UK VAT (Books are exempt)

```sql
INSERT INTO tax_rates (
    name, country, state_province, tax_percentage,
    effective_from, applies_to, tax_type, is_active
) VALUES (
    'UK - Zero VAT (Books)',
    'GB',
    NULL,
    0.00,
    '2025-01-01',
    'products',
    'vat',
    TRUE
);
```

### Tax Exemptions

#### Exempt by Product

```sql
INSERT INTO tax_exemptions (
    exemption_type, entity_id, tax_exempt_reason, is_active
) VALUES (
    'product',
    123,  -- Product ID
    'Textbook - exempt under education law',
    TRUE
);
```

#### Exempt by Customer Type

```sql
INSERT INTO tax_exemptions (
    exemption_type, customer_type, tax_exempt_reason,
    countries_applicable, is_active
) VALUES (
    'customer_type',
    'nonprofit',
    'Non-profit organization exemption',
    JSON_ARRAY('US', 'CA'),
    TRUE
);
```

---

## 🚚 Shipping System Guide

### Shipping Zones

#### Understanding Zones

Zones define geographic areas where shipping methods are available.

**Zone Types:**
```
country       - Entire country
state         - Specific state/province
city          - Specific cities
postal_code   - Postal code ranges
region        - Multiple countries (EU, Asia-Pacific)
custom        - Special configurations
```

#### Create a Shipping Zone

```sql
INSERT INTO shipping_zones (
    name, zone_type, countries, priority, is_active
) VALUES (
    'Australia & New Zealand',
    'region',
    JSON_ARRAY('AU', 'NZ'),
    4,
    TRUE
);
```

### Shipping Methods

#### Method Types

```
standard     - 5-7 business days, cheapest
express      - 2-3 business days, mid-range
overnight    - Next business day, expensive
pickup       - Customer picks up from location
digital      - Instant (for digital products)
free         - Free shipping (threshold/promotion)
```

#### Create a Shipping Method

```sql
INSERT INTO shipping_methods (
    shipping_zone_id, name, shipping_type, base_price,
    min_days, max_days, provides_tracking, is_active
) VALUES (
    1,  -- Zone ID for "Domestic US"
    'Priority Express (2-day)',
    'express',
    16.99,
    2, 2,
    TRUE,
    TRUE
);
```

### Shipping Rate Structures

#### Flat Rate

```
Fixed price for any order in zone
Cost: $5.99
```

#### Weight-Based Rate

```
Cost = Base + (Weight × Rate/kg)
Cost = $2.00 + (2kg × $1.50/kg) = $5.00
```

#### Quantity-Based Rate

```
Cost = Base + (Items × Rate/item)
Cost = $1.00 + (5 items × $0.50/item) = $3.50
```

#### Order Total-Based Rate

```
Cost = Base + (OrderTotal >= Threshold ? $X : $0)
Cost = $2.00 + ($50 >= $50 ? $5.00 : $0) = $7.00
```

#### Tiered/Bulk Rate

```
1-10 items:   $0.75/item
11-50 items:  $0.50/item
50+ items:    $0.25/item
```

### Advanced: Free Shipping Threshold

```php
// Offer free shipping for orders over $100
INSERT INTO shipping_methods (
    shipping_zone_id, name, shipping_type, base_price,
    free_shipping_threshold, is_active
) VALUES (
    1,
    'Free Shipping (Orders $100+)',
    'free',
    0.00,
    100.00,
    TRUE
);
```

---

## 🔧 Implementation in Checkout

### Checkout Flow with Tax & Shipping

```
1. Customer enters shipping address
   ↓
2. Frontend calls: POST /api/calculate-shipping.php
   {destination, items, subtotal}
   ↓
3. Backend returns available shipping methods with costs
   {available_methods, default_method}
   ↓
4. Customer selects shipping method
   ↓
5. Frontend calls: POST /api/calculate-tax.php
   {shipping_address, items, subtotal}
   ↓
6. Backend calculates taxes for selected address
   {total_tax, tax_breakdown, taxable_amount}
   ↓
7. Order Total = Subtotal + Shipping + Tax
   ↓
8. Customer reviews and completes payment
```

### Frontend Integration Example

```javascript
// Get available shipping methods
async function getShippingMethods(address, items, subtotal) {
    const response = await fetch('/api/calculate-shipping.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            destination: address,
            items: items,
            subtotal: subtotal
        })
    });

    const data = await response.json();
    if (data.success) {
        displayShippingOptions(data.available_methods);
    } else {
        showError(data.error);
    }
}

// Calculate taxes when address changes
async function calculateTax(address, items, subtotal) {
    const response = await fetch('/api/calculate-tax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            shipping_address: address,
            items: items,
            subtotal: subtotal
        })
    });

    const data = await response.json();
    if (data.success) {
        updateOrderTotal(subtotal, selectedShippingCost, data.total_tax);
    }
}

// Update order total whenever tax or shipping changes
function updateOrderTotal(subtotal, shipping, tax) {
    const total = parseFloat(subtotal) +
                  parseFloat(shipping || 0) +
                  parseFloat(tax || 0);

    document.getElementById('orderTotal').textContent =
        '$' + total.toFixed(2);
}
```

### Backend Integration Example

```php
<?php
// In checkout_process.php

// Get customer's shipping address
$shipping_address = [
    'country' => $_POST['country'],
    'state_province' => $_POST['state'],
    'postal_code' => $_POST['zip'],
    'city' => $_POST['city']
];

// Get cart items with details
$items = getCartItemsWithDetails($cart_id);
$subtotal = getCartSubtotal($cart_id);

// Initialize calculators
$tax_calc = new TaxCalculator($conn);
$shipping_calc = new ShippingCalculator($conn);

// Get available shipping methods
$shipping_result = $shipping_calc->getAvailableShippingMethods([
    'destination' => $shipping_address,
    'items' => $items,
    'subtotal' => $subtotal
]);

if (!$shipping_result['success']) {
    die("Cannot ship to this address");
}

// Get selected shipping method
$selected_method = $shipping_result['available_methods'][0]; // User selected
$shipping_cost = $selected_method['calculated_cost'];

// Calculate taxes
$tax_result = $tax_calc->calculateOrderTax([
    'customer_id' => $customer_id,
    'shipping_address' => $shipping_address,
    'items' => $items,
    'subtotal' => $subtotal
]);

$tax_amount = $tax_result['total_tax'];

// Calculate final order total
$order_total = $subtotal + $shipping_cost + $tax_amount;

// Create order in database
$order_id = createOrder([
    'customer_id' => $customer_id,
    'subtotal' => $subtotal,
    'shipping_method_id' => $selected_method['id'],
    'shipping_cost' => $shipping_cost,
    'tax_amount' => $tax_amount,
    'total' => $order_total
]);

// Save tax & shipping details
$tax_calc->saveTaxCalculation($order_id, $tax_result);
$shipping_calc->saveShippingToOrder($order_id, $selected_method['id'], $shipping_cost);
?>
```

---

## 👨‍💼 Admin Panel Setup

### Access Points

**Tax Management:**
- Admin → Settings → Taxes
- View all tax rates by country
- Create/edit/delete tax rates
- Set exemptions
- View tax reports by date range

**Shipping Management:**
- Admin → Settings → Shipping
- Create/edit zones
- Configure shipping methods
- Set rates and rules
- View shipping statistics

### Common Admin Tasks

#### Add New Tax Rate

```
1. Go to Admin → Settings → Taxes
2. Click "Add Tax Rate"
3. Fill in:
   - Name: "France VAT"
   - Country: FR
   - Tax %: 20.00
   - Type: VAT
4. Save
```

#### Create New Shipping Zone

```
1. Go to Admin → Settings → Shipping → Zones
2. Click "Add Zone"
3. Fill in:
   - Name: "Middle East"
   - Type: Region
   - Countries: SA, AE, QA
   - Priority: 5
4. Save
```

#### Configure Shipping Method

```
1. Go to Admin → Settings → Shipping → Methods
2. Select Zone: "Middle East"
3. Click "Add Method"
4. Fill in:
   - Name: "International Standard (2-3 weeks)"
   - Type: Standard
   - Base Price: $15.99
   - Min/Max Days: 14-21
5. Add Rate (Weight-based):
   - $2.00/kg
6. Save
```

---

## 📊 Reporting & Analytics

### Tax Reports

**Available Reports:**
- Tax collected by country
- Tax collected by month/year
- Tax by type (Sales Tax, VAT, GST)
- Effective tax rates by region
- Exempt orders report

**Access:**
```
Admin → Reports → Taxes
```

### Shipping Reports

**Available Reports:**
- Orders shipped by method
- Shipping revenue by zone
- Average shipping cost
- Shipping by carrier/provider
- Delivery performance metrics

**Access:**
```
Admin → Reports → Shipping
```

---

## 🧮 Examples & Scenarios

### Scenario 1: Multi-Tax Order (Compound)

**Order Details:**
- Subtotal: $100
- Location: California, USA
- Tax Rule 1: State tax 7.25% (priority 0)
- Tax Rule 2: County tax 1.25% (priority 1, compound)

**Calculation:**
```
Tax 1 = $100 × 7.25% = $7.25
Tax 2 = ($100 + $7.25) × 1.25% = $1.34
Total Tax = $8.59
Final Total = $108.59
```

### Scenario 2: International Shipping with Weight

**Order Details:**
- Items: 3 books (500g each)
- Total Weight: 1.5kg
- Destination: London, UK
- Shipping Zone: European Union
- Shipping Method: Express
  - Base: £8.99
  - Weight Rate: £2.50/kg
  - Handling Fee: £1.50

**Calculation:**
```
Base Cost = £8.99
Weight Cost = 1.5kg × £2.50 = £3.75
Handling Fee = £1.50
Total Shipping = £14.24
```

### Scenario 3: Free Shipping Promotion

**Order Details:**
- Subtotal: $125
- Location: USA
- Shipping Threshold: $100 (free shipping applies)

**Calculation:**
```
Shipping Cost: FREE ($0)
Note: Displayed as "Free Shipping - Orders $100+"
```

### Scenario 4: Tax-Exempt Product (Book in UK)

**Order Details:**
- Book: £20.00 (0% VAT - books exempt)
- DVD: £15.00 (20% VAT)
- Location: UK

**Calculation:**
```
Book: £20.00 × 0% = £0 tax
DVD: £15.00 × 20% = £3.00 tax
Total Tax = £3.00
Total = £38.00
```

---

## 🔐 Security & Compliance

### Tax Compliance

- ✅ Correct tax rates by jurisdiction
- ✅ Proper exemption handling
- ✅ Audit trail of tax calculations
- ✅ Historical rate tracking (effective_from/to dates)

### Shipping Safety

- ✅ Weight/dimension validation
- ✅ Hazardous material handling
- ✅ Signature requirements for high-value items
- ✅ Insurance options

### Data Protection

- Tax calculations logged
- Shipping history maintained
- Audit trail for changes
- Secure API endpoints

---

## 🚀 Deployment Checklist

### Before Going Live

- [ ] All tax rates configured correctly
- [ ] Tax tested for major markets (US, EU, India)
- [ ] Shipping zones configured for all target countries
- [ ] Shipping methods tested with various items/weights
- [ ] API endpoints tested and responding
- [ ] Frontend integration tested in checkout
- [ ] Tax & shipping displayed correctly on order confirmation
- [ ] Reports working and accessible
- [ ] Decimal precision set correctly (2 decimals)

### Maintenance Tasks

- [ ] Review tax rates quarterly (they change)
- [ ] Monitor shipping costs (adjust if carrier rates change)
- [ ] Verify exemptions annually
- [ ] Audit tax calculations monthly
- [ ] Update international shipping for new countries

---

## 📞 API Reference

### Calculate Tax Endpoint

**URL:** `POST /api/calculate-tax.php`

**Request:**
```json
{
    "customer_id": 123,
    "shipping_address": {
        "country": "US",
        "state_province": "CA",
        "postal_code": "94105",
        "city": "San Francisco"
    },
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "price": 15.99,
            "type": "products"
        }
    ],
    "subtotal": 31.98,
    "customer_type": "individual"
}
```

**Response:**
```json
{
    "success": true,
    "total_tax": 2.32,
    "tax_breakdown": [
        {
            "tax_id": 1,
            "tax_name": "California Sales Tax",
            "rate": 7.25,
            "amount": 2.32
        }
    ],
    "taxable_amount": 31.98,
    "exempt_amount": 0,
    "effective_tax_rate": 7.25
}
```

### Calculate Shipping Endpoint

**URL:** `POST /api/calculate-shipping.php`

**Request:**
```json
{
    "destination": {
        "country": "US",
        "state_province": "CA",
        "postal_code": "94105",
        "city": "San Francisco"
    },
    "items": [
        {
            "product_id": 1,
            "quantity": 2,
            "weight_kg": 0.5
        }
    ],
    "subtotal": 31.98
}
```

**Response:**
```json
{
    "success": true,
    "zone_id": 1,
    "zone_name": "Domestic US",
    "available_methods": [
        {
            "id": 1,
            "name": "Standard Shipping (5-7 days)",
            "shipping_type": "standard",
            "calculated_cost": 5.99,
            "cost_breakdown": [
                {"type": "Base Shipping", "amount": 5.99}
            ],
            "estimated_delivery": {
                "min_days": 5,
                "max_days": 7,
                "min_date": "2025-11-15",
                "max_date": "2025-11-17"
            }
        }
    ],
    "default_method": {...}
}
```

---

## 📖 Further Reading

- US Sales Tax Nexus: https://www.taxfoundation.org/
- UK VAT Rules: https://www.gov.uk/vat
- EU VAT Guide: https://taxation-customs.ec.europa.eu/
- India GST: https://www.gst.gov.in/
- Shipping Best Practices: https://www.shipping.com/guides

---

**Last Updated:** 2025-11-10
**Version:** 1.0
**Status:** ✅ COMPLETE & READY FOR INTEGRATION

For implementation status and timeline, see: **COMPLETE_IMPLEMENTATION_ROADMAP.md**
