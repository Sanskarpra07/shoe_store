<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

// OTP verification is used both during registration and password reset.
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
            $_SESSION['reset_email']        = $email;
            $_SESSION['reset_otp_verified'] = true;
            header("Location: reset_password.php");
            exit();
        }

        // Registration flow: log the customer in immediately
        session_regenerate_id(true);
        $_SESSION['customer_id']    = $customer['id'];
        $_SESSION['customer_name']  = $customer['full_name'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['login_success']  = "Your account has been verified successfully. Welcome to StepStyle!";
        header("Location: my_account.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mode === 'reset' ? 'Verify OTP - Password Reset - StepStyle' : 'Verify OTP - StepStyle' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<?php frontend_navbar(); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header text-center py-4 fw-bold" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color:#fff;">
                    <i class="bi bi-shield-lock me-2"></i><?= $mode === 'reset' ? 'Verify OTP - Password Reset' : 'Email Verification' ?>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <p class="text-muted small">
                        We sent a 6-digit OTP code to <strong><?= htmlspecialchars($email) ?></strong>.
                        Enter it below to <?= $mode === 'reset' ? 'confirm your identity' : 'verify your account' ?>.
                    </p>

                    <?php if (!empty($demo_otp)): ?>
                        <div class="alert alert-info py-2 small">
                            <strong>Demo Mode:</strong> Since mail is not configured on localhost,
                            your OTP is <span class="fw-bold fs-5"><?= htmlspecialchars($demo_otp) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errors) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="verify_otp.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Enter OTP Code <span class="text-danger">*</span></label>
                            <input type="text" name="otp" class="form-control text-center fs-4" maxlength="6" required
                                   placeholder="______" pattern="[0-9]{6}">
                        </div>
                        <button type="submit" name="verify" class="btn btn-accent w-100">
                            <i class="bi bi-check-circle me-1"></i>Verify OTP
                        </button>
                    </form>

                    <div class="text-center mt-3 small">
                        Didn't receive the code?
                        <form method="POST" action="verify_otp.php" style="display:inline;">
                            <button type="submit" name="resend" class="btn btn-link btn-sm p-0 align-baseline">Resend OTP</button>
                        </form>
                        <br><a href="<?= $mode === 'reset' ? 'forgot_password.php' : 'register.php' ?>" class="text-muted mt-1 d-inline-block">
                            <?= $mode === 'reset' ? 'Start over' : 'Use a different email' ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>