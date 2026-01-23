# 📚📱 Digital & Physical Products Guide

Complete guide for managing both physical books and digital eBooks in your Bookory store.

---

## 🎯 Overview

Bookory supports **four product types**:

1. **📦 Physical Book Only** - Hardcopy books with shipping
2. **📱 Digital Book Only** - PDF/eBook downloads (no shipping)
3. **📦📱 Both (Physical + Digital)** - Bundle with both formats
4. **🔗 Affiliate Link** - Link to external store

---

## ✨ Features

### For Physical Products
- ✅ Inventory/stock management
- ✅ Shipping cost calculation
- ✅ Physical dimensions & weight
- ✅ Binding types (Hardcover, Paperback, etc.)
- ✅ Multiple product images

### For Digital Products
- ✅ Secure file downloads (PDF, EPUB, MOBI, AZW)
- ✅ Automatic delivery after payment
- ✅ Download tracking (count, timestamps)
- ✅ Expiration dates (optional)
- ✅ No shipping costs
- ✅ Unlimited "stock"

### For Both (Hybrid) Products
- ✅ All features from both types
- ✅ Customer gets hardcopy + digital copy
- ✅ Separate pricing or bundle discount
- ✅ Two delivery methods (shipping + download)

---

## 🛠️ How to Add Products

### 1. Physical Book Only

**Use Case:** Selling hardcopy books that will be shipped

**Steps:**
1. Admin Panel → **Products** → **Add New Product**
2. Fill in basic details (Title, Author, Description, Price)
3. Set **Product Type**: `📦 Physical Book Only`
4. Upload **Cover Image** (required)
5. Set **Stock Quantity** (e.g., 50 copies)
6. Add product details:
   - ISBN-10 / ISBN-13
   - Publisher
   - Binding Type (Paperback/Hardcover)
   - Pages, Dimensions, Weight
7. Click **Save Product**

**Result:** Product will require shipping and stock management

---

### 2. Digital Book Only (eBook/PDF)

**Use Case:** Selling downloadable PDFs or eBooks

**Steps:**
1. Admin Panel → **Products** → **Add New Product**
2. Fill in basic details (Title, Author, Description, Price)
3. Set **Product Type**: `📱 Digital Book Only (PDF/eBook)`
4. Upload **Cover Image** (required)
5. Upload **Digital File**:
   - Click the digital file upload section
   - Select your PDF, EPUB, MOBI, or AZW file
   - Accepted formats: `.pdf`, `.epub`, `.mobi`, `.azw`, `.azw3`
   - Max file size: 50MB (recommended)
6. Set **Binding Type**: `eBook`
7. Stock Quantity will automatically be set to unlimited (999999)
8. Click **Save Product**

**Result:**
- No shipping required
- Customer receives instant download link after purchase
- File stored securely in `/uploads/digital_products/`

---

### 3. Both (Physical + Digital Bundle)

**Use Case:** Offer hardcopy book + digital PDF as a bundle

**Steps:**
1. Admin Panel → **Products** → **Add New Product**
2. Fill in basic details (Title, Author, Description, Price)
3. Set **Product Type**: `📦📱 Both (Physical + Digital)`
4. Upload **Cover Image**
5. Upload **Digital File** (PDF/EPUB)
6. Set **Stock Quantity** (for physical copies)
7. Add all physical product details (ISBN, dimensions, etc.)
8. Click **Save Product**

**Result:**
- Physical book shipped to customer
- Digital file available for immediate download
- Best value for customers!

**Pricing Strategy:**
```
Physical only: $25.00
Digital only:  $15.00
Both bundle:   $30.00 (save $10!)
```

---

## 📥 Supported Digital File Formats

| Format | Extension | Best For | Notes |
|--------|-----------|----------|-------|
| **PDF** | `.pdf` | Universal compatibility | Most popular, works everywhere |
| **EPUB** | `.epub` | eReaders, tablets | Industry standard for eBooks |
| **MOBI** | `.mobi` | Kindle devices | Amazon Kindle format |
| **AZW** | `.azw`, `.azw3` | Kindle devices | Enhanced Kindle format |

**Recommended Format:** PDF (best compatibility across all devices)

**File Size Limits:**
- Recommended: Under 50MB
- Maximum: 100MB per file

---

## 🔄 How Digital Downloads Work

### Customer Purchase Flow

1. **Customer adds digital product to cart**
2. **Proceeds to checkout** (no shipping address for digital-only)
3. **Completes payment** (PayPal, Stripe, Razorpay)
4. **Order confirmed** ✅
5. **Automatic digital delivery:**
   - Download link sent via email
   - Available in Customer Dashboard → My Orders
   - Click "Download" button

### Download Security Features

