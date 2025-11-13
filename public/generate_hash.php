<?php
// Replace 'Admin123' with the password you want for your admin account
$password = 'Admin123';

// Generate the hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Output the hash
echo "Your hashed password is: " . $hash;