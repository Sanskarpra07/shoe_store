<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$errors = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$customer) {
            // Do not reveal whether the account exists
            $errors = "If an account exists for this email, an OTP has been sent to reset your password.";
        } else {
            $otp        = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $stmt = mysqli_prepare($conn, "UPDATE customers SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $otp, $otp_expiry, $customer['id']);
            mysqli_stmt_execute($stmt);

            @mail($email, "StepStyle Password Reset OTP",
                "Your password reset OTP is: $otp\nIt expires in 10 minutes.\n\n- StepStyle");

            $_SESSION['pending_otp_email'] = $email;
            $_SESSION['pending_otp_code']  = $otp; // Demo display fallback
            $_SESSION['pending_otp_mode']  = 'reset';
            header("Location: verify_otp.php");
            exit();
        }
    }
}

site_header('Forgot Password - StepStyle', '');
?>

<h2 class="page-title">Forgot Password</h2>

<?php if ($errors): ?>
    <div class="msg-error"><?= htmlspecialchars($errors) ?></div>
<?php endif; ?>

<div class="form-box">
    <h3>Reset Your Password</h3>
    <p style="font-size:13px; margin-bottom:14px;">
        Enter the email address linked to your account. We will send you an OTP to verify your identity,
        after which you can set a new password.
    </p>
    <form method="POST" action="forgot_password.php">
        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required placeholder="you@example.com">
        </div>
        <button type="submit" class="btn">Send OTP</button>
    </form>
    <p style="text-align:center; margin-top:12px; font-size:13px;">
        <a href="login.php">&laquo; Back to Login</a>
    </p>
</div>

<?php site_footer(); ?>