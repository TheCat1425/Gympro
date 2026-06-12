<?php
// api/migrate.php — Database migration script
// Run this once in your browser: http://localhost:8080/gymprojects/api/migrate.php

header('Content-Type: text/html; charset=utf-8');

$host     = 'localhost';
$user     = 'root';
$password = '';
$charset  = 'utf8mb4';

// Connect WITHOUT selecting a database first
try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

$output = [];
$output[] = "<h1>🏋️ GymPro Database Migration</h1>";

// 1. Create database
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `gym_booking_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $output[] = "✅ Database <code>gym_booking_system</code> created/verified.";
} catch (PDOException $e) {
    $output[] = "❌ Database creation failed: " . $e->getMessage();
    die(implode("<br>", $output));
}

$pdo->exec("USE `gym_booking_system`");

// 2. Users table — drop old incompatible one and recreate
try {
    // Check if existing users table has password_hash column
    $cols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    $hasPasswordHash = in_array('password_hash', $cols);
    
    if (!$hasPasswordHash) {
        // Old schema — drop foreign keys first, then recreate
        try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 0"); } catch(Exception $e) {}
        $pdo->exec("DROP TABLE IF EXISTS `bookings`");
        $pdo->exec("DROP TABLE IF EXISTS `orders`");
        $pdo->exec("DROP TABLE IF EXISTS `order_items`");
        $pdo->exec("DROP TABLE IF EXISTS `users`");
        try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch(Exception $e) {}
        $output[] = "🔄 Dropped old users table (incompatible schema).";
    }
} catch (PDOException $e) {
    // Table doesn't exist yet — fine
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `user_id`       INT AUTO_INCREMENT PRIMARY KEY,
            `full_name`     VARCHAR(100) NOT NULL,
            `email`         VARCHAR(150) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `phone`         VARCHAR(20) DEFAULT NULL,
            `role`          ENUM('member','admin') NOT NULL DEFAULT 'member',
            `status`        ENUM('active','blocked') NOT NULL DEFAULT 'active',
            `avatar_url`    VARCHAR(255) DEFAULT NULL,
            `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $output[] = "✅ Table <code>users</code> created/verified.";
} catch (PDOException $e) {
    $output[] = "⚠️ Users table: " . $e->getMessage();
}

// 3. Schedules table (keep existing)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schedules` (
            `schedule_id`      INT AUTO_INCREMENT PRIMARY KEY,
            `class_name`       VARCHAR(100) NOT NULL,
            `category`         VARCHAR(50) NOT NULL,
            `instructor`       VARCHAR(100) NOT NULL,
            `day_of_week`      VARCHAR(20) NOT NULL,
            `start_time`       TIME NOT NULL,
            `duration_minutes` INT NOT NULL DEFAULT 60,
            `capacity`         INT NOT NULL DEFAULT 20,
            `level`            VARCHAR(30) NOT NULL DEFAULT 'All Levels',
            `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $output[] = "✅ Table <code>schedules</code> created/verified.";
} catch (PDOException $e) {
    $output[] = "⚠️ Schedules: " . $e->getMessage();
}

// 4. Bookings table (keep existing)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `bookings` (
            `booking_id`   INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`      INT NOT NULL,
            `schedule_id`  INT NOT NULL,
            `booking_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status`       ENUM('confirmed','cancelled','pending') NOT NULL DEFAULT 'confirmed',
            UNIQUE KEY `unique_booking` (`user_id`, `schedule_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
            FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`schedule_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $output[] = "✅ Table <code>bookings</code> created/verified.";
} catch (PDOException $e) {
    $output[] = "⚠️ Bookings: " . $e->getMessage();
}

