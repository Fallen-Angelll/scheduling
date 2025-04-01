<?php
session_start();
require_once dirname(__FILE__) . '/../config/db_config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Handle availability form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete existing availability for this time slot
        $delete_stmt = $pdo->prepare('DELETE FROM teacher_availability WHERE teacher_id = ? AND day_of_week = ? AND start_time = ? AND end_time = ?');
        $delete_stmt->execute([$teacher_id, $_POST['day_of_week'], $_POST['start_time'], $_POST['end_time']]);

        // Insert new availability
        $stmt = $pdo->prepare('INSERT INTO teacher_availability (teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)');
        $stmt->execute([$teacher_id, $_POST['day_of_week'], $_POST['start_time'], $_POST['end_time']]);
        
        header('Location: dashboard.php?success=availability_updated');
        exit();
    } catch (PDOException $e) {
        header('Location: dashboard.php?error=database_error');
        exit();
    }
}

// Fetch current availability
$stmt = $pdo->prepare('SELECT * FROM teacher_availability WHERE teacher_id = ? ORDER BY FIELD(day_of_week, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"), start_time');
$stmt->execute([$teacher_id]);
$availabilities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Scheduling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-item nav-link text-light">Welcome, <?php echo htmlspecialchars($full_name); ?></span>
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Teacher Dashboard</h2>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'availability_updated'): ?>
        <div class="alert alert-success" role="alert">
            Availability has been updated successfully!
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            An error occurred. Please try again.
        </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Set Availability</h5>
                        <form method="POST" action="dashboard.php">
                            <div class="mb-3">
                                <label for="day_of_week" class="form-label">Day of Week</label>
                                <select class="form-select" id="day_of_week" name="day_of_week" required>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Availability</button>
                        </form>

                        <div class="mt-4">
                            <h6>Current Availability</h6>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($availabilities as $availability): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($availability['day_of_week']); ?></td>
                                        <td><?php echo htmlspecialchars($availability['start_time']); ?></td>
                                        <td><?php echo htmlspecialchars($availability['end_time']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">My Schedule</h5>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: 'get_schedules.php'
            });
            calendar.render();
        });
    </script>
</body>
</html>