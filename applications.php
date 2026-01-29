<?php
/* ====================================================
   File: applications.php
   Purpose:
   - Administrator view of submitted student applications
   - Supports search and pagination
   - Only Administrator can access this page
   ==================================================== */

require "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== ACCESS CONTROL ====================
if (!isset($_SESSION["teacher_logged_in"]) || $_SESSION["teacher_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$pageTitle = "Student Applications - Koro High School";
include "partials/header.php";

// ==================== SEARCH & PAGINATION SETUP ====================
$search = trim($_GET['search'] ?? '');
$limit  = 20; // number of results per page
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$totalRows = 0;

// ==================== COUNT TOTAL RECORDS ====================
$countSql = "SELECT COUNT(*) AS total FROM student_enrollments";

if ($search !== '') {
    $countSql .= " WHERE 
        application_id LIKE CONCAT('%', ?, '%')
        OR student_id LIKE CONCAT('%', ?, '%')
        OR first_name LIKE CONCAT('%', ?, '%')
        OR last_name LIKE CONCAT('%', ?, '%')";
}

$stmtCount = $conn->prepare($countSql);
if ($search !== '') {
    $stmtCount->bind_param("ssss", $search, $search, $search, $search);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
if ($row = $resultCount->fetch_assoc()) {
    $totalRows = (int)$row['total'];
}
$stmtCount->close();

// ==================== MAIN DATA QUERY ====================
$sql = "SELECT 
            application_id,
            student_id,
            first_name,
            last_name,
            year_level,
            status,
            submitted_at
        FROM student_enrollments";

if ($search !== '') {
    $sql .= " WHERE 
        application_id LIKE CONCAT('%', ?, '%')
        OR student_id LIKE CONCAT('%', ?, '%')
        OR first_name LIKE CONCAT('%', ?, '%')
        OR last_name LIKE CONCAT('%', ?, '%')";
}

// 🔹 Add pagination control in SQL
$sql .= " ORDER BY submitted_at DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($search !== '') {
    $stmt->bind_param("ssssii", $search, $search, $search, $search, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!-- ==================== PAGE BODY ==================== -->
<div class="container my-4">

  <h2 class="mb-3">Student Applications</h2>
  <p class="text-muted small">View, approve, or reject student enrollment applications.</p>

  <div class="card shadow-sm">
    <div class="card-body">

      <!-- Search BAR -->
      <form method="get" class="mb-3 d-flex align-items-center gap-2">
        <input type="text" name="search" class="form-control"
               placeholder="Search by Student Name or Application ID"
               value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if (!empty($_GET['search'])): ?>
          <a href="applications.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        <?php endif; ?>
      </form>

      <!-- Applications Table -->
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
                  $fullName = trim(($row["first_name"] ?? "") . " " . ($row["last_name"] ?? ""));
                  $statusClass = match($row["status"]) {
                    "pending"  => "warning",
                    "approved" => "success",
                    "rejected" => "danger",
                    default    => "secondary",
                  };
                ?>
                <tr>
                  <td><?= htmlspecialchars($row["application_id"]); ?></td>
                  <td><?= htmlspecialchars($fullName !== "" ? $fullName : "Unknown"); ?></td>
                  <td><?= htmlspecialchars($row["student_id"]); ?></td>
                  <td><?= htmlspecialchars($row["year_level"]); ?></td>
                  <td><span class="badge bg-<?= $statusClass; ?>"><?= htmlspecialchars(ucfirst($row["status"])); ?></span></td>
                  <td><?= htmlspecialchars($row["submitted_at"]); ?></td>
                  <td><a href="application_view.php?app_id=<?= urlencode($row["application_id"]); ?>" class="btn btn-sm btn-primary">View</a></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center text-muted py-3">No applications found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <?php
        $totalPages = ceil($totalRows / $limit);
        if ($totalPages > 1):
        ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-muted small">
            Showing <?= ($offset + 1) ?>–<?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> results
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a></li>
              <?php endif; ?>

              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
              </li>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
              <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a></li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>

