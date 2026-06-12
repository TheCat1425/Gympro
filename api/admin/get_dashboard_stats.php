<?php
// api/admin/get_dashboard_stats.php — Admin dashboard statistics
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

try {
    $stats = [];
    $stats['totalUsers']    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();
    $stats['totalBookings'] = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
    $stats['totalProducts'] = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status != 'discontinued'")->fetchColumn();
    $stats['totalOrders']   = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['totalRevenue']  = (float) ($pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn());
    $stats['blockedUsers']  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn();
    $stats['activeClasses'] = (int) $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
    $stats['soldOutProducts'] = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'sold_out'")->fetchColumn();

    jsonResponse(['success' => true, 'stats' => $stats]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch stats.'], 500);
}
