<?php
// Quick fix for the users table schema
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'gym_booking_system';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$output = [];

// Check current columns
$cols = [];
$result = $pdo->query("SHOW COLUMNS FROM users");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $row['Field'];
}
$output[] = "Current columns: " . implode(', ', $cols);

// Add 'phone' column if missing
if (!in_array('phone', $cols)) {
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL AFTER `password_hash`");
        $output[] = "✅ Added 'phone' column.";
    } catch (PDOException $e) {
        $output[] = "⚠️ phone: " . $e->getMessage();
    }
}

// Add 'status' column if missing
if (!in_array('status', $cols)) {
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `status` ENUM('active','blocked') NOT NULL DEFAULT 'active' AFTER `role`");
        $output[] = "✅ Added 'status' column.";
    } catch (PDOException $e) {
        $output[] = "⚠️ status: " . $e->getMessage();
    }
}

// Add 'avatar_url' column if missing
if (!in_array('avatar_url', $cols)) {
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `avatar_url` VARCHAR(255) DEFAULT NULL");
        $output[] = "✅ Added 'avatar_url' column.";
    } catch (PDOException $e) {
        $output[] = "⚠️ avatar_url: " . $e->getMessage();
    }
}

// Ensure instructor table exists
try {
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS `instructors` (\n            `instructor_id` INT AUTO_INCREMENT PRIMARY KEY,\n            `full_name`     VARCHAR(100) NOT NULL UNIQUE,\n            `specialty`     VARCHAR(100) DEFAULT NULL,\n            `email`         VARCHAR(150) DEFAULT NULL UNIQUE,\n            `phone`         VARCHAR(20) DEFAULT NULL,\n            `bio`           TEXT DEFAULT NULL,\n            `status`        ENUM('active','blocked','removed') NOT NULL DEFAULT 'active',\n            `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n            `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4\n    ");
    $output[] = "✅ Instructor table ready.";
} catch (PDOException $e) {
    $output[] = "⚠️ instructors table: " . $e->getMessage();
}

// Add instructor_id to schedules if missing
try {
    $scheduleCols = [];
    $result = $pdo->query("SHOW COLUMNS FROM schedules");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $scheduleCols[] = $row['Field'];
    }

    if (!in_array('instructor_id', $scheduleCols)) {
        $pdo->exec("ALTER TABLE `schedules` ADD COLUMN `instructor_id` INT DEFAULT NULL AFTER `instructor`");
        $output[] = "✅ Added schedules.instructor_id column.";
    }

    $create = $pdo->query("SHOW CREATE TABLE schedules")->fetch(PDO::FETCH_ASSOC);
    if ($create && strpos($create['Create Table'], 'fk_schedules_instructor') === false) {
        try {
            $pdo->exec("ALTER TABLE `schedules` ADD CONSTRAINT `fk_schedules_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors`(`instructor_id`) ON DELETE SET NULL");
            $output[] = "✅ Added schedules instructor foreign key.";
        } catch (PDOException $e) {
            $output[] = "⚠️ schedule FK: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    $output[] = "⚠️ schedules.instructor_id: " . $e->getMessage();
}

// Modify role to include admin if needed (add 'admin' to enum)
try {
    $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('member','trainer','admin') NOT NULL DEFAULT 'member'");
    $output[] = "✅ Updated 'role' enum to include admin.";
} catch (PDOException $e) {
    $output[] = "⚠️ role modify: " . $e->getMessage();
}

// Now seed admin user if not exists
try {
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute(['admin@gympro.com']);
    if (!$check->fetch()) {
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `users` (`full_name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES (?, ?, ?, ?, 'admin', 'active')");
        $stmt->execute(['Admin User', 'admin@gympro.com', $adminHash, '+1234567890']);
        $output[] = "✅ Admin user seeded: admin@gympro.com / admin123";
    } else {
        // Update existing admin's role
        $pdo->exec("UPDATE users SET role = 'admin', status = 'active' WHERE email = 'admin@gympro.com'");
        $output[] = "✅ Admin user already exists — ensured admin role.";
    }
} catch (PDOException $e) {
    $output[] = "⚠️ Admin user: " . $e->getMessage();
}

// Seed demo member if not exists
try {
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute(['john@gympro.com']);
    if (!$check->fetch()) {
        $memberHash = password_hash('member123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `users` (`full_name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES (?, ?, ?, ?, 'member', 'active')");
        $stmt->execute(['John Doe', 'john@gympro.com', $memberHash, '+9876543210']);
        $output[] = "✅ Demo member seeded: john@gympro.com / member123";
    } else {
        $output[] = "✅ Demo member already exists.";
    }
} catch (PDOException $e) {
    $output[] = "⚠️ Demo member: " . $e->getMessage();
}

// Seed instructor rows and backfill schedule links
try {
    $instructors = [
        ['Sarah Johnson', 'Cardio', 'sarah@gympro.com', '+1111111111', 'High-energy coach focused on endurance and fat loss.'],
        ['Maya Patel', 'Yoga', 'maya@gympro.com', '+2222222222', 'Mind-body instructor specializing in mobility and recovery.'],
        ['Marcus Chen', 'Strength', 'marcus@gympro.com', '+3333333333', 'Strength coach with a focus on progressive overload.'],
        ['Emma Wilson', 'Flexibility', 'emma@gympro.com', '+4444444444', 'Mobility specialist helping members move better.'],
        ['Jake Rivera', 'Combat', 'jake@gympro.com', '+5555555555', 'Combat sports coach with technique-first sessions.'],
    ];

    $insertInstructor = $pdo->prepare("INSERT IGNORE INTO instructors (full_name, specialty, email, phone, bio, status) VALUES (?, ?, ?, ?, ?, 'active')");
    foreach ($instructors as $instructor) {
        $insertInstructor->execute($instructor);
    }

    $link = $pdo->prepare("UPDATE schedules SET instructor_id = (SELECT instructor_id FROM instructors WHERE full_name = ?) WHERE instructor = ?");
    foreach ($instructors as $instructor) {
        $link->execute([$instructor[0], $instructor[0]]);
    }

    $output[] = "✅ Instructors seeded and schedules linked.";
} catch (PDOException $e) {
    $output[] = "⚠️ instructor seed/link: " . $e->getMessage();
}

// Show final schema
$output[] = "<hr><strong>Final users schema:</strong>";
$result = $pdo->query("SHOW COLUMNS FROM users");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $output[] = "  • {$row['Field']} ({$row['Type']})";
}

// Show users
$output[] = "<hr><strong>Users in DB:</strong>";
$users = $pdo->query("SELECT user_id, full_name, email, role, status FROM users")->fetchAll();
foreach ($users as $u) {
    $output[] = "  #{$u['user_id']} — {$u['full_name']} ({$u['email']}) — role:{$u['role']} status:{$u['status']}";
}

echo "<pre style='font-family:monospace;background:#0b0d17;color:#f0f0f8;padding:40px;line-height:1.8'>";
echo implode("\n", $output);
echo "</pre>";
