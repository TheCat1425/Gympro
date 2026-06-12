<?php
// api/admin/get_all_instructors.php — Get all instructors (admin)
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

try {
    $sql = "
        SELECT
            i.instructor_id as id,
            i.full_name as fullName,
            i.specialty,
            i.email,
            i.phone,
            i.bio,
            i.status,
            i.created_at as createdAt,
            COUNT(DISTINCT s.schedule_id) as totalClasses,
            COUNT(DISTINCT CASE WHEN b.status = 'confirmed' THEN b.booking_id END) as totalBookings
        FROM instructors i
        LEFT JOIN schedules s ON s.instructor_id = i.instructor_id
        LEFT JOIN bookings b ON b.schedule_id = s.schedule_id
        GROUP BY i.instructor_id, i.full_name, i.specialty, i.email, i.phone, i.bio, i.status, i.created_at
        ORDER BY i.created_at DESC
    ";

    $instructors = $pdo->query($sql)->fetchAll();

    foreach ($instructors as &$instructor) {
        $instructor['id'] = (int)$instructor['id'];
        $instructor['totalClasses'] = (int)$instructor['totalClasses'];
        $instructor['totalBookings'] = (int)$instructor['totalBookings'];
    }

    jsonResponse(['success' => true, 'instructors' => $instructors]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to fetch instructors.'], 500);
}
