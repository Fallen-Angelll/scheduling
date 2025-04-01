<?php
session_start();
require_once '../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        header('Location: ../index.php?error=empty_fields');
        exit();
    }

    try {
        // Get user from database
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {       
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Redirect to dashboard
            header('Location: ../dashboard.php');
            exit();
        } else {
            header('Location: ../index.php?error=invalid_credentials');
            exit();
        }
    } catch (PDOException $e) {
        header('Location: ../index.php?error=database_error');
        exit();
    }
}

// If not POST request, redirect to index
header('Location: ../index.php');
exit();