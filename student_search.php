<?php
// Load database connection
require "db.php";

// Load header and layout
include "partials/header.php";

// Start session if not already running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow teachers to access this page
if (!isset($_SESSION["teacher_logged_in"])) {
    header("Location: login.php");
    exit;
}

// Capture search query from the form
$query = $_GET["query"] ?? "";

// Placeholder for student search result
$student = null;

// Only run the search if query is not empty
if ($query !== "") {

    // SQL joins student_accounts with latest application info in student_enrollments
$sql = "SELECT 
            sa.student_id,
            sa.full_name,
            sa.email,
            sa.photo_filename AS account_photo,
            se.application_id,
            se.first_name,
            se.last_name,
            se.year_level,
            se.stream,
            se.subjects,
            se.status,
            se.submitted_at,
            se.rejection_reason,
            se.offer_letter,
            se.student_photo AS app_photo
        FROM student_accounts sa
        LEFT JOIN student_enrollments se 
            ON sa.student_id = se.student_id
        WHERE sa.student_id LIKE ?
           OR sa.full_name LIKE ?
        ORDER BY se.submitted_at DESC
        LIMIT 1";

    // Prepare SQL query
$stmt = $conn->prepare($sql);

    // Use LIKE for partial name searching
$like = "%$query%";
    // Bind parameters to prevent SQL injection
$stmt->bind_param("ss", $like, $like);

    // Execute the SQL query
    $stmt->execute();

    // Fetch the result from query execution
    $result = $stmt->get_result();

    // If student found, store record in $student array
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
    }
}
?>

<div class="container my-4">

    <h3 class="mb-3">Search Student</h3>

    <!-- Search form -->
    <form method="GET" class="d-flex gap-2 mb-3">
        <input type="text"
               name="query"
               class="form-control"
               placeholder="Enter Student ID or Name"
               value="<?= htmlspecialchars($query); ?>"
               required>
        <button class="btn btn-primary">Search</button>
    </form>

    <?php if ($student): ?>

    <!-- Student result card -->
    <div class="card shadow-sm">
        <div class="card-body d-flex gap-3">

            <!-- Student photo (account photo or default image) -->
            <img src="<?= $student["account_photo"] 
                        ? 'assets/uploads/students/'.$student["account_photo"] 
                        : 'assets/images/student_default.png'; ?>"
                 class="profile-photo"
                 onerror="this.src='assets/images/student_default.png';">

            <div>
                <!-- Basic student details -->
                <h5 class="fw-bold"><?= htmlspecialchars($student["full_name"]); ?></h5>
                <p class="text-muted small mb-1">Student ID: <?= $student["student_id"]; ?></p>
                <p class="text-muted small mb-3">Email: <?= $student["email"]; ?></p>

                <?php if ($student["application_id"]): ?>
                    <!-- Show application information if found -->
                    <p><strong>Application ID:</strong> <?= $student["application_id"]; ?></p>
                    <p><strong>Status:</strong> <?= ucfirst($student["status"]); ?></p>
                    <p><strong>Year Level:</strong> <?= $student["year_level"]; ?></p>
                    <p><strong>Stream:</strong> <?= $student["stream"]; ?></p>
                    <p><strong>Subjects:</strong> <?= $student["subjects"]; ?></p>
                <?php else: ?>
                    <!-- Message if no enrollment application exists -->
                    <p class="text-muted">No enrollment application has been submitted.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php elseif ($query !== ""): ?>

    <!-- Message if no student found -->
    <div class="alert alert-warning">
        No student found matching "<strong><?= htmlspecialchars($query); ?></strong>"
    </div>

    <?php endif; ?>

</div>

<?php include "partials/footer.php"; ?>
