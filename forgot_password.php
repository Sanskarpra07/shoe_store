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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - StepStyle</title>
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
                    <i class="bi bi-key me-2"></i>Forgot Password
                </div>
                <div class="card-body p-4">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errors) ?></div>
                    <?php endif; ?>

                    <p class="text-muted small">
                        Enter the email address linked to your account. We will send you an OTP
                        to verify your identity, after which you can set a new password.
                    </p>

                    <form method="POST" action="forgot_password.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="you@example.com">
                        </div>
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-envelope-check me-1"></i>Send OTP
                        </button>
                    </form>
                    <p class="text-center mt-3 small mb-0">
                        <a href="login.php" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>