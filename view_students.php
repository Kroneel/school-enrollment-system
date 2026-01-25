<?php
include 'db.php';
$result = mysqli_query($conn, "SELECT * FROM students");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enrolled Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Enrolled Students</h4>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= $row['student_id']; ?></td>
                        <td><?= $row['full_name']; ?></td>
                        <td><?= $row['email']; ?></td>
                        <td><?= $row['course']; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>

            <a href="index.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>

</body>
</html>
