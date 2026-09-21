<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header('Location: /task_manager/pages/dashboard.php');
    exit;
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'login';

    if ($mode === 'register') {

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (!$name || !$email || !$password) {

            $error = 'All fields are required.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = 'Enter a valid email address.';

        } elseif (strlen($password) < 6) {

            $error = 'Password must be at least 6 characters.';

        } elseif ($password !== $confirm) {

            $error = 'Passwords do not match.';

        } else {

            $stmt = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            $stmt->execute([$email]);

            if ($stmt->fetch()) {

                $error = 'That email is already registered.';

            } else {

                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password)
                     VALUES (?, ?, ?)"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $hash
                ]);

                $success = 'Account created! You can now log in.';
                $mode = 'login';
            }
        }

    } else {

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {

            $error = 'Email and password are required.';

        } else {

            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE email = ?"
            );

            $stmt->execute([$email]);

            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                header(
                    'Location: /task_manager/pages/dashboard.php'
                );

                exit;

            } else {

                $error = 'Incorrect email or password.';
            }
        }
    }
}

$pageTitle = $mode === 'register'
    ? 'Register'
    : 'Login';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= $pageTitle ?> — Task Manager</title>

    <!-- CSS utama -->
    <link
        rel="stylesheet"
        href="/task_manager/assets/css/style.css"
    >

    <!-- CSS khusus halaman login -->
    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body.auth-body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 40px 20px;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;

            background:
                radial-gradient(
                    circle at 0% 100%,
                    rgba(79, 70, 229, 0.13) 0,
                    rgba(79, 70, 229, 0.13) 16%,
                    transparent 16.5%
                ),

                radial-gradient(
                    circle at 100% 0%,
                    rgba(99, 102, 241, 0.12) 0,
                    rgba(99, 102, 241, 0.12) 15%,
                    transparent 15.5%
                ),

                linear-gradient(
                    135deg,
                    #f8faff 0%,
                    #eef2ff 100%
                );

        }


        /* ==============================
           CARD
           ============================== */

        .auth-body .auth-card {

            width: 100%;

            max-width: 460px;

            padding: 42px;

            background: rgba(255, 255, 255, 0.97);

            border: 1px solid rgba(255, 255, 255, 0.9);

            border-radius: 24px;

            box-shadow:
                0 25px 60px rgba(30, 41, 59, 0.12),
                0 8px 25px rgba(30, 41, 59, 0.05);

            backdrop-filter: blur(12px);

            -webkit-backdrop-filter: blur(12px);

        }


        /* ==============================
           LOGO
           ============================== */

        .auth-body .auth-logo {

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 0 30px 0;

            color: #1e293b;

            font-size: 30px;

            font-weight: 800;

            letter-spacing: -1px;

        }


        .auth-body .auth-logo::before {

            content: "✓";

            width: 46px;

            height: 46px;

            margin-right: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            color: #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #4f46e5,
                    #6366f1
                );

            border-radius: 13px;

            font-size: 26px;

            font-weight: 700;

            box-shadow:
                0 8px 18px
                rgba(79, 70, 229, 0.25);

        }


        /* ==============================
           TABS
           ============================== */

        .auth-body .auth-tabs {

            display: flex;

            gap: 4px;

            padding: 5px;

            margin-bottom: 28px;

            background: #f1f5f9;

            border: none;

            border-radius: 12px;

        }


        .auth-body .auth-tab {

            flex: 1;

            padding: 11px 14px;

            text-align: center;

            color: #64748b;

            background: transparent;

            border: none;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

            transition: 0.2s ease;

        }


        .auth-body .auth-tab:hover {

            color: #4f46e5;

        }


        .auth-body .auth-tab.active {

            color: #4f46e5;

            background: #ffffff;

            box-shadow:
                0 2px 8px
                rgba(15, 23, 42, 0.08);

        }


        /* ==============================
           ALERT
           ============================== */

        .auth-body .alert {

            margin-bottom: 20px;

            padding: 12px 14px;

            border-radius: 10px;

            font-size: 13px;

            line-height: 1.5;

        }


        .auth-body .alert-error {

            color: #b91c1c;

            background: #fef2f2;

            border: 1px solid #fecaca;

        }


        .auth-body .alert-success {

            color: #15803d;

            background: #f0fdf4;

            border: 1px solid #bbf7d0;

        }


        /* ==============================
           FORM
           ============================== */

        .auth-body .auth-form {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }


        .auth-body .form-group {

            display: flex;

            flex-direction: column;

            gap: 8px;

        }


        .auth-body .form-group label {

            color: #334155;

            font-size: 14px;

            font-weight: 600;

            text-transform: none;

            letter-spacing: normal;

        }


        /* ==============================
           INPUT
           ============================== */

        .auth-body .form-group input {

            width: 100%;

            height: 50px;

            padding: 0 15px;

            color: #1e293b;

            background: #ffffff;

            border: 1px solid #dbe2ea;

            border-radius: 11px;

            outline: none;

            font-family: inherit;

            font-size: 15px;

            transition: 0.2s ease;

        }


        .auth-body .form-group input::placeholder {

            color: #a3afc2;

        }


        .auth-body .form-group input:hover {

            border-color: #cbd5e1;

        }


        .auth-body .form-group input:focus {

            background: #ffffff;

            border-color: #6366f1;

            box-shadow:
                0 0 0 4px
                rgba(99, 102, 241, 0.10);

        }


        /* ==============================
           BUTTON
           ============================== */

        .auth-body .btn {

            width: 100%;

            min-height: 50px;

            border: none;

            border-radius: 11px;

            cursor: pointer;

            font-family: inherit;

            font-size: 15px;

            font-weight: 700;

            transition: 0.2s ease;

        }


        .auth-body .btn-primary {

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #4f46e5,
                    #6366f1
                );

            box-shadow:
                0 8px 18px
                rgba(79, 70, 229, 0.22);

        }


        .auth-body .btn-primary:hover {

            transform: translateY(-1px);

            background:
                linear-gradient(
                    135deg,
                    #4338ca,
                    #4f46e5
                );

            box-shadow:
                0 12px 25px
                rgba(79, 70, 229, 0.28);

        }


        .auth-body .btn-primary:active {

            transform: translateY(0);

        }


        /* ==============================
           MOBILE
           ============================== */

        @media (max-width: 520px) {

            body.auth-body {

                padding: 20px 14px;

            }


            .auth-body .auth-card {

                padding: 32px 24px 34px;

                border-radius: 20px;

            }


            .auth-body .auth-logo {

                font-size: 26px;

            }


            .auth-body .auth-logo::before {

                width: 42px;

                height: 42px;

                font-size: 24px;

            }

        }

    </style>

