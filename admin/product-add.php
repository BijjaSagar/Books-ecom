<?php
session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Get current language from session or URL
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$_SESSION['language'] = $lang;

// Language translations
$translations = [
    'en' => [
        'add_product' => 'Add Product',
        'basic_info' => 'Basic Information',
        'product_details' => 'Product Details',
        'images' => 'Product Images',
        'pricing' => 'Pricing & Inventory',
        'seo' => 'SEO & Marketing',
        'categories' => 'Categories & Classification',
        'item_name' => 'Product Title',
        'author' => 'Author',
        'brand_name' => 'Brand Name',
        'isbn' => 'ISBN / External Product ID',
        'isbn_10' => 'ISBN-10',
        'isbn_13' => 'ISBN-13',
        'ean' => 'EAN',
        'sku' => 'SKU',
        'item_type' => 'Item Type',
        'manufacturer' => 'Manufacturer',
        'publisher' => 'Publisher',
        'description' => 'Product Description',
        'num_items' => 'Number of Items',
        'part_number' => 'Part Number',
        'keywords' => 'Subject Keywords',
        'add_keyword' => 'Add Keyword',
        'pages' => 'Pages',
        'num_pages' => 'Number of Pages',
        'edition' => 'Edition',
        'min_reading_age' => 'Minimum Reading Age',
        'max_reading_age' => 'Maximum Reading Age',
        'format' => 'Format',
        'binding_type' => 'Binding Type',
        'language' => 'Language',
        'language_type' => 'Language Type',
        'publication_date' => 'Publication Date',
        'genre' => 'Genre',
        'min_grade' => 'Minimum Recommended Grade',
        'max_grade' => 'Maximum Recommended Grade',
        'school_type' => 'School Type',
        'series_title' => 'Series Title',
        'series_number' => 'Series Number',
        'volume' => 'Volume',
        'textbook_type' => 'Textbook Type',
        'price' => 'Selling Price',
        'original_price' => 'Original Price (MRP)',
        'stock_quantity' => 'Stock Quantity',
        'status' => 'Status',
        'product_type' => 'Product Type',
        'featured' => 'Featured Product',
        'category' => 'Category',
        'dimensions' => 'Dimensions',
        'weight' => 'Weight',
        'affiliate_link' => 'Affiliate Link',
        'meta_title' => 'SEO Meta Title',
        'meta_description' => 'SEO Meta Description',
        'cover_image' => 'Cover Image',
        'additional_images' => 'Additional Images',
        'save' => 'Save Product',
        'cancel' => 'Cancel',
        'select' => 'Select...',
        'add_more' => 'Add More',
        'remove' => 'Remove',
    ],
    'ar' => [
        'add_product' => 'إضافة منتج',
        'basic_info' => 'المعلومات الأساسية',
        'product_details' => 'تفاصيل المنتج',
        'images' => 'صور المنتج',
        'pricing' => 'التسعير والمخزون',
        'seo' => 'SEO والتسويق',
        'categories' => 'الفئات والتصنيف',
        'item_name' => 'عنوان المنتج',
        'author' => 'المؤلف',
        'brand_name' => 'اسم العلامة التجارية',
        'isbn' => 'ISBN / معرف المنتج الخارجي',
        'isbn_10' => 'ISBN-10',
        'isbn_13' => 'ISBN-13',
        'ean' => 'EAN',
        'sku' => 'SKU',
        'item_type' => 'نوع العنصر',
        'manufacturer' => 'الشركة المصنعة',
        'publisher' => 'الناشر',
        'description' => 'وصف المنتج',
        'num_items' => 'عدد العناصر',
        'part_number' => 'رقم الجزء',
        'keywords' => 'الكلمات الرئيسية للموضوع',
        'add_keyword' => 'إضافة كلمة رئيسية',
        'pages' => 'الصفحات',
        'num_pages' => 'عدد الصفحات',
        'edition' => 'الطبعة',
        'min_reading_age' => 'الحد الأدنى لعمر القراءة',
        'max_reading_age' => 'الحد الأقصى لعمر القراءة',
        'format' => 'التنسيق',
        'binding_type' => 'نوع التجليد',
        'language' => 'اللغة',
        'language_type' => 'نوع اللغة',
        'publication_date' => 'تاريخ النشر',
        'genre' => 'النوع الأدبي',
        'min_grade' => 'الحد الأدنى للصف الموصى به',
        'max_grade' => 'الحد الأقصى للصف الموصى به',
        'school_type' => 'نوع المدرسة',
        'series_title' => 'عنوان السلسلة',
        'series_number' => 'رقم السلسلة',
        'volume' => 'المجلد',
        'textbook_type' => 'نوع الكتاب المدرسي',
        'price' => 'سعر البيع',
        'original_price' => 'السعر الأصلي',
        'stock_quantity' => 'كمية المخزون',
        'status' => 'الحالة',
        'product_type' => 'نوع المنتج',
        'featured' => 'منتج مميز',
        'category' => 'الفئة',
        'dimensions' => 'الأبعاد',
        'weight' => 'الوزن',
        'affiliate_link' => 'رابط التسويق بالعمولة',
        'meta_title' => 'عنوان SEO',
        'meta_description' => 'وصف SEO',
        'cover_image' => 'صورة الغلاف',
        'additional_images' => 'صور إضافية',
        'save' => 'حفظ المنتج',
        'cancel' => 'إلغاء',
        'select' => 'اختر...',
        'add_more' => 'إضافة المزيد',
        'remove' => 'إزالة',
    ],
    'ur' => [
        'add_product' => 'پروڈکٹ شامل کریں',
        'basic_info' => 'بنیادی معلومات',
        'product_details' => 'پروڈکٹ کی تفصیلات',
        'images' => 'پروڈکٹ کی تصاویر',
        'pricing' => 'قیمت اور انوینٹری',
        'seo' => 'SEO اور مارکیٹنگ',
        'categories' => 'زمرہ جات اور درجہ بندی',
        'item_name' => 'پروڈکٹ کا عنوان',
        'author' => 'مصنف',
        'brand_name' => 'برانڈ کا نام',
        'isbn' => 'ISBN / بیرونی پروڈکٹ ID',
        'isbn_10' => 'ISBN-10',
        'isbn_13' => 'ISBN-13',
        'ean' => 'EAN',
        'sku' => 'SKU',
        'item_type' => 'چیز کی قسم',
        'manufacturer' => 'مینوفیکچرر',
        'publisher' => 'پبلشر',
        'description' => 'پروڈکٹ کی تفصیل',
        'num_items' => 'چیزوں کی تعداد',
        'part_number' => 'حصے کی تعداد',
        'keywords' => 'موضوع کے کلیدی الفاظ',
        'add_keyword' => 'کلیدی لفظ شامل کریں',
        'pages' => 'صفحات',
        'num_pages' => 'صفحات کی تعداد',
        'edition' => 'ایڈیشن',
        'min_reading_age' => 'کم سے کم پڑھنے کی عمر',
        'max_reading_age' => 'زیادہ سے زیادہ پڑھنے کی عمر',
        'format' => 'فارمیٹ',
        'binding_type' => 'بائنڈنگ کی قسم',
        'language' => 'زبان',
        'language_type' => 'زبان کی قسم',
        'publication_date' => 'اشاعت کی تاریخ',
        'genre' => 'صنف',
        'min_grade' => 'کم سے کم تجویز کردہ گریڈ',
        'max_grade' => 'زیادہ سے زیادہ تجویز کردہ گریڈ',
        'school_type' => 'سکول کی قسم',
        'series_title' => 'سیریز کا عنوان',
        'series_number' => 'سیریز نمبر',
        'volume' => 'حجم',
        'textbook_type' => 'ٹیکسٹ بک کی قسم',
        'price' => 'فروخت کی قیمت',
        'original_price' => 'اصل قیمت',
        'stock_quantity' => 'اسٹاک کی مقدار',
        'status' => 'حیثیت',
        'product_type' => 'پروڈکٹ کی قسم',
        'featured' => 'نمایاں پروڈکٹ',
        'category' => 'زمرہ',
        'dimensions' => 'جہتیں',
        'weight' => 'وزن',
        'affiliate_link' => 'ملحق لنک',
        'meta_title' => 'SEO میٹا ٹائٹل',
        'meta_description' => 'SEO میٹا تفصیل',
        'cover_image' => 'کور امیج',
        'additional_images' => 'اضافی تصاویر',
        'save' => 'پروڈکٹ محفوظ کریں',
        'cancel' => 'منسوخ کریں',
        'select' => 'منتخب کریں...',
        'add_more' => 'مزید شامل کریں',
        'remove' => 'ہٹائیں',
    ],
    'hi' => [
        'add_product' => 'उत्पाद जोड़ें',
        'basic_info' => 'बुनियादी जानकारी',
        'product_details' => 'उत्पाद विवरण',
        'images' => 'उत्पाद चित्र',
        'pricing' => 'मूल्य निर्धारण और सूची',
        'seo' => 'SEO और विपणन',
        'categories' => 'श्रेणियां और वर्गीकरण',
        'item_name' => 'उत्पाद शीर्षक',
        'author' => 'लेखक',
        'brand_name' => 'ब्रांड का नाम',
        'isbn' => 'ISBN / बाहरी उत्पाद ID',
        'isbn_10' => 'ISBN-10',
        'isbn_13' => 'ISBN-13',
        'ean' => 'EAN',
        'sku' => 'SKU',
        'item_type' => 'आइटम का प्रकार',
        'manufacturer' => 'निर्माता',
        'publisher' => 'प्रकाशक',
        'description' => 'उत्पाद विवरण',
        'num_items' => 'आइटम की संख्या',
        'part_number' => 'भाग संख्या',
        'keywords' => 'विषय कीवर्ड',
        'add_keyword' => 'कीवर्ड जोड़ें',
        'pages' => 'पृष्ठ',
        'num_pages' => 'पृष्ठों की संख्या',
        'edition' => 'संस्करण',
        'min_reading_age' => 'न्यूनतम पढ़ने की आयु',
        'max_reading_age' => 'अधिकतम पढ़ने की आयु',
        'format' => 'प्रारूप',
        'binding_type' => 'बाइंडिंग प्रकार',
        'language' => 'भाषा',
        'language_type' => 'भाषा का प्रकार',
        'publication_date' => 'प्रकाशन की तारीख',
        'genre' => 'शैली',
        'min_grade' => 'न्यूनतम अनुशंसित ग्रेड',
        'max_grade' => 'अधिकतम अनुशंसित ग्रेड',
        'school_type' => 'स्कूल का प्रकार',
        'series_title' => 'श्रृंखला का शीर्षक',
        'series_number' => 'श्रृंखला संख्या',
        'volume' => 'मात्रा',
        'textbook_type' => 'पाठ्यपुस्तक का प्रकार',
        'price' => 'विक्रय मूल्य',
        'original_price' => 'मूल कीमत',
        'stock_quantity' => 'स्टॉक मात्रा',
        'status' => 'स्थिति',
        'product_type' => 'उत्पाद प्रकार',
        'featured' => 'विशेष उत्पाद',
        'category' => 'श्रेणी',
        'dimensions' => 'आयाम',
        'weight' => 'वजन',
        'affiliate_link' => 'संबद्ध लिंक',
        'meta_title' => 'SEO मेटा शीर्षक',
        'meta_description' => 'SEO मेटा विवरण',
        'cover_image' => 'कवर छवि',
        'additional_images' => 'अतिरिक्त चित्र',
        'save' => 'उत्पाद सहेजें',
        'cancel' => 'रद्द करें',
        'select' => 'चुनें...',
        'add_more' => 'अधिक जोड़ें',
        'remove' => 'हटाएं',
    ],
];

