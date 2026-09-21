<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $userId = currentUserId();
    $allowed = ['pending', 'in_progress', 'completed'];

    if (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $taskId, $userId]);
    }
}

header('Location: /task_manager/pages/dashboard.php');
exit;