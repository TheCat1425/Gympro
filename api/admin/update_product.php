<?php
// api/admin/update_product.php — Update product details
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$productId   = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$price       = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$stock       = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
$image       = trim($_POST['image'] ?? '');

if (!$productId) {
    jsonResponse(['success' => false, 'message' => 'Product ID is required.']);
}

try {
    $fields = [];
    $params = [];
    
    if (!empty($name))        { $fields[] = "name = ?";        $params[] = $name; }
    if (!empty($description)) { $fields[] = "description = ?"; $params[] = $description; }
    if (!empty($category))    { $fields[] = "category = ?";    $params[] = $category; }
    if ($price !== false && $price !== null) { $fields[] = "price = ?"; $params[] = $price; }
    if ($stock !== false && $stock !== null) { $fields[] = "stock = ?"; $params[] = $stock; }
    if (!empty($image))       { $fields[] = "image_url = ?";   $params[] = $image; }
    
    if (empty($fields)) {
        jsonResponse(['success' => false, 'message' => 'No fields to update.']);
    }
    
    $params[] = $productId;
    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    jsonResponse(['success' => true, 'message' => 'Product updated successfully.']);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update product.'], 500);
}
