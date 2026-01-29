<?php
/* ====================================================
   File: login.php
   Purpose:
   - ADMIN Login with Email-based OTP (2FA)
   - Supports login using Teacher ID OR Email
   ==================================================== */

$pageTitle = "Administrator Login - My School";

require "db.php";
include "partials/header.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

// If already logged in → redirect to dashboard
if (!empty($_SESSION["teacher_logged_in"])) {
    header("Location: dashboard.php");
    exit;
}

// ====================================================
//  Handle Login Form Submission
// ====================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identifier = trim($_POST["identifier"] ?? "");
    $password   = $_POST["password"] ?? "";

    if ($identifier === "") $errors[] = "Please enter your Administrator ID or Email.";
    if ($password === "")   $errors[] = "Please enter your password.";

    if (empty($errors)) {

        $sql = "SELECT id, teacher_id, full_name, email, password_hash, photo_filename
                FROM teachers
                WHERE email = ? OR teacher_id = ?
                LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {

            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {

                if (password_verify($password, $row["password_hash"])) {

                    /* ====================================================
                       STEP 2FA — Create One-Time Password (OTP)
                       ==================================================== */

                    $otp = random_int(100000, 999999); // secure 6 digits

                    $_SESSION["2fa_teacher_id"]    = $row["teacher_id"];
                    $_SESSION["2fa_teacher_email"] = $row["email"];
                    $_SESSION["2fa_teacher_name"]  = $row["full_name"];
                    $_SESSION["2fa_teacher_photo"] = $row["photo_filename"];
                    $_SESSION["2fa_code"]          = $otp;
                    $_SESSION["2fa_expiry"]        = time() + 300; // 5 minutes

                    /* ====================================================
                       SEND OTP EMAIL USING PHPMailer + Outlook SMTP
                       ==================================================== */

                    require "phpmailer/src/PHPMailer.php";
                    require "phpmailer/src/SMTP.php";
                    require "phpmailer/src/Exception.php";

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host       = "smtp.office365.com";
                        $mail->SMTPAuth   = true;
                        $mail->Username   = "din.kumar@vodafone.com.fj";   // my Outlook email
                        $mail->Password   = "Password@2601!";               // TEMP password for demo
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        // Sender + recipient
                        $mail->setFrom("din.kumar@vodafone.com.fj", "Koro High School");
                        $mail->addAddress($row["email"], $row["full_name"]);

                        // Email message
                        $mail->isHTML(false);
                        $mail->Subject = "Your Login Verification Code (Koro High School)";
                        $mail->Body =
                            "Dear " . $row["full_name"] . ",\n\n" .
                            "Your Koro High School login verification code is:\n\n" .
                            "        $otp\n\n" .
                            "This code expires in 5 minutes.\n\n" .
                            "If you did not attempt to log in, please ignore this email.\n\n" .
                            "Regards,\nKoro High School ICT Team";

                        $mail->send();

                    } catch (Exception $e) {
                        $errors[] = "Could not send OTP email. Contact ICT.";
                        error_log("2FA email error: " . $mail->ErrorInfo);
                    }

                    // Go to OTP verification page
                    header("Location: verify_2fa.php");
                    exit;

                } else {
                    $errors[] = "Incorrect password.";
                }

            } else {
                $errors[] = "No teacher found with that ID or email.";
            }

            $stmt->close();
        }
    }
}
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">

            <div class="card shadow-sm">
                <div class="card-body">

                    <h3 class="card-title mb-3">Administrator Login</h3>
                    <p class="text-muted small mb-3">
                        Login using your Administrator ID (e.g. <strong>T0001</strong>) or Email.
                    </p>

                    <!-- Display Errors -->
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
                            <label class="form-label">Administrator ID or Email</label>
                            <input type="text" name="identifier" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Login
                        </button>

                    </form>

                    <!-- New teacher registration link under login button
                    <div class="mt-3 text-center">
                        <a href="register_teacher.php" class="btn btn-outline-secondary w-100">
                            New teacher? Register here
                        </a>
                    </div> -->

                </div>
            </div>

        </div>
    </div>
</div>

<?php include "partials/footer.php"; ?>
