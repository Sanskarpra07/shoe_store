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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - StepStyle</title>
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
                    <i class="bi bi-shield-lock me-2"></i>Set a New Password
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 small">
                            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted small">
                        Account email: <strong><?= htmlspecialchars($email) ?></strong>
                    </p>

                    <form method="POST" action="reset_password.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-check-circle me-1"></i>Save New Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>