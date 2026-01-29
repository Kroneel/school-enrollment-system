<?php
/* ====================================================
   File: student_register.php
   Purpose:
   - Allow students to create their own login account
   - Auto-generate Student ID (S0001, S0002, ...)
   - Store hashed password (password_hash)
   - This is Step 1 before they submit an application
   ==================================================== */

$pageTitle = "Student Registration - My School";

require "db.php"; // DB connection ($conn)

// Collect errors and success message
$errors = [];
$successMessage = "";

/**
 * Generate next Student ID.
 * Pattern: S0001, S0002, ...
 * Logic:
 *  - Look at latest student_id in student_accounts
 *  - Extract numeric part, add 1, pad with zeros
 */
function generateStudentId(mysqli $conn): string
{
    $nextNumber = 1;

    $sql = "SELECT student_id FROM student_accounts ORDER BY id DESC LIMIT 1";
    if ($result = $conn->query($sql)) {
        if ($row = $result->fetch_assoc()) {
            // e.g. "S0005" → "0005" → 5
            $lastId = $row["student_id"];
            $num = (int)substr($lastId, 1);
            $nextNumber = $num + 1;
        }
        $result->free();
    }

    return "S" . str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
}

// Generate ID to show (for display only, we’ll regen on POST)
$displayStudentId = generateStudentId($conn);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get and clean user inputs
    $fullName        = trim($_POST["full_name"] ?? "");
    $email           = trim($_POST["email"] ?? "");
    $password        = $_POST["password"] ?? "";
    $passwordConfirm = $_POST["password_confirm"] ?? "";

    // Basic validation
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

    // Check email uniqueness
    if (empty($errors)) {
        $checkSql = "SELECT id FROM student_accounts WHERE email = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errors[] = "A student with this email address already exists.";
        }

        $checkStmt->close();
    }

    // Insert into DB if everything is valid
    if (empty($errors)) {

        // Generate Student ID safely on server side
        $studentId = generateStudentId($conn);

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO student_accounts (student_id, full_name, email, password_hash, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssss", $studentId, $fullName, $email, $passwordHash);

            if ($stmt->execute()) {
                $successMessage = "Registration successful! Your Student ID is <strong>{$studentId}</strong>. "
                                . "Please remember this ID for login and application tracking.";

                // Clear form fields after success
                $fullName = "";
                $email = "";
            } else {
                $errors[] = "Error saving student account. Please try again.";
            }

            $stmt->close();
        } else {
            $errors[] = "Database error. Please contact the system administrator.";
        }
    }
}

// Include header after $pageTitle is set
include "partials/header.php";
?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title mb-3">Student Registration</h3>
          <p class="text-muted small mb-3">
            Create your student account first. After login, you will be able to submit an
            enrollment application by selecting your year level and subjects.
          </p>

          <!-- Success message -->
          <?php if ($successMessage): ?>
            <div class="alert alert-success">
              <?= $successMessage; ?>
              <div class="mt-2">
                <a href="student_login.php" class="btn btn-sm btn-success">
                  Go to Student Login
                </a>
              </div>
            </div>
          <?php endif; ?>

          <!-- Errors -->
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

            <!-- Student ID (read-only display) -->
            <div class="mb-3">
              <label class="form-label">Student ID</label>
              <input type="text"
                     class="form-control"
                     value="<?= htmlspecialchars($displayStudentId); ?>"
                     readonly>
              <div class="form-text">
                This Student ID is generated automatically to keep IDs consistent.
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
                You will use this together with your Student ID for login and notifications.
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

            <!-- Confirm Password -->
            <div class="mb-3">
              <label for="password_confirm" class="form-label">Confirm Password</label>
              <input type="password"
                     name="password_confirm"
                     id="password_confirm"
                     class="form-control"
                     required>
            </div>

            <button type="submit" class="btn btn-primary">
              Register Student
            </button>

            <a href="student_login.php" class="btn btn-link">
              Already have a student account? Login
            </a>

          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>

