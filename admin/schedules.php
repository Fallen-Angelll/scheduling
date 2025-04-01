<?php
session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Function to check for schedule conflicts
function hasScheduleConflict($pdo, $room_id, $teacher_id, $day_of_week, $start_time, $end_time, $exclude_id = null) {
    // Check room availability
    $room_sql = 'SELECT id FROM schedules WHERE room_id = ? AND day_of_week = ? AND 
                ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))';
    $room_stmt = $pdo->prepare($room_sql);
    $room_stmt->execute([$room_id, $day_of_week, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
    
    if ($room_stmt->rowCount() > 0) {
        return 'Room is already scheduled for this time slot.';
    }

    // Check teacher availability
    $teacher_sql = 'SELECT id FROM schedules WHERE teacher_id = ? AND day_of_week = ? AND 
                   ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))';
    $teacher_stmt = $pdo->prepare($teacher_sql);
    $teacher_stmt->execute([$teacher_id, $day_of_week, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
    
    if ($teacher_stmt->rowCount() > 0) {
        return 'Teacher is already scheduled for this time slot.';
    }

    // Check if teacher is available during this time
    $availability_sql = 'SELECT id FROM teacher_availability WHERE teacher_id = ? AND day_of_week = ? AND 
                        start_time <= ? AND end_time >= ?';
    $availability_stmt = $pdo->prepare($availability_sql);
    $availability_stmt->execute([$teacher_id, $day_of_week, $start_time, $end_time]);
    
    if ($availability_stmt->rowCount() === 0) {
        return 'Teacher is not available during this time slot.';
    }

    return false;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    // Check for conflicts
                    $conflict = hasScheduleConflict(
                        $pdo,
                        $_POST['room_id'],
                        $_POST['teacher_id'],
                        $_POST['day_of_week'],
                        $_POST['start_time'],
                        $_POST['end_time']
                    );

                    if ($conflict) {
                        header('Location: schedules.php?error=' . urlencode($conflict));
                        exit();
                    }

                    $stmt = $pdo->prepare('INSERT INTO schedules (course_id, room_id, teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $_POST['course_id'],
                        $_POST['room_id'],
                        $_POST['teacher_id'],
                        $_POST['day_of_week'],
                        $_POST['start_time'],
                        $_POST['end_time']
                    ]);
                    header('Location: schedules.php?success=schedule_added');
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare('DELETE FROM schedules WHERE id = ?');
                    $stmt->execute([$_POST['schedule_id']]);
                    header('Location: schedules.php?success=schedule_deleted');
                    break;
            }
            exit();
        } catch (PDOException $e) {
            header('Location: schedules.php?error=database_error');
            exit();
        }
    }
}

// Fetch all necessary data
$courses = $pdo->query('SELECT * FROM courses ORDER BY course_code')->fetchAll();
$rooms = $pdo->query('SELECT * FROM rooms ORDER BY room_number')->fetchAll();
$teachers = $pdo->query('SELECT * FROM users WHERE role = "teacher" ORDER BY full_name')->fetchAll();
$schedules = $pdo->query('
    SELECT s.*, c.course_code, c.course_name, r.room_number, u.full_name as teacher_name
    FROM schedules s
    JOIN courses c ON s.course_id = c.id
    JOIN rooms r ON s.room_id = r.id
    JOIN users u ON s.teacher_id = u.id
    ORDER BY s.day_of_week, s.start_time
')->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - Scheduling System</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rooms.php">Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="schedules.php">Schedules</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Manage Schedules</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?php
            switch ($_GET['success']) {
                case 'schedule_added':
                    echo 'Schedule has been added successfully!';
                    break;
                case 'schedule_deleted':
                    echo 'Schedule has been deleted successfully!';
                    break;
            }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Add New Schedule</h5>
                        <form method="POST" action="schedules.php">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Select Room</option>
                                    <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        <?php echo htmlspecialchars($room['room_number'] . ' (Capacity: ' . $room['capacity'] . ')');
                                        ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="teacher_id" class="form-label">Teacher</label>
                                <select class="form-select" id="teacher_id" name="teacher_id" required>
                                    <option value="">Select Teacher</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                            <button type="submit" class="btn btn-primary">Add Schedule</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Schedule Calendar</h5>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Schedule List</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Course</th>
                                <th>Room</th>
                                <th>Teacher</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($schedule['day_of_week']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars(date('h:i A', strtotime($schedule['start_time']))) . ' - ' .
                                              htmlspecialchars(date('h:i A', strtotime($schedule['end_time']))); ?>
                                </td>
                                <td><?php echo htmlspecialchars($schedule['course_code'] . ' - ' . $schedule['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['teacher_name']); ?></td>
                                <td>
                                    <form method="POST" action="schedules.php" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this schedule?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                events: 'get_schedules.php',
                slotMinTime: '07:00:00',
                slotMaxTime: '22:00:00',
                allDaySlot: false,
                slotDuration: '00:30:00',
                weekends: true,
                height: 'auto'
            });
            calendar.render();
        });
    </script>
</body>
</html>