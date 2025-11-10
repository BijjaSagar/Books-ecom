# Bookory - Add Product Flow Guide

**Status:** ✅ Production Ready
**Language Support:** English, Arabic, Urdu, Hindi
**Based on:** Amazon Seller Central Product Details Form

---

## 🎯 Overview

The Add Product feature is a comprehensive multi-language form that allows admin users to add books to the system with detailed information, following the Amazon Seller Central product structure.

---

## 📍 Access Points

### Admin Dashboard
- **URL:** `/admin/product-add.php`
- **Default Language:** English
- **Change Language:** `/admin/product-add.php?lang=ar` (Arabic), `/admin/product-add.php?lang=ur` (Urdu), `/admin/product-add.php?lang=hi` (Hindi)

### Language Switcher
Located in the header of the product add form:
- 🇬🇧 English
- 🇸🇦 العربية (Arabic)
- 🇵🇰 اردو (Urdu)
- 🇮🇳 हिंदी (Hindi)

---

## 📋 Product Form Sections

### 1. **Product Details** (Required)
Essential information about the product:

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| **Item Name** | Text | Product title (Required) | "Islamic Belief 15 Booklets" |
| **Brand Name** | Text | Publisher/Brand | "Al Ittehad Publication" |
| **ISBN** | Text | International Standard Book Number | "9780946791" |
| **Item Type** | Text | Type of product | "Booklets" |
| **Manufacturer** | Text | Who made the product | "Al Ittehad Publication Pvt. Ltd." |
| **Part Number** | Text | Internal part reference | "RIV001" |
| **Description** | Textarea | Detailed product description (Required) | Long form description |
| **Number of Items** | Number | Quantity available | 15 |
| **Number of Pages** | Number | Total pages | 388 |

---

### 2. **Keywords** (Optional but Recommended)
Multiple subject keywords for searchability:

**Features:**
- ✅ Add multiple keywords
- ✅ Remove keywords
- ✅ Each keyword is independent

**Examples:**
```
- Mazooron, Pasmandah Tabagat
- Jihad, Ghulami, Jizye Ka Masla
- Haiwanat, Jamadat, Nabatai Ke Huqooq
- Watan, Reaya, Hukmranon Ke Huqooq
- Afrad e Muaashra, Rishtedarion, Islam aur Huqooq e Insani
```

**How to Use:**
```
1. Enter first keyword
2. Click "Add Keyword" button
3. Enter next keyword
4. Repeat as needed
5. Click "×" to remove a keyword
```

---

### 3. **Edition Details** (Optional)
Book specific information:

| Field | Type | Options/Example |
|-------|------|-----------------|
| **Edition** | Text | "First Edition", "2nd Edition" |
| **Format** | Dropdown | Box calendar, Subtitled, Picture Book, Hardcover, Paperback |
| **Minimum Reading Age** | Number | 0-100 (years) |
| **Maximum Reading Age** | Number | 0-100 (years) |
| **Minimum Recommended Grade** | Text | "1, 2, Kindergarten" |
| **Maximum Recommended Grade** | Text | "10, 12, High School" |

---

### 4. **Languages**  (Multiple Support)
Books available in different languages:

**Supported Languages:**
- 🇬🇧 English (en)
- 🇸🇦 العربية - Arabic (ar)
- 🇵🇰 اردو - Urdu (ur)
- 🇮🇳 हिंदी - Hindi (hi)

**How to Use:**
```
1. Select first language from dropdown
2. Click "Add More" to add another language
3. Select different language
4. Repeat for all available languages
5. Click "×" to remove a language
```

**Example:**
```
Language 1: Urdu (ur)
Language 2: English (en)
Language 3: Arabic (ar)
```

---

### 5. **Publication & Classification** (Optional)

| Field | Type | Example |
|-------|------|---------|
| **Publication Date** | Date | 1/5/2025 |
| **Genre** | Dropdown | Fiction, Non-fiction, Mystery, Romance, etc. |
| **School Type** | Text | "Elementary School", "High School" |
| **Textbook Type** | Dropdown | Core, Supplementary, Reference |
| **Series Title** | Text | "Mahwar Huqooq Insani Aur Islam" |
| **Series Number** | Number | 3 |
| **Volume** | Text | "3, IV" |

