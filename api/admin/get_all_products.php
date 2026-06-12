<?php
// api/admin/get_all_products.php — Get all products including hidden ones (admin)
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

try {
    $sql = "SELECT product_id as id, name, description, category, price, stock, image_url as image, status, created_at as createdAt, updated_at as updatedAt FROM products ORDER BY created_at DESC";
    $products = $pdo->query($sql)->fetchAll();
    
    foreach ($products as &$p) {
        $p['id'] = (int)$p['id'];
        $p['price'] = (float)$p['price'];
        $p['stock'] = (int)$p['stock'];
    }
    
    jsonResponse(['success' => true, 'products' => $products]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch products.'], 500);
}