✅ **Purchase Verification** - Only paying customers can download
✅ **Expiration Tracking** - Optional expiration dates
✅ **Download Counting** - Track how many times downloaded
✅ **Secure URLs** - Unique, non-guessable download links
✅ **File Protection** - Files stored outside public web directory

---

## 📊 Managing Digital Products

### View Digital Downloads (Admin)

**Location:** Admin Panel → **Reports** → **Digital Downloads**

You can see:
- Which customers downloaded which files
- Download timestamps
- Download counts
- Order information

### Customer View

**Location:** Customer Dashboard → **My Orders** → **Order Details**

Customers can:
- View all their digital purchases
- Download files multiple times
- See download history
- Access files anytime (unless expired)

---

## 🎨 Product Display Examples

### Shop Page Display

```
┌─────────────────────────────┐
│   [Book Cover Image]        │
│                              │
│  "The Great Novel"          │
│  by John Doe                │
│                              │
│  📱 Digital Download         │
│  $15.99                     │
│                              │
│  [Add to Cart]              │
└─────────────────────────────┘
```

### Product Details Page

Shows:
- Product type badge (Physical/Digital/Both)
- For digital: File format and size
- For physical: Shipping info, dimensions
- Download button (after purchase)

---

## 💡 Best Practices

### For Physical Books
1. ✅ Add high-quality cover images (300x400px minimum)
2. ✅ Include accurate dimensions and weight for shipping
3. ✅ Keep stock quantity updated
4. ✅ Use clear, detailed descriptions
5. ✅ Add ISBN for better discoverability

### For Digital Books
1. ✅ Optimize PDF file size (compress images)
2. ✅ Include table of contents in PDF
3. ✅ Add bookmarks for easy navigation
4. ✅ Test download link before publishing
5. ✅ Provide EPUB for better eReader experience
6. ✅ Set realistic pricing ($5-$15 for eBooks)

### For Both (Bundles)
1. ✅ Clearly show value proposition ("Get both for $X")
2. ✅ Price bundle slightly lower than buying separately
3. ✅ Mention "Instant Digital Access" as selling point
4. ✅ Highlight in product description

---

## 🔧 Technical Setup

### File Upload Limits

Configure in `.env`:

```ini
# Max upload size in bytes (50MB)
MAX_UPLOAD_SIZE=52428800

# Max digital file size (100MB)
MAX_DIGITAL_FILE_SIZE=104857600

# Allowed file extensions
ALLOWED_DIGITAL_FORMATS=pdf,epub,mobi,azw,azw3
```

### Directory Structure

```
Books-ecom/
├── uploads/
│   ├── digital_products/       # Digital files stored here
│   │   ├── 1234567890_book.pdf
│   │   └── .gitkeep
│   └── bulk/                   # CSV imports
├── public/
│   └── images/
│       └── products/           # Product cover images
└── ...
```

### File Permissions

```bash
# Digital products directory (secure)
chmod 755 uploads/digital_products/

# Files should not be publicly accessible
# Access only via download API
```

---

## 📧 Email Notifications

### For Digital Products

Customers receive automated emails with:

**Order Confirmation Email:**
```
Subject: Your Order #12345 - Download Your eBook

Hi John,

Thank you for your purchase!

Your digital product is ready for download:

📚 The Great Novel
📱 Digital PDF (5.2 MB)

[Download Now]

This download link is valid for 30 days.

Questions? Reply to this email.
```

**Email Template:** `includes/email_templates/digital_delivery.html`

---

## 🛡️ Security Considerations

### Protecting Digital Files

1. **Files stored outside web root** ✅
   - Not directly accessible via URL
   - Must go through download API

2. **Download verification** ✅
   - Checks customer purchased product
   - Validates order status

3. **Unique download IDs** ✅
   - Non-guessable URLs
   - No direct file paths exposed

4. **Optional expiration** ✅
   - Set expiration dates if needed
   - Default: No expiration

### Database Security

Digital downloads tracked in `digital_downloads` table:

```sql
CREATE TABLE digital_downloads (
    id INT PRIMARY KEY,
    order_id INT,           -- FK to orders
    product_id INT,         -- FK to products
    customer_id INT,        -- FK to users
    download_count INT,     -- How many times downloaded
    expires_at TIMESTAMP,   -- Optional expiration
    created_at TIMESTAMP,
    last_downloaded_at TIMESTAMP
);
```

---

## 🚀 Quick Start Checklist

### Adding Your First Digital Product

- [ ] Prepare your PDF/EPUB file (under 50MB)
- [ ] Create product cover image (300x400px)
- [ ] Go to Admin → Products → Add New
- [ ] Set product type to "Digital Book Only"
- [ ] Upload cover image
- [ ] Upload digital file
- [ ] Set binding type to "eBook"
- [ ] Write description mentioning file format
- [ ] Set price ($5-$15 recommended for eBooks)
- [ ] Save product
- [ ] Test by making a purchase
- [ ] Verify download link works

