<?php
/* ====================================================
   File: student_application.php
   Purpose:
   - Step 1: Capture personal details + photo
   - Step 2: Select subjects based on year level
     * Year 9 default core + 2 elective dropdowns
     * Year 10–13: visible but disabled (Coming Soon)
   - Save into student_enrollments table as "pending"
   ==================================================== */

require "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  Only logged in students
if (!isset($_SESSION["student_logged_in"]) || $_SESSION["student_logged_in"] !== true) {
    header("Location: student_login.php");
    exit;
}

$pageTitle = "Enrollment Application - My School";

// Basic student info from account
$studentId    = $_SESSION["student_id"]    ?? "N/A";
$studentName  = $_SESSION["student_name"]  ?? "Student";
$studentEmail = $_SESSION["student_email"] ?? "N/A";

// Step handling: default = 1
$step = isset($_GET["step"]) ? (int)$_GET["step"] : 1;
if ($step !== 1 && $step !== 2) {
    $step = 1;
}

$errors = [];
$successMessage = "";

/* -----------------------------------------------
   Helper: generate next Application ID
   Format: APP0001, APP0002, ...
----------------------------------------------- */
function generateApplicationId(mysqli $conn): string
{
    $nextNumber = 1;
    $sql = "SELECT application_id FROM student_enrollments ORDER BY id DESC LIMIT 1";
    if ($result = $conn->query($sql)) {
        if ($row = $result->fetch_assoc()) {
            $lastId = $row["application_id"];   // e.g. APP0012
            $num = (int)substr($lastId, 3);     // → 12
            $nextNumber = $num + 1;
        }
        $result->free();
    }
    return "APP" . str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
}

/* ============================================================
   STEP 1: Personal details + photo
   ============================================================ */
if ($step === 1 && $_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName     = trim($_POST["first_name"] ?? "");
    $lastName      = trim($_POST["last_name"] ?? "");
    $dob           = trim($_POST["dob"] ?? "");
    $parentName    = trim($_POST["parent_name"] ?? "");
    $parentContact = trim($_POST["parent_contact"] ?? "");
    if (!preg_match('/^[0-9]{7}$/', $parentContact)) {
    $errors[] = "Parent/Guardian contact must contain exactly 7 digits (e.g. 9876543).";
    }
    $address       = trim($_POST["address"] ?? "");

    if ($firstName === "")      $errors[] = "Please enter your first name.";
    if ($lastName === "")       $errors[] = "Please enter your last name.";
    if ($dob === "")            $errors[] = "Please enter your date of birth.";
    if ($parentName === "")     $errors[] = "Please enter your parent's/guardian's name.";
    if ($parentContact === "")  $errors[] = "Please enter your parent's/guardian's contact.";
    

    // Handle photo upload (optional)
    $photoFilename = null;
    if (isset($_FILES["student_photo"]) && $_FILES["student_photo"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["student_photo"]["error"] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES["student_photo"]["tmp_name"];
            $origName = $_FILES["student_photo"]["name"];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png"];
            if (!in_array($ext, $allowed, true)) {
                $errors[] = "Photo must be a JPG or PNG image.";
            } else {
                $uploadDir = __DIR__ . "/assets/uploads/students/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $photoFilename = $studentId . "_" . time() . "." . $ext;
                $destPath = $uploadDir . $photoFilename;
                if (!move_uploaded_file($tmpName, $destPath)) {
                    $errors[] = "Failed to upload photo. Please try again.";
                    $photoFilename = null;
                }
            }
        } else {
            $errors[] = "Error uploading photo. Please try again.";
        }
    }

    if (empty($errors)) {
        $_SESSION["enroll_step1"] = [
            "first_name"     => $firstName,
            "last_name"      => $lastName,
            "dob"            => $dob,
            "parent_name"    => $parentName,
            "parent_contact" => $parentContact,
            "address"        => $address,
            "student_photo"  => $photoFilename
        ];
        header("Location: student_application.php?step=2");
        exit;
    }
}

/* ============================================================
   STEP 2: Year level + subjects
   ============================================================ */
