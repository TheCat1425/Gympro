<?php
// api/admin/update_instructor.php — Update instructor details
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$instructorId = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT);
$fullName = trim($_POST['full_name'] ?? '');
$specialty = trim($_POST['specialty'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if (!$instructorId || $fullName === '') {
    jsonResponse(['success' => false, 'message' => 'Invalid instructor data.']);
}

try {
    $stmt = $pdo->prepare("SELECT full_name FROM instructors WHERE instructor_id = ?");
    $stmt->execute([$instructorId]);
    $current = $stmt->fetch();

    if (!$current) {
        jsonResponse(['success' => false, 'message' => 'Instructor not found.']);
    }

    $update = $pdo->prepare("UPDATE instructors SET full_name = ?, specialty = ?, email = ?, phone = ?, bio = ? WHERE instructor_id = ?");
    $update->execute([
        $fullName,
        $specialty !== '' ? $specialty : null,
        $email !== '' ? $email : null,
        $phone !== '' ? $phone : null,
        $bio !== '' ? $bio : null,
        $instructorId,
    ]);

    if ($current['full_name'] !== $fullName) {
        $linkStmt = $pdo->prepare("UPDATE schedules SET instructor = ? WHERE instructor_id = ? OR instructor = ?");
        $linkStmt->execute([$fullName, $instructorId, $current['full_name']]);
    }

    jsonResponse(['success' => true, 'message' => 'Instructor updated successfully.']);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update instructor.'], 500);
}
