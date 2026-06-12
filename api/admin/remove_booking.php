<?php
// api/admin/remove_booking.php — Admin removes a booking
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
if (!$bookingId) {
    jsonResponse(['success' => false, 'message' => 'Invalid booking ID.']);
}

try {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(['success' => true, 'message' => 'Booking removed successfully.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Booking not found.']);
    }
} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to remove booking.'], 500);
}
