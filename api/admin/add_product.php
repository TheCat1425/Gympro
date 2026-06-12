<?php
// api/admin/add_product.php — Add a new product
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$price       = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$stock       = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
$image       = trim($_POST['image'] ?? '');

$validCategories = ['supplements','food','gear','apparel','accessories'];

if (empty($name)) {
    jsonResponse(['success' => false, 'message' => 'Product name is required.']);
}
if (!in_array($category, $validCategories)) {
    jsonResponse(['success' => false, 'message' => 'Invalid category.']);
}
if ($price === false || $price < 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid price.']);
}
if ($stock === false || $stock < 0) {
    $stock = 0;
}

try {
    $stmt = $pdo->prepare("INSERT INTO products (name, description, category, price, stock, image_url, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
    $stmt->execute([$name, $description, $category, $price, $stock, $image ?: null]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Product added successfully!',
        'product_id' => (int) $pdo->lastInsertId(),
    ]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()], 500);
}
