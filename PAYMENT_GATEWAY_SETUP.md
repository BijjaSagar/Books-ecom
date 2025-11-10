# Payment Gateway Integration - Setup Guide

**Phase 1, Task 1: Payment Gateway Integration**
**Status:** ✅ Implementation Complete
**Effort:** 50 hours (as per roadmap)

## 📋 Overview

This document provides complete instructions for setting up and configuring payment gateways in the Books eCommerce platform.

### Current Implementation Status

✅ **Completed:**
- Database schema for payment processing (8 tables)
- Stripe gateway implementation (full integration)
- Payment processing controller
- Payment form with Stripe Elements
- Webhook handler for Stripe
- API endpoint for payment methods
- Multi-currency support structure
- 3D Secure authentication support
- Refund processing capability

🔄 **In Progress:**
- PayPal integration (skeleton created)
- Razorpay integration (skeleton created)
- Additional 10 gateways (ready for implementation)

---

## 🗄️ Database Schema

### Tables Created

```
1. payment_methods      - Available payment gateways (13 seed records)
2. gateway_keys         - API credentials storage
3. transactions         - Payment transaction records
4. payment_webhooks     - Webhook event logs
5. refunds              - Refund operation logs
6. payment_intents      - Payment intent tracking (ACH, transfers)
7. saved_payment_methods - Customer saved cards/accounts
8. subscription_payments - Future subscription support
```

### Apply Migration

Run this before deploying:

```bash
php database/migrate.php
```

This will:
1. Create all 8 tables with proper indexes
2. Insert 13 payment gateway seed records
3. Create necessary foreign key relationships

---

## 🔑 Stripe Configuration

### Step 1: Create Stripe Account

1. Go to https://stripe.com
2. Sign up for a business account
3. Complete identity verification
4. Get your API keys from https://dashboard.stripe.com/apikeys

### Step 2: Get Your Keys

**Test Mode Keys (for development):**
- Publishable Key: `pk_test_xxx...`
- Secret Key: `sk_test_xxx...`

**Live Mode Keys (for production):**
- Publishable Key: `pk_live_xxx...`
- Secret Key: `sk_live_xxx...`

### Step 3: Configure in Admin Panel

**For now, add directly to database:**

```sql
INSERT INTO gateway_keys (
    payment_method_id,
    is_test_mode,
    api_key,
    api_secret,
    is_active
) VALUES (
    1,
    TRUE,
    'pk_test_xxx...',
    'sk_test_xxx...',
    TRUE
);
```

Where `payment_method_id = 1` for Stripe.

### Step 4: Configure Webhook in Stripe Dashboard

1. Go to https://dashboard.stripe.com/webhooks
2. Click "Add an endpoint"
3. Endpoint URL: `https://yourdomain.com/webhooks/stripe-webhook.php`
4. Select events to listen:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
   - `charge.refunded`
   - `charge.dispute.created`
5. Copy the Webhook Signing Secret
6. Store in database:

```sql
UPDATE gateway_keys
SET webhook_secret = 'whsec_xxx...'
WHERE payment_method_id = 1 AND is_test_mode = TRUE;
```

---

## 💳 Testing Stripe Payments

### Test Card Numbers

```
Success:        4242 4242 4242 4242
Visa:          4263 9826 4026 9299
Mastercard:    5555 5555 5555 4444
Amex:          3782 822463 10005
```

**For all test cards:**
- Expiry: Any future date (e.g., 12/25)
- CVC: Any 3 digits (e.g., 123)

### Test 3D Secure Card

```
Card: 4000 0025 0000 3155
Result: Pass authentication
```

### Test Failed Payment

```
Card: 4000 0000 0000 9995
Result: Declined
```

---

## 🔐 PayPal Configuration (In Progress)

### Step 1: Create PayPal Business Account

1. Go to https://www.paypal.com/signin
2. Create or sign into your account
3. Switch to Business account
4. Get API credentials from https://developer.paypal.com/dashboard

### Step 2: Get PayPal Credentials

```
Client ID: YOUR_CLIENT_ID
Secret: YOUR_SECRET
```

### Step 3: Configure in Admin Panel (When UI Ready)

Will be added to gateway_keys table with gateway_name = 'paypal'

### Step 4: Configure Webhook

Will configure in PayPal Developer Dashboard to:
- URL: `https://yourdomain.com/webhooks/paypal-webhook.php`
- Events: PAYMENT.SALE.COMPLETED, PAYMENT.SALE.DENIED, etc.

---

## 🇮🇳 Razorpay Configuration (India) (In Progress)

### Step 1: Create Razorpay Account

1. Go to https://razorpay.com
2. Sign up with your details
3. Complete KYC verification
4. Get keys from https://dashboard.razorpay.com/app/keys