### Adding Your First Hybrid Product (Physical + Digital)

- [ ] Prepare physical book details (ISBN, dimensions, weight)
- [ ] Prepare digital file (PDF/EPUB)
- [ ] Create product cover image
- [ ] Go to Admin → Products → Add New
- [ ] Set product type to "Both (Physical + Digital)"
- [ ] Upload cover image
- [ ] Upload digital file
- [ ] Fill in physical details (stock, ISBN, pages, etc.)
- [ ] Set bundle price (should be less than buying separately)
- [ ] Highlight "Get both formats!" in description
- [ ] Save product
- [ ] Test checkout process
- [ ] Verify both shipping and download work

---

## ❓ Frequently Asked Questions

### Can customers download files multiple times?

**Yes!** Once purchased, customers can download as many times as needed (unless you set an expiration date).

### What happens if I update the digital file?

Upload a new version and it will replace the old file. Existing customers will get the updated version when they download.

### How do I set download limits?

Currently unlimited. To add limits, you can set expiration dates in the database `digital_downloads.expires_at` field.

### Can I sell audiobooks?

Yes! Upload audio files (MP3, M4B) - just add these formats to the allowed extensions.

### What if customer loses download link?

They can always access it from:
1. Customer Dashboard → My Orders
2. Request new email from admin
3. Admin can resend download link

### Can I offer both formats at different prices?

Yes! Create two separate products:
- Product 1: "Book Name (Physical)" - $25
- Product 2: "Book Name (Digital)" - $15
- Product 3: "Book Name (Bundle)" - $30

---

## 🎯 Use Cases & Examples

### Use Case 1: Self-Published Author

**Scenario:** You wrote an eBook and want to sell it directly

**Setup:**
- Create digital-only product
- Upload your PDF
- Price at $9.99
- No inventory worries
- Instant delivery to customers
- 100% of revenue (minus payment fees)

### Use Case 2: Academic Bookstore

**Scenario:** Sell textbooks with free PDF access

**Setup:**
- Create "Both" product type
- Physical textbook: Stock of 100 copies
- Digital PDF: Same content
- Price bundle at $79.99 (physical) + free PDF
- Students get both formats

### Use Case 3: Limited Edition with Bonus

**Scenario:** Physical signed book + bonus digital content

**Setup:**
- Physical product: Signed hardcover
- Digital file: Bonus chapter + author interview
- Bundle price: $45
- Limited stock: 50 signed copies
- Digital bonus adds value

---

## 🔗 Related Documentation

- [Product Management Guide](FEATURES.md#product-management)
- [Order Processing](FEATURES.md#order-management)
- [Email Templates](includes/email_templates/)
- [API Documentation](API_DOCUMENTATION.md#digital-downloads)

---

## 🆘 Troubleshooting

### Issue: Digital file not uploading

**Solutions:**
1. Check file size (must be under 50MB)
2. Verify file format (PDF, EPUB, MOBI, AZW only)
3. Check `/uploads/digital_products/` exists and is writable
4. Review server PHP upload limits:
   ```bash
   # Check PHP limits
   php -i | grep upload_max_filesize
   php -i | grep post_max_size
   ```

### Issue: Customer can't download file

**Solutions:**
1. Verify order status is "completed" or "paid"
2. Check download link hasn't expired
3. Verify file exists in `/uploads/digital_products/`
4. Check server file permissions
5. Review error logs: `logs/YYYY-MM-DD.log`

### Issue: Download link returns 404

**Solutions:**
1. Verify digital_file_path in database is correct
2. Check file physically exists on server
3. Verify download API endpoint is accessible
4. Check `.htaccess` rules aren't blocking `/api/download-product.php`

---

## 📈 Analytics & Reporting

Track digital product performance:

**Admin Panel → Sales Dashboard**
- Digital vs Physical sales ratio
- Most downloaded products
- Average downloads per customer
- Revenue by product type

**Admin Panel → Reports → Digital Downloads**
- Download logs
- Customer download history
- Popular file formats
- Download completion rates

---

## 🎓 Advanced Tips

### 1. Creating Product Bundles

Offer special pricing for multiple digital books:

```
Individual books: $12 each
3-book bundle: $30 (save $6!)
Complete series (10 books): $89 (save $31!)
```

### 2. Seasonal Promotions

Create coupon codes for digital products:
- `EBOOK50` - 50% off all eBooks
- `SUMMER2025` - $5 off physical + digital bundles

### 3. Cross-Selling

In product descriptions, link to related formats:
- "Also available in hardcover"
- "Get the complete series bundle"
- "Save 25% when you buy both formats"

---

**Last Updated:** November 15, 2025
**Version:** 1.0
**Platform:** Bookory Books Ecommerce
