<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';

$success = '';
$error   = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'make_admin' && $uid !== currentUserId()) {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$uid]);
        $success = 'User promoted to admin.';
    } elseif ($action === 'remove_admin' && $uid !== currentUserId()) {
        $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$uid]);
        $success = 'Admin role removed.';
    } elseif ($action === 'delete_user' && $uid !== currentUserId()) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        $success = 'User deleted.';
        header('Location: /admin/users.php?msg=deleted');
        exit;
    }
}

// View specific user's tasks
$viewUserId = (int)($_GET['view'] ?? 0);
$viewUser   = null;
$userTasks  = [];

if ($viewUserId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$viewUserId]);
    $viewUser = $stmt->fetch();

    if ($viewUser) {
        $stmt = $pdo->prepare("
            SELECT t.*, c.name AS category_name
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$viewUserId]);
        $userTasks = $stmt->fetchAll();
    }
}

// All users
$users = $pdo->query("
    SELECT u.*, COUNT(t.id) AS task_count
    FROM users u
    LEFT JOIN tasks t ON t.user_id = u.id
    GROUP BY u.id ORDER BY u.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Manage users</h1>
    <a href="/admin/dashboard.php" class="btn btn-ghost">&larr; Admin dashboard</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= escape($success) ?></div><?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?><div class="alert alert-success">User deleted.</div><?php endif; ?>

<?php if ($viewUser): ?>
<!-- User tasks panel -->
<div class="form-card" style="margin-bottom:2rem;">
    <div class="section-header">
        <h2>Tasks for <?= escape($viewUser['name']) ?></h2>
        <a href="/admin/users.php" class="btn btn-ghost">Back to all users</a>
    </div>
    <?php if (empty($userTasks)): ?>
    <p class="text-muted">This user has no tasks.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Title</th><th>Priority</th><th>Status</th><th>Category</th><th>Due</th></tr></thead>
        <tbody>
        <?php foreach ($userTasks as $t): ?>
        <tr>
            <td><?= escape($t['title']) ?></td>
            <td><span class="badge badge-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
            <td><span class="badge badge-status-<?= $t['status'] ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
            <td><?= $t['category_name'] ? escape($t['category_name']) : '-' ?></td>
            <td><?= $t['due_date'] ? date('d M Y', strtotime($t['due_date'])) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- All users table -->
<div class="table-wrap">
<table class="data-table">
    <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Tasks</th><th>Joined</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= $u['id'] ?></td>
        <td><?= escape($u['name']) ?></td>
        <td><?= escape($u['email']) ?></td>
        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-high' : 'badge-category' ?>"><?= $u['role'] ?></span></td>
        <td><?= $u['task_count'] ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td style="display:flex; gap:6px; flex-wrap:wrap;">
            <a href="?view=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">Tasks</a>
            <?php if ($u['id'] !== currentUserId()): ?>
            <form method="POST" style="display:inline">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <?php if ($u['role'] === 'admin'): ?>
                <input type="hidden" name="action" value="remove_admin">
                <button type="submit" class="btn btn-sm btn-ghost" onclick="return confirm('Remove admin role?')">Remove admin</button>
                <?php else: ?>
                <input type="hidden" name="action" value="make_admin">
                <button type="submit" class="btn btn-sm btn-ghost" onclick="return confirm('Make admin?')">Make admin</button>
                <?php endif; ?>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user and ALL their tasks?')">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
            <?php else: ?>
            <span class="text-muted" style="font-size:12px;">You</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>