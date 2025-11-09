<?php
/**
 * admin/bulk-upload.php - Bulk Product Upload Manager
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/BulkUploadManager.php';

$uploadManager = new BulkUploadManager($conn);
$uploadHistory = $uploadManager->getUploadHistory(50);

// Handle file upload
$uploadResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    $uploadType = $_POST['upload_type'] ?? 'products';
    $uploadDir = '../uploads/bulk/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = basename($_FILES['upload_file']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = time() . '_' . uniqid() . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $uploadPath)) {
        $uploadResult = $uploadManager->processBulkUpload($uploadPath, $uploadType, $_SESSION['user_id'] ?? null);

        // Clean up after processing
        if ($uploadResult['success']) {
            unlink($uploadPath);
        }
    } else {
        $uploadResult = ['success' => false, 'error' => 'File upload failed'];
    }
}

// Handle download sample
if (isset($_GET['download']) && $_GET['download'] === 'sample') {
    $type = $_GET['type'] ?? 'products';
    $csv = $uploadManager->generateSampleCSV($type);

    $filename = 'sample_' . $type . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $csv;
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .upload-area { border: 2px dashed #007bff; border-radius: 8px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { background-color: #f0f8ff; border-color: #0056b3; }
        .upload-area.dragover { background-color: #e7f3ff; border-color: #0056b3; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
        .sidebar a.active { background-color: #007bff; }
        .progress-container { display: none; margin-top: 20px; }
        .template-guide { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .template-guide table { font-size: 12px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h5 class="text-white"><i class="bi bi-bar-chart"></i> Bookory Admin</h5>
                </div>
                <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="bulk-upload.php" class="active"><i class="bi bi-cloud-upload"></i> Bulk Upload</a>
                <a href="products.php"><i class="bi bi-bag"></i> Products</a>
                <a href="inventory-management.php"><i class="bi bi-box"></i> Inventory</a>
                <a href="sales-dashboard.php"><i class="bi bi-graph-up"></i> Sales</a>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div style="background-color: #34495e; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2><i class="bi bi-cloud-upload"></i> Bulk Product Upload</h2>
                    <p class="mb-0">Upload CSV or Excel files to manage multiple products at once</p>
                </div>

                <!-- Upload Form -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-plus-circle"></i> Upload File</h5>

                    <?php if ($uploadResult): ?>
                    <div class="alert alert-<?php echo $uploadResult['success'] ? 'success' : 'danger'; ?>" role="alert">
                        <strong><?php echo $uploadResult['success'] ? 'Success!' : 'Error'; ?></strong>
                        <?php if ($uploadResult['success']): ?>
                            <p class="mb-0">
                                Successfully imported <?php echo $uploadResult['successful']; ?> of <?php echo $uploadResult['total']; ?> records.
                                <?php if ($uploadResult['failed'] > 0): ?>
                                Failed: <?php echo $uploadResult['failed']; ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($uploadResult['errors'])): ?>
                            <small class="text-danger">
                                Errors:
                                <?php foreach ($uploadResult['errors'] as $error): ?>
                                <br/><?php echo htmlspecialchars($error); ?>
                                <?php endforeach; ?>
                            </small>
                            <?php endif; ?>
                        <?php else: ?>
                        <?php echo htmlspecialchars($uploadResult['error']); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label"><strong>Upload Type</strong></label>
                                <select name="upload_type" id="uploadType" class="form-select" required>
                                    <option value="products">Products (New/Update)</option>
                                    <option value="inventory">Inventory (Stock Only)</option>
                                    <option value="prices">Prices (Price Only)</option>
                                </select>
                                <small class="text-muted">Choose what you're uploading</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><strong>&nbsp;</strong></label>
                                <a href="?download=sample&type=products" class="btn btn-outline-info w-100">
                                    <i class="bi bi-download"></i> Sample - Products
                                </a>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><strong>&nbsp;</strong></label>
                                <a href="?download=sample&type=inventory" class="btn btn-outline-info w-100">
                                    <i class="bi bi-download"></i> Sample - Inventory
                                </a>
                            </div>
                        </div>

                        <!-- Drag & Drop Area -->
                        <div class="upload-area" id="uploadArea">
                            <i class="bi bi-cloud-upload" style="font-size: 48px; color: #007bff;"></i>
                            <h5 class="mt-3">Drag & Drop or Click to Upload</h5>
                            <p class="text-muted">CSV, XLS, or XLSX files (Max 10MB)</p>
                            <input type="file" name="upload_file" id="fileInput" style="display: none;" accept=".csv,.xlsx,.xls" required>
                        </div>

                        <!-- Progress -->
                        <div class="progress-container">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="progressText">Uploading...</small>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="bi bi-upload"></i> Upload & Process
                            </button>
                        </div>
                    </form>

                    <!-- Template Guide -->
                    <div class="template-guide">
                        <h6><i class="bi bi-info-circle"></i> CSV Template Format</h6>
                        <div id="templateInfo">
                            <p class="mb-2"><strong>Products:</strong></p>
                            <table class="table table-sm">
                                <tr>
                                    <td><code>name</code></td>
                                    <td>Product title (required)</td>
                                </tr>
                                <tr>
                                    <td><code>price</code></td>
                                    <td>Product price (required)</td>
                                </tr>
                                <tr>
                                    <td><code>stock_quantity</code></td>
                                    <td>Stock quantity (optional)</td>
                                </tr>
                                <tr>
                                    <td><code>description</code></td>
                                    <td>Product description (optional)</td>
                                </tr>
                                <tr>
                                    <td><code>category_id</code></td>
                                    <td>Category ID (optional)</td>
                                </tr>
                                <tr>
                                    <td><code>author</code></td>
                                    <td>Author name (optional)</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Upload History -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-history"></i> Recent Uploads</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Total Records</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Uploaded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uploadHistory as $upload): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($upload['filename']); ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($upload['upload_type']); ?></span></td>
                                    <td>
                                        <?php
                                        $statusClass = match($upload['status']) {
                                            'completed' => 'success',
                                            'processing' => 'warning',
                                            'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($upload['status']); ?></span>
                                    </td>
                                    <td><?php echo $upload['total_records'] ?? 0; ?></td>
                                    <td><span class="badge bg-success"><?php echo $upload['successful_records'] ?? 0; ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo $upload['failed_records'] ?? 0; ?></span></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($upload['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');
        const progressContainer = document.querySelector('.progress-container');
        const progressBar = document.getElementById('progressBar');

        // Click to upload
        uploadArea.addEventListener('click', () => fileInput.click());

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
        });

        // File selected
        fileInput.addEventListener('change', (e) => {
            const fileName = e.target.files[0]?.name || 'No file selected';
            uploadArea.innerHTML = `<i class="bi bi-check-circle" style="font-size: 48px; color: #28a745;"></i><h5 class="mt-3">${fileName}</h5><p class="text-muted">Ready to upload</p>`;
        });

        // Form submission
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(uploadForm);
            progressContainer.style.display = 'block';

            try {
                const response = await fetch(uploadForm.action || window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Simulate progress
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 30;
                    if (progress > 90) progress = 90;
                    progressBar.style.width = progress + '%';

                    if (response.ok) {
                        clearInterval(interval);
                        progressBar.style.width = '100%';
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                }, 300);
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed: ' + error.message);
            }
        });

        // Update template info based on selection
        document.getElementById('uploadType').addEventListener('change', (e) => {
            const type = e.target.value;
            const templateInfo = document.getElementById('templateInfo');

            if (type === 'inventory') {
                templateInfo.innerHTML = `
                    <p class="mb-2"><strong>Inventory Update:</strong></p>
                    <table class="table table-sm">
                        <tr><td><code>product_id</code></td><td>Product ID (required)</td></tr>
                        <tr><td><code>stock_quantity</code></td><td>New stock quantity (required)</td></tr>
                    </table>
                `;
            } else if (type === 'prices') {
                templateInfo.innerHTML = `
                    <p class="mb-2"><strong>Price Update:</strong></p>
                    <table class="table table-sm">
                        <tr><td><code>product_id</code></td><td>Product ID (required)</td></tr>
                        <tr><td><code>price</code></td><td>New price (required)</td></tr>
                    </table>
                `;
            } else {
                templateInfo.innerHTML = `
                    <p class="mb-2"><strong>Products:</strong></p>
                    <table class="table table-sm">
                        <tr><td><code>name</code></td><td>Product title (required)</td></tr>
                        <tr><td><code>price</code></td><td>Product price (required)</td></tr>
                        <tr><td><code>stock_quantity</code></td><td>Stock quantity (optional)</td></tr>
                        <tr><td><code>description</code></td><td>Product description (optional)</td></tr>
                        <tr><td><code>category_id</code></td><td>Category ID (optional)</td></tr>
                        <tr><td><code>author</code></td><td>Author name (optional)</td></tr>
                    </table>
                `;
            }
        });
    </script>
</body>
</html>
