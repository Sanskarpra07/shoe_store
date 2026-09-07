<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

// Only allow this page after the OTP has been verified in the reset flow.
if (empty($_SESSION['reset_otp_verified']) || empty($_SESSION['reset_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['reset_email'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE customers SET password_eg = ? WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);
        mysqli_stmt_execute($stmt);

        unset($_SESSION['reset_email'], $_SESSION['reset_otp_verified']);
        $_SESSION['login_success'] = "Your password has been reset successfully. Please login with your new password.";
        header("Location: login.php");
        exit();
    }
}

site_header('Reset Password - StepStyle', '');
?>

<h2 class="page-title">Reset Password</h2>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<div class="form-box">
    <h3>Set a New Password</h3>
    <p style="font-size:13px; margin-bottom:14px;">
        Account email: <strong><?= htmlspecialchars($email) ?></strong>
    </p>
    <form method="POST" action="reset_password.php">
        <div class="form-group">
            <label>New Password * (min 6 characters)</label>
            <input type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label>Confirm New Password *</label>
            <input type="password" name="confirm_password" required minlength="6">
        </div>
        <button type="submit" class="btn">Save New Password</button>
    </form>
</div>

<?php site_footer(); ?>