<?php
// api/products/checkout.php — Create order from cart
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireAuth();
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    jsonResponse(['success' => false, 'message' => 'Your cart is empty.']);
}

try {
    $pdo->beginTransaction();
    
    // Fetch product details and validate stock
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT product_id, name, price, stock, status FROM products WHERE product_id IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    
    $totalAmount = 0;
    $orderItems = [];
    
    foreach ($products as $p) {
        $qty = $cart[$p['product_id']];
        
        if ($p['status'] !== 'available') {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => "{$p['name']} is no longer available."]);
        }
        
        if ($qty > $p['stock']) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => "Only {$p['stock']} of {$p['name']} in stock."]);
        }
        
        $lineTotal = (float)$p['price'] * $qty;
        $totalAmount += $lineTotal;
        $orderItems[] = [
            'product_id' => $p['product_id'],
            'quantity'   => $qty,
            'unit_price' => $p['price'],
        ];
    }
    
    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'confirmed')");
    $stmt->execute([$user['user_id'], $totalAmount]);
    $orderId = (int) $pdo->lastInsertId();
    
    // Insert order items and decrement stock
    $insertItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    $updateStock = $pdo->prepare("UPDATE products SET stock = stock - ?, status = CASE WHEN stock - ? <= 0 THEN 'sold_out' ELSE status END WHERE product_id = ?");
    
    foreach ($orderItems as $item) {
        $insertItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price']]);
        $updateStock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
    }
    
    $pdo->commit();
    
    // Clear cart
    $_SESSION['cart'] = [];
    
    jsonResponse([
        'success'  => true,
        'message'  => 'Order placed successfully! 🎉',
        'order_id' => $orderId,
        'total'    => round($totalAmount, 2),
    ]);

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()], 500);
}
