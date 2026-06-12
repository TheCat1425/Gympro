<?php
// api/get_bookings.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$user = requireAuth();
$userId = $user['user_id'];

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
        WHERE b.user_id = :user_id AND b.status = 'confirmed'
        ORDER BY b.booking_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $bookings = $stmt->fetchAll();
    
    foreach ($bookings as &$booking) {
        $booking['id'] = (int)$booking['id'];
        $booking['scheduleId'] = (int)$booking['scheduleId'];
    }
    
    jsonResponse(['success' => true, 'bookings' => $bookings]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch bookings: ' . $e->getMessage()], 500);
}
