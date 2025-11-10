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
        'product_details' => 'Product Details',
        'item_name' => 'Item Name',
        'brand_name' => 'Brand Name',
        'isbn' => 'ISBN / External Product ID',
        'item_type' => 'Item Type',
        'manufacturer' => 'Manufacturer',
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
        'save' => 'Save Product',
        'cancel' => 'Cancel',
        'select' => 'Select...',
        'add_more' => 'Add More',
        'remove' => 'Remove',
    ],
    'ar' => [
        'add_product' => 'إضافة منتج',
        'product_details' => 'تفاصيل المنتج',
        'item_name' => 'اسم العنصر',
        'brand_name' => 'اسم العلامة التجارية',
        'isbn' => 'ISBN / معرف المنتج الخارجي',
        'item_type' => 'نوع العنصر',
        'manufacturer' => 'الشركة المصنعة',
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
        'save' => 'حفظ المنتج',
        'cancel' => 'إلغاء',
        'select' => 'اختر...',
        'add_more' => 'إضافة المزيد',
        'remove' => 'إزالة',
    ],
    'ur' => [
        'add_product' => 'پروڈکٹ شامل کریں',
        'product_details' => 'پروڈکٹ کی تفصیلات',
        'item_name' => 'چیز کا نام',
        'brand_name' => 'برانڈ کا نام',
        'isbn' => 'ISBN / بیرونی پروڈکٹ ID',
        'item_type' => 'چیز کی قسم',
        'manufacturer' => 'مینوفیکچرر',
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
        'save' => 'پروڈکٹ محفوظ کریں',
        'cancel' => 'منسوخ کریں',
        'select' => 'منتخب کریں...',
        'add_more' => 'مزید شامل کریں',
        'remove' => 'ہٹائیں',
    ],
    'hi' => [
        'add_product' => 'उत्पाद जोड़ें',
        'product_details' => 'उत्पाद विवरण',
        'item_name' => 'आइटम का नाम',
        'brand_name' => 'ब्रांड का नाम',
        'isbn' => 'ISBN / बाहरी उत्पाद ID',
        'item_type' => 'आइटम का प्रकार',
        'manufacturer' => 'निर्माता',
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
    // Get form data
    $item_name = $_POST['item_name'] ?? '';
    $brand_name = $_POST['brand_name'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $item_type = $_POST['item_type'] ?? '';
    $manufacturer = $_POST['manufacturer'] ?? '';
    $description = $_POST['description'] ?? '';
    $num_items = $_POST['num_items'] ?? 1;
    $part_number = $_POST['part_number'] ?? '';
    $keywords = $_POST['keywords'] ?? [];
    $num_pages = $_POST['num_pages'] ?? '';
    $edition = $_POST['edition'] ?? '';
    $min_age = $_POST['min_age'] ?? '';
    $max_age = $_POST['max_age'] ?? '';
    $format = $_POST['format'] ?? '';
    $languages = $_POST['languages'] ?? [];
    $publication_date = $_POST['publication_date'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $min_grade = $_POST['min_grade'] ?? '';
    $max_grade = $_POST['max_grade'] ?? '';
    $school_type = $_POST['school_type'] ?? '';
    $series_title = $_POST['series_title'] ?? '';
    $series_number = $_POST['series_number'] ?? '';
    $volume = $_POST['volume'] ?? '';
    $textbook_type = $_POST['textbook_type'] ?? '';

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO products (name, brand, isbn, item_type, manufacturer, description, quantity, part_number, pages, edition, min_reading_age, max_reading_age, format, publication_date, genre, min_grade, max_grade, school_type, series_title, series_number, volume, textbook_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    if ($stmt) {
        $stmt->bind_param("ssssssisssiisssssssssss",
            $item_name, $brand_name, $isbn, $item_type, $manufacturer,
            $description, $num_items, $part_number, $num_pages, $edition,
            $min_age, $max_age, $format, $publication_date, $genre,
            $min_grade, $max_grade, $school_type, $series_title,
            $series_number, $volume, $textbook_type
        );

        if ($stmt->execute()) {
            $product_id = $conn->insert_id;

            // Insert keywords
            foreach ($keywords as $keyword) {
                if (!empty($keyword)) {
                    $kw_stmt = $conn->prepare("INSERT INTO product_keywords (product_id, keyword) VALUES (?, ?)");
                    $kw_stmt->bind_param("is", $product_id, $keyword);
                    $kw_stmt->execute();
                    $kw_stmt->close();
                }
            }

            // Insert languages
            foreach ($languages as $lang_code) {
                if (!empty($lang_code)) {
                    $lang_stmt = $conn->prepare("INSERT INTO product_languages (product_id, language) VALUES (?, ?)");
                    $lang_stmt->bind_param("is", $product_id, $lang_code);
                    $lang_stmt->execute();
                    $lang_stmt->close();
                }
            }

            $_SESSION['success'] = 'Product added successfully!';
            header("Location: product-add.php?lang=$lang");
            exit();
        } else {
            $_SESSION['error'] = 'Error adding product: ' . $stmt->error;
        }
        $stmt->close();
    }
}

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
        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .container-main {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);
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
        }
        .language-switcher a.active {
            background: rgba(255,255,255,0.5);
            font-weight: 600;
        }
        .form-section {
            padding: 2rem;
            border-bottom: 1px solid #eee;
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #146eb4;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.75rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #146eb4;
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
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-remove:hover {
            background: #c82333;
        }
        .btn-add-more {
            background: #146eb4;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
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
            background: linear-gradient(135deg, #f0c14b 0%, #e6ac00 100%);
            color: #111;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-save:hover {
            background: linear-gradient(135deg, #e6ac00 0%, #d49e00 100%);
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
        }
        .btn-cancel:hover {
            background: #ccc;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
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
    </style>
</head>
<body>
    <div class="container-main">
        <div class="header">
            <h1><?php echo $t['add_product']; ?></h1>
            <div class="language-switcher">
                <a href="product-add.php?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
                <a href="product-add.php?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                <a href="product-add.php?lang=ur" class="<?php echo $lang === 'ur' ? 'active' : ''; ?>">اردو</a>
                <a href="product-add.php?lang=hi" class="<?php echo $lang === 'hi' ? 'active' : ''; ?>">हिंदी</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Basic Details -->
            <div class="form-section">
                <h3 class="section-title"><?php echo $t['product_details']; ?></h3>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['item_name']; ?> <span class="required">*</span></label>
                            <input type="text" class="form-control" name="item_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['brand_name']; ?></label>
                            <input type="text" class="form-control" name="brand_name">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['isbn']; ?></label>
                            <input type="text" class="form-control" name="isbn">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['item_type']; ?></label>
                            <input type="text" class="form-control" name="item_type">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['manufacturer']; ?></label>
                            <input type="text" class="form-control" name="manufacturer">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['part_number']; ?></label>
                            <input type="text" class="form-control" name="part_number">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $t['description']; ?> <span class="required">*</span></label>
                    <textarea class="form-control" name="description" rows="5" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['num_items']; ?></label>
                            <input type="number" class="form-control" name="num_items" value="1" min="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['num_pages']; ?></label>
                            <input type="number" class="form-control" name="num_pages">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keywords -->
            <div class="form-section">
                <h3 class="section-title"><?php echo $t['keywords']; ?></h3>
                <div id="keywords-container">
                    <div class="keyword-item">
                        <input type="text" class="form-control" name="keywords[]" placeholder="<?php echo $t['keywords']; ?>">
                        <button type="button" class="btn-remove" onclick="removeKeyword(this)">×</button>
                    </div>
                </div>
                <button type="button" class="btn-add-more" onclick="addKeyword()">+ <?php echo $t['add_keyword']; ?></button>
            </div>

            <!-- Book Details -->
            <div class="form-section">
                <h3 class="section-title"><?php echo $t['edition']; ?></h3>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['edition']; ?></label>
                            <input type="text" class="form-control" name="edition">
                        </div>
                    </div>
                    <div class="col-md-6">
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
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['min_reading_age']; ?></label>
                            <input type="number" class="form-control" name="min_age" min="0" max="100">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['max_reading_age']; ?></label>
                            <input type="number" class="form-control" name="max_age" min="0" max="100">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['min_grade']; ?></label>
                            <input type="text" class="form-control" name="min_grade">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['max_grade']; ?></label>
                            <input type="text" class="form-control" name="max_grade">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Language -->
            <div class="form-section">
                <h3 class="section-title"><?php echo $t['language']; ?></h3>
                <div id="languages-container">
                    <div class="language-item">
                        <select class="form-select" name="languages[]">
                            <option value=""><?php echo $t['select']; ?></option>
                            <option value="en">English</option>
                            <option value="ar">العربية</option>
                            <option value="ur">اردو</option>
                            <option value="hi">हिंदी</option>
                        </select>
                        <button type="button" class="btn-remove" onclick="removeLanguage(this)">×</button>
                    </div>
                </div>
                <button type="button" class="btn-add-more" onclick="addLanguage()">+ <?php echo $t['add_more']; ?></button>
            </div>

            <!-- More Details -->
            <div class="form-section">
                <h3 class="section-title"><?php echo $t['publication_date']; ?></h3>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['publication_date']; ?></label>
                            <input type="date" class="form-control" name="publication_date">
                        </div>
                    </div>
                    <div class="col-md-6">
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
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['school_type']; ?></label>
                            <input type="text" class="form-control" name="school_type">
                        </div>
                    </div>
                    <div class="col-md-6">
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

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['series_title']; ?></label>
                            <input type="text" class="form-control" name="series_title">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><?php echo $t['series_number']; ?></label>
                            <input type="number" class="form-control" name="series_number" min="0">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo $t['volume']; ?></label>
                    <input type="text" class="form-control" name="volume">
                </div>
            </div>

            <!-- Buttons -->
            <div class="form-section">
                <div class="button-group">
                    <button type="button" class="btn-cancel" onclick="window.history.back();"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn-save"><?php echo $t['save']; ?></button>
                </div>
            </div>
        </form>
    </div>

    <script>
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
    </script>
</body>
</html>