---

## 🌐 Multi-Language Support

### How It Works

**Language Detection:**
- System detects language from URL parameter `?lang=XX`
- Stores preference in session
- All UI labels change based on selected language
- RTL (Right-to-Left) support for Arabic and Urdu automatically enabled

**Language UI Elements:**
```
Header Language Switcher:
├── English (default)
├── العربية (Arabic)
├── اردو (Urdu)
└── हिंदी (Hindi)
```

### Text Direction
- **LTR** (Left-to-Right): English, Hindi
- **RTL** (Right-to-Left): Arabic, Urdu

---

## 💾 Data Storage

### Tables Created

**1. Products Table** (Enhanced)
- Stores all product details
- New columns: item_type, manufacturer, edition, format, grades, school_type, series info, etc.

**2. product_keywords Table**
```sql
- product_id (FK)
- keyword (TEXT)
- created_at (TIMESTAMP)
```

**3. product_languages Table**
```sql
- product_id (FK)
- language (VARCHAR 10)
- created_at (TIMESTAMP)
```

**4. language_translations Table** (For UI strings)
```sql
- language_code (VARCHAR 10)
- translation_key (VARCHAR 100)
- translation_value (LONGTEXT)
```

---

## 🚀 How to Use

### Step 1: Access the Form
```
Visit: https://yourdomain.com/admin/product-add.php
Default Language: English
```

### Step 2: Select Language
```
Click one of the language buttons in header:
- English
- العربية
- اردو
- हिंदी

All labels and instructions will change.
```

### Step 3: Fill Product Details
```
1. Enter Item Name (Required)
2. Enter Brand Name
3. Enter ISBN
4. Enter Item Type
5. Enter Manufacturer
6. Enter Description (Required)
7. Enter Number of Items
8. Enter Number of Pages
```

### Step 4: Add Keywords
```
1. Click first keyword field
2. Enter keyword
3. Click "Add Keyword" button
4. Enter next keyword
5. Repeat for all keywords
```

### Step 5: Select Book Details
```
1. Choose Edition
2. Select Format
3. Enter Reading Ages
4. Enter Grade Levels
5. Fill other optional fields
```

### Step 6: Add Languages
```
1. Select first language
2. Click "Add More"
3. Select second language
4. Repeat for all languages
```

### Step 7: Add Publication Info
```
1. Select Publication Date
2. Choose Genre
3. Fill Series Title
4. Enter Series Number
5. Complete remaining fields
```

### Step 8: Save
```
Click "Save Product" button
System saves:
- Product details to products table
- Keywords to product_keywords table
- Languages to product_languages table
```

---

## 📊 Database Relationships

```
┌─────────────────────────────────────┐
│        PRODUCTS TABLE               │
│  (Main product information)         │
│  - id (PK)                          │
│  - name, brand, isbn, etc.          │
│  - item_type, manufacturer          │
│  - edition, format, grades          │
│  - school_type, series info         │
└────────┬────────────────────────────┘
         │
    ┌────┴────────────────────┬──────────────────────┐
    │                         │                      │
┌───▼──────────────┐  ┌──────▼─────────────┐  ┌────▼──────────────┐
│PRODUCT_KEYWORDS  │  │PRODUCT_LANGUAGES   │  │LANGUAGE_TRANS.    │
│  - id (PK)       │  │  - id (PK)         │  │  - id (PK)        │
│  - product_id(FK)│  │  - product_id(FK)  │  │  - language_code  │
│  - keyword       │  │  - language        │  │  - translation_key│
│  - created_at    │  │  - created_at      │  │  - translation_val│
└──────────────────┘  └────────────────────┘  └───────────────────┘
```

---

## 🔒 Security Features

✅ **Admin-only access** - Session validation required
✅ **Prepared statements** - SQL injection prevention
✅ **Input validation** - Data type checking
✅ **XSS protection** - htmlspecialchars on outputs
✅ **Database integrity** - Foreign key constraints

---

## 📱 Responsive Design

