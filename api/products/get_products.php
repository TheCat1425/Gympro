<?php
// api/products/get_products.php — List all available products
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

$category = trim($_GET['category'] ?? '');

try {
    $sql = "SELECT product_id as id, name, description, category, price, stock, image_url as image, status, created_at FROM products WHERE status != 'discontinued'";
    $params = [];
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    foreach ($products as &$p) {
        $p['id'] = (int)$p['id'];
        $p['price'] = (float)$p['price'];
        $p['stock'] = (int)$p['stock'];
    }
    
    jsonResponse(['success' => true, 'products' => $products]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch products.'], 500);
}
