<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (isset($_SESSION['customer_id'])) {
    header("Location: my_account.php");
    exit();
}

$email = $_SESSION['pending_otp_email'] ?? '';
if (empty($email)) {
    header("Location: register.php");
    exit();
}

$errors = [];
$success = "";

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt = mysqli_prepare($conn, "UPDATE customers SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "sss", $otp, $otp_expiry, $email);
    mysqli_stmt_execute($stmt);
    $_SESSION['pending_otp_code'] = $otp;
    @mail($email, "StepStyle Email Verification",
        "Your new OTP verification code is: $otp\nIt expires in 10 minutes.\n\n- StepStyle");
    $success = "A new OTP has been sent to $email.";
}

// Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
    $entered = trim($_POST['otp'] ?? '');

    $stmt = mysqli_prepare($conn,
        "SELECT otp_code, otp_expires_at, id FROM customers WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$user) {
        $errors[] = "Account not found. Please register again.";
    } elseif (empty($entered)) {
        $errors[] = "Please enter the OTP.";
    } elseif ($user['otp_code'] !== $entered) {
        $errors[] = "Invalid OTP. Please try again.";
    } elseif (strtotime($user['otp_expires_at']) < time()) {
        $errors[] = "OTP has expired. Please request a new one.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE customers SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user['id']);
        mysqli_stmt_execute($stmt);

        unset($_SESSION['pending_otp_email'], $_SESSION['pending_otp_code']);

        $_SESSION['customer_id']   = $user['id'];
        $_SESSION['customer_name'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name, email FROM customers WHERE id = {$user['id']}"))['full_name'];

        header("Location: my_account.php?verified=1");
        exit();
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/frontend.css" rel="stylesheet">
</head>
<body>

<?php frontend_navbar(); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header text-center py-4 fw-bold" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color:#fff;">
                    <i class="bi bi-shield-lock me-2"></i>Email Verification
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success py-2 small"><?= $success ?></div>
                    <?php endif; ?>

                    <p class="text-muted small">
                        We sent a 6-digit OTP code to <strong><?= htmlspecialchars($email) ?></strong>.
                        Enter it below to verify your account.
                    </p>

                    <?php if (isset($_SESSION['pending_otp_code'])): ?>
                        <div class="alert alert-info py-2 small">
                            <strong>Demo Mode:</strong> Since mail is not configured on localhost,
                            your OTP is <span class="fw-bold fs-5"><?= htmlspecialchars($_SESSION['pending_otp_code']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 small">
                            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="verify_otp.php">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Enter OTP Code <span class="text-danger">*</span></label>
                            <input type="text" name="otp" class="form-control text-center fs-4" maxlength="6" required
                                   placeholder="______" pattern="[0-9]{6}">
                        </div>
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-check-circle me-1"></i>Verify Email
                        </button>
                    </form>

                    <div class="text-center mt-3 small">
                        Didn't receive the code? <a href="verify_otp.php?resend=1">Resend OTP</a>
                        <br><a href="register.php" class="text-muted mt-1 d-inline-block">Use a different email</a>
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