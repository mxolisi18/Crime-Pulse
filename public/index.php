<?php
require_once '../vendor/autoload.php';
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crime Report System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <h1> CRIME REPORT SYSTEM</h1>
    <p>Welcome to the PHP XAMPP Crime Reporting Project!</p>
    <p>This is an example of using git</p>
    <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
    <h1>CRIME REPORT SYSTEM</h1>

    <p>Welcome to anonymous crime reporting system, report crimes securely and anonymously without any fear.</p>
    
    <button><a href="login_register.php">Login</a> to report a crime.</button>
</body>
</html>
