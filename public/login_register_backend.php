<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

if (isset($_POST['register'])){
    $phonenumber = $_POST['phonenumber'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role =$_POST['role'];

    $checkPhone =$conn->query("SELECT phonenumber FROM users WHERE phonenumber = '$phonenumber'");
    if ($checkPhone->num_rows > 0){
        $_SESSION['register_error'] = "Phone number is already registered.";
        $_SESSION['active_form'] = 'register';
    } else{
        $conn->query("INSERT INTO users (phonenumber, password, role) VALUES ('$phonenumber', '$password', '$role')");
        $_SESSION['register_success'] = "Registration Successful. You can log in.";
        $_SESSION['active_form'] = 'login';
    }

    header("Location: login_register.php");
    exit();
}


if (isset($_POST['login'])){
    $phonenumber = $_POST['phonenumber'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE phonenumber = '$phonenumber'");
    if ($result->num_rows > 0){
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])){
            $_SESSION['phonenumber'] = $user['phonenumber'];
 
            if ($user['role'] === 'admin'){
                header("Location: admin_page.php");
            } else{
                header("Location: user_page.php");
            }
            exit();
        }
    }
    $_SESSION['login_error'] = "Incorrect phone number or password.";
    $_SESSION['active_form'] = 'login';
    header("Location: login_register.php");
    exit();
}
?>