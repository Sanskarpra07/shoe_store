<?php
session_start();
require_once __DIR__ . '/../backend/db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['error_message'] = 'Please enter both username and password';
    header("Location: login.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($user && password_verify($password, $user['password_eg'])) {
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    header("Location: dashboard.php");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid Username or Password";
    header("Location: login.php");
    exit();
}