<?php
session_start();
require_once 'db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!csrf_check()) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again';
    header("Location: admin/login.php");
    exit();
}

if ($username ==='' || $password === '') {
    $_SESSION['error_message'] = 'Please enter both username and password';
    header("Location: admin/login.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username =?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result= mysqli_stmt_get_result($stmt);
$user= mysqli_fetch_assoc($result);

if ($user && password_verify($password, $user['password_eg'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    header("Location: admin/dashboard.php");
    exit();
}
else {
    $_SESSION['error_message'] = "Invalid Username or Password";
    header("Location: admin/login.php");
    exit();
}
