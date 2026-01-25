<?php
/* ====================================================
   File: application_view.php
   Purpose:
   - Show full details of a single student application
   - Allow teacher to:
       * Approve application
       * Reject application (with reason)
       * Upload offer letter file
   - Only teachers can access this page
   ==================================================== */

require "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect page: only logged-in teachers
if (!isset($_SESSION["teacher_logged_in"]) || $_SESSION["teacher_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

// Teacher ID (used for reviewed_by)
$teacherId = $_SESSION["teacher_id"] ?? null;

$pageTitle = "View Application - Koro High School";

$messages = [];
$errors   = [];

// Get application ID from query string
$appId = $_GET["app_id"] ?? "";
$appId = trim($appId);

if ($appId === "") {
    // No application ID, redirect back to list
    header("Location: applications.php");
    exit;
}

/* ====================================================
   Handle POST Actions: Approve / Reject / Upload letter
   ==================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Approve application
    if (isset($_POST["action"]) && $_POST["action"] === "approve") {

        $sql = "UPDATE student_enrollments
                SET status = 'approved',
                    reviewed_by = ?,
                    reviewed_at = NOW()
                WHERE application_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $teacherId, $appId);
            if ($stmt->execute()) {
                $messages[] = "Application approved successfully.";
            } else {
                $errors[] = "Failed to approve application.";
            }
            $stmt->close();
        }

    }

    // Reject application
    if (isset($_POST["action"]) && $_POST["action"] === "reject") {

        $reason = trim($_POST["teacher_comment"] ?? "");

        if ($reason === "") {
            $errors[] = "Please enter a reason for rejection.";
        } else {
            $sql = "UPDATE student_enrollments
                    SET status = 'rejected',
                        teacher_comment = ?,
                        reviewed_by = ?,
                        reviewed_at = NOW()
                    WHERE application_id = ?";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sss", $reason, $teacherId, $appId);
                if ($stmt->execute()) {
                    $messages[] = "Application rejected successfully.";
                } else {
                    $errors[] = "Failed to reject application.";
                }
                $stmt->close();
            }
        }
    }

    // Upload offer letter
    if (isset($_POST["action"]) && $_POST["action"] === "upload_offer") {

        if (!isset($_FILES["offer_letter"]) || $_FILES["offer_letter"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Please select a file to upload.";
        } else {

            $file     = $_FILES["offer_letter"];
            $fileName = $file["name"];
            $tmpPath  = $file["tmp_name"];

            // Allowed extensions: pdf, doc, docx, jpg, png
            $allowedExt = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
            $ext        = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                $errors[] = "Invalid file type. Allowed: pdf, doc, docx, jpg, jpeg, png.";
            } else {

                // Ensure directory exists
                $uploadDir = "assets/uploads/offer_letters";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Create a unique file name using application ID
                $newName  = $appId . "_" . time() . "." . $ext;
                $destPath = $uploadDir . "/" . $newName;

                if (move_uploaded_file($tmpPath, $destPath)) {

                    // Save relative path in DB (or only file name, up to you)
                    $sql = "UPDATE student_enrollments
                            SET offer_letter = ?
                            WHERE application_id = ?";

                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("ss", $destPath, $appId);
                        if ($stmt->execute()) {
                            $messages[] = "Offer letter uploaded successfully.";
                        } else {
                            $errors[] = "Failed to update offer letter in database.";
                        }
                        $stmt->close();
                    }

                } else {
                    $errors[] = "Failed to move uploaded file.";
                }
            }
        }
    }
}

/* ====================================================
   Fetch application details from DB
   ==================================================== */

$sql = "SELECT 
            se.*,
            sa.full_name AS account_full_name,
            sa.email     AS student_email
        FROM student_enrollments se
        LEFT JOIN student_accounts sa
            ON se.student_id = sa.student_id
        WHERE se.application_id = ?
        LIMIT 1";

$app = null;

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $appId);
    $stmt->execute();
    $result = $stmt->get_result();
    $app    = $result->fetch_assoc();
    $stmt->close();
}

if (!$app) {
    // Application not found
    include "partials/header.php";
    echo '<div class="container my-4"><div class="alert alert-danger">Application not found.</div></div>';
    include "partials/footer.php";
    exit;
}

