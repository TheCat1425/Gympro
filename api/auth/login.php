<?php
// api/auth/login.php — Authenticate a user
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Email and password are required.']);
}

try {
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, phone, role, status, created_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
    }

    if ($user['status'] === 'blocked') {
        jsonResponse(['success' => false, 'message' => 'Your account has been blocked. Please contact support.']);
    }

    if (!password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
    }

    // Set session
    $_SESSION['user_id']   = (int) $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];

    jsonResponse([
        'success' => true,
        'message' => 'Login successful!',
        'user' => [
            'user_id'   => (int) $user['user_id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
            'phone'     => $user['phone'],
            'created_at'=> $user['created_at'],
        ]
    ]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()], 500);
}
