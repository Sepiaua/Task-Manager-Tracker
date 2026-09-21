<?php
$pageTitle = 'Edit Task';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$userId = currentUserId();
$taskId = (int)($_GET['id'] ?? 0);
$error  = '';

// Load task — only owner can edit
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: /task_manager/pages/dashboard.php');
    exit;
}

// Load categories
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name");
$catStmt->execute([$userId]);
$categories = $catStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';
    $status      = $_POST['status'] ?? 'pending';
    $categoryId  = $_POST['category_id'] ?: null;
    $dueDate     = $_POST['due_date'] ?: null;
    $newCat      = trim($_POST['new_category'] ?? '');

    if (!$title) {
        $error = 'Task title is required.';
    } else {
        if ($newCat) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
            $stmt->execute([$newCat, $userId]);
            $categoryId = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("
            UPDATE tasks
            SET title = ?, description = ?, priority = ?, status = ?, category_id = ?, due_date = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$title, $description, $priority, $status, $categoryId, $dueDate, $taskId, $userId]);
        header('Location: /task_manager/pages/dashboard.php?updated=1');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Edit task</h1>
    <a href="/task_manager/pages/dashboard.php" class="btn btn-ghost">&larr; Back</a>
</div>

<?php if ($error): ?>
<div class="alert alert-error"><?= escape($error) ?></div>
<?php endif; ?>

<div class="form-card">
<form method="POST" class="task-form">
    <div class="form-group">
        <label>Task title <span class="required">*</span></label>
        <input type="text" name="title" required value="<?= escape($_POST['title'] ?? $task['title']) ?>">
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4"><?= escape($_POST['description'] ?? $task['description']) ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Priority</label>
            <select name="priority">
                <?php foreach (['low', 'medium', 'high'] as $p): ?>
                <option value="<?= $p ?>" <?= ($_POST['priority'] ?? $task['priority']) === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="pending"     <?= ($_POST['status'] ?? $task['status']) === 'pending'     ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= ($_POST['status'] ?? $task['status']) === 'in_progress' ? 'selected' : '' ?>>In progress</option>
                <option value="completed"   <?= ($_POST['status'] ?? $task['status']) === 'completed'   ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>

        <div class="form-group">
            <label>Due date</label>
            <input type="date" name="due_date" value="<?= escape($_POST['due_date'] ?? $task['due_date']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label>Category</label>
        <select name="category_id">
            <option value="">No category</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? $task['category_id']) == $cat['id'] ? 'selected' : '' ?>><?= escape($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Or create a new category</label>
        <input type="text" name="new_category" placeholder="e.g. Kuliah, Personal" value="<?= escape($_POST['new_category'] ?? '') ?>">
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="/task_manager/pages/dashboard.php" class="btn btn-ghost">Cancel</a>
    </div>
</form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>