$t = $translations[$lang] ?? $translations['en'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic Information
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $brand_name = trim($_POST['brand_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // ISBN & Identifiers
        $isbn = trim($_POST['isbn'] ?? '');
        $isbn_10 = trim($_POST['isbn_10'] ?? '');
        $isbn_13 = trim($_POST['isbn_13'] ?? '');
        $ean = trim($_POST['ean'] ?? '');
        $sku = trim($_POST['sku'] ?? '');

        // Auto-generate SKU if not provided
        if (empty($sku)) {
            $sku = 'BK-' . strtoupper(substr(md5($title . time()), 0, 10));
        }

        // Product Details
        $item_type = trim($_POST['item_type'] ?? '');
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        $publisher = trim($_POST['publisher'] ?? '');
        $part_number = trim($_POST['part_number'] ?? '');
        $num_items = intval($_POST['num_items'] ?? 1);

        // Book Specifications
        $num_pages = intval($_POST['num_pages'] ?? 0) ?: null;
        $pages = intval($_POST['pages'] ?? 0) ?: null;
        $edition = trim($_POST['edition'] ?? '');
        $binding_type = trim($_POST['binding_type'] ?? 'Paperback');
        $dimensions = trim($_POST['dimensions'] ?? '');
        $weight = floatval($_POST['weight'] ?? 0) ?: null;

        // Age & Grade
        $min_age = intval($_POST['min_age'] ?? 0) ?: null;
        $max_age = intval($_POST['max_age'] ?? 0) ?: null;
        $min_grade = trim($_POST['min_grade'] ?? '');
        $max_grade = trim($_POST['max_grade'] ?? '');

        // Format & Language
        $format = trim($_POST['format'] ?? '');
        $language = trim($_POST['language'] ?? 'English');
        $languages = $_POST['languages'] ?? [];

        // Publication & Genre
        $publication_date = $_POST['publication_date'] ?: null;
        $genre = trim($_POST['genre'] ?? '');

        // Educational
        $school_type = trim($_POST['school_type'] ?? '');
        $textbook_type = trim($_POST['textbook_type'] ?? '');

        // Series
        $series_title = trim($_POST['series_title'] ?? '');
        $series = trim($_POST['series'] ?? '');
        $series_number = intval($_POST['series_number'] ?? 0) ?: null;
        $volume = trim($_POST['volume'] ?? '');

        // Pricing & Inventory
        $price = floatval($_POST['price'] ?? 0);
        $original_price = floatval($_POST['original_price'] ?? 0) ?: null;
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);

        // Status & Type
        $status = $_POST['status'] ?? 'active';
        $product_type = $_POST['product_type'] ?? 'physical';
        $featured = isset($_POST['featured']) ? 1 : 0;

        // Category
        $category_id = intval($_POST['category_id'] ?? 0) ?: null;

        // Marketing
        $affiliate_link = trim($_POST['affiliate_link'] ?? '');
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');

        // Keywords
        $keywords = $_POST['keywords'] ?? [];

        // Handle image uploads
        $cover_image_name = '';
        $additional_images = [];

        // Handle main cover image upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
            $target_dir = "../public/images/products/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $cover_image_name = time() . '_main_' . basename($_FILES["cover_image"]["name"]);
            $target_file = $target_dir . $cover_image_name;

            // Validate image
            $check = getimagesize($_FILES["cover_image"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
                    error_log('Cover image uploaded: ' . $cover_image_name);
                } else {
                    throw new Exception('Failed to upload cover image');
                }
            } else {
                throw new Exception('Invalid image file');
            }
        }

        // Handle additional images upload
        if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
            $target_dir = "../public/images/products/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            for ($i = 0; $i < count($_FILES['additional_images']['name']) && $i < 5; $i++) {
                if ($_FILES['additional_images']['error'][$i] == 0 && !empty($_FILES['additional_images']['name'][$i])) {
                    $file_extension = pathinfo($_FILES['additional_images']['name'][$i], PATHINFO_EXTENSION);
                    $additional_image_name = time() . '_add_' . $i . '_' . uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $additional_image_name;

                    // Validate image
                    $check = getimagesize($_FILES['additional_images']['tmp_name'][$i]);
                    if ($check !== false) {
                        if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                            $additional_images[] = $additional_image_name;
                        }
                    }
                }
            }
        }

        // Convert additional images array to JSON
        $additional_images_json = !empty($additional_images) ? json_encode($additional_images) : null;

        // Insert into products table
        $sql = "INSERT INTO products (
            title, author, isbn_10, isbn_13, description, category_id, price,
            original_price, product_type, featured, status, cover_image,
            stock_quantity, sku, ean, publisher, publication_date, language,
            pages, binding_type, dimensions, weight, edition, series,
            affiliate_link, meta_title, meta_description, image_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("sssssiidisissssssissdsssssss",
            $title, $author, $isbn_10, $isbn_13, $description,
            $category_id, $price, $original_price, $product_type,
            $featured, $status, $cover_image_name, $stock_quantity,
            $sku, $ean, $publisher, $publication_date, $language,
            $pages, $binding_type, $dimensions, $weight, $edition,
            $series, $affiliate_link, $meta_title, $meta_description,
            $additional_images_json
        );

        if (!$stmt->execute()) {
            throw new Exception('Database execute failed: ' . $stmt->error);
        }

        $product_id = $conn->insert_id;

        // Insert keywords
        foreach ($keywords as $keyword) {
            if (!empty(trim($keyword))) {
                $kw_stmt = $conn->prepare("INSERT INTO product_keywords (product_id, keyword) VALUES (?, ?)");
                $kw_stmt->bind_param("is", $product_id, $keyword);
                $kw_stmt->execute();
                $kw_stmt->close();
            }
        }

        // Insert languages (multiple)
        foreach ($languages as $lang_code) {
            if (!empty(trim($lang_code))) {
                $lang_stmt = $conn->prepare("INSERT INTO product_languages (product_id, language) VALUES (?, ?)");
                $lang_stmt->bind_param("is", $product_id, $lang_code);
                $lang_stmt->execute();
                $lang_stmt->close();
            }
        }

        $stmt->close();

        $_SESSION['success'] = 'Product added successfully!';
        header("Location: product-add.php?lang=$lang");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = 'Error adding product: ' . $e->getMessage();
        error_log('Product add error: ' . $e->getMessage());
    }
}

