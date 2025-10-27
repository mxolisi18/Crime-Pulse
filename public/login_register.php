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

    session_unset();

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
    <title>index</title>
</head>
<body>
    <div class="container">
    <div class="form-box <?php echo isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="login_register.php" method="post">
                <h2>Login</h2>
                <?php echo showError($errors['login']); ?>

                <?php echo showSuccess($success['login']); ?>

                <input type="text" name="phonenumber" placeholder="Enter cellphone number" required>
                <input type="password" name="password" placeholder="Enter your password" required>
                <button type="submit" name="login">Login</button>
                <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Register</a></p>
            </form>
        </div>

    <div class="form-box <?php echo isActiveForm('register', $activeForm); ?>" id="register-form">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                <?php echo showError($errors['register']); ?>

                <?php echo showSuccess($success['register']); ?>

                <input type="text" name="phonenumber" placeholder="Enter cellphone number" required>
                <input type="password" name="password" placeholder="Enter your password" required>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
                <button type="submit" name="register">Register</button>
                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
            </form>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>