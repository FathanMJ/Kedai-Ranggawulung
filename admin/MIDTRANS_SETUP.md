# Midtrans Integration Setup Guide

## Overview
This restaurant management system now supports **Midtrans** payment gateway, a popular payment platform in Indonesia that supports multiple payment methods including:
- Credit/Debit Cards (Visa, Mastercard)
- Bank Transfers (BCA, BNI, Mandiri, CIMB, Permata)
- E-Wallets (OVO, Dana, LinkAja, GCash)
- Mobile Wallets (GoPay, ShopeePay)

## Security Improvements

### Bugs Fixed:
1. **SQL Injection Vulnerabilities** - Fixed in:
   - `admin/print_receipt.php` - Now uses prepared statements
   - `admin/pay_order.php` - Now uses parameterized queries
   - `admin/payments_reports.php` - Fixed customer_id query

2. **Payment Status Bypass** - Fixed:
   - Removed `order_status` from GET parameters
   - Status is now hardcoded to "Paid" after payment
   - Added validation checks to prevent double-payment

3. **Payment Amount Validation** - Added:
   - Validates that payment amount matches order total
   - Prevents partially paid orders
   - Checks if order is already paid

4. **Payment Verification** - Added:
   - Payment status tracking (pending, completed, failed, expired)
   - Verification status field for admin approval
   - Audit trail of all payment changes
   - Payment reference tracking for Midtrans transactions

## Setup Instructions

### 1. Register with Midtrans
1. Go to https://midtrans.com
2. Click "Daftar" (Register)
3. Fill in your business details
4. Verify your email
5. Dashboard will be available at https://dashboard.midtrans.com

### 2. Get Midtrans API Keys
1. Log in to Midtrans Dashboard
2. Go to **Settings → Configuration**
3. Copy your:
   - **Server Key** (for server-side operations)
   - **Client Key** (for client-side JavaScript)
4. Note: Keep **Server Key** secret and never share it!

### 3. Update Midtrans Configuration

**File:** `admin/config/midtrans.php`

Replace placeholder values with your actual credentials:
```php
define('MIDTRANS_SERVER_KEY', 'SB-Mid-xxxxxxxxxxxxxxxxxxxxxx');
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-xxxxxxxxxxxxxxxxxxxxxx');
define('MERCHANT_ID', 'YOUR_MERCHANT_ID');
```

Set environment (use `false` for testing, `true` for production):
```php
define('MIDTRANS_IS_PRODUCTION', false); // Change to true when ready for production
```

### 4. Run Database Migration

Execute the migration SQL file to add new payment tables:

**File:** `admin/migrations/001_add_midtrans_tables.sql`

Option A - Using phpMyAdmin:
1. Open phpMyAdmin
2. Select database `projek`
3. Go to SQL tab
4. Paste contents of `001_add_midtrans_tables.sql`
5. Click Execute

Option B - Using MySQL CLI:
```bash
mysql -u root projek < admin/migrations/001_add_midtrans_tables.sql
```

Option C - Using the setup script (recommended):
1. Navigate to: `http://localhost/Project%201/percobaan/Kedai/Restro/admin/setup/run_migration.php`
2. Click "Run Migration"

### 5. Configure Webhook (Important!)

**Server-to-Server Notification:**
1. Go to Midtrans Dashboard
2. **Settings → Configuration → Notification URL**
3. Add this URL in both HTTP and HTTPS sections:
   ```
   http://yourdomain.com/admin/webhooks/midtrans_webhook.php
   ```
   
   For localhost (development):
   ```
   http://localhost/Project%201/percobaan/Kedai/Restro/admin/webhooks/midtrans_webhook.php
   ```

4. Enable "Snap Redirect Mode" notifications
5. Save configuration

### 6. Test the Integration

#### Sandbox Testing:
Use Midtrans test credentials (from dashboard):

**Test Credit Card:**
- Card Number: `4811111111111114`
- Expiry: Any future date (e.g., 12/25)
- CVV: Any 3 digits

**Test Bank Transfer:**
- Use any bank option in the payment gateway

#### Production Setup:
1. Update `midtrans.php` and set `MIDTRANS_IS_PRODUCTION` to `true`
2. Replace with production API keys
3. Configure production webhook URL

## Payment Flow

### Admin Panel:
1. Go to **Payments** menu
2. Click **Pay Order** button for unpaid orders
3. **New page:** Select payment method:
   - **Cash** - Manual payment recording
   - **Paypal** - Manual recording
   - **Midtrans** - Automated payment gateway
