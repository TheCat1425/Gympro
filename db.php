<?php
// db.php — Database connection + Auth helpers

// Start session (must be first thing)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Database configuration
// Load a simple local .env file if present (KEY=VALUE lines)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($k !== '') {
            if (getenv($k) === false) putenv("$k=$v");
            if (!isset($_ENV[$k])) $_ENV[$k] = $v;
        }
    }
}

// Read configuration from environment with sensible defaults
$host     = getenv('DB_HOST') ?: 'localhost';
$db       = getenv('DB_NAME') ?: 'gym_booking_system';
$user     = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$charset  = getenv('DB_CHARSET') ?: 'utf8mb4';

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

ensureInstructorSchema($pdo);

function ensureInstructorSchema(PDO $pdo) {
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'instructors'")->fetchColumn();
        if (!$tableExists) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `instructors` (
                    `instructor_id` INT AUTO_INCREMENT PRIMARY KEY,
                    `full_name` VARCHAR(100) NOT NULL UNIQUE,
                    `specialty` VARCHAR(100) DEFAULT NULL,
                    `email` VARCHAR(150) DEFAULT NULL UNIQUE,
                    `phone` VARCHAR(20) DEFAULT NULL,
                    `bio` TEXT DEFAULT NULL,
                    `status` ENUM('active','blocked','removed') NOT NULL DEFAULT 'active',
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }
    } catch (\PDOException $e) {
        // Ignore schema create failures here; endpoints will return their own errors.
    }

    try {
        $scheduleCols = $pdo->query("SHOW COLUMNS FROM `schedules`")->fetchAll(PDO::FETCH_COLUMN);
        if ($scheduleCols && !in_array('instructor_id', $scheduleCols, true)) {
            $pdo->exec("ALTER TABLE `schedules` ADD COLUMN `instructor_id` INT DEFAULT NULL AFTER `instructor`");
        }

        $create = $pdo->query("SHOW CREATE TABLE `schedules`")->fetch(PDO::FETCH_ASSOC);
        if ($create && strpos($create['Create Table'], 'fk_schedules_instructor') === false) {
            $pdo->exec("ALTER TABLE `schedules` ADD CONSTRAINT `fk_schedules_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors`(`instructor_id`) ON DELETE SET NULL");
        }
    } catch (\PDOException $e) {
        // Ignore if schedules table does not exist or constraint cannot be added.
    }
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