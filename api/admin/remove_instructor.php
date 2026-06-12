<?php
// api/admin/remove_instructor.php — Archive/remove an instructor
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$instructorId = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT);
if (!$instructorId) {
    jsonResponse(['success' => false, 'message' => 'Invalid instructor ID.']);
}

try {
    $stmt = $pdo->prepare("UPDATE instructors SET status = 'removed' WHERE instructor_id = ?");
    $stmt->execute([$instructorId]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(['success' => true, 'message' => 'Instructor removed successfully.']);
    }

    jsonResponse(['success' => false, 'message' => 'Instructor not found.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to remove instructor.'], 500);
}
