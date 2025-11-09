<?php
/**
 * BulkUploadManager.php - Handle bulk product uploads
 */

class BulkUploadManager {
    private $conn;
    private $maxFileSize = 10485760; // 10MB
    private $allowedExtensions = ['csv', 'xlsx', 'xls'];

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Process bulk upload
     */
    public function processBulkUpload($filePath, $uploadType = 'products', $userId = null) {
        try {
            if (!file_exists($filePath)) {
                return ['success' => false, 'error' => 'File not found'];
            }

            if (filesize($filePath) > $this->maxFileSize) {
                return ['success' => false, 'error' => 'File too large (max 10MB)'];
            }

            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedExtensions)) {
                return ['success' => false, 'error' => 'Invalid file format. Use CSV or Excel.'];
            }

            // Create upload record
            $filename = basename($filePath);
            $stmt = $this->conn->prepare("
                INSERT INTO bulk_uploads (upload_type, filename, status, created_by)
                VALUES (?, ?, 'processing', ?)
            ");
            $stmt->bind_param("ssi", $uploadType, $filename, $userId);
            $stmt->execute();
            $uploadId = $this->conn->insert_id;

            // Parse file
            $data = $this->parseFile($filePath, $ext);
            if (!$data['success']) {
                $this->updateUploadStatus($uploadId, 'failed', $data['error']);
                return $data;
            }

            // Process records
            $records = $data['data'];
            $result = $this->processRecords($uploadId, $records, $uploadType);

            return $result;
        } catch (Exception $e) {
            error_log("Bulk upload error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse CSV file
     */
    private function parseFile($filePath, $ext) {
        try {
            $data = [];

            if ($ext === 'csv') {
                $handle = fopen($filePath, 'r');
                if (!$handle) {
                    return ['success' => false, 'error' => 'Cannot read file'];
                }

                $header = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty(array_filter($row))) continue;
                    $data[] = array_combine($header, $row);
                }
                fclose($handle);
            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                // For Excel, using simple CSV conversion
                // In production, use PHPOffice/PhpSpreadsheet library
                $data = $this->parseExcelAsCSV($filePath);
            }

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse Excel file as CSV (simple method)
     */
    private function parseExcelAsCSV($filePath) {
        // This is a basic implementation
        // For production, use: composer require phpoffice/phpspreadsheet
        $handle = fopen($filePath, 'r');
        $data = [];
        $header = null;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = $row;
            } else {
                if (empty(array_filter($row))) continue;
                $data[] = array_combine($header, $row);
            }
        }

        fclose($handle);
        return $data;
    }

