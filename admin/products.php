<?php
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Handle status messages
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'added':
            $_SESSION['success_message'] = 'Product added successfully!';
            break;
        case 'updated':
            $_SESSION['success_message'] = 'Product updated successfully!';
            break;
        case 'deleted':
            $_SESSION['success_message'] = 'Product deleted successfully!';
            break;
        case 'error':
            $_SESSION['error_message'] = 'Error: ' . ($_GET['message'] ?? 'Unknown error occurred');
            break;
    }
}

// -- Handle DELETE Action --
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success_message'] = 'Product deleted successfully!';
    header("Location: products.php");
    exit();
}

// -- Handle ADD/EDIT Action --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']) ?: null;
    $price = floatval($_POST['price']);
    $original_price = floatval($_POST['original_price']) ?: null;
    $stock_quantity = intval($_POST['stock_quantity']);
    $product_type = $_POST['product_type'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'];
    $sku = trim($_POST['sku']);
    
    // New book-specific fields
    $isbn_10 = trim($_POST['isbn_10'] ?? '');
    $isbn_13 = trim($_POST['isbn_13'] ?? '');
    $ean = trim($_POST['ean'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publication_date = $_POST['publication_date'] ?: null;
    $language = trim($_POST['language'] ?? 'English');
    $pages = intval($_POST['pages']) ?: null;
    $binding_type = $_POST['binding_type'] ?? 'Paperback';
    $dimensions = trim($_POST['dimensions'] ?? '');
    $weight = floatval($_POST['weight']) ?: null;
    $edition = trim($_POST['edition'] ?? '');
    $series = trim($_POST['series'] ?? '');
    $affiliate_link = trim($_POST['affiliate_link'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    $cover_image_name = $_POST['existing_cover_image'] ?? '';
    $additional_images = [];
    
    // Handle main cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../public/images/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $cover_image_name = time() . '_main_' . basename($_FILES["cover_image"]["name"]);
        $target_file = $target_dir . $cover_image_name;
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            error_log('Cover image uploaded: ' . $cover_image_name);
        } else {
            error_log('Failed to upload cover image');
        }
    }
    
    // Handle additional images upload
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        $target_dir = "../public/images/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $upload_count = 0;
        for ($i = 0; $i < count($_FILES['additional_images']['name']) && $i < 5; $i++) {
            if ($_FILES['additional_images']['error'][$i] == 0 && !empty($_FILES['additional_images']['name'][$i])) {
                $file_extension = pathinfo($_FILES['additional_images']['name'][$i], PATHINFO_EXTENSION);
                $additional_image_name = time() . '_add_' . $i . '_' . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $additional_image_name;
                
                if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                    $additional_images[] = $additional_image_name;
                    $upload_count++;
                    error_log('Additional image uploaded: ' . $additional_image_name);
                } else {
                    error_log('Failed to upload additional image: ' . $_FILES['additional_images']['name'][$i]);
                }
            }
        }
        error_log('Total additional images uploaded: ' . $upload_count);
    }
    
    // Convert additional images array to JSON string for database storage
    $additional_images_json = !empty($additional_images) ? json_encode($additional_images) : null;

    try {
        if ($product_id > 0) {
            // Update existing product
            $sql = "UPDATE products SET 
                title = ?, author = ?, isbn_10 = ?, isbn_13 = ?, description = ?, 
                category_id = ?, price = ?, original_price = ?, product_type = ?, 
                featured = ?, status = ?, cover_image = ?, stock_quantity = ?, 
                sku = ?, ean = ?, publisher = ?, publication_date = ?, language = ?, 
                pages = ?, binding_type = ?, dimensions = ?, weight = ?, edition = ?, 
                series = ?, affiliate_link = ?, meta_title = ?, meta_description = ?";
            
            $params = [$title, $author, $isbn_10, $isbn_13, $description, 
                $category_id, $price, $original_price, $product_type, 
                $featured, $status, $cover_image_name, $stock_quantity, 
                $sku, $ean, $publisher, $publication_date, $language, 
                $pages, $binding_type, $dimensions, $weight, $edition, 
                $series, $affiliate_link, $meta_title, $meta_description];
            
            $types = "sssssidisissssssissdsssss";
            
            if ($additional_images_json) {
                $sql .= ", image_url = ?";
                $params[] = $additional_images_json;
                $types .= "s";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $product_id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $_SESSION['success_message'] = 'Product updated successfully!';
            $stmt->close();
        } else {
            // Insert new product
            $sql = "INSERT INTO products (
                title, author, isbn_10, isbn_13, description, category_id, price, 
                original_price, product_type, featured, status, cover_image, 
                stock_quantity, sku, ean, publisher, publication_date, language, 
                pages, binding_type, dimensions, weight, edition, series, 
                affiliate_link, meta_title, meta_description";
            
            $values = " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
            
            $params = [$title, $author, $isbn_10, $isbn_13, $description, 
                $category_id, $price, $original_price, $product_type, 
                $featured, $status, $cover_image_name, $stock_quantity, 
                $sku, $ean, $publisher, $publication_date, $language, 
                $pages, $binding_type, $dimensions, $weight, $edition, 
                $series, $affiliate_link, $meta_title, $meta_description];
            
            $types = "sssssidisissssssissdsssss";
            
            if ($additional_images_json) {
                $sql .= ", image_url";
                $values .= ", ?";
                $params[] = $additional_images_json;
                $types .= "s";
            }
            
            $sql .= ")" . $values . ")";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $conn->error);
            }
            
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception('Database execute failed: ' . $stmt->error);
            }
            
            $_SESSION['success_message'] = 'Product added successfully!';
            $stmt->close();
        }
        header("Location: products.php");
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header("Location: products.php");
    }
    exit();
}

