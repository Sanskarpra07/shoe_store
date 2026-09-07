<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (is_customer_logged_in()) {
    header("Location: my_account.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = "Please enter both email and password.";
    header("Location: login.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$customer || !password_verify($password, $customer['password_eg'])) {
    $_SESSION['login_error'] = "Invalid email or password.";
    header("Location: login.php");
    exit();
}

if (!$customer['is_verified']) {
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = mysqli_prepare($conn, "UPDATE customers SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $otp, $otp_expiry, $customer['id']);
    mysqli_stmt_execute($stmt);

    @mail($email, "StepStyle Email Verification",
        "Your OTP verification code is: $otp\nIt expires in 10 minutes.\n\n- StepStyle");

    $_SESSION['pending_otp_email'] = $email;
    $_SESSION['pending_otp_code']  = $otp; // Demo display fallback
    $_SESSION['pending_otp_mode']  = 'register';
    header("Location: verify_otp.php");
    exit();
}

$_SESSION['customer_id']    = $customer['id'];
$_SESSION['customer_name']  = $customer['full_name'];
$_SESSION['customer_email'] = $customer['email'];

header("Location: my_account.php");
exit();