<?php
// api/auth/register.php — Register a new user
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validation
if (empty($fullName) || strlen($fullName) < 2) {
    jsonResponse(['success' => false, 'message' => 'Full name is required (min 2 characters).']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.']);
}

if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
}

if ($password !== $confirmPassword) {
    jsonResponse(['success' => false, 'message' => 'Passwords do not match.']);
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'An account with this email already exists.']);
    }

    // Hash password and insert
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, phone, role, status) VALUES (?, ?, ?, ?, 'member', 'active')");
    $stmt->execute([$fullName, $email, $hash, $phone ?: null]);

    $userId = (int) $pdo->lastInsertId();

    // Set session
    $_SESSION['user_id']   = $userId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email']     = $email;
    $_SESSION['role']      = 'member';

    jsonResponse([
        'success' => true,
        'message' => 'Registration successful! Welcome to GymPro.',
        'user' => [
            'user_id'   => $userId,
            'full_name' => $fullName,
            'email'     => $email,
            'role'      => 'member',
        ]
    ]);

} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
}
