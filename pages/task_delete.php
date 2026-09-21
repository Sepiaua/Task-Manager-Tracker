<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $taskId = (int)($_POST['id'] ?? 0);
    $userId = currentUserId();

    $stmt = $pdo->prepare("
        DELETE FROM tasks
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->execute([
        $taskId,
        $userId
    ]);
}

header('Location: /task_manager/pages/dashboard.php?deleted=1');
exit;