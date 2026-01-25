<?php
include 'db.php';

$student_id = $_POST['student_id'];
$full_name = $_POST['full_name'];
$email = $_POST['email'];
$course = $_POST['course'];

$sql = "INSERT INTO students (student_id, full_name, email, course)
        VALUES ('$student_id', '$full_name', '$email', '$course')";

if (mysqli_query($conn, $sql)) {
    echo "Student enrolled successfully!";
    echo "<br><a href='index.php'>Go Back</a>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>