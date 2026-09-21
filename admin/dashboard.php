<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';

// Global stats
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM users)                                AS total_users,
        (SELECT COUNT(*) FROM tasks)                               AS total_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status = 'completed')    AS completed,
        (SELECT COUNT(*) FROM tasks WHERE status = 'in_progress')  AS in_progress,
        (SELECT COUNT(*) FROM tasks WHERE status = 'pending')      AS pending,
        (SELECT COUNT(*) FROM tasks WHERE status != 'completed' AND due_date < CURDATE()) AS overdue
")->fetch();

// Users with task counts
$users = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, u.created_at,
        COUNT(t.id) AS task_count,
        SUM(t.status = 'completed') AS completed
    FROM users u
    LEFT JOIN tasks t ON t.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

// Recent tasks (all users)
$recentTasks = $pdo->query("
    SELECT t.*, u.name AS user_name
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Admin dashboard</h1>
    <a href="/admin/users.php" class="btn btn-secondary">Manage users</a>
</div>

<!-- Global stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-num"><?= $stats['total_users'] ?></div>
        <div class="stat-label">Total users</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $stats['total_tasks'] ?></div>
        <div class="stat-label">Total tasks</div>
    </div>
    <div class="stat-card stat-progress">
        <div class="stat-num"><?= $stats['in_progress'] ?></div>
        <div class="stat-label">In progress</div>
    </div>
    <div class="stat-card stat-done">
        <div class="stat-num"><?= $stats['completed'] ?></div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card stat-overdue">
        <div class="stat-num"><?= $stats['overdue'] ?></div>
        <div class="stat-label">Overdue</div>
    </div>
</div>

<!-- Users table -->
<div class="section-header">
    <h2>All users</h2>
</div>
<div class="table-wrap">
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Tasks</th>
            <th>Completed</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= $u['id'] ?></td>
        <td><?= escape($u['name']) ?></td>
        <td><?= escape($u['email']) ?></td>
        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-high' : 'badge-category' ?>"><?= $u['role'] ?></span></td>
        <td><?= $u['task_count'] ?></td>
        <td><?= $u['completed'] ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td>
            <a href="/admin/users.php?view=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">View tasks</a>
            <?php if ($u['role'] !== 'admin'): ?>
            <form method="POST" action="/admin/users.php" style="display:inline">
                <input type="hidden" name="action" value="make_admin">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-ghost" onclick="return confirm('Make this user admin?')">Make admin</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Recent tasks -->
<div class="section-header" style="margin-top: 2rem;">
    <h2>Recent tasks</h2>
    <a href="/admin/tasks.php" class="btn btn-ghost">View all</a>
</div>
<div class="table-wrap">
<table class="data-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>User</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Due date</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($recentTasks as $t): ?>
    <tr>
        <td><?= escape($t['title']) ?></td>
        <td><?= escape($t['user_name']) ?></td>
        <td><span class="badge badge-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
        <td><span class="badge badge-status-<?= $t['status'] ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
        <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '-' ?></td>
        <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>