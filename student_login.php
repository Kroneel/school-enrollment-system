<?php
/* ====================================================
   File: student_login.php
   Purpose:
   - Student Login (Email or Student ID)
   - Password verification
   - Sends OTP code via email (PHPMailer)
   - Redirects to verify_2fa.php
   ==================================================== */

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Student Login - My School";

require "db.php";
include "partials/header.php";

$errors = [];

// Already logged in → go to dashboard
if (!empty($_SESSION["student_logged_in"])) {
    header("Location: student_dashboard.php");
    exit;
}

// ====================================================
//  Handle Login Form
// ====================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identifier = trim($_POST["identifier"] ?? "");
    $password   = $_POST["password"] ?? "";

    if ($identifier === "") $errors[] = "Please enter your Student ID or Email.";
    if ($password === "")   $errors[] = "Please enter your password.";

    if (empty($errors)) {

        $sql = "SELECT id, student_id, full_name, email, password_hash, photo_filename
                FROM student_accounts
                WHERE email = ? OR student_id = ?
                LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {

            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {

                // Password correct?
                if (password_verify($password, $row["password_hash"])) {

                    /* ====================================================
                       STEP 2FA — Generate OTP
                       ==================================================== */
                    $otp = random_int(100000, 999999);

                    $_SESSION["2fa_student_id"]    = $row["student_id"];
                    $_SESSION["2fa_student_email"] = $row["email"];
                    $_SESSION["2fa_student_name"]  = $row["full_name"];
                    $_SESSION["2fa_student_photo"] = $row["photo_filename"];
                    $_SESSION["2fa_code"]          = $otp;
                    $_SESSION["2fa_expiry"]        = time() + 300; // valid 5 min


                    /* ====================================================
                       SEND OTP EMAIL (PHPMailer + Outlook SMTP)
                       ==================================================== */

                    require "phpmailer/src/PHPMailer.php";
                    require "phpmailer/src/SMTP.php";
                    require "phpmailer/src/Exception.php";

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host       = "smtp.office365.com";
                        $mail->SMTPAuth   = true;
                        $mail->Username   = "din.kumar@vodafone.com.fj"; // My email
                        $mail->Password   = "Password@2601!";            // TEMP password for demo
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        // Recipients
                        $mail->setFrom("din.kumar@vodafone.com.fj", "Koro High School");
                        $mail->addAddress($row["email"], $row["full_name"]);

                        // Message
                        $mail->isHTML(false);
                        $mail->Subject = "Your Student Login Verification Code (Koro High School)";

                        $mail->Body =
                            "Dear {$row["full_name"]},\n\n" .
                            "Your login verification code is:\n\n    $otp\n\n" .
                            "This code will expire in 5 minutes.\n\n" .
                            "If you did not try to log in, please ignore this message.\n\n" .
                            "Regards,\nKoro High School ICT Team";

                        $mail->send();

                    } catch (Exception $e) {
                        error_log("Student OTP email failed: " . $mail->ErrorInfo);
                        $errors[] = "OTP email could not be sent. Please contact ICT.";
                    }

                    // Redirect to OTP page
                    header("Location:verify_2fa.php");
                    exit;

                } else {
                    $errors[] = "Invalid password.";
                }

            } else {
                $errors[] = "No student found with that ID or Email.";
            }

            $stmt->close();

        } else {
            $errors[] = "Database error. Try again later.";
        }
    }
}

?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">

      <div class="card shadow-sm">
        <div class="card-body">

          <h3 class="card-title mb-3">Student Login</h3>
          <p class="text-muted small mb-3">
            Login using your Student ID (e.g. <strong>S0001</strong>) or your registered Email.
          </p>

          <!-- Error messages -->
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- Login Form -->
          <form method="post">

            <div class="mb-3">
              <label class="form-label">Student ID or Email</label>
              <input type="text" name="identifier" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Login</button>

            <div class="mt-3 text-center">
                        <a href="student_register.php" class="btn btn-primary w-100">
                            New Student? Register here
                        </a>
                    </div>
          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>
