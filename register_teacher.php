<?php
/* ====================================================
   File: register_teacher.php
   Purpose:
   - Show teacher registration form
   - Auto-generate next Teacher ID (T0001, T0002, ...)
   - Hash password before saving
   - Save teacher in `teachers` table
   ==================================================== */

$pageTitle = "Teacher Registration - My School";

require "db.php"; // uses your existing DB connection ($conn)

$errors = [];
$successMessage = "";

/**
 * Generate next Teacher ID.
 * Pattern: T0001, T0002, ...
 * Logic:
 *  - Look at latest teacher_id in DB
 *  - Extract numeric part, add 1
 *  - Pad with zeros to 4 digits
 */
function generateTeacherId(mysqli $conn): string
{
    $nextNumber = 1;

    $sql = "SELECT teacher_id FROM teachers ORDER BY id DESC LIMIT 1";
    if ($result = $conn->query($sql)) {
        if ($row = $result->fetch_assoc()) {
            // e.g. "T0005" → "0005" → 5
            $lastId = $row["teacher_id"];
            $num = (int)substr($lastId, 1);
            $nextNumber = $num + 1;
        }
        $result->free();
    }

    return "T" . str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
}

// We always generate a Teacher ID for display.
// On POST, we will regenerate again server-side (for safety).
$displayTeacherId = generateTeacherId($conn);

// -------------------------------------------
// Handle form submission (POST)
// -------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get and clean form inputs
    $fullName         = trim($_POST["full_name"] ?? "");
    $email            = trim($_POST["email"] ?? "");
    $password         = $_POST["password"] ?? "";
    $passwordConfirm  = $_POST["password_confirm"] ?? "";

    // Basic validations
    if ($fullName === "") {
        $errors[] = "Full name is required.";
    }

    if ($email === "") {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($password === "" || $passwordConfirm === "") {
        $errors[] = "Password and confirmation are required.";
    } elseif ($password !== $passwordConfirm) {
        $errors[] = "Password and confirmation do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password should be at least 6 characters long.";
    }

    // Check if email already exists for a teacher
    if (empty($errors)) {
        $checkSql = "SELECT id FROM teachers WHERE email = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errors[] = "A teacher with this email address already exists.";
        }

        $checkStmt->close();
    }

    // If no validation errors, insert into DB
    if (empty($errors)) {

        // Generate next ID on server side (ignore any value from browser)
        $teacherId = generateTeacherId($conn);

        // Hash password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert teacher record
        $sql = "INSERT INTO teachers (teacher_id, full_name, email, password_hash, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssss", $teacherId, $fullName, $email, $passwordHash);

            if ($stmt->execute()) {
                $successMessage = "Registration successful! Your Teacher ID is <strong>{$teacherId}</strong>. You can now log in.";
                // Clear form values after success
                $fullName = "";
                $email = "";
            } else {
                $errors[] = "Error saving teacher. Please try again.";
            }

            $stmt->close();
        } else {
            $errors[] = "Database error. Please contact the system administrator.";
        }
    }
}

// Include header *after* processing so $pageTitle is set
include "partials/header.php";
?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title mb-3">Administrator Registration</h3>
          <p class="text-muted small mb-3">
            Please fill in your details to create a Admin account for the Student Enrollment System.
          </p>

          <!-- Show success message -->
          <?php if ($successMessage): ?>
            <div class="alert alert-success">
              <?= $successMessage; ?>
              <div class="mt-2">
                <a href="login.php" class="btn btn-sm btn-success">Go to Login</a>
              </div>
            </div>
          <?php endif; ?>

          <!-- Show validation errors -->
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- Registration form -->
          <form method="post" novalidate>

            <!-- Teacher ID (auto-generated, read-only) -->
            <div class="mb-3">
              <label class="form-label">Administrator ID</label>
              <input type="text"
                     class="form-control"
                     value="<?= htmlspecialchars($displayTeacherId); ?>"
                     readonly>
              <div class="form-text">
                This ID is generated automatically to keep Administrator IDs consistent.
              </div>
            </div>

            <!-- Full name -->
            <div class="mb-3">
              <label for="full_name" class="form-label">Full Name</label>
              <input type="text"
                     name="full_name"
                     id="full_name"
                     class="form-control"
                     value="<?= htmlspecialchars($fullName ?? ""); ?>"
                     required>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email Address</label>
              <input type="email"
                     name="email"
                     id="email"
                     class="form-control"
                     value="<?= htmlspecialchars($email ?? ""); ?>"
                     required>
              <div class="form-text">
                This will be used for your login username.
              </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password"
                     name="password"
                     id="password"
                     class="form-control"
                     required>
            </div>

            <!-- Confirm password -->
            <div class="mb-3">
              <label for="password_confirm" class="form-label">Confirm Password</label>
              <input type="password"
                     name="password_confirm"
                     id="password_confirm"
                     class="form-control"
                     required>
            </div>

            <button type="submit" class="btn btn-primary">
              Register Administrator
            </button>

            <a href="login.php" class="btn btn-link">
              Already have an account? Login
            </a>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>
