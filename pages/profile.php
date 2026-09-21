<?php
$pageTitle = 'Profile';

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$userId  = currentUserId();
$error   = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Load user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die('User account not found.');
}


/*
|--------------------------------------------------------------------------
| Load categories
|--------------------------------------------------------------------------
*/

$catStmt = $pdo->prepare("
    SELECT
        c.*,
        COUNT(t.id) AS task_count
    FROM categories c
    LEFT JOIN tasks t ON t.category_id = c.id
    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.name
");

$catStmt->execute([$userId]);
$categories = $catStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Update profile
    |--------------------------------------------------------------------------
    */

    if ($action === 'update_profile') {

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!$name || !$email) {

            $error = 'Name and email are required.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = 'Enter a valid email address.';

        } else {

            $check = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
            ");

            $check->execute([$email, $userId]);

            if ($check->fetch()) {

                $error = 'That email is already in use.';

            } else {

                $update = $pdo->prepare("
                    UPDATE users
                    SET name = ?, email = ?
                    WHERE id = ?
                ");

                $update->execute([
                    $name,
                    $email,
                    $userId
                ]);

                $_SESSION['name'] = $name;

                $user['name']  = $name;
                $user['email'] = $email;

                $success = 'Profile updated successfully.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Change password
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'change_password') {

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {

            $error = 'All password fields are required.';

        } elseif (!password_verify($current, $user['password'])) {

            $error = 'Current password is incorrect.';

        } elseif (strlen($new) < 6) {

            $error = 'New password must be at least 6 characters.';

        } elseif ($new !== $confirm) {

            $error = 'New passwords do not match.';

        } else {

            $newHash = password_hash(
                $new,
                PASSWORD_DEFAULT
            );

            $update = $pdo->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            $update->execute([
                $newHash,
                $userId
            ]);

            $success = 'Password changed successfully.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Add category
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'add_category') {

        $catName = trim($_POST['category_name'] ?? '');

        if (!$catName) {

            $error = 'Category name is required.';

        } else {

            $insert = $pdo->prepare("
                INSERT INTO categories (name, user_id)
                VALUES (?, ?)
            ");

            $insert->execute([
                $catName,
                $userId
            ]);

            header(
                'Location: /task_manager/pages/profile.php?success=1'
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Delete category
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete_category') {

        $catId = (int)($_POST['category_id'] ?? 0);

        $delete = $pdo->prepare("
            DELETE FROM categories
            WHERE id = ?
            AND user_id = ?
        ");

        $delete->execute([
            $catId,
            $userId
        ]);

        header(
            'Location: /task_manager/pages/profile.php?deleted=1'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<div class="profile-page">

    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="page-header profile-page-header">

        <div>

            <span class="profile-eyebrow">
                ACCOUNT
            </span>

            <h1>Profile</h1>

            <p>
                Manage your personal information, password, and categories.
            </p>

        </div>

    </div>


    <!-- =====================================================
         ALERTS
         ===================================================== -->

    <?php if ($error): ?>

        <div class="alert alert-error">
            <?= escape($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($success): ?>

        <div class="alert alert-success">
            <?= escape($success) ?>
        </div>

    <?php endif; ?>


    <?php if (isset($_GET['success'])): ?>

        <div class="alert alert-success">
            Category added successfully.
        </div>

    <?php endif; ?>


    <?php if (isset($_GET['deleted'])): ?>

        <div class="alert alert-success">
            Category deleted successfully.
        </div>

    <?php endif; ?>


    <!-- =====================================================
         PROFILE OVERVIEW
         ===================================================== -->

    <section class="profile-overview">

        <div class="profile-avatar-large">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>


        <div class="profile-overview-info">

            <h2>
                <?= escape($user['name']) ?>
            </h2>

            <p>
                <?= escape($user['email']) ?>
            </p>

            <div class="profile-role">

                <span class="profile-role-dot"></span>

                <?= escape(ucfirst($user['role'])) ?>

            </div>

        </div>


        <div class="profile-overview-stats">

            <div class="profile-stat">

                <strong>
                    <?= count($categories) ?>
                </strong>

                <span>
                    Categories
                </span>

            </div>

        </div>

    </section>


    <!-- =====================================================
         PROFILE GRID
         ===================================================== -->

    <div class="profile-grid">


        <!-- =================================================
             ACCOUNT DETAILS
             ================================================= -->

        <div class="form-card profile-card">

            <div class="profile-card-header">

                <div class="profile-card-icon">
                    👤
                </div>

                <div>

                    <h2>
                        Account details
                    </h2>

                    <p>
                        Update your personal information.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                class="task-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="update_profile"
                >


                <div class="form-group">

                    <label>
                        Full name
                    </label>

                    <input
                        type="text"
                        name="name"
                        required
                        value="<?= escape($user['name']) ?>"
                        placeholder="Your full name"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Email address
                    </label>

                    <input
                        type="email"
                        name="email"
                        required
                        value="<?= escape($user['email']) ?>"
                        placeholder="name@email.com"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Account role
                    </label>

                    <div class="readonly-field">

                        <span class="readonly-role-icon">
                            ●
                        </span>

                        <span>
                            <?= escape(ucfirst($user['role'])) ?>
                        </span>

                        <span class="readonly-label">
                            Read only
                        </span>

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save changes
                    </button>

                </div>

            </form>

        </div>


        <!-- =================================================
             CHANGE PASSWORD
             ================================================= -->

        <div class="form-card profile-card">

            <div class="profile-card-header">

                <div class="profile-card-icon">
                    🔒
                </div>

                <div>

                    <h2>
                        Change password
                    </h2>

                    <p>
                        Keep your account secure.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                class="task-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="change_password"
                >


                <div class="form-group">

                    <label>
                        Current password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        required
                        placeholder="Enter current password"
                    >

                </div>


                <div class="form-group">

                    <label>
                        New password
                    </label>

                    <input
                        type="password"
                        name="new_password"
                        required
                        placeholder="Minimum 6 characters"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Confirm new password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        required
                        placeholder="Repeat new password"
                    >

                </div>


                <div class="password-hint">

                    <span>
                        ⓘ
                    </span>

                    <span>
                        Use at least 6 characters for your new password.
                    </span>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Change password
                    </button>

                </div>

            </form>

        </div>


        <!-- =================================================
             CATEGORIES
             ================================================= -->

        <div class="form-card profile-card profile-category-card">

            <div class="profile-card-header category-header">

                <div class="profile-card-icon">
                    🏷
                </div>

                <div>

                    <h2>
                        My categories
                    </h2>

                    <p>
                        Organize your tasks with custom categories.
                    </p>

                </div>

            </div>


            <!-- ADD CATEGORY -->

            <form
                method="POST"
                class="category-add-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="add_category"
                >


                <div class="category-input-wrapper">

                    <input
                        type="text"
                        name="category_name"
                        placeholder="Enter a new category name"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    + Add category
                </button>

            </form>


            <!-- CATEGORY LIST -->

            <?php if (empty($categories)): ?>

                <div class="category-empty">

                    <div class="category-empty-icon">
                        🏷
                    </div>

                    <h3>
                        No categories yet
                    </h3>

                    <p>
                        Create your first category to organize your tasks.
                    </p>

                </div>

            <?php else: ?>

                <div class="category-list">

                    <?php foreach ($categories as $cat): ?>

                        <div class="category-row">

                            <div class="category-info">

                                <div class="category-color-dot"></div>

                                <div>

                                    <div class="category-name">
                                        <?= escape($cat['name']) ?>
                                    </div>

                                    <div class="category-count">

                                        <?= $cat['task_count'] ?>

                                        <?= $cat['task_count'] == 1
                                            ? 'task'
                                            : 'tasks'
                                        ?>

                                    </div>

                                </div>

                            </div>


                            <form
                                method="POST"
                                class="category-delete-form"
                                onsubmit="return confirm('Delete this category?')"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete_category"
                                >

                                <input
                                    type="hidden"
                                    name="category_id"
                                    value="<?= $cat['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-danger"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>