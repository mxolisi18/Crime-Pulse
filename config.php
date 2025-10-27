<?php
$host =  "localhost";
$username = "root";
$password = "";
$dbname = "users_db";

$conn = new mysqli($host, $phonenumber, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>