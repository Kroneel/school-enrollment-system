<?php
// Database connection settings
$host = "localhost";
$user = "root";
$password = "";
$dbname = "student_enrollment";

// Create connection to MySQL database
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check if connection failed
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
