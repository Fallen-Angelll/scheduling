<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db_config.php';


// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare('INSERT INTO courses (course_code, course_name, description) VALUES (?, ?, ?)');
                    $stmt->execute([$_POST['course_code'], $_POST['course_name'], $_POST['description']]);
                    header('Location: courses.php?success=course_added');
                    break;
                    
                case 'edit':
                    $stmt = $pdo->prepare('UPDATE courses SET course_code = ?, course_name = ?, description = ? WHERE id = ?');
                    $stmt->execute([$_POST['course_code'], $_POST['course_name'], $_POST['description'], $_POST['course_id']]);
                    header('Location: courses.php?success=course_updated');
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare('DELETE FROM courses WHERE id = ?');
                    $stmt->execute([$_POST['course_id']]);
                    header('Location: courses.php?success=course_deleted');
                    break;
            }
            exit();
        } catch (PDOException $e) {
            header('Location: courses.php?error=database_error');
            exit();
        }
    }
}

// Fetch all courses
$stmt = $pdo->query('SELECT * FROM courses ORDER BY course_code');
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Scheduling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Scheduling System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rooms.php">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">Schedules</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage Courses</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?php
            switch ($_GET['success']) {
                case 'course_added':
                    echo 'Course has been added successfully!';
                    break;
                case 'course_updated':
                    echo 'Course has been updated successfully!';
                    break;
                case 'course_deleted':
                    echo 'Course has been deleted successfully!';
                    break;
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            An error occurred. Please try again.
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Add New Course</h5>
                <form method="POST" action="courses.php">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="course_code" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Course List</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['description']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $course['id']; ?>">
                                        Edit
                                    </button>
                                    <form method="POST" action="courses.php" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $course['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Course</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="courses.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="edit_course_code<?php echo $course['id']; ?>" class="form-label">Course Code</label>
                                                    <input type="text" class="form-control" id="edit_course_code<?php echo $course['id']; ?>" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_course_name<?php echo $course['id']; ?>" class="form-label">Course Name</label>
                                                    <input type="text" class="form-control" id="edit_course_name<?php echo $course['id']; ?>" name="course_name" value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_description<?php echo $course['id']; ?>" class="form-label">Description</label>
                                                    <input type="text" class="form-control" id="edit_description<?php echo $course['id']; ?>" name="description" value="<?php echo htmlspecialchars($course['description']); ?>">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>