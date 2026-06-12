<?php
// api/admin/block_instructor.php — Block or unblock an instructor
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$instructorId = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT);
$action = trim($_POST['action'] ?? '');

if (!$instructorId || !in_array($action, ['block', 'unblock'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid instructor ID or action.']);
}

try {
    $newStatus = $action === 'block' ? 'blocked' : 'active';
    $stmt = $pdo->prepare("UPDATE instructors SET status = ? WHERE instructor_id = ?");
    $stmt->execute([$newStatus, $instructorId]);

    if ($stmt->rowCount() > 0) {
        jsonResponse(['success' => true, 'message' => 'Instructor ' . $action . 'ed successfully.']);
    }

    jsonResponse(['success' => false, 'message' => 'Instructor not found.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update instructor status.'], 500);
}
