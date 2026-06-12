<?php
// cancel_booking.php
header('Content-Type: application/json');
require_once 'db.php';

$bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$bookingId || !$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $sql = "DELETE FROM bookings WHERE booking_id = :booking_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':booking_id' => $bookingId,
        ':user_id'    => $userId
    ]);
    
    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully.']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Booking not found or already cancelled.']);
    }

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
}
