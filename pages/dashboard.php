<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

$userId = currentUserId();

// Filters
$filterStatus   = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$search         = trim($_GET['search'] ?? '');

// Build query
$where  = ['t.user_id = ?'];
$params = [$userId];

if ($filterStatus)   { $where[] = 't.status = ?';      $params[] = $filterStatus; }
if ($filterPriority) { $where[] = 't.priority = ?';    $params[] = $filterPriority; }
if ($filterCategory) { $where[] = 't.category_id = ?'; $params[] = $filterCategory; }
if ($search)         { $where[] = 't.title LIKE ?';    $params[] = "%$search%"; }

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT t.*, c.name AS category_name
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE $whereSQL
    ORDER BY
        FIELD(t.status, 'in_progress', 'pending', 'completed'),
        FIELD(t.priority, 'high', 'medium', 'low'),
        t.due_date ASC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Stats
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'in_progress') AS in_progress,
        SUM(status = 'completed') AS completed,
        SUM(status != 'completed' AND due_date < CURDATE()) AS overdue
    FROM tasks WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

// Categories for filter
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name");
$catStmt->execute([$userId]);
$categories = $catStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>My tasks</h1>
        <p>Manage and track your tasks in one place.</p>
    </div>

    <a href="/task_manager/pages/task_create.php"
       class="btn btn-primary">
        + New task
    </a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-num"><?= $stats['total'] ?></div>
        <div class="stat-label">Total</div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-num"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card stat-progress">
        <div class="stat-num"><?= $stats['in_progress'] ?></div>
        <div class="stat-label">In progress</div>
    </div>
    <div class="stat-card stat-done">
        <div class="stat-num"><?= $stats['completed'] ?></div>
        <div class="stat-label">Completed</div>
    </div>
    <?php if ($stats['overdue'] > 0): ?>
    <div class="stat-card stat-overdue">
        <div class="stat-num"><?= $stats['overdue'] ?></div>
        <div class="stat-label">Overdue</div>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search tasks..." value="<?= escape($search) ?>">
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
    <select name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= escape($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="/task_manager/pages/dashboard.php" class="btn btn-ghost">Clear</a>
</form>

<!-- Task list -->
<?php if (empty($tasks)): ?>
<div class="empty-state">
    <p>No tasks found.</p>
    <a href="/task_manager/pages/task_create.php" class="btn btn-primary">Create your first task</a>
</div>
<?php else: ?>
<div class="task-list">
    <?php foreach ($tasks as $task):
        $isOverdue = $task['status'] !== 'completed' && $task['due_date'] && $task['due_date'] < date('Y-m-d');
    ?>
    <div class="task-card <?= $task['status'] === 'completed' ? 'task-done' : '' ?> <?= $isOverdue ? 'task-overdue' : '' ?>">
        <div class="task-main">
            <div class="task-title"><?= escape($task['title']) ?></div>
            <?php if ($task['description']): ?>
            <div class="task-desc"><?= escape(mb_strimwidth($task['description'], 0, 120, '...')) ?></div>
            <?php endif; ?>
            <div class="task-meta">
                <span class="badge badge-<?= $task['priority'] ?>"><?= $task['priority'] ?></span>
                <span class="badge badge-status-<?= $task['status'] ?>"><?= str_replace('_', ' ', $task['status']) ?></span>
                <?php if ($task['category_name']): ?>
                <span class="badge badge-category"><?= escape($task['category_name']) ?></span>
                <?php endif; ?>
                <?php if ($task['due_date']): ?>
                <span class="task-due <?= $isOverdue ? 'overdue-text' : '' ?>">
                    Due: <?= date('d M Y', strtotime($task['due_date'])) ?>
                    <?= $isOverdue ? '(overdue)' : '' ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="task-actions">
            <form method="POST" action="/task_manager/pages/task_status.php" style="display:inline">
                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                <select name="status" onchange="this.form.submit()" class="status-select">
                    <option value="pending"     <?= $task['status'] === 'pending'     ? 'selected' : '' ?>>Pending</option>
                    <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option>
                    <option value="completed"   <?= $task['status'] === 'completed'   ? 'selected' : '' ?>>Completed</option>
                </select>
            </form>
            <a href="/task_manager/pages/task_edit.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
            <form method="POST" action="/task_manager/pages/task_delete.php" style="display:inline" onsubmit="return confirm('Delete this task?')">
                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>