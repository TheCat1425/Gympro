<?php
// api/products/add_to_cart.php — Add item to session cart
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity  = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

if (!$productId || $quantity < 1) {
    jsonResponse(['success' => false, 'message' => 'Invalid product or quantity.']);
}

// Verify product exists and has stock
try {
    $stmt = $pdo->prepare("SELECT product_id, name, stock, status FROM products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product || $product['status'] !== 'available') {
        jsonResponse(['success' => false, 'message' => 'Product is not available.']);
    }
    
    // Initialize cart if needed
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $currentQty = $_SESSION['cart'][$productId] ?? 0;
    $newQty = $currentQty + $quantity;
    
    if ($newQty > $product['stock']) {
        jsonResponse(['success' => false, 'message' => "Only {$product['stock']} items in stock."]);
    }
    
    $_SESSION['cart'][$productId] = $newQty;
    
    jsonResponse([
        'success'    => true,
        'message'    => "{$product['name']} added to cart!",
        'totalItems' => array_sum($_SESSION['cart']),
    ]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to add to cart.'], 500);
}
