<?php
session_start();
require_once __DIR__ . '/../db.php';
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Shoe Store</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <h2>Shoe Store Admin Login</h2>

        <?php if ($error_message): ?>
            <div class="msg-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="msg-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <form method="POST" action="../process_login.php">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required autofocus placeholder="Username">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" id="password" required placeholder="Password">
            </div>
            <div class="form-group">
                <label style="font-weight:normal; font-size:12px;">
                    <input type="checkbox" id="show-password" onclick="togglePassword()"> Show Password
                </label>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p class="link-row"><a href="../index.php">&laquo; Back to Store</a></p>
    </div>
</div>
<script>
function togglePassword() {
    var field = document.getElementById("password");
    field.type = field.type === "password" ? "text" : "password";
}
</script>
</body>
</html>