// Build student full name from enrollment table (first_name + last_name)
$studentName = trim(($app["first_name"] ?? "") . " " . ($app["last_name"] ?? ""));

// Fallback to account_full_name if first/last missing
if ($studentName === "" && !empty($app["account_full_name"])) {
    $studentName = $app["account_full_name"];
}

// Status badge class
$statusClass = "secondary";
if ($app["status"] === "pending")  $statusClass = "warning";
if ($app["status"] === "approved") $statusClass = "success";
if ($app["status"] === "rejected") $statusClass = "danger";

include "partials/header.php";
?>

<div class="container my-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-1">Application Details</h2>
      <p class="text-muted small mb-0">
        Application ID: <strong><?= htmlspecialchars($app["application_id"]); ?></strong>
      </p>
    </div>
    <a href="applications.php" class="btn btn-outline-secondary btn-sm">
      Back to Applications
    </a>
  </div>

  <!-- Messages -->
  <?php if (!empty($messages)): ?>
    <div class="alert alert-success">
      <ul class="mb-0 small">
        <?php foreach ($messages as $m): ?>
          <li><?= htmlspecialchars($m); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0 small">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Top section: Student info + Application status -->
  <div class="row g-3 mb-3">

    <!-- Student Information Card -->
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">

          <h5 class="fw-bold mb-3">Student Information</h5>

          <p class="mb-1">
            <span class="text-muted small">Name:</span><br>
            <span class="fw-semibold"><?= htmlspecialchars($studentName); ?></span>
          </p>

          <p class="mb-1">
            <span class="text-muted small">Student ID:</span><br>
            <span class="fw-semibold"><?= htmlspecialchars($app["student_id"]); ?></span>
          </p>

          <p class="mb-1">
            <span class="text-muted small">Year Level:</span><br>
            <span class="fw-semibold"><?= htmlspecialchars($app["year_level"]); ?></span>
          </p>

          <?php if (!empty($app["student_email"])): ?>
            <p class="mb-1">
              <span class="text-muted small">Email:</span><br>
              <span class="fw-semibold"><?= htmlspecialchars($app["student_email"]); ?></span>
            </p>
          <?php endif; ?>

          <?php if (!empty($app["dob"])): ?>
            <p class="mb-1">
              <span class="text-muted small">Date of Birth:</span><br>
              <span class="fw-semibold"><?= htmlspecialchars($app["dob"]); ?></span>
            </p>
          <?php endif; ?>

          <?php if (!empty($app["parent_name"])): ?>
            <p class="mb-1">
              <span class="text-muted small">Parent/Guardian:</span><br>
              <span class="fw-semibold"><?= htmlspecialchars($app["parent_name"]); ?></span>
            </p>
          <?php endif; ?>

          <?php if (!empty($app["parent_contact"])): ?>
            <p class="mb-1">
              <span class="text-muted small">Parent Contact:</span><br>
              <span class="fw-semibold"><?= htmlspecialchars($app["parent_contact"]); ?></span>
            </p>
          <?php endif; ?>

          <?php if (!empty($app["address"])): ?>
            <p class="mb-0">
              <span class="text-muted small">Address:</span><br>
              <span class="fw-semibold"><?= nl2br(htmlspecialchars($app["address"])); ?></span>
            </p>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- Application Status Card -->
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">

          <h5 class="fw-bold mb-3">Application Status</h5>

          <p class="mb-1">
            <span class="text-muted small">Current Status:</span><br>
            <span class="badge bg-<?= $statusClass; ?> px-3 py-2">
              <?= htmlspecialchars(ucfirst($app["status"])); ?>
            </span>
          </p>

          <?php if (!empty($app["submitted_at"])): ?>
            <p class="mb-1">
              <span class="text-muted small">Submitted At:</span><br>
              <span class="fw-semibold"><?= htmlspecialchars($app["submitted_at"]); ?></span>
            </p>
          <?php endif; ?>

          <?php if (!empty($app["reviewed_by"])): ?>
            <p class="mb-1">
              <span class="text-muted small">Reviewed By (Teacher ID):</span><br>
              <span class="fw-semibold"><?= htmlspecialchars($app["reviewed_by"]); ?></span>
            </p>
          <?php endif; ?>

          <?php if (!empty($app["reviewed_at"])): ?>
            <p class="mb-1">
              <span class="text-muted small">Reviewed At:</span><br>
              <span class="fw-semibold"><?= htmlspecialchars($app["reviewed_at"]); ?></span>
            </p>
          <?php endif; ?>

          <?php if (!empty($app["teacher_comment"])): ?>
            <p class="mb-0">
              <span class="text-muted small">Teacher Comment:</span><br>
              <span class="fw-semibold"><?= nl2br(htmlspecialchars($app["teacher_comment"])); ?></span>
            </p>
          <?php endif; ?>

        </div>
      </div>
    </div>

  </div>

  <!-- Subjects section (auto-detect subject-related fields) -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h5 class="fw-bold mb-3">Subjects Applied For</h5>

      <?php
        // Collect subject-related fields from the application row
        $subjectLines = [];

        foreach ($app as $field => $value) {
            if ($value === null || $value === "") {
                continue;
            }

            // Treat any field containing these strings as "subject info"
            if (
                stripos($field, "subject")  !== false || //stripos finds the position of the first occurrence of a substring within another string
                stripos($field, "stream")   !== false ||
                stripos($field, "elective") !== false ||
                stripos($field, "option")   !== false
            ) {
                // Make a nicer label from the column name
                $label = ucwords(str_replace("_", " ", $field));
                $subjectLines[] = $label . ": " . $value;
            }
        }

        if (!empty($subjectLines)) {
            echo '<ul class="small mb-0">';
            foreach ($subjectLines as $line) {
                echo "<li>" . htmlspecialchars($line) . "</li>";
            }
            echo "</ul>";
        } else {
            echo '<p class="text-muted small mb-0">No subject information stored for this application.</p>';
        }
      ?>

    </div>
  </div>


  <!-- Offer letter and actions row -->
  <div class="row g-3">

    <!-- Offer Letter Card -->
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Offer Letter</h5>

          <?php if (!empty($app["offer_letter"])): ?>
            <p class="small mb-2">
              An offer letter has been uploaded for this application.
            </p>
            <a href="<?= htmlspecialchars($app["offer_letter"]); ?>" target="_blank"
               class="btn btn-sm btn-outline-primary">
              View / Download Offer Letter
            </a>
          <?php else: ?>
            <p class="text-muted small mb-2">
              No offer letter uploaded yet. You can upload one below.
            </p>
          <?php endif; ?>

          <!-- Upload form -->
          <form method="post" enctype="multipart/form-data" class="mt-3">
            <div class="mb-2">
              <label class="form-label small">Upload Offer Letter (PDF, DOC, JPG, PNG)</label>
              <input type="file" name="offer_letter" class="form-control form-control-sm">
            </div>
            <button type="submit" name="action" value="upload_offer"
                    class="btn btn-sm btn-secondary">
              Upload Offer Letter
            </button>
          </form>

        </div>
      </div>
    </div>

    <!-- Approve / Reject Card -->
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Review Actions</h5>

          <?php if ($app["status"] === "pending"): ?>

            <p class="text-muted small">
              You can approve or reject this application. If rejecting, please provide a reason so the student can correct and reapply.
            </p>

            <!-- Approve form -->
            <form method="post" class="mb-3">
              <button type="submit"
                      name="action"
                      value="approve"
                      class="btn btn-success btn-sm">
                Approve Application
              </button>
            </form>

            <!-- Reject form -->
            <form method="post">
              <div class="mb-2">
                <label class="form-label small">Reason for Rejection</label>
                <textarea name="teacher_comment"
                          rows="3"
                          class="form-control"
                          placeholder="Explain why the application is rejected so the student can improve and reapply."></textarea>
              </div>
              <button type="submit"
                      name="action"
                      value="reject"
                      class="btn btn-danger btn-sm">
                Reject Application
              </button>
            </form>

          <?php else: ?>

            <p class="text-muted small mb-1">
              This application has already been reviewed.
            </p>
            <p class="mb-0">
              Current status:
              <span class="badge bg-<?= $statusClass; ?>">
                <?= htmlspecialchars(ucfirst($app["status"])); ?>
              </span>
            </p>

          <?php endif; ?>

        </div>
      </div>
    </div>

  </div>

</div>

<?php include "partials/footer.php"; ?>
