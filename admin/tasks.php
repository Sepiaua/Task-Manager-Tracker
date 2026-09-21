<?php
$pageTitle = 'All Tasks';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';

// Delete task (admin can delete any)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $tid = (int)($_POST['task_id'] ?? 0);
    $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$tid]);
    header('Location: /admin/tasks.php?deleted=1');
    exit;
}

// Filters
$filterStatus   = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$search         = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterStatus)   { $where[] = 't.status = ?';    $params[] = $filterStatus; }
if ($filterPriority) { $where[] = 't.priority = ?';  $params[] = $filterPriority; }
if ($search)         { $where[] = 't.title LIKE ?';  $params[] = "%$search%"; }

$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT t.*, u.name AS user_name, c.name AS category_name
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE $whereSQL
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>All tasks</h1>
    <a href="/admin/dashboard.php" class="btn btn-ghost">&larr; Dashboard</a>
</div>

<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Task deleted.</div><?php endif; ?>

<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search title..." value="<?= escape($search) ?>">
    <select name="status">
        <option value="">All statuses</option>
        <option value="pending"     <?= $filterStatus === 'pending'     ? 'selected' : '' ?>>Pending</option>
        <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>In progress</option>
        <option value="completed"   <?= $filterStatus === 'completed'   ? 'selected' : '' ?>>Completed</option>
    </select>
    <select name="priority">
        <option value="">All priorities</option>
        <option value="high"   <?= $filterPriority === 'high'   ? 'selected' : '' ?>>High</option>
        <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Medium</option>
        <option value="low"    <?= $filterPriority === 'low'    ? 'selected' : '' ?>>Low</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="/admin/tasks.php" class="btn btn-ghost">Clear</a>
</form>

<div class="table-wrap">
<table class="data-table">
    <thead>
        <tr><th>Title</th><th>User</th><th>Priority</th><th>Status</th><th>Category</th><th>Due</th><th>Created</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php if (empty($tasks)): ?>
    <tr><td colspan="8" style="text-align:center;color:var(--text-muted)">No tasks found.</td></tr>
    <?php endif; ?>
    <?php foreach ($tasks as $t): ?>
    <tr>
        <td><?= escape($t['title']) ?></td>
        <td><?= escape($t['user_name']) ?></td>
        <td><span class="badge badge-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
        <td><span class="badge badge-status-<?= $t['status'] ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
        <td><?= $t['category_name'] ? escape($t['category_name']) : '-' ?></td>
        <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '-' ?></td>
        <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
        <td>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this task?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>