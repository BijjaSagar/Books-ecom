<?php
/**
 * Delete Product API Endpoint
 * POST /admin/delete-product.php
 *
 * Handles secure deletion of products with validation and error handling
 * Returns JSON response with success/error status
 */

session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '❌ Unauthorized access. Please log in.'
    ]);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '❌ Invalid request method. Use POST.'
    ]);
    exit;
}

// Include database config
require_once '../includes/config.php';

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate product ID
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;

    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '❌ Invalid product ID'
        ]);
        exit;
    }

    // Get product details before deletion (for logging)
    $stmt = $conn->prepare("
        SELECT id, title, cover_image FROM products WHERE id = ?
    ");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '❌ Product not found'
        ]);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete related records

        // 1. Delete from inventory management tables (if they exist)
        $tables_to_clean = [
            'order_items',
            'cart_items',
            'product_reviews',
            'product_images',
            'product_inventory',
            'product_analytics'
        ];

        foreach ($tables_to_clean as $table) {
            // Check if table exists first
            $check_table = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check_table->num_rows > 0) {
                $delete_stmt = $conn->prepare("DELETE FROM $table WHERE product_id = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param('i', $product_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    error_log("✅ Deleted records from $table for product_id: $product_id");
                }
            }
        }

        // 2. Delete cover image file if exists
        if (!empty($product['cover_image'])) {
            $image_path = '../public/images/products/' . basename($product['cover_image']);
            if (file_exists($image_path)) {
                if (unlink($image_path)) {
                    error_log("✅ Deleted image file: " . $product['cover_image']);
                } else {
                    error_log("⚠️ Failed to delete image file: " . $product['cover_image']);
                }
            }
        }

        // 3. Delete additional product images
        $images_result = $conn->query("
            SELECT file_path FROM product_images WHERE product_id = $product_id
        ");

        if ($images_result && $images_result->num_rows > 0) {
            while ($image = $images_result->fetch_assoc()) {
                $image_path = '../public/images/products/' . basename($image['file_path']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }

        // 4. Delete the product itself
        $delete_product_stmt = $conn->prepare("
            DELETE FROM products WHERE id = ?
        ");
        $delete_product_stmt->bind_param('i', $product_id);
        $delete_product_stmt->execute();
        $delete_product_stmt->close();

        error_log("✅ Product deleted: ID=$product_id, Title={$product['title']}");

        // Commit transaction
        $conn->commit();

        // Return success response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "✅ Product '{$product['title']}' has been deleted successfully",
            'product_id' => $product_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("❌ Error deleting product: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '❌ Error deleting product: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
