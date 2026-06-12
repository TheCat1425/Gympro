<?php
// api/admin/add_instructor.php — Create a new instructor
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$fullName = trim($_POST['full_name'] ?? '');
$specialty = trim($_POST['specialty'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$dayOfWeek = trim($_POST['day_of_week'] ?? '');
$startTime = trim($_POST['start_time'] ?? '');

if ($fullName === '') {
    jsonResponse(['success' => false, 'message' => 'Instructor name is required.']);
}

try {
    $stmt = $pdo->prepare("INSERT INTO instructors (full_name, specialty, email, phone, bio, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        $fullName,
        $specialty !== '' ? $specialty : null,
        $email !== '' ? $email : null,
        $phone !== '' ? $phone : null,
        $bio !== '' ? $bio : null,
    ]);

    $instructorId = (int) $pdo->lastInsertId();

    $linkStmt = $pdo->prepare("UPDATE schedules SET instructor_id = ? WHERE instructor = ?");
    $linkStmt->execute([$instructorId, $fullName]);

    // If day and time provided, create a class schedule for this instructor
    if ($dayOfWeek !== '' && $startTime !== '') {
        try {
            $scheduleStmt = $pdo->prepare(
                "INSERT INTO schedules (class_name, category, instructor, instructor_id, day_of_week, start_time, duration_minutes, capacity, level) 
                 VALUES (?, ?, ?, ?, ?, ?, 45, 25, 'Intermediate')"
            );
            $scheduleStmt->execute([
                $specialty . ' with ' . $fullName,
                strtolower($specialty),
                $fullName,
                $instructorId,
                $dayOfWeek,
                $startTime
            ]);
        } catch (PDOException $e) {
            // Schedule creation failed, but instructor was created successfully
            // Don't fail the request if schedule creation fails
        }
    }

    jsonResponse(['success' => true, 'message' => 'Instructor added successfully.']);
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
        if (stripos($errorMessage, 'full_name') !== false) {
            $errorMessage = 'An instructor with this name already exists.';
        } elseif (stripos($errorMessage, 'email') !== false) {
            $errorMessage = 'This email is already assigned to another instructor.';
        } else {
            $errorMessage = 'A duplicate value conflict occurred.';
        }
    }
    jsonResponse(['success' => false, 'message' => 'Failed to add instructor. ' . $errorMessage], 500);
}