// Get statistics for dashboard
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$featured_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE featured = 1")->fetch_assoc()['count'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 10")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0")->fetch_assoc()['count'];

// --- Fetch all products for display ---
$result_products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");

// --- Fetch categories for dropdown ---
$result_categories = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

// Inject professional CSS
injectProfessionalCSS();
renderProfessionalJavaScript();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">📚 Product Management</h1>
                <p class="page-subtitle">Manage your book inventory and listings</p>
            </div>
            <button class="btn-professional btn-primary-professional" onclick="openNewProductModal()">
                <span>➕</span> Add New Book
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php renderProfessionalAlert('success', $_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <?php renderProfessionalAlert('error', $_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-professional">
            <div class="stat-icon">📚</div>
            <h2 class="stat-value"><?php echo number_format($total_products); ?></h2>
            <p class="stat-label">Total Products</p>
            <div class="stat-change positive">
                <span>📈</span> Active inventory
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">
                ⭐
            </div>
            <h2 class="stat-value"><?php echo number_format($featured_products); ?></h2>
            <p class="stat-label">Featured Products</p>
            <div class="stat-change positive">
                <span>🌟</span> Premium listings
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);">
                ⚠️
            </div>
            <h2 class="stat-value"><?php echo number_format($low_stock); ?></h2>
            <p class="stat-label">Low Stock Items</p>
            <div class="stat-change <?php echo $low_stock > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $low_stock > 0 ? '⚠️' : '✅'; ?></span> Need attention
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">
                📦
            </div>
            <h2 class="stat-value"><?php echo number_format($out_of_stock); ?></h2>
            <p class="stat-label">Out of Stock</p>
            <div class="stat-change <?php echo $out_of_stock > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $out_of_stock > 0 ? '🚫' : '✅'; ?></span> Status
            </div>
        </div>
    </div>

    <!-- Professional Product Table -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>📋</span> Product List
                <span class="status-badge-professional status-active" style="margin-left: var(--spacing-2);">
                    <?php echo number_format($total_products); ?> total
                </span>
            </h3>
        </div>
        <div class="card-body-professional" style="padding: 0;">
            <div class="table-responsive">
                <table class="table-professional">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>ISBN-13</th>
                            <th>Publisher</th>
                            <th>Binding</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result_products->fetch_assoc()): ?>
                            <tr class="fade-in">
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php 
                                    $image_url = "/bookshelf/public/images/products/" . htmlspecialchars($row['cover_image'] ?? 'default.jpg');
                                    ?>
                                    <img src="<?php echo $image_url; ?>" alt="" width="40" height="60" style="object-fit: cover; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                    <?php if($row['featured']): ?>
                                        <span class="status-badge-professional status-warning" style="margin-left: var(--spacing-1);">Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['author']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo htmlspecialchars($row['isbn_13'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['publisher'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['binding_type'] ?? 'Paperback'); ?></td>
                                <td>
                                    <div style="font-weight: 600; color: var(--success);">
                                        ₹<?php echo number_format($row['price'], 2); ?>
                                    </div>
                                    <?php if($row['original_price'] && $row['original_price'] > $row['price']): ?>
                                        <div style="font-size: var(--font-size-xs); color: var(--gray-500); text-decoration: line-through;">
                                            ₹<?php echo number_format($row['original_price'], 2); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge-professional <?php 
                                        if ($row['stock_quantity'] == 0) {
                                            echo 'status-danger';
                                        } elseif ($row['stock_quantity'] <= 10) {
                                            echo 'status-warning';
                                        } else {
                                            echo 'status-success';
                                        }
                                    ?>">
                                        <?php echo $row['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $status_labels = [
                                        'active' => ['✅', 'Active', 'status-success'],
                                        'inactive' => ['❌', 'Inactive', 'status-secondary'], 
                                        'draft' => ['📝', 'Draft', 'status-info'],
                                        'out_of_stock' => ['📦', 'Out of Stock', 'status-warning'],
                                        'discontinued' => ['🚫', 'Discontinued', 'status-danger']
                                    ];
                                    $status_config = $status_labels[$row['status']] ?? ['📋', ucfirst($row['status']), 'status-secondary'];
                                    ?>
                                    <span class="status-badge-professional <?php echo $status_config[2]; ?>">
                                        <?php echo $status_config[0] . ' ' . $status_config[1]; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div style="display: flex; gap: var(--spacing-1); justify-content: flex-end;">
                                        <button class="btn-professional btn-info-professional" 
                                                onclick="openEditProductModal(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);">
                                            <span>✏️</span> Edit
                                        </button>
                                        <a href="products.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn-professional btn-danger-professional" 
                                           onclick="return confirm('Are you sure you want to delete this book?');"
                                           style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);">
                                            <span>🗑️</span> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Professional Add/Edit Book Modal - Comprehensive Form -->
    <div class="modal-professional" id="productModal">
        <div class="modal-content-professional" style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header-professional">
                <h4 class="modal-title" id="productModalLabel">
                    <span>📚</span> Add New Book
                </h4>
                <button type="button" class="btn-professional" style="background: none; border: none; font-size: 1.5rem; padding: var(--spacing-1);" onclick="closeModal('productModal')">
                    <span>❌</span>
                </button>
            </div>
            
            <div class="modal-body-professional" style="overflow-y: auto; flex: 1;">
                <form action="products.php" method="POST" enctype="multipart/form-data" id="productForm" class="form-professional">
                    <input type="hidden" name="product_id" id="product_id" value="0">
                    <input type="hidden" name="existing_cover_image" id="existing_cover_image">

                    <!-- Section 1: Basic Information -->
                    <div class="form-section mb-4 pb-3 border-bottom">
                        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">
                            <span>📖</span> Basic Information
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Book Title *</label>
                                    <input type="text" class="form-control-professional" id="title" name="title" placeholder="Enter book title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Author *</label>
                                    <input type="text" class="form-control-professional" id="author" name="author" placeholder="Author name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Brand Name</label>
                                    <input type="text" class="form-control-professional" id="brand_name" name="brand_name" placeholder="Brand name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Category</label>
                                    <select class="form-select form-control-professional" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php
                                        $result_categories->data_seek(0);
                                        while($category = $result_categories->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">SKU</label>
                                    <input type="text" class="form-control-professional" id="sku" name="sku" placeholder="Auto-generated if blank">
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="form-group-professional">
                                <label class="form-label-professional">Description *</label>
                                <textarea class="form-control-professional" id="description" name="description" rows="4" placeholder="Enter detailed description" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Images -->
                    <div class="form-section mb-4 pb-3 border-bottom">
                        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">
                            <span>🖼️</span> Product Images
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="professional-card" style="cursor: pointer;" onclick="document.getElementById('cover_image').click();">
                                    <div class="card-body-professional text-center" style="padding: 2rem;">
                                        <input class="form-control d-none" type="file" id="cover_image" name="cover_image" accept="image/*">
                                        <i class="bi bi-image" style="font-size: 2.5rem; color: var(--primary);"></i>
                                        <h6 class="mt-2">Main Cover Image</h6>
                                        <small class="text-muted">Click to upload</small>
                                        <div id="cover-preview" class="mt-3" style="display: none;">
                                            <img id="cover-img" src="" alt="Cover" class="img-fluid rounded" style="max-height: 100px;">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeCoverImage()">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="professional-card" style="cursor: pointer;" onclick="document.getElementById('additional_images').click();">
                                    <div class="card-body-professional text-center" style="padding: 2rem;">
                                        <input class="form-control d-none" type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                                        <i class="bi bi-images" style="font-size: 2.5rem; color: var(--success);"></i>
                                        <h6 class="mt-2">Additional Images</h6>
                                        <small class="text-muted">Up to 5 images</small>
                                        <div id="additional-preview" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Product Details & ISBNs -->
                    <div class="form-section mb-4 pb-3 border-bottom">
                        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">
                            <span>📚</span> Book Details
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">ISBN</label>
                                    <input type="text" class="form-control-professional" id="isbn" name="isbn" placeholder="Generic ISBN">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">ISBN-10</label>
                                    <input type="text" class="form-control-professional" id="isbn_10" name="isbn_10" placeholder="ISBN-10" maxlength="10">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">ISBN-13</label>
                                    <input type="text" class="form-control-professional" id="isbn_13" name="isbn_13" placeholder="ISBN-13" maxlength="13">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">EAN</label>
                                    <input type="text" class="form-control-professional" id="ean" name="ean" placeholder="EAN" maxlength="13">
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Publisher</label>
                                    <input type="text" class="form-control-professional" id="publisher" name="publisher" placeholder="Publisher name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Manufacturer</label>
                                    <input type="text" class="form-control-professional" id="manufacturer" name="manufacturer" placeholder="Manufacturer">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Binding Type</label>
                                    <select class="form-select form-control-professional" id="binding_type" name="binding_type">
                                        <option value="Paperback">Paperback</option>
                                        <option value="Hardcover">Hardcover</option>
                                        <option value="eBook">eBook</option>
                                        <option value="Audiobook">Audiobook</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Format</label>
                                    <select class="form-select form-control-professional" id="format" name="format">
                                        <option value="">Select Format</option>
                                        <option value="Hardcover">Hardcover</option>
                                        <option value="Paperback">Paperback</option>
                                        <option value="Picture Book">Picture Book</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Pages</label>
                                    <input type="number" class="form-control-professional" id="pages" name="pages" placeholder="Number of pages">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Edition</label>
                                    <input type="text" class="form-control-professional" id="edition" name="edition" placeholder="Edition">
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Dimensions</label>
                                    <input type="text" class="form-control-professional" id="dimensions" name="dimensions" placeholder="L x W x H cm">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Weight (grams)</label>
                                    <input type="number" step="0.01" class="form-control-professional" id="weight" name="weight" placeholder="Weight">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Publication Date</label>
                                    <input type="date" class="form-control-professional" id="publication_date" name="publication_date">
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Language</label>
                                    <select class="form-select form-control-professional" id="language" name="language">
                                        <option value="English">English</option>
                                        <option value="Hindi">Hindi</option>
                                        <option value="Bengali">Bengali</option>
                                        <option value="Tamil">Tamil</option>
                                        <option value="Marathi">Marathi</option>
                                        <option value="Arabic">Arabic</option>
                                        <option value="Urdu">Urdu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Series Title</label>
                                    <input type="text" class="form-control-professional" id="series" name="series" placeholder="Series title">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Pricing & Inventory -->
                    <div class="form-section mb-4 pb-3 border-bottom">
                        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">
                            <span>💰</span> Pricing & Inventory
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Selling Price (₹) *</label>
                                    <input type="number" step="0.01" class="form-control-professional" id="price" name="price" placeholder="Selling price" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Original Price (₹)</label>
                                    <input type="number" step="0.01" class="form-control-professional" id="original_price" name="original_price" placeholder="MRP" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Stock Quantity *</label>
                                    <input type="number" class="form-control-professional" id="stock_quantity" name="stock_quantity" placeholder="Stock" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Product Type</label>
                                    <select class="form-select form-control-professional" id="product_type" name="product_type">
                                        <option value="physical">Physical Book</option>
                                        <option value="digital">Digital Book</option>
                                        <option value="affiliate">Affiliate Link</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Status</label>
                                    <select class="form-select form-control-professional" id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="draft">Draft</option>
                                        <option value="out_of_stock">Out of Stock</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Featured</label>
                                    <div class="mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="featured" name="featured">
                                            <label class="form-check-label" for="featured">
                                                Mark as featured product
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-12">
                                <div class="form-group-professional">
                                    <label class="form-label-professional">Affiliate Link</label>
                                    <input type="url" class="form-control-professional" id="affiliate_link" name="affiliate_link" placeholder="https://example.com/product">
                                    <small class="text-muted">Optional: External store or affiliate link</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: SEO & Marketing -->
                    <div class="form-section mb-4">
                        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">
                            <span>🔍</span> SEO & Marketing
                        </h5>
                        <div class="form-group-professional mb-3">
                            <label class="form-label-professional">SEO Meta Title</label>
                            <input type="text" class="form-control-professional" id="meta_title" name="meta_title" placeholder="SEO meta title" maxlength="255">
                            <small class="text-muted">Leave blank to use product title</small>
                        </div>
                        <div class="form-group-professional">
                            <label class="form-label-professional">SEO Meta Description</label>
                            <textarea class="form-control-professional" id="meta_description" name="meta_description" rows="3" placeholder="SEO meta description (160 characters)" maxlength="160"></textarea>
                            <small class="text-muted">Brief description for search engines</small>
                        </div>
                    </div>

                    <div class="tab-content" id="bookTabContent" style="display: none;"></div>
                    <div class="modal-footer-professional">
                        <button type="button" class="btn-professional btn-outline-professional" onclick="closeModal('productModal')">
                            <span>❌</span> Cancel
                        </button>
                        <button type="submit" class="btn-professional btn-success-professional" id="submitBtn">
                            <span>💾</span> Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Page loaded, initializing form...');
    
    const productModal = document.getElementById('productModal');
    const modalTitle = document.getElementById('productModalLabel');
    const productForm = document.getElementById('productForm');
    
    // Tab management
    const tabs = ['basic', 'details', 'pricing', 'seo'];
    let currentTabIndex = 0;
    
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const submitBtn = document.getElementById('submitBtn');
    const currentStepSpan = document.getElementById('currentStep');
    const stepDescription = document.getElementById('stepDescription');
    
    const stepDescriptions = [
        'Enter basic book information',
        'Add detailed book specifications',
        'Set pricing and inventory details',
        'Optimize for search engines'
    ];
    
    console.log('✅ Elements found:', {
        nextBtn: !!nextBtn,
        prevBtn: !!prevBtn,
        submitBtn: !!submitBtn
    });
    
    // Function to show specific tab
    function showTab(tabIndex) {
        console.log(`📋 Showing tab: ${tabs[tabIndex]} (index: ${tabIndex})`);
        
        // Hide all tab content
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });
        
        // Show target tab content
        const targetTab = document.getElementById(tabs[tabIndex]);
        if (targetTab) {
            targetTab.classList.add('show', 'active');
            console.log(`✅ Tab ${tabs[tabIndex]} is now active`);
        } else {
            console.log(`❌ Tab ${tabs[tabIndex]} not found`);
        }
        
        // Update tab buttons
        document.querySelectorAll('#bookTabs button').forEach((btn, index) => {
            btn.classList.remove('active-step');
            if (index === tabIndex) {
                btn.classList.add('active-step');
            }
        });
    }
    
    // Update UI based on current step
    function updateStepUI() {
        console.log(`🔄 Updating UI for step ${currentTabIndex + 1}`);
        
        // Update step indicator
        if (currentStepSpan) {
            currentStepSpan.textContent = `Step ${currentTabIndex + 1}`;
        }
        if (stepDescription) {
            stepDescription.textContent = stepDescriptions[currentTabIndex];
        }
        
        // Show/hide buttons
        if (prevBtn) {
            prevBtn.style.display = currentTabIndex === 0 ? 'none' : 'inline-flex';
        }
        if (nextBtn) {
            nextBtn.style.display = currentTabIndex === tabs.length - 1 ? 'none' : 'inline-flex';
        }
        if (submitBtn) {
            submitBtn.style.display = currentTabIndex === tabs.length - 1 ? 'inline-flex' : 'none';
        }
        
        console.log(`✅ UI updated - Step ${currentTabIndex + 1} of ${tabs.length}`);
    }
    
    // Navigate to next step
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔄 Next button clicked!');
            console.log('Current tab index:', currentTabIndex);
            
            // Move to next step
            if (currentTabIndex < tabs.length - 1) {
                console.log('✅ Moving to next step');
                currentTabIndex++;
                showTab(currentTabIndex);
                updateStepUI();
                console.log('Moved to tab:', tabs[currentTabIndex]);
            } else {
                console.log('⚠️ Already at last step');
            }
        });
        console.log('✅ Next button event listener added');
    } else {
        console.log('❌ Next button not found!');
    }
    
    // Navigate to previous step
    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔄 Previous button clicked!');
            
            if (currentTabIndex > 0) {
                console.log('✅ Moving to previous step');
                currentTabIndex--;
                showTab(currentTabIndex);
                updateStepUI();
                console.log('Moved to tab:', tabs[currentTabIndex]);
            }
        });
        console.log('✅ Previous button event listener added');
    }
    
    // Tab click handler
    document.querySelectorAll('#bookTabs button').forEach((tab, index) => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            console.log(`📋 Tab ${index + 1} clicked directly`);
            currentTabIndex = index;
            showTab(currentTabIndex);
            updateStepUI();
        });
    });
    
    // Reset modal when closed
    if (productModal) {
        productModal.addEventListener('hidden.bs.modal', function () {
            console.log('🔄 Modal closed, resetting...');

            // Reset form title and content
            modalTitle.innerHTML = '<span>📚</span> Add New Book';
            productForm.reset();
            document.getElementById('product_id').value = '0';
            document.getElementById('existing_cover_image').value = '';

            // Hide cover image preview
            const coverPreview = document.getElementById('cover-preview');
            if (coverPreview) {
                coverPreview.style.display = 'none';
                const coverImg = document.getElementById('cover-img');
                if (coverImg) coverImg.src = '';
            }

            // Clear additional images preview
            const additionalPreview = document.getElementById('additional-preview');
            if (additionalPreview) {
                additionalPreview.innerHTML = '';
            }

            // Reset to first tab
            currentTabIndex = 0;
            showTab(0);
            updateStepUI();

            // Clear validation classes
            document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });

            // Scroll to top
            const modalBody = document.querySelector('.modal-body-professional');
            if (modalBody) {
                modalBody.scrollTop = 0;
            }

            console.log('✅ Modal reset complete');
        });
    }

    // Handle add new product button click
    window.openNewProductModal = function() {
        console.log('➕ Opening new product modal');

        // Reset form
        modalTitle.innerHTML = '<span>📚</span> Add New Book';
        productForm.reset();
        document.getElementById('product_id').value = '0';
        document.getElementById('existing_cover_image').value = '';

        // Hide cover image preview
        const coverPreview = document.getElementById('cover-preview');
        if (coverPreview) {
            coverPreview.style.display = 'none';
            const coverImg = document.getElementById('cover-img');
            if (coverImg) coverImg.src = '';
        }

        // Clear additional images preview
        const additionalPreview = document.getElementById('additional-preview');
        if (additionalPreview) {
            additionalPreview.innerHTML = '';
        }

        // Clear all input fields
        document.querySelectorAll('input[type="text"], input[type="number"], input[type="email"], input[type="url"], input[type="date"], textarea, select').forEach(field => {
            if (field.id !== 'product_id' && field.id !== 'existing_cover_image') {
                if (field.type === 'checkbox') {
                    field.checked = false;
                } else {
                    field.value = '';
                }
            }
        });

        // Scroll to top
        const modalBody = document.querySelector('.modal-body-professional');
        if (modalBody) {
            modalBody.scrollTop = 0;
        }

        // Open modal
        openModal('productModal');
        console.log('✅ New product modal opened');
    };

    // Handle edit button click - FIXED FUNCTION
    window.openEditProductModal = function(data) {
        console.log('✏️ Opening edit modal with data:', data);

        // Update modal title
        modalTitle.innerHTML = '<span>✏️</span> Edit Book';

        // Set product ID and existing cover image first
        document.getElementById('product_id').value = data.id;
        document.getElementById('existing_cover_image').value = data.cover_image || '';

        // Fill all form fields from database
        Object.keys(data).forEach(key => {
            const field = document.getElementById(key);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = data[key] == 1 || data[key] === true || data[key] === '1';
                } else if (field.type === 'date') {
                    // Format date properly for date input
                    field.value = data[key] ? data[key].split(' ')[0] : '';
                } else {
                    field.value = data[key] || '';
                }
                console.log(`✅ Field "${key}" set to:`, data[key]);
            }
        });

        // Special handling for featured checkbox
        const featuredCheckbox = document.getElementById('featured');
        if (featuredCheckbox) {
            featuredCheckbox.checked = data['featured'] == 1 || data['featured'] === true || data['featured'] === '1';
            console.log('✅ Featured checkbox set to:', featuredCheckbox.checked);
        }

        // Show existing cover image preview
        if (data.cover_image) {
            const coverPreview = document.getElementById('cover-preview');
            const coverImg = document.getElementById('cover-img');
            if (coverPreview && coverImg) {
                coverImg.src = '/bookshelf/public/images/products/' + data.cover_image;
                coverPreview.style.display = 'block';
                console.log('✅ Cover image preview displayed:', data.cover_image);
            }
        }

        // Scroll to top of form
        const modalBody = document.querySelector('.modal-body-professional');
        if (modalBody) {
            modalBody.scrollTop = 0;
        }

        // Open modal
        openModal('productModal');
    };
    
    // Image handling functions
    window.removeCoverImage = function() {
        const coverImageField = document.getElementById('cover_image');
        const coverPreview = document.getElementById('cover-preview');
        if (coverImageField) coverImageField.value = '';
        if (coverPreview) coverPreview.style.display = 'none';
    };
    
    // Cover image preview
    const coverImageField = document.getElementById('cover_image');
    if (coverImageField) {
        coverImageField.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                console.log('📸 Cover image selected:', file.name);
                const reader = new FileReader();
                reader.onload = function(e) {
                    const coverImg = document.getElementById('cover-img');
                    const coverPreview = document.getElementById('cover-preview');
                    if (coverImg) coverImg.src = e.target.result;
                    if (coverPreview) coverPreview.style.display = 'block';
                    console.log('✅ Cover image preview updated');
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Additional images preview
    const additionalImagesField = document.getElementById('additional_images');
    if (additionalImagesField) {
        additionalImagesField.addEventListener('change', function(e) {
            const files = e.target.files;
            const previewContainer = document.getElementById('additional-preview');
            
            console.log('📸 Additional images selected:', files.length);
            
            if (previewContainer) {
                previewContainer.innerHTML = '';
                
                if (files.length > 0) {
                    const maxFiles = Math.min(files.length, 5); // Limit to 5 images
                    console.log(`📸 Processing ${maxFiles} additional images`);
                    
                    for (let i = 0; i < maxFiles; i++) {
                        const file = files[i];
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const imageDiv = document.createElement('div');
                            imageDiv.className = 'position-relative d-inline-block me-2 mb-2';
                            imageDiv.innerHTML = `
                                <img src="${e.target.result}" alt="Additional Image ${i + 1}" class="img-thumbnail border-2" style="width: 60px; height: 80px; object-fit: cover; border-radius: 8px;">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle p-1" 
                                        onclick="this.parentElement.remove();" 
                                        style="width: 20px; height: 20px; font-size: 10px; transform: translate(25%, -25%);">×</button>
                                <div class="text-center mt-1">
                                    <small class="status-badge-professional status-secondary">${i + 1}</small>
                                </div>
                            `;
                            previewContainer.appendChild(imageDiv);
                            console.log(`✅ Preview added for image ${i + 1}`);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                    
                    if (files.length > 5) {
                        const warningDiv = document.createElement('div');
                        warningDiv.className = 'alert-professional alert-warning-professional mt-2';
                        warningDiv.innerHTML = `
                            <span>⚠️</span>
                            <small><strong>Note:</strong> Only the first 5 images will be uploaded. You selected ${files.length} images.</small>
                        `;
                        previewContainer.appendChild(warningDiv);
                        console.log(`⚠️ Too many images: ${files.length}, limited to 5`);
                    }
                    
                    // Add upload summary
                    const summaryDiv = document.createElement('div');
                    summaryDiv.className = 'text-center mt-2';
                    summaryDiv.innerHTML = `
                        <small class="text-muted">
                            <span>🖼️</span>
                            ${Math.min(files.length, 5)} additional image${Math.min(files.length, 5) !== 1 ? 's' : ''} ready to upload
                        </small>
                    `;
                    previewContainer.appendChild(summaryDiv);
                }
            }
        });
        console.log('✅ Additional images event listener added');
    }
    
    // Initialize
    console.log('🎯 Initializing UI...');
    updateStepUI();
    console.log('✅ Initialization complete!');
});
</script>

<?php include '../includes/admin_footer.php'; ?>
