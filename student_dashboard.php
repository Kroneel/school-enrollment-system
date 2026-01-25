<?php
require "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["student_logged_in"]) || $_SESSION["student_logged_in"] !== true) {
    header("Location: student_login.php");
    exit;
}

$pageTitle = "Student Dashboard - My School";

$studentName  = $_SESSION["student_name"];
$studentId    = $_SESSION["student_id"];
$studentEmail = $_SESSION["student_email"];

$defaultPhoto = "assets/images/student_default.png";
$studentPhoto = $_SESSION["student_photo"] ?? $defaultPhoto;

$todayDate = date("l, d M Y");

// ----------------------------------------------------
// Load latest application
// ----------------------------------------------------
$applicationId      = null;
$submittedAt        = null;
$applicationStatus  = null;
$rejectionReason    = null;
$offerLetter        = null; // holds offer letter filename from DB

// Note: we now also select offer_letter (column must exist in student_enrollments)
$sql = "SELECT application_id, status, submitted_at, rejection_reason, offer_letter
        FROM student_enrollments
        WHERE student_id = ?
        ORDER BY submitted_at DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $applicationId     = $row["application_id"];
    $submittedAt       = $row["submitted_at"];
    $applicationStatus = $row["status"];
    $rejectionReason   = $row["rejection_reason"];
    $offerLetter       = $row["offer_letter"]; // filename of uploaded offer letter
}

$stmt->close();

// ----------------------------------------------------
// Status UI Mapping
// ----------------------------------------------------
$statusBadgeClass = "secondary";
$statusText       = "You have not submitted any application yet.";
$statusDetail     = "";

if (!empty($applicationId)) {
    if ($applicationStatus === "pending") {
        $statusBadgeClass = "warning";
        $statusText       = "Your application is pending review.";
        $statusDetail     = "A teacher has not yet approved or rejected your application.";
    }
    elseif ($applicationStatus === "approved") {
        $statusBadgeClass = "success";
        $statusText       = "Your application has been approved.";
        $statusDetail     = "You may download your offer letter if uploaded.";
    }
    elseif ($applicationStatus === "rejected") {
        $statusBadgeClass = "danger";
        $statusText       = "Your application has been rejected.";
        $statusDetail     = "Review the reason below and submit a new corrected application.";
    }
}

include "partials/header.php";
?>

<div class="container my-4">

  <!-- Heading -->
  <div class="mb-3">
    <h2>Student Dashboard</h2>
    <p class="text-muted">
      Welcome, <?= htmlspecialchars($studentName) ?>. Track your enrollment status here.
    </p>
  </div>

  <div class="row g-3">

    <!-- LEFT COLUMN -->
    <div class="col-lg-6">

      <!-- Profile Card -->
      <div class="card shadow-sm mb-3">
        <div class="card-body d-flex align-items-center gap-3">
          <img src="<?= htmlspecialchars($studentPhoto) ?>"
               class="profile-photo"
               alt="photo"
               onerror="this.src='assets/images/student_default.png';">

          <div>
            <h5 class="fw-bold"><?= htmlspecialchars($studentName) ?></h5>
            <div class="text-muted small">Student ID: <strong><?= $studentId ?></strong></div>
            <div class="text-muted small">Email: <?= htmlspecialchars($studentEmail) ?></div>
          </div>
        </div>
      </div>

      <!-- Start Enrollment Application -->
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="fw-bold">Start Enrollment Application</h5>
          <p class="text-muted small">
            Choose your year level and preferred subjects, then submit for teacher approval.
          </p>

          <?php
          // Decide button state based on latest application status
          $disableButton = false;
          $disableReason = "";

          if (!empty($applicationId)) {
              if ($applicationStatus === "pending") {
                  $disableButton = true;
                  $disableReason = "Your application is currently pending approval.";
              }
              elseif ($applicationStatus === "approved") {
                  $disableButton = true;
                  $disableReason = "Your application has been approved. No need to reapply.";
              }
              elseif ($applicationStatus === "rejected") {
                  $disableButton = false; // allow reapply after rejection
              }
          }
          ?>

          <?php if ($disableButton): ?>
              <button class="btn btn-secondary w-100" disabled>
                  Start Application (Unavailable)
              </button>
              <p class="text-danger small mt-2 mb-0">
                  <?= htmlspecialchars($disableReason); ?>
              </p>
          <?php else: ?>
              <a href="student_application.php" class="btn btn-primary w-100">
                  Start Application
              </a>
          <?php endif; ?>

        </div>
      </div>

      <!-- School Announcements -->
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h5 class="fw-bold mb-3">School Announcements</h5>

          <ul class="list-unstyled mb-0 announcements-list">

            <li class="announcement-item mb-3">
              <h6 class="fw-semibold mb-1">Term 1 Classes Begin</h6>
              <p class="text-muted small mb-0">
                All students are advised that Term 1 officially begins on 
                <strong>Monday, 5th February 2026</strong>. 
              </p>
            </li>

            <hr class="announcement-divider">

            <li class="announcement-item mb-3">
              <h6 class="fw-semibold mb-1">Uniform Collection</h6>
              <p class="text-muted small mb-0">
                New school uniforms will be available for collection from 
                <strong>1st–4th February</strong> at the school hall.
              </p>
            </li>

            <hr class="announcement-divider">

            <li class="announcement-item">
              <h6 class="fw-semibold mb-1">ICT Lab Access Hours</h6>
              <p class="text-muted small mb-0">
                The computer lab will remain open from <strong>8:00 AM to 5:00 PM</strong> 
                for students who need assistance with their enrollment.
              </p>
            </li>

          </ul>
        </div>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-lg-6">
      <!-- Status Card -->
      <div class="card shadow-sm">
        <div class="card-body">

          <h5 class="fw-bold">Enrollment Status</h5>

          <p class="mb-1 text-muted small">Today:</p>
          <p class="fw-semibold"><?= $todayDate ?></p>

          <p class="mb-1 text-muted small">Current Application:</p>
          <span class="badge bg-<?= $statusBadgeClass ?>"><?= ucfirst($applicationStatus ?? "None") ?></span>

          <?php if ($applicationId): ?>
            <div class="mt-3">
              <p class="text-muted small mb-1">Application ID:</p>
              <p class="fw-semibold"><?= $applicationId ?></p>

              <p class="text-muted small mb-1">Submitted At:</p>
              <p class="fw-semibold"><?= date("d-m-Y H:i:s", strtotime($submittedAt)) ?></p>
            </div>

            <p class="mt-3 text-muted small"><?= $statusDetail ?></p>

