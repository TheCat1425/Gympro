<?php
// get_classes.php
header('Content-Type: application/json');
require_once 'db.php';

try {
    // Get all classes along with the number of confirmed bookings (enrolled)
    $sql = "
        SELECT 
            s.schedule_id as id,
            s.class_name as name,
            s.category,
            s.instructor,
            s.day_of_week as day,
            DATE_FORMAT(s.start_time, '%h:%i %p') as time,
            s.duration_minutes as duration,
            s.capacity,
            s.level,
            (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.schedule_id AND b.status = 'confirmed') as enrolled
        FROM schedules s
        ORDER BY s.day_of_week, s.start_time
    ";
    
    $stmt = $pdo->query($sql);
    $classes = $stmt->fetchAll();
    
    // Convert numeric strings to integers if needed
    foreach ($classes as &$cls) {
        $cls['id'] = (int)$cls['id'];
        $cls['duration'] = (int)$cls['duration'];
        $cls['capacity'] = (int)$cls['capacity'];
        $cls['enrolled'] = (int)$cls['enrolled'];
    }
    
    echo json_encode(['success' => true, 'classes' => $classes]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch classes: ' . $e->getMessage()]);
}
