<?php
// Start session only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ====================================================
   Login state checks
   ==================================================== */

// Teacher login status
$teacherLoggedIn = isset($_SESSION["teacher_logged_in"]) && $_SESSION["teacher_logged_in"] === true;

// Student login status
$studentLoggedIn = isset($_SESSION["student_logged_in"]) && $_SESSION["student_logged_in"] === true;

// Names to display on navbar
$teacherName = $_SESSION["teacher_name"] ?? "";
$studentName = $_SESSION["student_name"] ?? "";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? "School Portal") ?></title>

    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>

<!-- Main navigation bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <!-- School / system name -->
    <a class="navbar-brand fw-bold" href="index.php">Koro High School</a>

    <!-- Mobile menu button -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar links -->
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">

        <!-- Home always visible -->
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>

        <?php if (!$teacherLoggedIn && !$studentLoggedIn) { ?>
          <!-- ============= PUBLIC MENU ============= -->
          <li class="nav-item"><a class="nav-link" href="login.php">Administrator Login</a></li>
          <li class="nav-item"><a class="nav-link" href="student_login.php">Student Login</a></li>

        <?php } elseif ($teacherLoggedIn) { ?>
          <!-- ============= TEACHER MENU ============= -->
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="student_search.php">Search Student</a></li>
          <li class="nav-item"><a class="nav-link" href="student_register.php">Register Student</a></li>

          <li class="nav-item ms-lg-3">
            <span class="navbar-text text-white-50 small">Hi, <?= htmlspecialchars($teacherName) ?></span>
          </li>

          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
          </li>

        <?php } else { ?>
          <!-- ============= STUDENT MENU ============= -->
          <li class="nav-item"><a class="nav-link" href="student_dashboard.php">Dashboard</a></li>

          <li class="nav-item ms-lg-3">
            <span class="navbar-text text-white-50 small">Hi, <?= htmlspecialchars($studentName) ?></span>
          </li>

          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-light btn-sm" href="student_logout.php">Logout</a>
          </li>
        <?php } ?>
      </ul>

      <!-- ====== DARK MODE SWITCH (global) ====== -->
      <div class="form-check form-switch ms-3">
        <input class="form-check-input" type="checkbox" id="darkModeSwitch">
        <label class="form-check-label" for="darkModeSwitch">Dark Mode</label>
      </div>
    </div>
  </div>
</nav>

<!-- ====== Global Dark Mode Script ====== -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  const switchInput = document.getElementById("darkModeSwitch");

  // Load saved theme
  if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    switchInput.checked = true;
  }

  // Toggle theme
  switchInput.addEventListener("change", function() {
    if (this.checked) {
      document.body.classList.add("dark-mode");
      localStorage.setItem("theme", "dark");
    } else {
      document.body.classList.remove("dark-mode");
      localStorage.setItem("theme", "light");
    }
  });
});
</script>
