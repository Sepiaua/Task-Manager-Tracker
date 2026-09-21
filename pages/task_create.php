<?php
$pageTitle = 'New Task';

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$userId = currentUserId();
$error  = '';


// =========================================
// LOAD CATEGORIES
// =========================================

$catStmt = $pdo->prepare("
    SELECT *
    FROM categories
    WHERE user_id = ?
    ORDER BY name
");

$catStmt->execute([$userId]);

$categories = $catStmt->fetchAll();


// =========================================
// CREATE TASK
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';
    $categoryId  = $_POST['category_id'] ?: null;
    $dueDate     = $_POST['due_date'] ?: null;
    $newCat      = trim($_POST['new_category'] ?? '');


    // Validate title
    if (!$title) {

        $error = 'Task title is required.';

    } else {

        // =====================================
        // CREATE NEW CATEGORY
        // =====================================

        if ($newCat) {

            $stmt = $pdo->prepare("
                INSERT INTO categories (name, user_id)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $newCat,
                $userId
            ]);

            $categoryId = $pdo->lastInsertId();
        }


        // =====================================
        // CREATE TASK
        // =====================================

        $stmt = $pdo->prepare("
            INSERT INTO tasks
            (
                user_id,
                category_id,
                title,
                description,
                priority,
                status,
                due_date
            )
            VALUES (?, ?, ?, ?, ?, 'pending', ?)
        ");

        $stmt->execute([
            $userId,
            $categoryId,
            $title,
            $description,
            $priority,
            $dueDate
        ]);


        // =====================================
        // REDIRECT TO DASHBOARD
        // =====================================

        header(
            'Location: /task_manager/pages/dashboard.php?created=1'
        );

        exit;
    }
}


require_once __DIR__ . '/../includes/header.php';
?>


<!-- =========================================
     PAGE HEADER
========================================= -->

<div class="page-header">

    <div>
        <h1>New task</h1>

        <p>
            Create a new task and keep your work organized.
        </p>
    </div>

    <a
        href="/task_manager/pages/dashboard.php"
        class="btn btn-ghost"
    >
        &larr; Back
    </a>

</div>


<!-- =========================================
     ERROR MESSAGE
========================================= -->

<?php if ($error): ?>

<div class="alert alert-error">
    <?= escape($error) ?>
</div>

<?php endif; ?>


<!-- =========================================
     FORM
========================================= -->

<div class="form-card">

<form method="POST" class="task-form">


    <!-- TASK TITLE -->

    <div class="form-group">

        <label>
            Task title
            <span class="required">*</span>
        </label>

        <input
            type="text"
            name="title"
            placeholder="e.g. Complete assignment 3"
            required
            value="<?= escape($_POST['title'] ?? '') ?>"
        >

    </div>


    <!-- DESCRIPTION -->

    <div class="form-group">

        <label>Description</label>

        <textarea
            name="description"
            rows="4"
            placeholder="Optional details..."
        ><?= escape($_POST['description'] ?? '') ?></textarea>

    </div>


    <!-- PRIORITY + DUE DATE -->

    <div class="form-row">


        <!-- PRIORITY -->

        <div class="form-group">

            <label>Priority</label>

            <select name="priority">

                <option
                    value="low"
                    <?= ($_POST['priority'] ?? '') === 'low'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Low
                </option>

                <option
                    value="medium"
                    <?= ($_POST['priority'] ?? 'medium') === 'medium'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Medium
                </option>

                <option
                    value="high"
                    <?= ($_POST['priority'] ?? '') === 'high'
                        ? 'selected'
                        : ''
                    ?>
                >
                    High
                </option>

            </select>

        </div>


        <!-- DUE DATE -->

        <div class="form-group">

            <label>Due date</label>

            <input
                type="date"
                name="due_date"
                value="<?= escape($_POST['due_date'] ?? '') ?>"
                min="<?= date('Y-m-d') ?>"
            >

        </div>

    </div>


    <!-- CATEGORY -->

    <div class="form-group">

        <label>Category</label>

        <select name="category_id">

            <option value="">
                No category
            </option>

            <?php foreach ($categories as $cat): ?>

                <option
                    value="<?= $cat['id'] ?>"
                    <?= ($_POST['category_id'] ?? '') == $cat['id']
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= escape($cat['name']) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <!-- NEW CATEGORY -->

    <div class="form-group">

        <label>
            Or create a new category
        </label>

        <input
            type="text"
            name="new_category"
            placeholder="e.g. Kuliah, Personal, Project"
            value="<?= escape($_POST['new_category'] ?? '') ?>"
        >

        <small class="form-hint">
            Leave blank to use the category above.
        </small>

    </div>


    <!-- FORM ACTIONS -->

    <div class="form-actions">

        <button
            type="submit"
            class="btn btn-primary"
        >
            Create task
        </button>

        <a
            href="/task_manager/pages/dashboard.php"
            class="btn btn-ghost"
        >
            Cancel
        </a>

    </div>

</form>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>