<?php
/* ====================================================
   File: applications.php
   Purpose:
   - Teacher view of submitted student applications
   - Shows all applications in a management-style table
   - Only teachers can access this page
   ==================================================== */

require "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Protect page: teacher must be logged in
if (!isset($_SESSION["teacher_logged_in"]) || $_SESSION["teacher_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Student Applications - Koro High School";

include "partials/header.php";

// Fetch all applications from student_enrollments
// Adjust submitted_at if your column name is different or does not exist
$sql = "SELECT 
            application_id,
            student_id,
            first_name,
            last_name,
            year_level,
            status,
            submitted_at
        FROM student_enrollments
        ORDER BY submitted_at DESC";

$result = $conn->query($sql);
?>

<div class="container my-4">

    <h2 class="mb-3">Student Applications</h2>
    <p class="text-muted small">
        View, approve, or reject student enrollment applications.
    </p>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle small">

                    <thead class="table-primary">
                        <tr>
                            <th>App ID</th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Year Level</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>

                                <?php
                                    // Combine first + last name
                                    $fullName = trim(($row["first_name"] ?? "") . " " . ($row["last_name"] ?? ""));

                                    // Status badge color
                                    $statusClass = "secondary";
                                    if ($row["status"] === "pending")  $statusClass = "warning";
                                    if ($row["status"] === "approved") $statusClass = "success";
                                    if ($row["status"] === "rejected") $statusClass = "danger";
                                ?>

                                <tr>
                                    <td><?= htmlspecialchars($row["application_id"]); ?></td>
                                    <td><?= htmlspecialchars($fullName !== "" ? $fullName : "Unknown"); ?></td>
                                    <td><?= htmlspecialchars($row["student_id"]); ?></td>
                                    <td><?= htmlspecialchars($row["year_level"]); ?></td>

                                    <td>
                                        <span class="badge bg-<?= $statusClass; ?>">
                                            <?= htmlspecialchars(ucfirst($row["status"])); ?>
                                        </span>
                                    </td>

                                    <td><?= htmlspecialchars($row["submitted_at"]); ?></td>

                                    <td>
                                        <a href="application_view.php?app_id=<?= urlencode($row["application_id"]); ?>"
                                           class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    No applications found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<?php include "partials/footer.php"; ?>