// Fetch categories for dropdown
$categories_result = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['add_product']; ?> - Bookory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #146eb4;
            --secondary-color: #232f3e;
            --accent-color: #f0c14b;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .container-main {
            max-width: 1400px;
            margin: 2rem auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .language-switcher {
            display: flex;
            gap: 10px;
        }

        .language-switcher a {
            padding: 6px 12px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .language-switcher a.active {
            background: rgba(255,255,255,0.5);
            font-weight: 600;
        }

        .language-switcher a:hover {
            background: rgba(255,255,255,0.3);
        }

        .form-section {
            padding: 2rem;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.75rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(20, 110, 180, 0.1);
        }

        .keywords-list, .languages-list {
            margin-top: 1rem;
        }

        .keyword-item, .language-item {
            display: flex;
            gap: 10px;
            margin-bottom: 0.5rem;
            align-items: center;
        }

        .keyword-item input, .language-item select {
            flex: 1;
        }

        .btn-remove {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .btn-add-more {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .btn-add-more:hover {
            background: #0f4d7f;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--accent-color) 0%, #e6ac00 100%);
            color: #111;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #e6ac00 0%, #d49e00 100%);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #ddd;
            color: #333;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #ccc;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin: 1.5rem 2rem;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .required {
            color: red;
        }

        .image-upload-container {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .upload-box {
            flex: 1;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-box:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }

        .upload-box i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .upload-box h6 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .upload-box small {
            color: #666;
        }

        .image-preview {
            margin-top: 1rem;
            display: none;
        }

        .image-preview img {
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .additional-images-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .additional-image-item {
            position: relative;
            width: 100px;
            height: 130px;
        }

        .additional-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .remove-image-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-guidelines {
            background: #e7f3ff;
            border-left: 4px solid var(--info-color);
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 4px;
        }

        .image-guidelines h6 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .image-guidelines ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }

        .image-guidelines li {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .form-check-input:checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 4px;
        }

        .badge-info {
            background-color: var(--info-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="header">
            <div>
                <h1><?php echo $t['add_product']; ?></h1>
                <p style="margin: 0; opacity: 0.9;">Complete product information form</p>
            </div>
            <div class="language-switcher">
                <a href="product-add.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
                <a href="product-add.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                <a href="product-add.php?lang=ur" class="<?php echo $lang === 'ur' ? 'active' : ''; ?>">اردو</a>
                <a href="product-add.php?lang=hi" class="<?php echo $lang === 'hi' ? 'active' : ''; ?>">हिंदी</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
            <!-- Section 1: Basic Information -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-info-circle"></i></span>
                    <?php echo $t['basic_info']; ?>
                </h3>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $t['item_name']; ?> <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" name="title" required placeholder="Enter product title">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $t['author']; ?> <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" name="author" required placeholder="Enter author name">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['brand_name']; ?></label>
                            <input type="text" class="form-control" name="brand_name" placeholder="Enter brand name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['category']; ?></label>
                            <select class="form-select" name="category_id">
                                <option value=""><?php echo $t['select']; ?></option>
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <?php echo $t['description']; ?> <span class="required">*</span>
                    </label>
                    <textarea class="form-control" name="description" rows="5" required placeholder="Enter detailed product description"></textarea>
                </div>
            </div>

            <!-- Section 2: Images -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-images"></i></span>
                    <?php echo $t['images']; ?>
                </h3>

                <div class="image-upload-container">
                    <div class="upload-box" onclick="document.getElementById('cover_image').click()">
                        <input type="file" id="cover_image" name="cover_image" accept="image/*" style="display: none;" onchange="previewCoverImage(this)">
                        <i class="bi bi-image"></i>
                        <h6><?php echo $t['cover_image']; ?></h6>
                        <small>Click to upload main cover</small>
                        <div id="cover-preview" class="image-preview">
                            <img id="cover-img" src="" alt="Cover Preview">
                            <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeCoverImage()">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </div>
                    </div>

                    <div class="upload-box" onclick="document.getElementById('additional_images').click()">
                        <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple style="display: none;" onchange="previewAdditionalImages(this)">
                        <i class="bi bi-images"></i>
                        <h6><?php echo $t['additional_images']; ?></h6>
                        <small>Up to 5 images</small>
                        <div id="additional-preview" class="additional-images-preview"></div>
                    </div>
                </div>

                <div class="image-guidelines">
                    <h6><i class="bi bi-info-circle"></i> Image Guidelines</h6>
                    <ul>
                        <li><strong>Main Cover:</strong> Front cover image (300x400px recommended)</li>
                        <li><strong>Additional Images:</strong> Back cover, inside pages, author photo, etc.</li>
                        <li><strong>Format:</strong> JPG, PNG, WebP (Max 5MB each)</li>
                        <li><strong>Quality:</strong> High resolution for better customer experience</li>
                    </ul>
                </div>
            </div>

            <!-- Section 3: Product Details & ISBNs -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-book"></i></span>
                    <?php echo $t['product_details']; ?>
                </h3>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['isbn']; ?></label>
                            <input type="text" class="form-control" name="isbn" placeholder="Generic ISBN">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['isbn_10']; ?></label>
                            <input type="text" class="form-control" name="isbn_10" maxlength="10" placeholder="ISBN-10">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['isbn_13']; ?></label>
                            <input type="text" class="form-control" name="isbn_13" maxlength="13" placeholder="ISBN-13">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['ean']; ?></label>
                            <input type="text" class="form-control" name="ean" maxlength="13" placeholder="EAN">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['sku']; ?></label>
                            <input type="text" class="form-control" name="sku" placeholder="Auto-generated if blank">
                            <small class="text-muted">Leave blank for auto-generation</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['publisher']; ?></label>
                            <input type="text" class="form-control" name="publisher" placeholder="Publisher name">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['manufacturer']; ?></label>
                            <input type="text" class="form-control" name="manufacturer" placeholder="Manufacturer">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['binding_type']; ?></label>
                            <select class="form-select" name="binding_type">
                                <option value="Paperback">Paperback</option>
                                <option value="Hardcover">Hardcover</option>
                                <option value="eBook">eBook</option>
                                <option value="Audiobook">Audiobook</option>
                                <option value="Board Book">Board Book</option>
                                <option value="Mass Market Paperback">Mass Market Paperback</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['format']; ?></label>
                            <select class="form-select" name="format">
                                <option value=""><?php echo $t['select']; ?></option>
                                <option value="Box calendar">Box calendar</option>
                                <option value="Subtitled">Subtitled</option>
                                <option value="Picture Book">Picture Book</option>
                                <option value="Hardcover">Hardcover</option>
                                <option value="Paperback">Paperback</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['item_type']; ?></label>
                            <input type="text" class="form-control" name="item_type" placeholder="Item type">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['num_pages']; ?></label>
                            <input type="number" class="form-control" name="num_pages" placeholder="Number of pages" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['pages']; ?></label>
                            <input type="number" class="form-control" name="pages" placeholder="Pages" min="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['edition']; ?></label>
                            <input type="text" class="form-control" name="edition" placeholder="Edition">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['num_items']; ?></label>
                            <input type="number" class="form-control" name="num_items" value="1" min="1">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['dimensions']; ?></label>
                            <input type="text" class="form-control" name="dimensions" placeholder="L x W x H cm">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['weight']; ?> (grams)</label>
                            <input type="number" step="0.01" class="form-control" name="weight" placeholder="Weight in grams" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['part_number']; ?></label>
                            <input type="text" class="form-control" name="part_number" placeholder="Part number">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['publication_date']; ?></label>
                            <input type="date" class="form-control" name="publication_date">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['language']; ?></label>
                            <select class="form-select" name="language">
                                <option value="English">English</option>
                                <option value="Hindi">Hindi</option>
                                <option value="Bengali">Bengali</option>
                                <option value="Tamil">Tamil</option>
                                <option value="Telugu">Telugu</option>
                                <option value="Marathi">Marathi</option>
                                <option value="Gujarati">Gujarati</option>
                                <option value="Arabic">Arabic</option>
                                <option value="Urdu">Urdu</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Keywords & Languages -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-tags"></i></span>
                    <?php echo $t['keywords']; ?> & <?php echo $t['language']; ?>
                </h3>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label"><?php echo $t['keywords']; ?></label>
                        <div id="keywords-container">
                            <div class="keyword-item">
                                <input type="text" class="form-control" name="keywords[]" placeholder="Enter keyword">
                                <button type="button" class="btn-remove" onclick="removeKeyword(this)">×</button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-more mt-2" onclick="addKeyword()">
                            + <?php echo $t['add_keyword']; ?>
                        </button>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><?php echo $t['language_type']; ?> (Multiple)</label>
                        <div id="languages-container">
                            <div class="language-item">
                                <select class="form-select" name="languages[]">
                                    <option value=""><?php echo $t['select']; ?></option>
                                    <option value="en">English</option>
                                    <option value="ar">العربية</option>
                                    <option value="ur">اردو</option>
                                    <option value="hi">हिंदी</option>
                                    <option value="bn">বাংলা</option>
                                    <option value="ta">தமிழ்</option>
                                </select>
                                <button type="button" class="btn-remove" onclick="removeLanguage(this)">×</button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-more mt-2" onclick="addLanguage()">
                            + <?php echo $t['add_more']; ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Section 5: Educational Details -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-mortarboard"></i></span>
                    Educational Details
                </h3>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['min_reading_age']; ?></label>
                            <input type="number" class="form-control" name="min_age" min="0" max="100" placeholder="Min age">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['max_reading_age']; ?></label>
                            <input type="number" class="form-control" name="max_age" min="0" max="100" placeholder="Max age">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['min_grade']; ?></label>
                            <input type="text" class="form-control" name="min_grade" placeholder="Min grade">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['max_grade']; ?></label>
                            <input type="text" class="form-control" name="max_grade" placeholder="Max grade">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['genre']; ?></label>
                            <select class="form-select" name="genre">
                                <option value=""><?php echo $t['select']; ?></option>
                                <option value="Fiction">Fiction</option>
                                <option value="Non-fiction">Non-fiction</option>
                                <option value="Mystery">Mystery</option>
                                <option value="Romance">Romance</option>
                                <option value="Science Fiction">Science Fiction</option>
                                <option value="Self-Help">Self-Help</option>
                                <option value="Biography">Biography</option>
                                <option value="Educational">Educational</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['school_type']; ?></label>
                            <input type="text" class="form-control" name="school_type" placeholder="School type">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['textbook_type']; ?></label>
                            <select class="form-select" name="textbook_type">
                                <option value=""><?php echo $t['select']; ?></option>
                                <option value="Core">Core</option>
                                <option value="Supplementary">Supplementary</option>
                                <option value="Reference">Reference</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 6: Series Information -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-collection"></i></span>
                    Series Information
                </h3>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['series_title']; ?></label>
                            <input type="text" class="form-control" name="series_title" placeholder="Series title">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['series_number']; ?></label>
                            <input type="number" class="form-control" name="series_number" min="0" placeholder="Series number">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['volume']; ?></label>
                            <input type="text" class="form-control" name="volume" placeholder="Volume">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 7: Pricing & Inventory -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-currency-rupee"></i></span>
                    <?php echo $t['pricing']; ?>
                </h3>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $t['price']; ?> (₹) <span class="required">*</span>
                            </label>
                            <input type="number" step="0.01" class="form-control" name="price" required placeholder="Selling price" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['original_price']; ?> (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="original_price" placeholder="Original/MRP" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">
                                <?php echo $t['stock_quantity']; ?> <span class="required">*</span>
                            </label>
                            <input type="number" class="form-control" name="stock_quantity" required placeholder="Stock" min="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['product_type']; ?></label>
                            <select class="form-select" name="product_type">
                                <option value="physical">Physical Book</option>
                                <option value="digital">Digital Book</option>
                                <option value="affiliate">Affiliate Link</option>
                                <option value="both">Both Types</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['status']; ?></label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="draft">Draft</option>
                                <option value="out_of_stock">Out of Stock</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['featured']; ?></label>
                            <div class="form-check form-switch" style="padding-top: 0.5rem;">
                                <input class="form-check-input" type="checkbox" name="featured" id="featured" style="width: 3rem; height: 1.5rem; cursor: pointer;">
                                <label class="form-check-label" for="featured" style="padding-left: 0.5rem; cursor: pointer;">
                                    Mark as featured
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $t['affiliate_link']; ?></label>
                    <input type="url" class="form-control" name="affiliate_link" placeholder="https://example.com/product">
                    <small class="text-muted">Optional: External store or affiliate marketing link</small>
                </div>
            </div>

            <!-- Section 8: SEO & Marketing -->
            <div class="form-section">
                <h3 class="section-title">
                    <span class="section-icon"><i class="bi bi-search"></i></span>
                    <?php echo $t['seo']; ?>
                </h3>

                <div class="form-group">
                    <label class="form-label"><?php echo $t['meta_title']; ?></label>
                    <input type="text" class="form-control" name="meta_title" maxlength="255" placeholder="SEO meta title">
                    <small class="text-muted">Leave blank to use product title</small>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $t['meta_description']; ?></label>
                    <textarea class="form-control" name="meta_description" rows="3" maxlength="160" placeholder="SEO meta description (160 characters)"></textarea>
                    <small class="text-muted">Brief description for search engines</small>
                </div>
            </div>

            <!-- Buttons -->
            <div class="form-section">
                <div class="button-group">
                    <button type="button" class="btn-cancel" onclick="window.location.href='products.php'">
                        <i class="bi bi-x-circle"></i> <?php echo $t['cancel']; ?>
                    </button>
                    <button type="submit" class="btn-save">
                        <i class="bi bi-save"></i> <?php echo $t['save']; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Keywords management
        function addKeyword() {
            const container = document.getElementById('keywords-container');
            const newItem = document.createElement('div');
            newItem.className = 'keyword-item';
            newItem.innerHTML = `
                <input type="text" class="form-control" name="keywords[]" placeholder="Enter keyword">
                <button type="button" class="btn-remove" onclick="removeKeyword(this)">×</button>
            `;
            container.appendChild(newItem);
        }

        function removeKeyword(btn) {
            const container = document.getElementById('keywords-container');
            if (container.children.length > 1) {
                btn.parentElement.remove();
            }
        }

        // Languages management
        function addLanguage() {
            const container = document.getElementById('languages-container');
            const newItem = document.createElement('div');
            newItem.className = 'language-item';
            newItem.innerHTML = `
                <select class="form-select" name="languages[]">
                    <option value="">Select...</option>
                    <option value="en">English</option>
                    <option value="ar">العربية</option>
                    <option value="ur">اردو</option>
                    <option value="hi">हिंदी</option>
                    <option value="bn">বাংলা</option>
                    <option value="ta">தமிழ்</option>
                </select>
                <button type="button" class="btn-remove" onclick="removeLanguage(this)">×</button>
            `;
            container.appendChild(newItem);
        }

        function removeLanguage(btn) {
            const container = document.getElementById('languages-container');
            if (container.children.length > 1) {
                btn.parentElement.remove();
            }
        }

        // Cover image preview
        function previewCoverImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('cover-preview');
                    const img = document.getElementById('cover-img');
                    img.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeCoverImage() {
            document.getElementById('cover_image').value = '';
            document.getElementById('cover-preview').style.display = 'none';
        }

        // Additional images preview
        function previewAdditionalImages(input) {
            const preview = document.getElementById('additional-preview');
            preview.innerHTML = '';

            if (input.files) {
                const maxFiles = Math.min(input.files.length, 5);

                for (let i = 0; i < maxFiles; i++) {
                    const file = input.files[i];
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'additional-image-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Additional ${i + 1}">
                            <button type="button" class="remove-image-btn" onclick="this.parentElement.remove()">×</button>
                        `;
                        preview.appendChild(div);
                    };

                    reader.readAsDataURL(file);
                }

                if (input.files.length > 5) {
                    alert('Only the first 5 images will be uploaded.');
                }
            }
        }

        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const author = document.querySelector('input[name="author"]').value.trim();
            const description = document.querySelector('textarea[name="description"]').value.trim();
            const price = parseFloat(document.querySelector('input[name="price"]').value);
            const stock = parseInt(document.querySelector('input[name="stock_quantity"]').value);

            if (!title || !author || !description) {
                e.preventDefault();
                alert('Please fill in all required fields: Title, Author, and Description');
                return false;
            }

            if (isNaN(price) || price <= 0) {
                e.preventDefault();
                alert('Please enter a valid selling price');
                return false;
            }

            if (isNaN(stock) || stock < 0) {
                e.preventDefault();
                alert('Please enter a valid stock quantity');
                return false;
            }
        });
    </script>
</body>
</html>