### Step 2: Get Razorpay Credentials

```
Key ID: rzp_test_xxx
Key Secret: xxx
```

### Step 3: Configure in Database

Will be added to gateway_keys table with gateway_name = 'razorpay'

---

## 🛠️ Implementation Guide

### File Structure

```
includes/
  payment_gateways/
    StripeGateway.php          ✅ Complete
    PayPalGateway.php          🔄 In Progress
    RazorpayGateway.php        🔄 In Progress
    AuthorizeNetGateway.php    ⏳ Planned
    SquareGateway.php          ⏳ Planned
    [9 more gateways...]

checkout/
  payment-form.php            ✅ Complete (Stripe + UI for all)
  process-payment.php         ✅ Complete
  success.php                 ⏳ Need to create

webhooks/
  stripe-webhook.php          ✅ Complete
  paypal-webhook.php          🔄 In Progress
  razorpay-webhook.php        🔄 In Progress

api/
  payment-methods.php         ✅ Complete (lists available methods)
  checkout/
    initiate.php              ⏳ Need to create
    confirm.php               ⏳ Need to create
    refund.php                ⏳ Need to create

database/
  migrations/
    001_payment_system.sql    ✅ Complete
  migrate.php                 ✅ Complete
```

### Key Classes

**StripeGateway** - Main Stripe integration class

```php
// Initialize
$stripe = new StripeGateway($conn, true); // true = test mode

// Create payment intent
$result = $stripe->createPaymentIntent([
    'order_id' => 123,
    'customer_id' => 456,
    'amount' => 10000, // in cents
    'currency' => 'usd'
]);

// Retrieve payment status
$status = $stripe->getPaymentIntentStatus('pi_xxx');

// Process refund
$refund = $stripe->processRefund('ch_xxx', [
    'amount' => 5000,
    'reason' => 'customer_request'
]);

// Handle webhook
$result = $stripe->handleWebhook($payload, $sig_header);
```

### Integration Points

#### 1. Checkout Flow

```
/checkout/payment-form.php
    ↓
    User selects payment method
    ↓
    /checkout/process-payment.php (POST)
    ↓
    Routes to StripeGateway::createPaymentIntent()
    ↓
    Returns client_secret for Stripe.js confirmation
    ↓
    Frontend confirms payment with client_secret
    ↓
    /webhooks/stripe-webhook.php (async)
    ↓
    Updates order status to 'completed'
    ↓
    Sends confirmation email
```

#### 2. Webhook Flow

```
Stripe sends event
    ↓
POST /webhooks/stripe-webhook.php
    ↓
Verify signature with webhook_secret
    ↓
Route event:
  - payment_intent.succeeded → update_order_to_completed()
  - payment_intent.payment_failed → update_order_to_failed()
  - charge.refunded → create_refund_record()
  - charge.dispute.created → flag_for_review()
    ↓
Log event to payment_webhooks table
    ↓
Return 200 OK to Stripe
```

#### 3. Refund Flow

```
Admin initiates refund in dashboard
    ↓
POST /api/refund.php
    ↓
StripeGateway::processRefund($charge_id, $data)
    ↓
Calls Stripe API to refund
    ↓
Logs refund to database
    ↓
Sends refund notification email
```

---

## 📊 Database Schema Detail

### payment_methods Table

| Field | Type | Purpose |
|-------|------|---------|
| id | INT | Primary key |
| gateway_name | VARCHAR(50) | Unique identifier: 'stripe', 'paypal', etc. |
| display_name | VARCHAR(100) | User-facing name: "Credit/Debit Card" |
| gateway_type | ENUM | 'card', 'wallet', 'bank', 'upi', 'crypto' |
| is_active | BOOLEAN | Enable/disable gateway |
| position | INT | Display order (1-13) |
| supports_refund | BOOLEAN | Can process refunds |
| requires_3d_secure | BOOLEAN | Mandate 3D Secure |
| transaction_fee_percent | DECIMAL | Stripe: 2.9% |
| transaction_fee_fixed | DECIMAL | Stripe: $0.30 |
| currencies_supported | JSON | ['USD', 'EUR', 'INR', ...] |
| countries_supported | JSON | ['US', 'UK', 'IN', ...] |

### transactions Table