</head>


<body class="auth-body">

    <div class="auth-card">

        <div class="auth-logo">
            TaskManager
        </div>


        <div class="auth-tabs">

            <a
                href="?mode=login"
                class="auth-tab <?= $mode === 'login' ? 'active' : '' ?>"
            >
                Login
            </a>

            <a
                href="?mode=register"
                class="auth-tab <?= $mode === 'register' ? 'active' : '' ?>"
            >
                Register
            </a>

        </div>


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


        <?php if ($mode === 'register'): ?>

            <!-- REGISTER -->

            <form
                method="POST"
                class="auth-form"
            >

                <input
                    type="hidden"
                    name="mode"
                    value="register"
                >


                <div class="form-group">

                    <label>Full name</label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Your Name"
                        required
                        value="<?= escape($_POST['name'] ?? '') ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="name@email.com"
                        required
                        value="<?= escape($_POST['email'] ?? '') ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Min. 6 characters"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Confirm password</label>

                    <input
                        type="password"
                        name="confirm"
                        placeholder="Repeat password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create account
                </button>

            </form>


        <?php else: ?>

            <!-- LOGIN -->

            <form
                method="POST"
                class="auth-form"
            >

                <input
                    type="hidden"
                    name="mode"
                    value="login"
                >


                <div class="form-group">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="name@email.com"
                        required
                        value="<?= escape($_POST['email'] ?? '') ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Your password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Log in
                </button>

            </form>

        <?php endif; ?>

    </div>

</body>

</html>