if ($step === 2 && $_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($_SESSION["enroll_step1"])) {
        header("Location: student_application.php?step=1");
        exit;
    }

    $yearLevel = trim($_POST["year_level"] ?? "");
    $subjects = [];

    if ($yearLevel === "") {
        $errors[] = "Please select your year level.";
    } elseif ($yearLevel === "Year 9") {
        // Year 9 subject logic
        $lang = trim($_POST["y910_lang"] ?? "");
        $prac = trim($_POST["y910_prac"] ?? "");
        if ($lang === "") $errors[] = "Please select a language / agriculture subject.";
        if ($prac === "") $errors[] = "Please select a practical subject.";
        if (empty($errors)) {
            $subjects = [
                "English", "Mathematics", "Basic Science", "Social Science",
                "Commercial Studies", $lang, $prac
            ];
        }
    } else {
        // Disabled years should never reach here
        $errors[] = "Applications for this year level are not open yet.";
    }

    if (empty($errors) && empty($subjects)) {
        $errors[] = "No subjects selected. Please check your choices.";
    }

    if (empty($errors)) {
        $step1 = $_SESSION["enroll_step1"];
        $applicationId = generateApplicationId($conn);
        $subjectsCsv = implode(", ", $subjects);

        $sql = "INSERT INTO student_enrollments
                (application_id, student_id, first_name, last_name, dob,
                 parent_name, parent_contact, address, student_photo,
                 year_level, subjects, status, submitted_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "sssssssssss",
                $applicationId,
                $studentId,
                $step1["first_name"],
                $step1["last_name"],
                $step1["dob"],
                $step1["parent_name"],
                $step1["parent_contact"],
                $step1["address"],
                $step1["student_photo"],
                $yearLevel,
                $subjectsCsv
            );
            if ($stmt->execute()) {
                $successMessage = "Your application has been submitted successfully! "
                    . "Your Application ID is <strong>{$applicationId}</strong>. Status: <strong>Pending</strong>.";
                unset($_SESSION["enroll_step1"]);
            } else {
                $errors[] = "Error saving your application. Please try again.";
            }
            $stmt->close();
        } else {
            $errors[] = "Database error. Please contact the system administrator.";
        }
    }
}

include "partials/header.php";
?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">

          <?php if ($step === 1): ?>
          <!-- ================= STEP 1 ================= -->
          <h3 class="card-title mb-3">Enrollment Application – Step 1 of 2</h3>
          <p class="text-muted small">Please provide your personal details and a passport-size photo.</p>

        <?php if (!empty($errors)): ?>
            <div class="mt-2">
              <?php foreach ($errors as $e): ?>
                <div class="text-danger small mb-1">⚠ <?= htmlspecialchars($e); ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>


          <?php
          $firstName     = $_POST["first_name"] ?? "";
          $lastName      = $_POST["last_name"] ?? "";
          $dob           = $_POST["dob"] ?? "";
          $parentName    = $_POST["parent_name"] ?? "";
          $parentContact = $_POST["parent_contact"] ?? "";
          $address       = $_POST["address"] ?? "";
          ?>

          <form method="post" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
              <label class="form-label">Student ID</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($studentId); ?>" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Account Name (from login)</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($studentName); ?>" readonly>
            </div>
            <div class="mb-3">
              <label for="first_name" class="form-label">First Name</label>
              <input type="text" name="first_name" id="first_name" class="form-control"
                     value="<?= htmlspecialchars($firstName); ?>" required>
            </div>
            <div class="mb-3">
              <label for="last_name" class="form-label">Last Name</label>
              <input type="text" name="last_name" id="last_name" class="form-control"
                     value="<?= htmlspecialchars($lastName); ?>" required>
            </div>
            <div class="mb-3">
              <label for="dob" class="form-label">Date of Birth</label>
              <input type="date" name="dob" id="dob" class="form-control"
                     min="2000-01-01" max="2010-12-31"
                     value="<?= htmlspecialchars($dob); ?>" required>
            </div>
            <div class="mb-3">
              <label for="parent_name" class="form-label">Parent/Guardian Name</label>
              <input type="text" name="parent_name" id="parent_name" class="form-control"
                     value="<?= htmlspecialchars($parentName); ?>" required>
            </div>

            <div class="mb-3">
  <label for="parent_contact" class="form-label">Parent/Guardian Contact</label>
  <input
    type="text"
    name="parent_contact"
    id="parent_contact"
    class="form-control"
    value="<?= htmlspecialchars($parentContact); ?>"
    pattern="^[0-9]{7}$"
    title="Please enter exactly 7 digits (e.g. 9876543)"
    required
    oninvalid="this.setCustomValidity('Please enter exactly 7 digits (e.g. 9876543)')"
    oninput="this.setCustomValidity('')"
  >
  <div class="form-text text-muted small">
    Enter a 7-digit mobile number only (e.g. 9876543)
  </div>
