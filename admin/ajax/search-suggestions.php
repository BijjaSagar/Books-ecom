<?php
/**
 * admin/ajax/search-suggestions.php - Real-time search suggestions
 */

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once '../../includes/db_connect.php';
require_once '../../includes/AdvancedSearch.php';

$search = new AdvancedSearch($conn);

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'products';

try {
    $suggestions = $search->getSearchSuggestions($query, $type);
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions,
        'count' => count($suggestions)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
