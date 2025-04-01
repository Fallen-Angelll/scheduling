<?php
session_start();
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
                    $stmt = $pdo->prepare('INSERT INTO rooms (room_number, capacity, description) VALUES (?, ?, ?)');
                    $stmt->execute([$_POST['room_number'], $_POST['capacity'], $_POST['description']]);
                    header('Location: rooms.php?success=room_added');
                    break;
                    
                case 'edit':
                    $stmt = $pdo->prepare('UPDATE rooms SET room_number = ?, capacity = ?, description = ? WHERE id = ?');
                    $stmt->execute([$_POST['room_number'], $_POST['capacity'], $_POST['description'], $_POST['room_id']]);
                    header('Location: rooms.php?success=room_updated');
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
                    $stmt->execute([$_POST['room_id']]);
                    header('Location: rooms.php?success=room_deleted');
                    break;
            }
            exit();
        } catch (PDOException $e) {
            header('Location: rooms.php?error=database_error');
            exit();
        }
    }
}

// Fetch all rooms
$stmt = $pdo->query('SELECT * FROM rooms ORDER BY room_number');
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Scheduling System</title>
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
                        <a class="nav-link" href="courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="rooms.php">Rooms</a>
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
        <h2>Manage Rooms</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?php
            switch ($_GET['success']) {
                case 'room_added':
                    echo 'Room has been added successfully!';
                    break;
                case 'room_updated':
                    echo 'Room has been updated successfully!';
                    break;
                case 'room_deleted':
                    echo 'Room has been deleted successfully!';
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
                <h5 class="card-title">Add New Room</h5>
                <form method="POST" action="rooms.php">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="room_number" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" required min="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Room List</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Capacity</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                                <td><?php echo htmlspecialchars($room['description']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $room['id']; ?>">
                                        Edit
                                    </button>
                                    <form method="POST" action="rooms.php" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this room?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $room['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Room</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="rooms.php">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="edit_room_number<?php echo $room['id']; ?>" class="form-label">Room Number</label>
                                                    <input type="text" class="form-control" id="edit_room_number<?php echo $room['id']; ?>" name="room_number" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_capacity<?php echo $room['id']; ?>" class="form-label">Capacity</label>
                                                    <input type="number" class="form-control" id="edit_capacity<?php echo $room['id']; ?>" name="capacity" value="<?php echo htmlspecialchars($room['capacity']); ?>" required min="1">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_description<?php echo $room['id']; ?>" class="form-label">Description</label>
                                                    <input type="text" class="form-control" id="edit_description<?php echo $room['id']; ?>" name="description" value="<?php echo htmlspecialchars($room['description']); ?>">
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