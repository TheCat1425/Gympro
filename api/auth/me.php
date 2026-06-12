<?php
// api/auth/me.php — Get current logged-in user
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'logged_in' => false, 'message' => 'Not logged in.']);
}

try {
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, phone, role, status, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // Session is stale — destroy it
        session_destroy();
        jsonResponse(['success' => false, 'logged_in' => false, 'message' => 'User not found.']);
    }

    if ($user['status'] === 'blocked') {
        session_destroy();
        jsonResponse(['success' => false, 'logged_in' => false, 'message' => 'Your account has been blocked.']);
    }

    jsonResponse([
        'success'   => true,
        'logged_in' => true,
        'user' => [
            'user_id'    => (int) $user['user_id'],
            'full_name'  => $user['full_name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'],
            'role'       => $user['role'],
            'created_at' => $user['created_at'],
        ]
    ]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
