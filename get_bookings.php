<?php
// get_bookings.php
header('Content-Type: application/json');
require_once 'db.php';

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

try {
    $sql = "
        SELECT 
            b.booking_id as id,
            b.schedule_id as scheduleId,
            s.class_name as className,
            s.instructor,
            s.day_of_week as day,
            DATE_FORMAT(s.start_time, '%h:%i %p') as time,
            b.booking_date as bookedAt,
            b.status
        FROM bookings b
        JOIN schedules s ON b.schedule_id = s.schedule_id
        WHERE b.user_id = :user_id
        ORDER BY b.booking_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $bookings = $stmt->fetchAll();
    
    foreach ($bookings as &$booking) {
        $booking['id'] = (int)$booking['id'];
        $booking['scheduleId'] = (int)$booking['scheduleId'];
    }
    
    echo json_encode(['success' => true, 'bookings' => $bookings]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch bookings: ' . $e->getMessage()]);
}
