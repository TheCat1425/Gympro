<?php
// api/cancel_booking.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireAuth();
$userId = $user['user_id'];
$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

if (!$bookingId) {
    jsonResponse(['success' => false, 'message' => 'Invalid booking ID.']);
}

try {
    $pdo->beginTransaction();

    $sql = "DELETE FROM bookings WHERE booking_id = :booking_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':booking_id' => $bookingId, ':user_id' => $userId]);
    
    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'Booking cancelled successfully.']);
    } else {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Booking not found or already cancelled.']);
    }

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()], 500);
}
