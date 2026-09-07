<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (is_customer_logged_in()) {
    header("Location: my_account.php");
    exit();
}

$errors = $_SESSION['login_error'] ?? [];
unset($_SESSION['login_error']);
$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);

site_header('Customer Login - StepStyle', '');
?>

<h2 class="page-title">Customer Login</h2>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ((array)$errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="form-box">
    <h3>Login to Your Account</h3>
    <form method="POST" action="process_customer_login.php">
        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required autofocus placeholder="you@example.com">
        </div>
        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required placeholder="Password">
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
    <p style="text-align:center; margin-top:15px; font-size:13px;">
        Don't have an account? <a href="register.php"><strong>Register here</strong></a>
    </p>
    <p style="text-align:center; font-size:13px;">
        <a href="forgot_password.php">Forgot Password?</a>
    </p>
    <p style="text-align:center; font-size:13px;">
        <a href="track_order.php">Track an order without login</a>
    </p>
</div>

<?php site_footer(); ?>