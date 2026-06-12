<?php
// api/products/update_cart.php — Update cart item quantity
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity  = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$productId || $quantity === false || $quantity === null) {
    jsonResponse(['success' => false, 'message' => 'Invalid data.']);
}

if ($quantity <= 0) {
    // Remove from cart
    unset($_SESSION['cart'][$productId]);
    jsonResponse(['success' => true, 'message' => 'Item removed from cart.', 'totalItems' => array_sum($_SESSION['cart'] ?? [])]);
}

// Check stock
try {
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? AND status = 'available'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        jsonResponse(['success' => false, 'message' => 'Product not available.']);
    }
    
    if ($quantity > $product['stock']) {
        jsonResponse(['success' => false, 'message' => "Only {$product['stock']} in stock."]);
    }
    
    $_SESSION['cart'][$productId] = $quantity;
    
    jsonResponse(['success' => true, 'message' => 'Cart updated.', 'totalItems' => array_sum($_SESSION['cart'])]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update cart.'], 500);
}
