<?php
/* ====================================================
   File: teacher_profile.php
   Purpose:
   - Allow a logged-in teacher to upload/change photo
   - Save file locally in assets/uploads/teachers/
   - Store only filename in teachers.photo_filename
   - Update session so dashboard shows the new photo
   ==================================================== */

require "db.php"; // DB connection ($conn)

// Start session early (in case header.php not loaded yet)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1) Protect page – must be logged in
if (!isset($_SESSION["teacher_logged_in"]) || $_SESSION["teacher_logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$teacherId   = $_SESSION["teacher_id"]   ?? null;
$teacherName = $_SESSION["teacher_name"] ?? "Teacher";

if (!$teacherId) {
    // Something wrong with session → logout for safety
    header("Location: logout.php");
    exit;
}

$errors = [];
$successMessage = "";

// Default profile image
$defaultPhotoPath = "assets/images/teacher_default.png";
$currentPhotoPath = $defaultPhotoPath;
$teacherEmailDb   = "";

// 2) Load current teacher details (including any existing photo)
$sql = "SELECT full_name, email, photo_filename
        FROM teachers
        WHERE teacher_id = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $teacherName   = $row["full_name"];
        $teacherEmailDb = $row["email"];
        $photoFilename  = $row["photo_filename"];

        // If DB has a photo, build URL path
        if (!empty($photoFilename)) {
            $currentPhotoPath = "assets/uploads/teachers/" . $photoFilename;
        }

        // Sync session (handy if name/email changed later)
        $_SESSION["teacher_name"]  = $teacherName;
        $_SESSION["teacher_email"] = $teacherEmailDb;

        if (!empty($photoFilename)) {
            $_SESSION["teacher_photo"] = $currentPhotoPath;
        }

    } else {
        $errors[] = "Teacher record not found. Please contact the system administrator.";
    }

    $stmt->close();
} else {
    $errors[] = "Database error while loading profile.";
}

// 3) Handle photo upload POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($errors)) {

    if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Please choose a photo to upload.";
    } elseif ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error. Please try again.";
    } else {

        $fileTmpPath = $_FILES["photo"]["tmp_name"];
        $fileName    = $_FILES["photo"]["name"];
        $fileSize    = $_FILES["photo"]["size"];

        // Max size ~2MB
        if ($fileSize > 2 * 1024 * 1024) {
            $errors[] = "Photo is too large. Please upload an image under 2 MB.";
        }

        // Allow only jpg/jpeg/png
        $allowedExtensions = ["jpg", "jpeg", "png"];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            $errors[] = "Invalid file type. Please upload a JPG or PNG image.";
        }

        if (empty($errors)) {
            // Upload directory (server filesystem path)
            $uploadDir = __DIR__ . "/assets/uploads/teachers/";

            // Ensure folder exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // New unique filename: e.g. T0001_1736423456.jpg
            $newFileName = $teacherId . "_" . time() . "." . $ext;
            $destPath    = $uploadDir . $newFileName;

            // Optional: delete old file if exists
            if (!empty($photoFilename)) {
                $oldPath = $uploadDir . $photoFilename;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Move uploaded file
            if (move_uploaded_file($fileTmpPath, $destPath)) {

                // Save filename in DB (not full path)
                $updateSql  = "UPDATE teachers SET photo_filename = ? WHERE teacher_id = ?";
                $updateStmt = $conn->prepare($updateSql);

                if ($updateStmt) {
                    $updateStmt->bind_param("ss", $newFileName, $teacherId);

                    if ($updateStmt->execute()) {
                        $successMessage   = "Profile picture updated successfully.";
                        $currentPhotoPath = "assets/uploads/teachers/" . $newFileName;

                        // Update session so dashboard shows new pic
                        $_SESSION["teacher_photo"] = $currentPhotoPath;

                    } else {
                        $errors[] = "Failed to save photo in the database.";
                    }

                    $updateStmt->close();
                } else {
                    $errors[] = "Database error while saving photo.";
                }

            } else {
                $errors[] = "Failed to move uploaded file. Please check folder permissions.";
            }
        }
    }
}

// Set page title and include header *after* session & checks
$pageTitle = "Upload Profile Picture - My School";
include "partials/header.php";
?>

<div class="container my-4">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title mb-3">Upload Profile Picture</h3>
          <p class="text-muted small mb-3">
            Choose a passport-style photo. This picture will appear on your Teacher Dashboard.
          </p>

          <!-- Success message -->
          <?php if ($successMessage): ?>
            <div class="alert alert-success">
              <?= $successMessage; ?>
            </div>
          <?php endif; ?>

          <!-- Error messages -->
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- Current photo + basic info -->
          <div class="d-flex align-items-center mb-3 gap-3">
            <img src="<?= htmlspecialchars($currentPhotoPath); ?>"
                 alt="Current photo"
                 class="profile-photo"
                 style="width:120px;height:120px;border-radius:50%;object-fit:cover;">
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($teacherName); ?></div>
              <div class="text-muted small">Teacher ID: <?= htmlspecialchars($teacherId); ?></div>
              <div class="text-muted small">Email: <?= htmlspecialchars($teacherEmailDb); ?></div>
            </div>
          </div>

          <!-- Upload form -->
          <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="photo" class="form-label">
                Select Photo (JPG/PNG, max 2 MB)
              </label>
              <input type="file"
                     name="photo"
                     id="photo"
                     class="form-control"
                     accept=".jpg,.jpeg,.png">
            </div>

            <button type="submit" class="btn btn-primary">
              Upload Picture
            </button>
            <a href="dashboard.php" class="btn btn-link">
              Back to Dashboard
            </a>
          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>
