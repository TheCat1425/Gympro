<?php
// api/book_class.php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$user = requireAuth();
$userId = $user['user_id'];
$scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);

if (!$scheduleId) {
    jsonResponse(['success' => false, 'message' => 'Invalid class selection.']);
}

try {
    $pdo->beginTransaction();

    // Check capacity
    $stmt = $pdo->prepare("SELECT capacity, (SELECT COUNT(*) FROM bookings WHERE schedule_id = s.schedule_id AND status = 'confirmed') as enrolled FROM schedules s WHERE schedule_id = ?");
    $stmt->execute([$scheduleId]);
    $classInfo = $stmt->fetch();

    if (!$classInfo) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Class not found.']);
    }

    if ($classInfo['enrolled'] >= $classInfo['capacity']) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => 'Sorry, this class is full.']);
    }

    // Book
    $sql = "INSERT INTO bookings (user_id, schedule_id, booking_date, status) 
            VALUES (:user_id, :schedule_id, NOW(), 'confirmed') 
            ON DUPLICATE KEY UPDATE status = 'confirmed', booking_date = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId, ':schedule_id' => $scheduleId]);

    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'Class booked successfully! 🎉']);

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    
    if ($e->getCode() == '45000') {
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
    jsonResponse(['success' => false, 'message' => 'An internal database error occurred.'], 500);
}
