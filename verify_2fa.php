<?php
/* ====================================================
   File: verify_2fa.php
   Purpose:
   - Verify email OTP for BOTH teacher & student login
   - Auto-detect account type
   ==================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "db.php";

$pageTitle = "Verify Login Code - Koro High School";

$errors = [];

/* ====================================================
   Detect Login Type (Teacher or Student)
   ==================================================== */
$isTeacher = isset($_SESSION["2fa_teacher_id"]);
$isStudent = isset($_SESSION["2fa_student_id"]);

if (!$isTeacher && !$isStudent) {
    // No 2FA session exists → invalid entry
    header("Location: index.php");
    exit;
}

$loginEmail = $isTeacher
    ? $_SESSION["2fa_teacher_email"]
    : $_SESSION["2fa_student_email"];

/* ====================================================
   Handle OTP Submission
   ==================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = trim($_POST["code"] ?? "");

    if ($code === "") {
        $errors[] = "Please enter the verification code.";
    } elseif (time() > ($_SESSION["2fa_expiry"] ?? 0)) {
        $errors[] = "Code has expired. Please login again.";
    } elseif ($code != ($_SESSION["2fa_code"] ?? "")) {
        $errors[] = "Invalid code. Please try again.";
    } else {

        /* ====================================================
           OTP Correct → Finalize Login Session
           ==================================================== */

        if ($isTeacher) {

            $_SESSION["teacher_logged_in"] = true;
            $_SESSION["teacher_id"]        = $_SESSION["2fa_teacher_id"];
            $_SESSION["teacher_email"]     = $_SESSION["2fa_teacher_email"];
            $_SESSION["teacher_name"]      = $_SESSION["2fa_teacher_name"];

            // Photo path
            $defaultPhoto = "assets/images/teacher_default.png";
            $photoFile    = $_SESSION["2fa_teacher_photo"];
            $_SESSION["teacher_photo"]     = $photoFile
                ? "assets/uploads/teachers/" . $photoFile
                : $defaultPhoto;

            // Clear 2FA temporary values
            unset(
                $_SESSION["2fa_teacher_id"],
                $_SESSION["2fa_teacher_email"],
                $_SESSION["2fa_teacher_name"],
                $_SESSION["2fa_teacher_photo"],
                $_SESSION["2fa_code"],
                $_SESSION["2fa_expiry"]
            );

            header("Location: dashboard.php");
            exit;
        }

        if ($isStudent) {

            $_SESSION["student_logged_in"] = true;
            $_SESSION["student_id"]        = $_SESSION["2fa_student_id"];
            $_SESSION["student_email"]     = $_SESSION["2fa_student_email"];
            $_SESSION["student_name"]      = $_SESSION["2fa_student_name"];

            // Photo path
            $defaultPhoto = "assets/images/student_default.png";
            $photoFile    = $_SESSION["2fa_student_photo"];
            $_SESSION["student_photo"]     = $photoFile
                ? "assets/uploads/students/" . $photoFile
                : $defaultPhoto;

            // Clear 2FA temporary values
            unset(
                $_SESSION["2fa_student_id"],
                $_SESSION["2fa_student_email"],
                $_SESSION["2fa_student_name"],
                $_SESSION["2fa_student_photo"],
                $_SESSION["2fa_code"],
                $_SESSION["2fa_expiry"]
            );

            header("Location: student_dashboard.php");
            exit;
        }
    }
}

include "partials/header.php";
?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-md-5">

      <div class="card shadow-sm">
        <div class="card-body">

          <h3 class="mb-3">Verify Login</h3>

          <p class="text-muted small">
            A 6-digit verification code was sent to your email:<br>
            <strong><?= htmlspecialchars($loginEmail); ?></strong>
          </p>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0 small">
                <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- OTP Form -->
          <form method="post">

            <div class="mb-3">
              <label class="form-label">Verification Code</label>
              <input type="text"
                     name="code"
                     class="form-control"
                     required
                     placeholder="Enter 6-digit code">
            </div>

            <button class="btn btn-primary w-100" type="submit">
              Verify Login
            </button>

            <a href="index.php" class="btn btn-link btn-sm mt-2">Cancel</a>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>