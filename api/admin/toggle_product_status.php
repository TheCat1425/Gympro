<?php
// api/admin/toggle_product_status.php — Toggle between available/sold_out
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$status    = trim($_POST['status'] ?? '');

if (!$productId || !in_array($status, ['available', 'sold_out'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid product ID or status.']);
}

try {
    $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE product_id = ?");
    $stmt->execute([$status, $productId]);
    
    jsonResponse(['success' => true, 'message' => "Product marked as {$status}."]);
} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update status.'], 500);
}