    /**
     * Process records from upload
     */
    private function processRecords($uploadId, $records, $uploadType) {
        try {
            $successful = 0;
            $failed = 0;
            $errors = [];

            foreach ($records as $index => $record) {
                if ($uploadType === 'products') {
                    $result = $this->processProductRecord($record);
                } elseif ($uploadType === 'inventory') {
                    $result = $this->processInventoryRecord($record);
                } elseif ($uploadType === 'prices') {
                    $result = $this->processPriceRecord($record);
                } else {
                    $result = ['success' => false, 'error' => 'Unknown upload type'];
                }

                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                    $errors[] = "Row " . ($index + 2) . ": " . ($result['error'] ?? 'Unknown error');
                }

                // Update progress every 10 records
                if (($index + 1) % 10 === 0) {
                    $this->updateUploadProgress($uploadId, $index + 1, count($records));
                }
            }

            $errorLog = implode("\n", array_slice($errors, 0, 50)); // Log first 50 errors
            $this->updateUploadStatus($uploadId, 'completed', $errorLog, $successful, $failed);

            return [
                'success' => true,
                'upload_id' => $uploadId,
                'total' => count($records),
                'successful' => $successful,
                'failed' => $failed,
                'errors' => count($errors) > 0 ? array_slice($errors, 0, 10) : null
            ];
        } catch (Exception $e) {
            $this->updateUploadStatus($uploadId, 'failed', $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process product record
     */
    private function processProductRecord($record) {
        try {
            // Validate required fields
            if (empty($record['name']) || empty($record['price'])) {
                return ['success' => false, 'error' => 'Missing name or price'];
            }

            $stmt = $this->conn->prepare("
                INSERT INTO products (
                    name, description, price, stock_quantity, category_id, author, publisher
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    description = VALUES(description),
                    price = VALUES(price),
                    stock_quantity = VALUES(stock_quantity)
            ");

            $stmt->bind_param(
                "ssdiiss",
                $record['name'],
                $record['description'] ?? '',
                $record['price'],
                $record['stock_quantity'] ?? 0,
                $record['category_id'] ?? null,
                $record['author'] ?? '',
                $record['publisher'] ?? ''
            );

            return ['success' => $stmt->execute()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process inventory record
     */
    private function processInventoryRecord($record) {
        try {
            if (empty($record['product_id']) || !isset($record['stock_quantity'])) {
                return ['success' => false, 'error' => 'Missing product_id or stock_quantity'];
            }

            $stmt = $this->conn->prepare("
                UPDATE products SET stock_quantity = ? WHERE id = ?
            ");

            $stmt->bind_param("ii", $record['stock_quantity'], $record['product_id']);
            return ['success' => $stmt->execute()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process price record
     */
    private function processPriceRecord($record) {
        try {
            if (empty($record['product_id']) || empty($record['price'])) {
                return ['success' => false, 'error' => 'Missing product_id or price'];
            }

            $stmt = $this->conn->prepare("
                UPDATE products SET price = ? WHERE id = ?
            ");

            $stmt->bind_param("di", $record['price'], $record['product_id']);
            return ['success' => $stmt->execute()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update upload status
     */
    private function updateUploadStatus($uploadId, $status, $errorLog = '', $successful = 0, $failed = 0) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE bulk_uploads
                SET status = ?, error_log = ?, successful_records = ?, failed_records = ?, completed_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param("sssii", $status, $errorLog, $successful, $failed, $uploadId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating upload: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update upload progress
     */
    private function updateUploadProgress($uploadId, $processed, $total) {
        try {
            $progress = ($processed / $total) * 100;
            $stmt = $this->conn->prepare("
                UPDATE bulk_uploads SET error_log = CONCAT(error_log, ?) WHERE id = ?
            ");
            // Store progress in a separate field if available
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get upload history
     */
    public function getUploadHistory($limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, upload_type, filename, total_records, successful_records, failed_records, status, created_at
                FROM bulk_uploads
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->bind_param("i", $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get upload details
     */
    public function getUploadDetails($uploadId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM bulk_uploads WHERE id = ?
            ");

            $stmt->bind_param("i", $uploadId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get sample template for download
     */
    public function generateSampleCSV($uploadType = 'products') {
        $headers = [];
        $sampleData = [];

        if ($uploadType === 'products') {
            $headers = ['name', 'description', 'price', 'stock_quantity', 'category_id', 'author', 'publisher'];
            $sampleData = [
                'The Great Gatsby,Classic American Novel,12.99,50,1,F. Scott Fitzgerald,Scribner',
                'To Kill a Mockingbird,American Legal Drama,14.99,45,1,Harper Lee,J.B. Lippincott & Co.',
            ];
        } elseif ($uploadType === 'inventory') {
            $headers = ['product_id', 'stock_quantity'];
            $sampleData = [
                '1,100',
                '2,85',
            ];
        } elseif ($uploadType === 'prices') {
            $headers = ['product_id', 'price'];
            $sampleData = [
                '1,12.99',
                '2,14.99',
            ];
        }

        $csv = implode(',', $headers) . "\n";
        $csv .= implode("\n", $sampleData);

        return $csv;
    }
}
