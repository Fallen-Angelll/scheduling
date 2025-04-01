<?php
session_start();
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Build the query based on user role
    if ($role === 'admin') {
        // Admins can see all schedules
        $sql = '
            SELECT 
                s.id,
                c.course_code,
                c.course_name,
                r.room_number,
                u.full_name as teacher_name,
                s.day_of_week,
                s.start_time,
                s.end_time
            FROM schedules s
            JOIN courses c ON s.course_id = c.id
            JOIN rooms r ON s.room_id = r.id
            JOIN users u ON s.teacher_id = u.id
        ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Teachers can only see their own schedules
        $sql = '
            SELECT 
                s.id,
                c.course_code,
                c.course_name,
                r.room_number,
                u.full_name as teacher_name,
                s.day_of_week,
                s.start_time,
                s.end_time
            FROM schedules s
            JOIN courses c ON s.course_id = c.id
            JOIN rooms r ON s.room_id = r.id
            JOIN users u ON s.teacher_id = u.id
            WHERE s.teacher_id = ?
        ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }

    $schedules = $stmt->fetchAll();
    $events = [];

    // Map days to numbers for date calculation
    $dayMap = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 0
    ];

    // Get the date of the most recent Monday
    $today = new DateTime();
    $monday = clone $today;
    $monday->modify('this week monday');

    // Convert schedules to FullCalendar events
    foreach ($schedules as $schedule) {
        // Clone the Monday date and add days to get to the correct day of week
        $date = clone $monday;
        $daysToAdd = $dayMap[$schedule['day_of_week']];
        if ($daysToAdd === 0) $daysToAdd = 7; // Handle Sunday
        $date->modify('+' . ($daysToAdd - 1) . ' days');

        // Create the event
        $event = [
            'id' => $schedule['id'],
            'title' => $schedule['course_code'] . '\n' . 
                      $schedule['room_number'] . '\n' .
                      $schedule['teacher_name'],
            'start' => $date->format('Y-m-d') . ' ' . $schedule['start_time'],
            'end' => $date->format('Y-m-d') . ' ' . $schedule['end_time'],
            'backgroundColor' => '#' . substr(md5($schedule['course_code']), 0, 6),
            'borderColor' => '#' . substr(md5($schedule['course_code']), 0, 6),
        ];

        $events[] = $event;
    }

    header('Content-Type: application/json');
    echo json_encode($events);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
    exit();
}