<?php if ($applicationStatus === "approved"): ?>

    <?php if (!empty($offerLetter)): ?>

        <?php
        // $offerLetter already contains something like:
        // "assets/uploads/offer_letters/APP0007_1769031637.docx"

        // Clean it to avoid any weird paths
        $offerRel = $offerLetter;

        // Absolute path on the server
        $offerAbs = __DIR__ . "/" . $offerRel;
        ?>

            <?php if (file_exists($offerAbs)): ?>
                <!-- File exists: show download button -->
                <a href="<?= htmlspecialchars($offerRel); ?>"
                  class="btn btn-success btn-sm mt-2"
                  target="_blank"
                  download>
                  Download Offer Letter
                </a>
            <?php else: ?>
                <!-- File path is in DB but file missing on disk -->
                <p class="text-danger small mt-2">
                  An offer letter has been recorded for your application, but the file
                  is currently not available on the server. Please contact the school office.
                </p>
            <?php endif; ?>

        <?php else: ?>
            <!-- No offer_letter set for approved application -->
            <p class="text-muted small mt-2">
              Your application has been approved, but an offer letter has not been uploaded yet.
              Please check again later.
            </p>
             <?php endif; ?>

          <?php endif; ?>


            <?php if ($applicationStatus === "rejected"): ?>
              <div class="alert alert-danger mt-3">
                <strong>Reason for rejection:</strong><br>
                <?= htmlspecialchars($rejectionReason) ?>
              </div>

              <a href="student_application.php" class="btn btn-warning btn-sm mt-2">
                Reapply
              </a>
            <?php endif; ?>

          <?php else: ?>
            <p class="text-muted small mt-3">
              You have not submitted any application yet.
            </p>
          <?php endif; ?>

        </div>
      </div>

     <!-- Ministry of Education Link Card -->
        <div class="card shadow-sm mt-3">
          <div class="card-body">
            <h6 class="fw-bold mb-2">Ministry of Education</h6>

            <p class="text-muted small mb-3">
              Visit the Ministry of Education website to view official education-related
              news, policies, announcements, and updates.
            </p>

            <a href="https://www.education.gov.fj"
              target="_blank"
              class="btn btn-outline-primary btn-sm w-100">
              Visit Ministry Website
            </a>
          </div>
        </div>


        <!-- Universities Information Card -->
        <div class="card shadow-sm mt-3">
          <div class="card-body">
            <h6 class="fw-bold mb-2">Fiji Universities</h6>

            <p class="text-muted small mb-3">
              Explore Fiji’s top universities to learn about programmes, admissions,
              and future study opportunities.
            </p>

            <div class="d-grid gap-2">
              <a href="https://www.usp.ac.fj"
                target="_blank"
                class="btn btn-outline-primary btn-sm">
                University of the South Pacific (USP)
              </a>

              <a href="https://www.fnu.ac.fj"
                target="_blank"
                class="btn btn-outline-primary btn-sm">
                Fiji National University (FNU)
              </a>

              <a href="https://www.unifiji.ac.fj"
                target="_blank"
                class="btn btn-outline-primary btn-sm">
                University of Fiji
              </a>
            </div>
          </div>
        </div>


    </div>

  </div>

</div>

<?php include "partials/footer.php"; ?>
