<?php
    session_start();

    $errors = [
        'login' => $_SESSION['login_error'] ?? "",
        'register' => $_SESSION['register_error'] ?? ""
    ];
    $success = [
        'register' => $_SESSION['register_success'] ?? "",
        'login' => ""
    ];

    $activeForm = $_SESSION['active_form'] ?? 'login';

    unset($_SESSION['login_error']);
    unset($_SESSION['register_error']);
    unset($_SESSION['register_success']);
    unset($_SESSION['active_form']);

    function showError($error){
        return !empty($error) ? "<p class='error-message'>$error</p>" : "";
    }

    function showSuccess($success){
        return !empty($success) ? "<p class='success-message'>$success</p>" : "";
    }

    function isActiveForm($formName, $activeForm){
        return $formName === $activeForm ? 'active' : "";
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Login & Register - Crime Report System</title>
</head>
<body class="auth-page">
    <div class="container">
        <!-- Login Form -->
        <div class="form-box <?php echo isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="login_register_backend.php" method="post">
                <h2>Login</h2>
                <?php echo showError($errors['login']); ?>
                <?php echo showSuccess($success['login']); ?>

                <input type="text" name="username" placeholder="Enter username" required>
                <input type="password" name="password" placeholder="Enter your password" required>
                <button type="submit" name="login">Login</button>
                <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Register</a></p>
                <p class="anonymous-link">Need to report anonymously? <a href="anonymous_report.php">Click here</a></p>
            </form>
        </div>

        <!-- Register Form -->
        <div class="form-box <?php echo isActiveForm('register', $activeForm); ?>" id="register-form">
            <form action="login_register_backend.php" method="post">
                <h2>Register</h2>
                <?php echo showError($errors['register']); ?>
                <?php echo showSuccess($success['register']); ?>

                <input type="text" name="username" placeholder="Choose a username" required>
                <input type="password" name="password" placeholder="Enter your password" required>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required>

                <button type="submit" name="register">Register</button>
                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
                <p class="anonymous-link">Need to report anonymously? <a href="anonymous_report.php">Click here <i class="fas fa-arrow-right"></i></a></p>
            </form>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="script.js"></script>
</body>
</html>