4. If selecting **Midtrans**:
   - Enter payment code/reference (if applicable)
   - Click "Proceed to Payment"
   - Redirected to Midtrans Snap payment page
   - Complete payment with preferred method
   - Status automatically updated upon completion

### Customer Portal:
Similar payment flow available in customer dashboard for order payments.

## File Structure

### New Files Created:
```
admin/
├── config/
│   ├── midtrans.php                 # Midtrans configuration
│   └── MidtransHelper.php            # Midtrans helper class
├── pay_order_midtrans.php            # New payment page with Midtrans option
├── pay_with_midtrans.php             # Midtrans Snap payment page
├── midtrans_callback.php             # Payment callback handler
├── webhooks/
│   └── midtrans_webhook.php          # Server webhook handler
└── migrations/
    └── 001_add_midtrans_tables.sql   # Database migration

customer/
├── config/
│   ├── midtrans.php                 # Midtrans configuration (copy of admin)
│   └── MidtransHelper.php            # Midtrans helper class (copy of admin)
└── [similar payment files for customer]
```

### Modified Files:
- `admin/pay_order.php` - Updated with validation and Midtrans support
- `admin/print_receipt.php` - Fixed SQL injection
- `admin/payments_reports.php` - Fixed SQL injection
- `admin/payments.php` - Updated payment links

## Database Changes

### New Columns in `rpos_payments`:
- `payment_status` - Track payment state (pending, completed, failed, expired, refunded)
- `payment_reference` - Midtrans transaction ID
- `verified_by` - Admin who verified the payment
- `verification_status` - Admin verification state
- `updated_at` - Last update timestamp
- `notes` - Admin notes

### New Tables:
- `rpos_payment_audit` - Audit log of all payment changes
- `rpos_payment_methods` - Reference table for payment methods

## API Reference

### MidtransHelper Class Methods:

```php
// Get Snap token for payment
$token = $midtrans->getSnapToken($orderId, $amount, $customerDetails, $itemDetails);

// Check transaction status
$status = $midtrans->getTransactionStatus($orderId);

// Cancel transaction
$result = $midtrans->cancelTransaction($orderId);

// Refund transaction
$result = $midtrans->refundTransaction($orderId, $refundAmount);

// Verify webhook signature
$isValid = $midtrans->verifyWebhookSignature($data, $signatureKey);
```

## Troubleshooting

### Issue: "Payment token creation failed"
**Solution:**
- Verify Midtrans API keys are correct in `midtrans.php`
- Check if `curl` extension is enabled in PHP
- Verify internet connection (needs to connect to Midtrans API)

### Issue: "Webhook not received"
**Solution:**
- Ensure webhook URL is correctly configured in Midtrans Dashboard
- Check server firewall allows POST requests
- For localhost development, use ngrok to expose your server

### Issue: "Order already paid" error
**Solution:**
- This is a security feature to prevent double-payment
- Check `rpos_orders.order_status` in database

### Issue: Payment shows "pending" but doesn't update
**Solution:**
- Check `rpos_payment_audit` table for transaction history
- Verify Midtrans webhook is correctly configured
- Check server error logs: `error_log()` output

## Security Best Practices

1. **Never expose Server Key** - Keep it only in server-side code
2. **Always verify webhook signatures** - Prevents fraudulent notifications
3. **Use HTTPS in production** - Required for payment security
4. **Enable two-factor authentication** on Midtrans Dashboard
5. **Regularly review audit logs** - Check `rpos_payment_audit` table
6. **Keep API keys secure** - Store in environment variables (not hardcoded in production)

## Production Deployment Checklist

- [ ] Test all payment methods in sandbox
- [ ] Update API keys to production keys
- [ ] Set `MIDTRANS_IS_PRODUCTION` to `true`
- [ ] Configure production webhook URL
- [ ] Test webhook delivery
- [ ] Enable SSL/HTTPS certificate
- [ ] Review and test edge cases (double payment, refunds, timeouts)
- [ ] Document payment procedures for staff
- [ ] Set up monitoring and error alerts
- [ ] Train staff on new payment features

## Support & Documentation

- **Midtrans Documentation:** https://docs.midtrans.com
- **Midtrans Sandbox:** https://app.sandbox.midtrans.com
- **Midtrans Production:** https://app.midtrans.com
- **API Status Page:** https://status.midtrans.com

## Contact

For issues or questions about this integration, please contact:
- System Administrator
- Midtrans Support: https://midtrans.com/help

---

**Last Updated:** 2024
**System Version:** 1.0 with Midtrans Integration
