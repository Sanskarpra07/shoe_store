<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN LOGIN
 * --------------------------------------------------------------------------
 * Standalone login screen for administrators and staff.
 * Reads flash messages (error/success) set by process_login.php and posts
 * the credentials back to it. Fully self-contained (does not use the shared
 * admin layout header/footer so it has its own centered card design).
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared database connection -------------------------
session_start();
require_once __DIR__ . '/../backend/db.php';

// --- Flash messages ----------------------------------------------------------
// Pull any error/success set by process_login.php, then clear them so they
// are only shown once.
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Document metadata -->
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Shoe Store</title>

    <!-- Bootstrap icons + custom admin theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<!-- ======== LOGIN PAGE ======== -->
<div class="login-page">
    <div class="login-box">
        <!-- Brand icon + heading -->
        <div class="login-logo"><i class="bi bi-bag-heart"></i></div>
        <h2>Admin Login</h2>
        <p class="login-sub">MegaFoot Shoe Store administration</p>

        <!-- Flash messages (error / success) -->
        <?php if ($error_message): ?>
            <div class="msg-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="msg-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="process_login.php">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required autofocus placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" id="password" required placeholder="Enter your password">
            </div>

            <!-- Show/hide password toggle -->
            <div class="form-group">
                <label style="font-weight:normal; font-size:12px; display:flex; align-items:center; gap:6px;">
                    <input type="checkbox" id="show-password" onclick="togglePassword()" style="width:auto;"> Show Password
                </label>
            </div>

            <button type="submit" class="btn" style="width:100%;"><i class="bi bi-box-arrow-in-right me-1"></i> Login</button>
        </form>

        <!-- Back to store link -->
        <p class="link-row"><a href="../index.php"><i class="bi bi-arrow-left"></i> Back to Store</a></p>
    </div>
</div>

<!-- ======== SCRIPTS ======== -->
<script>
// Toggle the password field between text and password visibility.
function togglePassword() {
    var field = document.getElementById("password");
    field.type = field.type === "password" ? "text" : "password";
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/notify.js"></script>
</body>
</html>