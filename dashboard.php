<?php
session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Get user-specific content based on role
if ($role === 'admin') {
    // Admin specific content
    require_once dirname(__FILE__) . '/admin/dashboard.php';
} elseif ($role === 'teacher') {
    // Teacher specific content
    require_once dirname(__FILE__) . '/teacher/dashboard.php';
} else {
    header('Location: index.php?error=invalid_role');
    exit();
}
?> 