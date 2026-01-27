<?php
/* ====================================================
   File: student_application.php
   Purpose:
   - Step 1: Capture personal details + photo
   - Step 2: Select subjects based on year level
     * Year 9–10: default core + 2 elective dropdowns
     * Year 11–13: core (English, Maths) + Arts/Science
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
   - Save into $_SESSION["enroll_step1"]
   - Then redirect to step 2
============================================================ */
if ($step === 1 && $_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName     = trim($_POST["first_name"] ?? "");
    $lastName      = trim($_POST["last_name"] ?? "");
    $dob           = trim($_POST["dob"] ?? "");
    $parentName    = trim($_POST["parent_name"] ?? "");
    $parentContact = trim($_POST["parent_contact"] ?? "");
    $address       = trim($_POST["address"] ?? "");

    // Simple validation



if (trim($firstName) === "") {
    $errors[] = "Please enter your first name.";
}
if (trim($lastName) === "") {
    $errors[] = "Please enter your last name.";
}

if (trim($dob) === "") {
    $errors[] = "Please enter your date of birth.";
} elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $dob)) {
    $errors[] = "Please enter a valid date of birth in the format YYYY-MM-DD.";
} else {
    [$y, $m, $d] = explode('-', $dob);
    if (!checkdate((int)$m, (int)$d, (int)$y)) {
        $errors[] = "The date of birth is not a valid calendar date.";
    }
}

if (trim($parentName) === "") {
    $errors[] = "Please enter your parent's/guardian's name.";
}
if (trim($parentContact) === "") {
    $errors[] = "Please enter your parent's/guardian's contact.";
} elseif (!preg_match('/^\+?[0-9\s\-\.\(\)]+$/', $parentContact) || 
          (strlen(preg_replace('/\D+/', '', $parentContact)) < 7) ||
          (strlen(preg_replace('/\D+/', '', $parentContact)) > 15)) {
    $errors[] = "Please enter a valid parent's/guardian's contact number, has to be 7 or more digits .";
}




    // Address optional if you like; you can also require it.

    // Handle photo upload (optional but recommended)
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
                // Folder for student photos
                $uploadDir = __DIR__ . "/assets/uploads/students/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Unique filename: studentID_timestamp.ext
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

    // If no validation errors, store in session and go to step 2
    if (empty($errors)) {

        $_SESSION["enroll_step1"] = [
            "first_name"     => $firstName,
            "last_name"      => $lastName,
            "dob"            => $dob,
            "parent_name"    => $parentName,
            "parent_contact" => $parentContact,
            "address"        => $address,
            "student_photo"  => $photoFilename   // may be null
        ];

        header("Location: student_application.php?step=2");
        exit;
    }
}

