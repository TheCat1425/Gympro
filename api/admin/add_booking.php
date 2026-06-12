<?php
// api/admin/add_booking.php — Admin adds a booking for a user
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$userId     = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);

if (!$userId || !$scheduleId) {
    jsonResponse(['success' => false, 'message' => 'User ID and Schedule ID are required.']);
}

try {
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, schedule_id, booking_date, status) VALUES (?, ?, NOW(), 'confirmed') ON DUPLICATE KEY UPDATE status = 'confirmed', booking_date = NOW()");
    $stmt->execute([$userId, $scheduleId]);
    
    jsonResponse(['success' => true, 'message' => 'Booking added successfully.']);
} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to add booking: ' . $e->getMessage()], 500);
}