**Device Support:**
- ✅ Desktop (Full width form)
- ✅ Tablet (2-column layout)
- ✅ Mobile (1-column stacked)

**Breakpoints:**
- Desktop: > 768px (col-md-6)
- Tablet: 576px - 768px (col-sm-12)
- Mobile: < 576px (full width)

---

## 🌍 Language Translations

### Supported Languages

**English (en)**
- Default language
- All labels in English

**Arabic (العربية) (ar)**
- RTL support enabled
- All labels translated
- Format: Box calendar → صندوق التقويم

**Urdu (اردو) (ur)**
- RTL support enabled
- Supports Urdu text input/output
- Format: Box calendar → باکس کیلنڈر

**Hindi (हिंदी) (hi)**
- LTR support
- All labels in Devanagari script
- Format: Box calendar → बॉक्स कैलेंडर

---

## 🛠️ Technical Details

### File Locations
```
/admin/product-add.php           - Main form
/admin/migrations/004_*.sql      - Database setup
```

### Session Variables
```php
$_SESSION['language']     - Current language code
$_SESSION['admin_id']     - Admin authentication
```

### URL Parameters
```
?lang=en  - English
?lang=ar  - Arabic
?lang=ur  - Urdu
?lang=hi  - Hindi
```

### Form Submission
```
Method: POST
Action: product-add.php
Encoding: application/x-www-form-urlencoded
```

---

## ✅ Validation Rules

| Field | Rule | Error Message |
|-------|------|---------------|
| Item Name | Required, Text | "Item name is required" |
| Description | Required, Textarea | "Description is required" |
| ISBN | Optional, Alphanumeric | - |
| Number of Items | Optional, Integer > 0 | - |
| Number of Pages | Optional, Integer > 0 | - |
| Reading Ages | Optional, Integer 0-100 | - |
| Languages | Optional, Valid codes | - |

---

## 🎨 UI/UX Features

**Visual Hierarchy:**
- Section titles with blue underline
- Color-coded buttons:
  - Blue: "Add More" buttons
  - Yellow/Gold: "Save" button
  - Gray: "Cancel" button
  - Red: "Remove" buttons
- Icons: ✓ Success, ✗ Error

**Accessibility:**
- Proper form labels with associations
- Required field indicators (*)
- Help text where needed
- Keyboard navigation support
- ARIA labels for screen readers

---

## 🚀 Deployment Checklist

- [ ] Run migration: `004_add_product_keywords_and_languages.sql`
- [ ] Verify products table has all new columns
- [ ] Test with all 4 languages
- [ ] Test on mobile devices
- [ ] Verify keywords are saved correctly
- [ ] Verify languages are saved correctly
- [ ] Test RTL display for Arabic/Urdu
- [ ] Verify form validation

---

## 📝 Example Product Entry

**Scenario:** Add an Urdu Islamic book with multiple keywords and languages

**Steps:**
```
1. Click language switcher: اردو
2. Form labels change to Urdu
3. Fill Item Name: "مہوار حقوق انسانی اور اسلام"
4. Fill Brand: "Al Ittehad Publication"
5. Fill ISBN: "9380946791"
6. Fill Description: (Detailed Urdu text)
7. Add Keywords:
   - Mazooron, Pasmandah Tabagat
   - Jihad, Ghulami
   - Haiwanat, Jamadat
8. Select Languages:
   - اردو (Urdu)
   - English
   - العربية (Arabic)
9. Click "Save Product"
10. System confirms: "Product added successfully!"
```

---

## 🔄 Future Enhancements

- [ ] Bulk product upload (CSV/Excel)
- [ ] Image upload for book covers
- [ ] Author management
- [ ] Publisher management
- [ ] Category tags
- [ ] Rating and reviews
- [ ] Inventory management integration
- [ ] Price management per language
- [ ] Search optimization

---

## 📞 Support

**Issues?** Check these:
1. Is admin logged in? Check `/admin/index.php`
2. Is database migrated? Run `004_add_product_keywords_and_languages.sql`
3. Are tables created? Check in database
4. Language not showing? Clear browser cache

---

**Created:** November 2024
**Version:** 1.0
**Status:** Production Ready ✅
