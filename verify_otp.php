<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

// OTP verification is used both during registration and password reset.
// The pending email + mode must be stored in the session.
$email = $_SESSION['pending_otp_email'] ?? '';
$mode  = $_SESSION['pending_otp_mode'] ?? 'register';
$demo_otp = $_SESSION['pending_otp_code'] ?? '';

if (empty($email)) {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = '';

// Resend the OTP
if (isset($_POST['resend'])) {
    $otp        = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = mysqli_prepare($conn, "UPDATE customers SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "sss", $otp, $otp_expiry, $email);
    mysqli_stmt_execute($stmt);

    $_SESSION['pending_otp_code'] = $otp;
    @mail($email, "StepStyle Email Verification",
        "Your new OTP verification code is: $otp\nIt expires in 10 minutes.\n\n- StepStyle");
    $success = "A new OTP has been sent to your email.";
    $demo_otp = $otp;
}

// Verify the entered OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $entered = trim($_POST['otp'] ?? '');

    $stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$customer || empty($customer['otp_code'])) {
        $errors = "No OTP found. Please register again or resend the code.";
    } elseif ($customer['otp_code'] !== $entered) {
        $errors = "Incorrect OTP. Please check and try again.";
    } elseif (strtotime($customer['otp_expires_at']) < time()) {
        $errors = "This OTP has expired. Please resend a new code.";
    } else {
        // OTP is correct and valid
        $stmt = mysqli_prepare($conn,
            "UPDATE customers SET otp_code = NULL, otp_expires_at = NULL, is_verified = 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $customer['id']);
        mysqli_stmt_execute($stmt);

        unset($_SESSION['pending_otp_email'], $_SESSION['pending_otp_code'], $_SESSION['pending_otp_mode']);

        if ($mode === 'reset') {
            // Password reset flow proceeds to the reset form
            $_SESSION['reset_email']         = $email;
            $_SESSION['reset_otp_verified']  = true;
            header("Location: reset_password.php");
            exit();
        }

        // Registration flow: log the customer in immediately
        $_SESSION['customer_id']    = $customer['id'];
        $_SESSION['customer_name']  = $customer['full_name'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['login_success']  = "Your account has been verified successfully. Welcome to StepStyle!";
        header("Location: my_account.php");
        exit();
    }
}

site_header($mode === 'reset' ? 'Verify OTP - Password Reset - StepStyle' : 'Verify OTP - StepStyle', '');
?>

<h2 class="page-title"><?= $mode === 'reset' ? 'Verify OTP - Password Reset' : 'Verify Email - OTP Verification' ?></h2>

<?php if (!empty($demo_otp)): ?>
    <div class="msg-info">
        <strong>Demo Mode Notice:</strong> In this project an email is sent with your OTP.
        Since no mail server is configured, your OTP is: <strong><?= htmlspecialchars($demo_otp) ?></strong>
        (expires in 10 minutes).
    </div>
<?php else: ?>
    <div class="msg-info">A 6-digit OTP has been sent to <strong><?= htmlspecialchars($email) ?></strong>. It expires in 10 minutes.</div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="msg-error"><?= htmlspecialchars($errors) ?></div>
<?php endif; ?>

<div class="form-box">
    <h3>Enter 6-Digit OTP</h3>
    <form method="POST" action="verify_otp.php">
        <div class="form-group">
            <label>OTP Code *</label>
            <input type="text" name="otp" required maxlength="6" placeholder="123456"
                   style="text-align:center; font-size:20px; letter-spacing:6px;">
        </div>
        <button type="submit" name="verify" class="btn">Verify OTP</button>
    </form>
    <p style="text-align:center; margin-top:12px; font-size:13px;">
        Didn't receive the code?
        <form method="POST" action="verify_otp.php" style="display:inline;">
            <button type="submit" name="resend" style="background:none; border:none; color:#1a237e; text-decoration:underline; cursor:pointer; font-size:13px;">Resend OTP</button>
        </form>
    </p>
    <p style="text-align:center; font-size:13px;">
        <a href="login.php">&laquo; Back to Login</a>
    </p>
</div>

<?php site_footer(); ?>