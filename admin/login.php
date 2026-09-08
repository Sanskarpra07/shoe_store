<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Shoe Store</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo"><i class="bi bi-bag-heart"></i></div>
        <h2>Admin Login</h2>
        <p class="login-sub">StepStyle Shoe Store administration</p>

        <?php if ($error_message): ?>
            <div class="msg-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="msg-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <form method="POST" action="process_login.php">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required autofocus placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" id="password" required placeholder="Enter your password">
            </div>
            <div class="form-group">
                <label style="font-weight:normal; font-size:12px; display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" id="show-password" onclick="togglePassword()" style="width:auto;"> Show Password
                </label>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p class="link-row"><a href="../frontend/index.php"><i class="bi bi-arrow-left"></i> Back to Store</a></p>
    </div>
</div>
<script>
function togglePassword() {
    var field = document.getElementById("password");
    field.type = field.type === "password" ? "text" : "password";
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/notify.js"></script>
</body>
</html>