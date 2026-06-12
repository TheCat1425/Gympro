<?php
// api/admin/delete_product.php — Delete a product (soft delete = set discontinued)
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if (!$productId) {
    jsonResponse(['success' => false, 'message' => 'Product ID is required.']);
}

try {
    $stmt = $pdo->prepare("UPDATE products SET status = 'discontinued' WHERE product_id = ?");
    $stmt->execute([$productId]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(['success' => true, 'message' => 'Product removed successfully.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Product not found.']);
    }
} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to delete product.'], 500);
}
