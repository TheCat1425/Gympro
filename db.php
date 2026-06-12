<?php
// db.php — Database connection + Auth helpers

// Start session (must be first thing)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Database configuration
$host     = 'localhost';
$db       = 'gym_booking_system';
$user     = 'root';
$password = '';
$charset  = 'utf8mb4';

// 2. DSN
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// 3. PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// =============================================
//  AUTH HELPER FUNCTIONS
// =============================================

/**
 * Check if a user is logged in. Returns user data or sends 401.
 */
function requireAuth() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required. Please login.']);
        exit;
    }
    return [
        'user_id'   => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'member',
    ];
}

/**
 * Check if the logged-in user is an admin. Returns user data or sends 403.
 */
function requireAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required.']);
        exit;
    }
    return $user;
}

/**
 * Send a JSON response and exit.
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}