/* ============================================================
   STEP 2: Year level + subjects
   - Use $_SESSION["enroll_step1"] + POST from this step
   - Insert into student_enrollments
============================================================ */
if ($step === 2 && $_SERVER["REQUEST_METHOD"] === "POST") {

    // Must have step1 data; if not, go back
    if (empty($_SESSION["enroll_step1"])) {
        header("Location: student_application.php?step=1");
        exit;
    }

    $yearLevel = trim($_POST["year_level"] ?? "");
    $stream    = trim($_POST["stream"] ?? "");  // Arts/Science for Year 11–13

    // Subject choices (different by year)
    $subjects = [];

    if ($yearLevel === "") {
        $errors[] = "Please select your year level.";
    } else {

        // Year 9–10 logic
        if ($yearLevel === "Year 9" || $yearLevel === "Year 10") {

            $lang = trim($_POST["y910_lang"] ?? "");
            $prac = trim($_POST["y910_prac"] ?? "");

            if ($lang === "") $errors[] = "Please select a language / agriculture subject.";
            if ($prac === "") $errors[] = "Please select a practical subject.";

            if (empty($errors)) {
                // Default core subjects
                $subjects = [
                    "English",
                    "Mathematics",
                    "Basic Science",
                    "Social Science",
                    "Commercial Studies",
                    $lang,
                    $prac
                ];
            }

        } else {
            // Year 11–13 logic
            if ($yearLevel === "Year 11" || $yearLevel === "Year 12" || $yearLevel === "Year 13") {

                if ($stream === "") {
                    $errors[] = "Please select Arts or Science.";
                } else {

                    if ($stream === "Arts") {
                        $arts1 = trim($_POST["arts_opt1"] ?? "");
                        $arts2 = trim($_POST["arts_opt2"] ?? "");
                        $arts3 = trim($_POST["arts_opt3"] ?? "");

                        if ($arts1 === "") $errors[] = "Please select a subject for Arts Option 1.";
                        if ($arts2 === "") $errors[] = "Please select a subject for Arts Option 2.";
                        if ($arts3 === "") $errors[] = "Please select a subject for Arts Option 3.";

                        if (empty($errors)) {
                            $subjects = [
                                "English",
                                "Mathematics",
                                $arts1,
                                $arts2,
                                $arts3
                            ];
                        }

                    } elseif ($stream === "Science") {
                        // Example Science combinations (you can customise)
                        $sci1 = trim($_POST["sci_opt1"] ?? "");
                        $sci2 = trim($_POST["sci_opt2"] ?? "");
                        $sci3 = trim($_POST["sci_opt3"] ?? "");

                        if ($sci1 === "") $errors[] = "Please select a subject for Science Option 1.";
                        if ($sci2 === "") $errors[] = "Please select a subject for Science Option 2.";
                        if ($sci3 === "") $errors[] = "Please select a subject for Science Option 3.";

                        if (empty($errors)) {
                            $subjects = [
                                "English",
                                "Mathematics",
                                $sci1,
                                $sci2,
                                $sci3
                            ];
                        }
                    }
                }

            } else {
                $errors[] = "Invalid year level selected.";
            }
        }
    }

    if (empty($errors) && empty($subjects)) {
        $errors[] = "No subjects selected. Please check your choices.";
    }

    if (empty($errors)) {

        // Data from step 1
        $step1 = $_SESSION["enroll_step1"];

        $firstName     = $step1["first_name"];
        $lastName      = $step1["last_name"];
        $dob           = $step1["dob"];
        $parentName    = $step1["parent_name"];
        $parentContact = $step1["parent_contact"];
        $address       = $step1["address"];
        $studentPhoto  = $step1["student_photo"];   // may be null

        $subjectsCsv = implode(", ", $subjects);
        $applicationId = generateApplicationId($conn);

        $sql = "INSERT INTO student_enrollments
                    (application_id, student_id, first_name, last_name, dob,
                     parent_name, parent_contact, address, student_photo,
                     year_level, stream, subjects, status, submitted_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssss",
                $applicationId,
                $studentId,
                $firstName,
                $lastName,
                $dob,
                $parentName,
                $parentContact,
                $address,
                $studentPhoto,
                $yearLevel,
                $stream,
                $subjectsCsv
            );

            if ($stmt->execute()) {
                $successMessage = "Your application has been submitted successfully! "
                                . "Your Application ID is <strong>{$applicationId}</strong>. "
                                . "Status: <strong>Pending</strong>.";

                // Clear temp session data after success
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

// Include header after $pageTitle is ready
include "partials/header.php";
?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card shadow-sm">
        <div class="card-body">

          <?php if ($step === 1): ?>
            <!-- ================= STEP 1: PERSONAL DETAILS ================= -->
            <h3 class="card-title mb-3">Enrollment Application – Step 1 of 2</h3>
            <p class="text-muted small">
              Please provide your personal details and a passport-style photo.  
              On the next step, you will choose your subjects.
            </p>

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

            <?php
            // Prefill values if form was submitted once
            $firstName     = $_POST["first_name"]     ?? "";
            $lastName      = $_POST["last_name"]      ?? "";
            $dob           = $_POST["dob"]            ?? "";
            $parentName    = $_POST["parent_name"]    ?? "";
            $parentContact = $_POST["parent_contact"] ?? "";
            $address       = $_POST["address"]        ?? "";
            ?>

            <form method="post" enctype="multipart/form-data" novalidate>
              <!-- Student account info (read-only) -->
              <div class="mb-3">
                <label class="form-label">Student ID</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($studentId); ?>" readonly>
              </div>

              <div class="mb-3">
                <label class="form-label">Account Name (from login)</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($studentName); ?>" readonly>
              </div>

              <!-- Editable personal details -->
              <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" name="first_name" id="first_name"
                       class="form-control"
                       value="<?= htmlspecialchars($firstName); ?>" required>
              </div>

              <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" name="last_name" id="last_name"
                       class="form-control"
                       value="<?= htmlspecialchars($lastName); ?>" required>
              </div>

              <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" name="dob" id="dob"
                       class="form-control"
                       value="<?= htmlspecialchars($dob); ?>" required>
              </div>

              <div class="mb-3">
                <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                <input type="text" name="parent_name" id="parent_name"
                       class="form-control"
                       value="<?= htmlspecialchars($parentName); ?>" required>
              </div>

              <div class="mb-3">
                <label for="parent_contact" class="form-label">Parent/Guardian Contact</label>
                <input type="text" name="parent_contact" id="parent_contact"
                       class="form-control"
                       value="<?= htmlspecialchars($parentContact); ?>" required>
              </div>

              <div class="mb-3">
                <label for="address" class="form-label">Home Address</label>
                <textarea name="address" id="address" rows="3"
                          class="form-control"><?= htmlspecialchars($address); ?></textarea>
              </div>

              <div class="mb-3">
                <label for="student_photo" class="form-label">
                  Student Photo (passport-style, JPG/PNG)
                </label>
                <input type="file" name="student_photo" id="student_photo"
                       class="form-control">
                <div class="form-text">
                  Optional but recommended – used for the student profile.
                </div>
              </div>

              <button type="submit" class="btn btn-primary">
                Next: Choose Subjects
              </button>
              <a href="student_dashboard.php" class="btn btn-link">
                Cancel and return to dashboard
              </a>
            </form>

          <?php else: ?>
            <!-- ================= STEP 2: SUBJECT SELECTION ================= -->
            <h3 class="card-title mb-3">Enrollment Application – Step 2 of 2</h3>
            <p class="text-muted small">
              Select your year level and subjects.  
              Year 9–10 have default core subjects.  
              Year 11–13 choose either Arts or Science.
            </p>

            <!-- Success -->
            <?php if ($successMessage): ?>
              <div class="alert alert-success">
                <?= $successMessage; ?>
                <div class="mt-2">
                  <a href="student_dashboard.php" class="btn btn-sm btn-success">
                    Back to Student Dashboard
                  </a>
                </div>
              </div>
            <?php endif; ?>

            <!-- Errors -->
            <?php if (!$successMessage && !empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <?php
            $yearLevel = $_POST["year_level"] ?? "";
            $stream    = $_POST["stream"]     ?? "";
            ?>

            <?php if (!$successMessage): ?>
            <form method="post" novalidate>

              <!-- Year Level -->
              <div class="mb-3">
                <label for="year_level" class="form-label">Year Level</label>
                <select name="year_level" id="year_level" class="form-select" required>
                  <option value="">-- Select Year Level --</option>
                  <option value="Year 9"  <?= $yearLevel === "Year 9"  ? "selected" : ""; ?>>Year 9</option>
                  <option value="Year 10" <?= $yearLevel === "Year 10" ? "selected" : ""; ?>>Year 10</option>
                  <option value="Year 11" <?= $yearLevel === "Year 11" ? "selected" : ""; ?>>Year 11</option>
                  <option value="Year 12" <?= $yearLevel === "Year 12" ? "selected" : ""; ?>>Year 12</option>
                  <option value="Year 13" <?= $yearLevel === "Year 13" ? "selected" : ""; ?>>Year 13</option>
                </select>
              </div>

              <!-- Section for Year 9–10 -->
              <div id="section_y910" class="border rounded p-3 mb-3" style="display:none;">
                <h6 class="fw-bold">Year 9–10 Subjects</h6>
                <p class="text-muted small mb-2">
                  Core subjects (automatic): <strong>English, Mathematics, Basic Science, Social Science, Commercial Studies</strong>
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

              <!-- Section for Year 11–13 -->
              <div id="section_y1113" class="border rounded p-3 mb-3" style="display:none;">
                <h6 class="fw-bold">Year 11–13 Subjects</h6>
                <p class="text-muted small mb-2">
                  Core subjects: <strong>English, Mathematics</strong>  
                  Then choose a combination from <strong>Arts</strong> or <strong>Science</strong>.
                </p>

                <!-- Stream selection -->
                <div class="mb-3">
                  <label class="form-label">Stream</label><br>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio"
                           name="stream" id="stream_arts" value="Arts"
                           <?= $stream === "Arts" ? "checked" : ""; ?>>
                    <label class="form-check-label" for="stream_arts">Arts</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio"
                           name="stream" id="stream_science" value="Science"
                           <?= $stream === "Science" ? "checked" : ""; ?>>
                    <label class="form-check-label" for="stream_science">Science</label>
                  </div>
                </div>

                <!-- Arts options -->
                <div id="arts_subjects" style="display:none;">
                  <p class="text-muted small mb-2"><strong>Arts stream:</strong></p>
                  <div class="mb-2">
                    <label class="form-label">Option 1 (choose ONE)</label>
                    <select name="arts_opt1" class="form-select">
                      <option value="">-- Select one --</option>
                      <option value="Hindi">Hindi</option>
                      <option value="Computer Studies">Computer Studies</option>
                      <option value="Home Economics">Home Economics</option>
                    </select>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Option 2 (choose ONE)</label>
                    <select name="arts_opt2" class="form-select">
                      <option value="">-- Select one --</option>
                      <option value="Accounting">Accounting</option>
                      <option value="Geography">Geography</option>
                    </select>
                  </div>
                  <div class="mb-0">
                    <label class="form-label">Option 3 (choose ONE)</label>
                    <select name="arts_opt3" class="form-select">
                      <option value="">-- Select one --</option>
                      <option value="Economics">Economics</option>
                      <option value="History">History</option>
                    </select>
                  </div>
                </div>

                <!-- Science options (example – you can customise later) -->
                <div id="science_subjects" style="display:none;">
                  <p class="text-muted small mb-2"><strong>Science stream (example set):</strong></p>
                  <div class="mb-2">
                    <label class="form-label">Option 1 (choose ONE)</label>
                    <select name="sci_opt1" class="form-select">
                      <option value="">-- Select one --</option>
                      <option value="Physics">Physics</option>
                      <option value="Biology">Biology</option>
                    </select>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Option 2 (choose ONE)</label>
                    <select name="sci_opt2" class="form-select">
                      <option value="">-- Select one --</option>
                      <option value="Chemistry">Chemistry</option>
                      <option value="Computer Studies">Computer Studies</option>
                    </select>
                  </div>
                  <div class="mb-0">
                    <label class="form-label">Option 3 (choose ONE)</label>
                    <select name="sci_opt3" class="form-select">
                      <option value="">-- Select one --</option>
                      <option value="Agriculture">Agriculture</option>
                      <option value="Geography">Geography</option>
                    </select>
                  </div>
                </div>

              </div>

              <button type="submit" class="btn btn-primary">
                Submit Application
              </button>
              <a href="student_application.php?step=1" class="btn btn-link">
                Back to Step 1
              </a>
              <a href="student_dashboard.php" class="btn btn-link">
                Cancel
              </a>
            </form>
            <?php endif; ?>

          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>

<!-- Simple JS to show/hide sections based on year & stream -->
<script>
// Run on load + on change
function updateYearSections() {
  const year = document.getElementById('year_level')?.value;
  const sec910 = document.getElementById('section_y910');
  const sec1113 = document.getElementById('section_y1113');

  if (!sec910 || !sec1113) return;

  if (year === 'Year 9' || year === 'Year 10') {
    sec910.style.display = 'block';
    sec1113.style.display = 'none';
  } else if (year === 'Year 11' || year === 'Year 12' || year === 'Year 13') {
    sec910.style.display = 'none';
    sec1113.style.display = 'block';
  } else {
    sec910.style.display = 'none';
    sec1113.style.display = 'none';
  }

  updateStreamSections();
}

function updateStreamSections() {
  const artsBox = document.getElementById('arts_subjects');
  const sciBox  = document.getElementById('science_subjects');
  if (!artsBox || !sciBox) return;

  const streamArts    = document.getElementById('stream_arts');
  const streamScience = document.getElementById('stream_science');

  if (streamArts && streamArts.checked) {
    artsBox.style.display = 'block';
    sciBox.style.display  = 'none';
  } else if (streamScience && streamScience.checked) {
    artsBox.style.display = 'none';
    sciBox.style.display  = 'block';
  } else {
    artsBox.style.display = 'none';
    sciBox.style.display  = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const yearSel = document.getElementById('year_level');
  if (yearSel) {
    yearSel.addEventListener('change', updateYearSections);
    updateYearSections();
  }

  const streamArts    = document.getElementById('stream_arts');
  const streamScience = document.getElementById('stream_science');
  if (streamArts)    streamArts.addEventListener('change', updateStreamSections);
  if (streamScience) streamScience.addEventListener('change', updateStreamSections);
});
</script>
