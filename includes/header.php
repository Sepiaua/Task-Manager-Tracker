<?php
require_once __DIR__ . '/../includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $pageTitle ?? 'Task Manager' ?></title>

    <link rel="stylesheet"
          href="/task_manager/assets/css/style.css?v=5">
</head>

<body>

<?php if (isLoggedIn()): ?>

<nav class="navbar">

    <div class="navbar-inner">

        <!-- BRAND -->
        <a href="/task_manager/pages/dashboard.php"
           class="nav-brand">

            <span class="brand-mark">T</span>

            <span class="brand-name">
                Task Manager
            </span>

        </a>


        <!-- NAVIGATION -->
        <div class="nav-links">

            <a href="/task_manager/pages/dashboard.php"
               class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                Dashboard
            </a>


            <a href="/task_manager/pages/task_create.php"
               class="nav-link nav-new-task <?= $currentPage === 'task_create.php' ? 'active' : '' ?>">

                <span class="plus-icon">+</span>
                New Task

            </a>


            <?php if (isAdmin()): ?>

                <a href="/task_manager/admin/dashboard.php"
                   class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : '' ?>">
                    Admin
                </a>

            <?php endif; ?>


            <!-- USER PROFILE -->
            <a href="/task_manager/pages/profile.php"
               class="user-profile <?= $currentPage === 'profile.php' ? 'active' : '' ?>">

                <span class="user-avatar">
                    <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                </span>

                <span class="user-name">
                    <?= escape($_SESSION['name']) ?>
                </span>

            </a>


            <!-- LOGOUT -->
            <a href="/task_manager/includes/logout.php"
               class="btn-logout">
                Logout
            </a>

        </div>

    </div>

</nav>

<?php endif; ?>


<main class="main-content">