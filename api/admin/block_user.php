<?php
// api/admin/block_user.php — Block or unblock a user
header('Content-Type: application/json');
require_once __DIR__ . '/../../db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$action = trim($_POST['action'] ?? ''); // 'block' or 'unblock'

if (!$userId || !in_array($action, ['block', 'unblock'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid user ID or action.']);
}

try {
    // Don't allow blocking yourself
    if ($userId === $_SESSION['user_id']) {
        jsonResponse(['success' => false, 'message' => 'You cannot block yourself.']);
    }

    $newStatus = $action === 'block' ? 'blocked' : 'active';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->execute([$newStatus, $userId]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(['success' => true, 'message' => "User {$action}ed successfully."]);
    } else {
        jsonResponse(['success' => false, 'message' => 'User not found.']);
    }
} catch (\PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update user status.'], 500);
}
