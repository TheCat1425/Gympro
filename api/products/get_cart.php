<?php
// api/products/get_cart.php — Get session-based cart
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    try {
        $stmt = $pdo->prepare("SELECT product_id as id, name, price, stock, image_url as image, status FROM products WHERE product_id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll();
        
        foreach ($products as $p) {
            $qty = $cart[$p['id']];
            $lineTotal = (float)$p['price'] * $qty;
            $total += $lineTotal;
            $items[] = [
                'id'        => (int)$p['id'],
                'name'      => $p['name'],
                'price'     => (float)$p['price'],
                'quantity'  => $qty,
                'lineTotal' => round($lineTotal, 2),
                'image'     => $p['image'],
                'stock'     => (int)$p['stock'],
                'status'    => $p['status'],
            ];
        }
    } catch (\PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to load cart.'], 500);
    }
}

jsonResponse([
    'success'    => true,
    'items'      => $items,
    'totalItems' => array_sum($cart ?: []),
    'totalPrice' => round($total, 2),
]);