// 5. Products table (NEW)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `products` (
            `product_id`  INT AUTO_INCREMENT PRIMARY KEY,
            `name`        VARCHAR(150) NOT NULL,
            `description` TEXT,
            `category`    ENUM('supplements','food','gear','apparel','accessories') NOT NULL DEFAULT 'supplements',
            `price`       DECIMAL(10,2) NOT NULL,
            `stock`       INT NOT NULL DEFAULT 0,
            `image_url`   VARCHAR(255) DEFAULT NULL,
            `status`      ENUM('available','sold_out','discontinued') NOT NULL DEFAULT 'available',
            `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $output[] = "✅ Table <code>products</code> created.";
} catch (PDOException $e) {
    $output[] = "⚠️ Products: " . $e->getMessage();
}

// 6. Orders table (NEW)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `order_id`     INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`      INT NOT NULL,
            `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `status`       ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
            `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $output[] = "✅ Table <code>orders</code> created.";
} catch (PDOException $e) {
    $output[] = "⚠️ Orders: " . $e->getMessage();
}

    // 4. Instructors table
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `instructors` (
                `instructor_id` INT AUTO_INCREMENT PRIMARY KEY,
                `full_name`     VARCHAR(100) NOT NULL UNIQUE,
                `specialty`     VARCHAR(100) DEFAULT NULL,
                `email`         VARCHAR(150) DEFAULT NULL UNIQUE,
                `phone`         VARCHAR(20) DEFAULT NULL,
                `bio`           TEXT DEFAULT NULL,
                `status`        ENUM('active','blocked','removed') NOT NULL DEFAULT 'active',
                `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $output[] = "✅ Table <code>instructors</code> created/verified.";
    } catch (PDOException $e) {
        $output[] = "⚠️ Instructors: " . $e->getMessage();
    }

    try {
        $scheduleCols = $pdo->query("SHOW COLUMNS FROM `schedules`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('instructor_id', $scheduleCols, true)) {
            $pdo->exec("ALTER TABLE `schedules` ADD COLUMN `instructor_id` INT DEFAULT NULL AFTER `instructor`");
            $output[] = "✅ Added `instructor_id` to schedules.";
        }

        $fkCheck = $pdo->query("SHOW CREATE TABLE `schedules`")->fetch(PDO::FETCH_ASSOC);
        if ($fkCheck && strpos($fkCheck['Create Table'], 'fk_schedules_instructor') === false) {
            try {
                $pdo->exec("ALTER TABLE `schedules` ADD CONSTRAINT `fk_schedules_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors`(`instructor_id`) ON DELETE SET NULL");
                $output[] = "✅ Added schedule instructor foreign key.";
            } catch (PDOException $e) {
                $output[] = "⚠️ schedule FK: " . $e->getMessage();
            }
        }
    } catch (PDOException $e) {
        $output[] = "⚠️ Schedule instructor link: " . $e->getMessage();
    }

    // 5. Bookings table (keep existing)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `order_items` (
            `item_id`    INT AUTO_INCREMENT PRIMARY KEY,
            `order_id`   INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity`   INT NOT NULL DEFAULT 1,
            `unit_price` DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
            FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $output[] = "✅ Table <code>order_items</code> created.";
} catch (PDOException $e) {
    $output[] = "⚠️ Order items: " . $e->getMessage();
}

// =============================================
    // 6. Products table (NEW)
// =============================================
$output[] = "<h2>📦 Seeding Data...</h2>";

