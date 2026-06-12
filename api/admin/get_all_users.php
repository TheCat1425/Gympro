<?php
// api/admin/get_all_users.php — Get all users (admin)
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

try {
    $sql = "
        SELECT 
            u.user_id as id,
            u.full_name as fullName,
            u.email,
            u.phone,
            u.role,
            u.status,
            u.created_at as createdAt,
            (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id AND b.status = 'confirmed') as totalBookings,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) as totalOrders
        FROM users u
        ORDER BY u.created_at DESC
    ";
    $users = $pdo->query($sql)->fetchAll();
    
    foreach ($users as &$u) {
        $u['id'] = (int)$u['id'];
        $u['totalBookings'] = (int)$u['totalBookings'];
        $u['totalOrders'] = (int)$u['totalOrders'];
    }
    
    jsonResponse(['success' => true, 'users' => $users]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch users.'], 500);
}
