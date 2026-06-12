<?php
// book_class.php
header('Content-Type: application/json');

// LINK THE DATABASE CONNECTION HERE:
// require_once pulls in the $pdo object from your db.php file seamlessly.
require_once 'db.php'; 

// Simulated input data from the user frontend
$userId     = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);

if (!$userId || !$scheduleId) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

try {
    // The $pdo variable is available here because we required 'db.php' above
    $pdo->beginTransaction();

    $sql = "INSERT INTO bookings (user_id, schedule_id, booking_date, status) 
            VALUES (:user_id, :schedule_id, NOW(), 'confirmed') 
            ON DUPLICATE KEY UPDATE status = 'confirmed', booking_date = NOW()";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':user_id'     => $userId,
        ':schedule_id' => $scheduleId
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Class booked successfully!']);

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Catch your custom MySQL trigger errors safely
    if ($e->getCode() == '45000') {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'An internal database error occurred.']);
    }
}