// Seed admin user (password: admin123)
try {
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (`full_name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES (?, ?, ?, ?, 'admin', 'active')");
    $stmt->execute(['Admin User', 'admin@gympro.com', $adminHash, '+1234567890']);
    $output[] = "✅ Admin user seeded: <code>admin@gympro.com</code> / <code>admin123</code>";
} catch (PDOException $e) {
    $output[] = "⚠️ Admin user: " . $e->getMessage();
}

// Seed demo member (password: member123)
try {
    $memberHash = password_hash('member123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (`full_name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES (?, ?, ?, ?, 'member', 'active')");
    $stmt->execute(['John Doe', 'john@gympro.com', $memberHash, '+9876543210']);
    $output[] = "✅ Demo member seeded: <code>john@gympro.com</code> / <code>member123</code>";
} catch (PDOException $e) {
    // 7. Orders table (NEW)
}

// Seed schedules (if empty)
try {
    $count = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
    if ($count == 0) {
        $schedules = [
            ['Power HIIT',        'Cardio',      'Sarah Johnson',   'Monday',    '07:00:00', 45, 25, 'Intermediate'],
            ['Vinyasa Flow',      'Yoga',        'Maya Patel',      'Monday',    '09:00:00', 60, 20, 'All Levels'],
            ['Beast Mode Lifting','Strength',    'Marcus Chen',     'Tuesday',   '06:30:00', 50, 15, 'Advanced'],
            ['Core & Stretch',    'Flexibility', 'Emma Wilson',     'Tuesday',   '10:00:00', 40, 30, 'Beginner'],
            ['Kickboxing Fusion', 'Combat',      'Jake Rivera',     'Wednesday', '18:00:00', 55, 20, 'Intermediate'],
            ['Zen Meditation',    'Yoga',        'Maya Patel',      'Thursday',  '08:00:00', 45, 25, 'Beginner'],
            ['CrossFit WOD',      'Strength',    'Marcus Chen',     'Thursday',  '17:00:00', 60, 18, 'Advanced'],
            ['Spin Cycle',        'Cardio',      'Sarah Johnson',   'Friday',    '07:00:00', 40, 22, 'All Levels'],
            ['Muay Thai Basics',  'Combat',      'Jake Rivera',     'Friday',    '19:00:00', 60, 16, 'Beginner'],
    // 8. Order items table (NEW)
        $stmt = $pdo->prepare("INSERT INTO `schedules` (`class_name`, `category`, `instructor`, `day_of_week`, `start_time`, `duration_minutes`, `capacity`, `level`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($schedules as $s) {
            $stmt->execute($s);
        }
        $output[] = "✅ Seeded " . count($schedules) . " class schedules.";
    } else {
        $output[] = "ℹ️ Schedules already have data ($count rows) — skipped.";
    }
} catch (PDOException $e) {
    $output[] = "⚠️ Schedules seeding: " . $e->getMessage();
}

try {
    $link = $pdo->prepare("UPDATE schedules SET instructor_id = (SELECT instructor_id FROM instructors WHERE full_name = ?) WHERE instructor = ?");
    foreach (['Sarah Johnson', 'Maya Patel', 'Marcus Chen', 'Emma Wilson', 'Jake Rivera'] as $name) {
        $link->execute([$name, $name]);
    }
    $output[] = "✅ Schedules linked to instructors.";
} catch (PDOException $e) {
    $output[] = "⚠️ Schedule link backfill: " . $e->getMessage();
}

// Seed products (if empty)
try {
    $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($count == 0) {
        $products = [
            ['Whey Protein Isolate',    'Premium 100% whey protein, 30g per scoop. Chocolate flavour.', 'supplements', 49.99, 50, '🥤'],
            ['BCAA Energy Drink',       'Branch chain amino acids with natural caffeine boost.',        'supplements', 29.99, 80, '⚡'],
            ['Creatine Monohydrate',    'Pure micronized creatine for strength and power.',             'supplements', 24.99, 60, '💊'],
            ['Protein Bar Box (12pk)',   'High protein, low sugar snack bars. Mixed flavours.',          'food',        34.99, 40, '🍫'],
            ['Organic Energy Balls',     'Date, almond & cocoa energy bites. 10 pack.',                 'food',        18.99, 100, '🟤'],
            ['Meal Prep Containers',     'BPA-free 3-compartment containers. Set of 10.',               'gear',        22.99, 35, '📦'],
            ['Resistance Bands Set',     '5-band set with varying resistance levels.',                  'gear',        19.99, 45, '🏋️'],
            ['Lifting Gloves Pro',       'Padded leather gloves with wrist wraps.',                     'gear',        32.99, 25, '🧤'],
            ['GymPro Tank Top',          'Moisture-wicking performance tank. Multiple sizes.',          'apparel',     27.99, 60, '👕'],

    // Seed instructor profiles and link existing schedules
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

        $output[] = "✅ Instructor profiles seeded and schedules linked.";
    } catch (PDOException $e) {
        $output[] = "⚠️ instructor seed/link: " . $e->getMessage();
    }
            ['Compression Leggings',     'High-waist athletic leggings with pocket.',                   'apparel',     39.99, 40, '🩳'],
            ['Shaker Bottle Pro',        '800ml leak-proof shaker with mixing ball.',                   'accessories', 14.99, 90, '🧴'],
            ['Gym Bag Duffle',           'Large capacity waterproof gym duffle bag.',                   'accessories', 44.99, 30, '🎒'],
        ];
        $stmt = $pdo->prepare("INSERT INTO `products` (`name`, `description`, `category`, `price`, `stock`, `image_url`) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmt->execute($p);
        }
        $output[] = "✅ Seeded " . count($products) . " products.";
    } else {
        $output[] = "ℹ️ Products already have data ($count rows) — skipped.";
    }
} catch (PDOException $e) {
    $output[] = "⚠️ Products seeding: " . $e->getMessage();
}

// Print results
echo "<!DOCTYPE html><html><head><title>GymPro Migration</title><style>
body{font-family:Inter,system-ui,sans-serif;background:#0b0d17;color:#f0f0f8;padding:40px;max-width:800px;margin:0 auto}
h1{background:linear-gradient(135deg,#6c5ce7,#00cec9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:20px}
h2{color:#a29bfe;margin-top:30px;margin-bottom:15px}
code{background:rgba(108,92,231,0.2);padding:2px 8px;border-radius:4px;font-size:0.9em}
</style></head><body>";
echo implode("<br><br>", $output);
echo "<br><br><hr><p style='color:#00cec9;font-weight:600'>✨ Migration complete! <a href='../login.html' style='color:#a29bfe'>Go to Login →</a></p>";
echo "</body></html>";