| Field | Type | Purpose |
|-------|------|---------|
| id | INT | Primary key |
| transaction_id | VARCHAR(100) | Unique transaction ID (TXN-xxxxx) |
| order_id | INT | FK to orders |
| customer_id | INT | FK to users |
| payment_method_id | INT | FK to payment_methods |
| gateway_name | VARCHAR(50) | 'stripe', 'paypal', etc. |
| gateway_transaction_id | VARCHAR(255) | Stripe's pi_xxx or ch_xxx |
| amount | DECIMAL(12,2) | Payment amount |
| currency | VARCHAR(3) | USD, EUR, INR, etc. |
| status | ENUM | initiated, processing, pending_3d, completed, failed, refunded |
| risk_level | ENUM | low, medium, high |
| gateway_response_json | JSON | Full API response from gateway |
| created_at | TIMESTAMP | When transaction was initiated |
| processed_at | TIMESTAMP | When transaction completed |

### gateway_keys Table

| Field | Type | Purpose |
|-------|------|---------|
| id | INT | Primary key |
| payment_method_id | INT | FK to payment_methods |
| is_test_mode | BOOLEAN | True for sandbox, false for live |
| api_key | VARCHAR(500) | Publishable key (encrypted in production) |
| api_secret | VARCHAR(500) | Secret key (encrypted in production) |
| webhook_secret | VARCHAR(500) | Webhook signing secret |
| is_active | BOOLEAN | Enable/disable this key set |

---

## ✅ Deployment Checklist

### Before Going Live

- [ ] All 13 payment methods listed in payment_methods table
- [ ] Stripe test keys configured and tested
- [ ] Stripe webhook configured and verified
- [ ] Database migration applied successfully
- [ ] SSL/HTTPS enabled on all pages
- [ ] Payment form tested with test cards
- [ ] Order completion flow working (form → payment → email)
- [ ] Webhook handler receiving events from Stripe
- [ ] Refund process tested
- [ ] Error handling tested with failed payments

### Production Migration

- [ ] Back up all database tables
- [ ] Update gateway_keys with LIVE mode keys (production Stripe)
- [ ] Update webhook configuration to production URL
- [ ] Test one real transaction with $0.50 charge
- [ ] Verify webhook events received
- [ ] Monitor transaction logs for 24 hours
- [ ] Enable email notifications to customers
- [ ] Configure PCI DSS compliance settings
- [ ] Set up fraud detection rules in Stripe Dashboard
- [ ] Configure refund policies

---

## 🔍 Monitoring & Debugging

### Logs Location

```
Database: payment_webhooks table
- Stores all webhook events
- Query: SELECT * FROM payment_webhooks WHERE processed = FALSE

Error logs:
- PHP error_log()
- Check server error logs: /var/log/apache2/error.log or similar
```

### Common Issues

**Issue: Webhook not received**
- Check webhook URL is publicly accessible
- Verify Stripe can reach your domain
- Check firewall/WAF isn't blocking requests
- Verify webhook secret is correct

**Issue: Payment fails with "Invalid API Key"**
- Check api_secret is correct (not api_key)
- Verify secret key is for same environment (test vs live)
- Check key hasn't expired or been rotated

**Issue: 3D Secure not triggering**
- Some cards require 3D Secure
- Use test card: 4000 0025 0000 3155
- Customer must complete 3D Secure prompt in frontend

---

## 📞 Support Resources

### Stripe Resources
- API Docs: https://stripe.com/docs/api
- Payment Intents: https://stripe.com/docs/payments/payment-intents
- Webhooks: https://stripe.com/docs/webhooks
- 3D Secure: https://stripe.com/docs/payments/3d-secure
- Testing: https://stripe.com/docs/testing

### PayPal Resources (Coming)
- API Docs: https://developer.paypal.com/docs/api/overview/
- Checkout: https://developer.paypal.com/docs/checkout/
- Webhooks: https://developer.paypal.com/docs/api-basics/notifications/webhooks/

### Razorpay Resources (Coming)
- API Docs: https://razorpay.com/docs/api/orders/
- Integration: https://razorpay.com/docs/payments/
- Webhooks: https://razorpay.com/docs/webhooks/

---

## 🎯 Next Steps

### Immediate (This Week)
1. ✅ Database schema created
2. ✅ Stripe integration implemented
3. ✅ Payment form created
4. ⏳ Configure Stripe test keys in admin panel
5. ⏳ Test payment flow end-to-end

### Short Term (Next 2 Weeks)
6. ⏳ PayPal integration
7. ⏳ Razorpay integration
8. ⏳ Create checkout success/error pages
9. ⏳ Set up email notifications

### Medium Term (Phase 2)
10. ⏳ Square gateway
11. ⏳ Authorize.Net gateway
12. ⏳ Additional 8 gateways
13. ⏳ Advanced fraud detection

---

**Last Updated:** 2025-11-10
**Version:** 1.0
**Maintainer:** Books Ecommerce Team

For detailed implementation status and timeline, see: **COMPLETE_IMPLEMENTATION_ROADMAP.md**
