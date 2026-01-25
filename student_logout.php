<?php
/* ====================================================
   File: student_logout.php
   Purpose:
   - Ends the student session and redirects to home page
   ==================================================== */

// Start session (required to destroy it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit;
?>
