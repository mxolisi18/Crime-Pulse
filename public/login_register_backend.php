<?php
// login_register_backend.php
error_reporting(E_ALL);
ini_set('display_errors', 1);   // OFF in production!

session_start();
require_once __DIR__ . '/../config.php';   // goes to root

/* ---------- REGISTER ---------- */
if (isset($_POST['register'])) {
    $username         = trim($_POST['username'] ?? '');       // changed
    $plainPassword    = $_POST['password'] ?? '';
    $confirmPassword  = $_POST['confirm_password'] ?? '';
    $role             = 'user';  // changed
    // $role             = $_POST['role'] ?? '';

    // ---- validation ----
    if ($username === '' || strlen($username) > 50) {
        $_SESSION['register_error'] = 'Username required (max 50 chars).';
        $_SESSION['active_form'] = 'register';
        header('Location: login_register.php');
        exit;
    }
    if (strlen($plainPassword) < 8) {
        $_SESSION['register_error'] = 'Password too short (min 8).';
        $_SESSION['active_form'] = 'register';
        header('Location: login_register.php');
        exit;
    }
    if ($plainPassword !== $confirmPassword) {
        $_SESSION['register_error'] = 'Passwords do not match.';
        $_SESSION['active_form'] = 'register';
        header('Location: login_register.php');
        exit;
    }
    $allowed = ['user'];
    if (!in_array($role, $allowed, true)) {
        $_SESSION['register_error'] = 'Invalid role.';
        $_SESSION['active_form'] = 'register';
        header('Location: login_register.php');
        exit;
    }

    try {
        // 1. Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['register_error'] = 'Username already taken.';
            $_SESSION['active_form'] = 'register';
        } else {
            // 2. Insert
            $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
            $ins = $pdo->prepare(
                "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)"
            );
            $ins->execute([$username, $hash, $role]);

            $_SESSION['register_success'] = 'Registered! You can now log in.';
            $_SESSION['active_form'] = 'login';
        }
    } catch (PDOException $e) {
        error_log('Register error: ' . $e->getMessage());
        $_SESSION['register_error'] = 'Registration failed – try again later.';
        $_SESSION['active_form'] = 'register';
    }

    header('Location: login_register.php');
    exit;
}

/* ---------- LOGIN ---------- */
if (isset($_POST['login'])) {
    $username      = trim($_POST['username'] ?? '');
    $plainPassword = $_POST['password'] ?? '';

    if ($username === '' || $plainPassword === '') {
        $_SESSION['login_error'] = 'Username and password required.';
        $_SESSION['active_form'] = 'login';
        header('Location: login_register.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($plainPassword, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin_page.php');
            } else {
                header('Location: user_page.php');
            }
            exit;
        }

        $_SESSION['login_error'] = 'Incorrect username or password.';
        $_SESSION['active_form'] = 'login';
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        $_SESSION['login_error'] = 'Login failed – try again later.';
        $_SESSION['active_form'] = 'login';
    }

    header('Location: login_register.php');
    exit;
}
?>