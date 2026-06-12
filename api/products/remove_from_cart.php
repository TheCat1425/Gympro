<?php
// api/products/remove_from_cart.php — Remove item from cart
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$productId) {
    jsonResponse(['success' => false, 'message' => 'Invalid product ID.']);
}

unset($_SESSION['cart'][$productId]);

jsonResponse([
    'success'    => true,
    'message'    => 'Item removed from cart.',
    'totalItems' => array_sum($_SESSION['cart'] ?? []),
]);
