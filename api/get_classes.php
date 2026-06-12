<?php
// api/get_classes.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $sql = "
        SELECT 
            s.schedule_id as id,
            s.class_name as name,
            s.category,
            COALESCE(i.full_name, s.instructor) as instructor,
            i.status as instructorStatus,
            s.day_of_week as day,
            DATE_FORMAT(s.start_time, '%h:%i %p') as time,
            s.duration_minutes as duration,
            s.capacity,
            s.level,
            (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.schedule_id AND b.status = 'confirmed') as enrolled
        FROM schedules s
        LEFT JOIN instructors i ON i.instructor_id = s.instructor_id
        ORDER BY FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time
    ";
    
    $stmt = $pdo->query($sql);
    $classes = $stmt->fetchAll();
    
    foreach ($classes as &$cls) {
        $cls['id'] = (int)$cls['id'];
        $cls['duration'] = (int)$cls['duration'];
        $cls['capacity'] = (int)$cls['capacity'];
        $cls['enrolled'] = (int)$cls['enrolled'];
    }
    
    jsonResponse(['success' => true, 'classes' => $classes]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch classes: ' . $e->getMessage()], 500);
}
