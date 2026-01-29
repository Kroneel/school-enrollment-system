<?php
/* ====================================================
   File: dashboard.php
   Purpose:
   - Administrator Dashboard (Profile + Quick Info + Actions)
   ==================================================== */

$pageTitle = "Administrator Dashboard - My School";

// Include header (starts session + navbar)
include "partials/header.php";

// Protect page
if (!isset($_SESSION["teacher_logged_in"]) || $_SESSION["teacher_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

// Administrator info
$teacherName  = $_SESSION["teacher_name"]  ?? "Teacher";
$teacherId    = $_SESSION["teacher_id"]    ?? "N/A";
$teacherEmail = $_SESSION["teacher_email"] ?? "N/A";

$defaultPhoto = "assets/images/teacher_default.png";
$teacherPhoto = $_SESSION["teacher_photo"] ?? $defaultPhoto;

// Load application statistics
require "db.php";

$totalApplications    = 0;
$pendingApplications  = 0;
$approvedApplications = 0;
$rejectedApplications = 0;

$sql = "SELECT 
          COUNT(*) AS total,
          SUM(status = 'pending')   AS pending,
          SUM(status = 'approved')  AS approved,
          SUM(status = 'rejected')  AS rejected
        FROM student_enrollments";

if ($result = $conn->query($sql)) {
    if ($row = $result->fetch_assoc()) {
        $totalApplications    = (int)$row["total"];
        $pendingApplications  = (int)$row["pending"];
        $approvedApplications = (int)$row["approved"];
        $rejectedApplications = (int)$row["rejected"];
    }
}

$todayDate = date("l, d M Y");
?>

<div class="container my-4">

  <!-- Page heading -->
  <div class="row mb-3">
    <div class="col-12">
      <h2 class="mb-1">Administrator Dashboard</h2>
      <p class="text-muted mb-0">
        Welcome back, <?= htmlspecialchars($teacherName); ?>. Manage students and view school updates here.
      </p>
    </div>
  </div>

  <!-- Top section: Profile + Quick Info + Applications Overview (single card) -->
  <div class="row g-3">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          
          <div class="row g-3 align-items-center">

            <!-- Left: Teacher Profile -->
            <div class="col-md-4 d-flex align-items-center gap-3 border-md-end">
              <img src="<?= htmlspecialchars($teacherPhoto); ?>"
                   alt="Teacher photo"
                   class="profile-photo"
                   onerror="this.src='assets/images/teacher_default.png';">

              <div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($teacherName); ?></h5>
                <div class="text-muted small">
                  Administrator ID: <strong><?= htmlspecialchars($teacherId); ?></strong>
                </div>
                <div class="text-muted small">
                  Email: <?= htmlspecialchars($teacherEmail); ?>
                </div>

                <div class="mt-2">
                  <a href="teacher_profile.php" class="btn btn-sm btn-outline-primary">
                    Upload Picture
                  </a>
                </div>
              </div>
            </div>

            <!-- Right: Quick Info + Applications Overview -->
            <div class="col-md-8">
              <div class="row g-3">

                <!-- Quick Info -->
                <div class="col-md-6 border-md-end">
                  <h5 class="fw-bold mb-2">Quick Info</h5>

                  <div class="mb-2">
                    <span class="text-muted small">Today:</span>
                    <div class="fw-semibold"><?= htmlspecialchars($todayDate); ?></div>
                  </div>

                  <div class="mb-2">
                    <span class="text-muted small">Status:</span>
                    <div class="fw-semibold text-success">Logged In</div>
                  </div>

                  <p class="text-muted small mb-0">
                    Use <strong>View Applications</strong> to review student enrollment requests.
                  </p>
                </div>

                <!-- Applications Overview -->
                <div class="col-md-6">
                  <h5 class="fw-bold mb-2">Applications Overview</h5>

                  <p class="small mb-1">
                    <span class="text-muted">Total Applications:</span>
                    <span class="fw-semibold"><?= $totalApplications; ?></span>
                  </p>

                  <p class="small mb-1">
                    <span class="text-muted">Pending Approval:</span>
                    <span class="fw-semibold text-warning"><?= $pendingApplications; ?></span>
                  </p>

                  <p class="small mb-1">
                    <span class="text-muted">Approved:</span>
                    <span class="fw-semibold text-success"><?= $approvedApplications; ?></span>
                  </p>

                  <p class="small mb-0">
                    <span class="text-muted">Rejected:</span>
                    <span class="fw-semibold text-danger"><?= $rejectedApplications; ?></span>
                  </p>
                </div>

              </div> <!-- /inner row -->
            </div> <!-- /col-md-8 -->

          </div> <!-- /row g-3 -->

        </div>
      </div>
    </div>
  </div>


  <!-- Action cards row -->
  <div class="row g-3 mt-3">

    <!-- Register Student -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold">Register Student</h5>
          <p class="text-muted small">
            Enroll a new student by entering details and uploading a passport photo.
          </p>
          <a href="student_register.php" class="btn btn-primary btn-sm">
            Go to Registration
          </a>
        </div>
      </div>
    </div>

    <!-- Search Student -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold">Search Student</h5>
          <p class="text-muted small">
            Search students by name or index number and view profile details.
          </p>
          <a href="student_search.php" class="btn btn-primary btn-sm">
            Search Now
          </a>
        </div>
      </div>
    </div>

    <!-- View Applications -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold">View Applications</h5>
          <p class="text-muted small">
            Review all student enrollment applications and approve or reject them.
          </p>
          <a href="applications.php" class="btn btn-primary btn-sm">
            Open Applications
          </a>
        </div>
      </div>
    </div>

    <!-- School Notices -->
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold">School Notices</h5>
          <p class="text-muted small mb-2">
            Quick updates for teachers.
          </p>
          <ul class="small text-muted mb-3">
            <li>Term 1 assessments start soon.</li>
            <li>ICT lab schedule updated for next week.</li>
            <li>Staff meeting on Friday afternoon.</li>
          </ul>
          <a href="#" class="btn btn-outline-primary btn-sm disabled">
            View All Notices (Coming Soon)
          </a>
        </div>
      </div>
    </div>

  </div> <!-- /row g-3 -->

</div>

<?php include "partials/footer.php"; ?>