</div>

            <div class="mb-3">
              <label for="address" class="form-label">Home Address</label>
              <textarea name="address" id="address" rows="3" class="form-control"><?= htmlspecialchars($address); ?></textarea>
            </div>
            <div class="mb-3">
              <label for="student_photo" class="form-label">Student Photo (JPG/PNG)</label>
              <input type="file" name="student_photo" id="student_photo" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Next: Choose Subjects</button>
            <a href="student_dashboard.php" class="btn btn-link">Cancel</a>
          </form>

          <?php else: ?>
          <!-- ================= STEP 2 ================= -->
          <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= $successMessage; ?>
              <div class="mt-2">
                <a href="student_dashboard.php" class="btn btn-sm btn-success">Back to Dashboard</a>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!$successMessage && !empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?>
            </ul></div>
          <?php endif; ?>

          <?php if (!$successMessage): ?>
          <form method="post" novalidate>
            <div class="mb-3">
              <label for="year_level" class="form-label">Year Level</label>
              <select name="year_level" id="year_level" class="form-select" required>
                <option value="">-- Select Year Level --</option>
                <option value="Year 9">Year 9</option>
                <option value="Year 10" disabled>Year 10 (COMING SOON)</option>
                <option value="Year 11" disabled>Year 11 (COMING SOON)</option>
                <option value="Year 12" disabled>Year 12 (COMING SOON)</option>
                <option value="Year 13" disabled>Year 13 (COMING SOON)</option>
              </select>
            </div>

            <div id="section_y910" class="border rounded p-3 mb-3" style="display:none;">
              <h6 class="fw-bold">Year 9 Subjects</h6>
              <p class="text-muted small mb-2">
                Core subjects: <strong>English, Mathematics, Basic Science, Social Science, Commercial Studies</strong>
              </p>
              <div class="mb-3">
                <label class="form-label">Choose ONE (Language / Agriculture)</label>
                <select name="y910_lang" class="form-select">
                  <option value="">-- Select one --</option>
                  <option value="Hindi">Hindi</option>
                  <option value="Agriculture">Agriculture</option>
                  <option value="Fijian">Fijian</option>
                </select>
              </div>
              <div class="mb-0">
                <label class="form-label">Choose ONE (Practical)</label>
                <select name="y910_prac" class="form-select">
                  <option value="">-- Select one --</option>
                  <option value="Home Economics">Home Economics</option>
                  <option value="Basic Technology">Basic Technology</option>
                  <option value="Applied Technology">Applied Technology</option>
                  <option value="Office Technology">Office Technology</option>
                </select>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn" style="display:none;">
              Submit Application
            </button>
            <a href="student_application.php?step=1" class="btn btn-link">Back to Step 1</a>
            <a href="student_dashboard.php" class="btn btn-link">Cancel</a>
          </form>
          <?php endif; ?>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const yearSel = document.getElementById('year_level');
  const sectionY9 = document.getElementById('section_y910');
  const submitBtn = document.getElementById('submitBtn');

  function updateSections() {
    if (!yearSel) return;
    const year = yearSel.value;
    if (year === 'Year 9') {
      sectionY9.style.display = 'block';
      submitBtn.style.display = 'inline-block';
    } else {
      sectionY9.style.display = 'none';
      submitBtn.style.display = 'none';
    }
  }

  yearSel.addEventListener('change', updateSections);
  updateSections();
});
</script>
