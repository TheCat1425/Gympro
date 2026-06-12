<?php
// api/admin/get_all_bookings.php — Get all bookings (admin)
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

try {
    $sql = "
        SELECT 
            b.booking_id as id,
            b.user_id as userId,
            u.full_name as userName,
            u.email as userEmail,
            b.schedule_id as scheduleId,
            s.class_name as className,
            s.instructor,
            s.day_of_week as day,
            DATE_FORMAT(s.start_time, '%h:%i %p') as time,
            b.booking_date as bookedAt,
            b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN schedules s ON b.schedule_id = s.schedule_id
        ORDER BY b.booking_date DESC
    ";
    $bookings = $pdo->query($sql)->fetchAll();
    
    foreach ($bookings as &$b) {
        $b['id'] = (int)$b['id'];
        $b['userId'] = (int)$b['userId'];
        $b['scheduleId'] = (int)$b['scheduleId'];
    }
    
    jsonResponse(['success' => true, 'bookings' => $bookings]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch bookings.'], 500);
}
