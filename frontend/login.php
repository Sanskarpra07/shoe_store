<?php
/**
 * --------------------------------------------------------------------------
 * Customer Login - MegaFoot Storefront
 * --------------------------------------------------------------------------
 * Renders the customer login form, redirects already-logged-in customers to
 * their account, and surfaces any login errors or success messages.
 * --------------------------------------------------------------------------
 */

// ---------- Session bootstrap --------------------------------
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

// ---------- Redirect logged-in customers --------------------
if (is_customer_logged_in()) {
    header("Location: my_account.php");
    exit();
}

// ---------- Read and clear flash messages -------------------
$errors = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
if (!empty($errors) && !is_array($errors)) {
    $errors = [$errors];
} elseif (!is_array($errors)) {
    $errors = [];
}

$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="en">
<!-- ======== HEAD ======== -->
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - MegaFoot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<!-- ======== NAVBAR ======== -->
<?php frontend_navbar(); ?>

<!-- ======== PAGE CONTENT ======== -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header text-center py-4 fw-bold" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color:#fff;">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Customer Login
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 small">
                            <?php if (is_array($errors)): ?>
                                <?php foreach ($errors as $e): ?>
                                    <?= htmlspecialchars($e) ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?= htmlspecialchars($errors) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- ======== LOGIN FORM ======== -->
                    <form method="POST" action="process_customer_login.php">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required autofocus
                                   placeholder="you@example.com">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required
                                       placeholder="Password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-accent w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </button>
                    </form>
                    <p class="text-center mt-3 small mb-0">
                        Don't have an account? <a href="register.php">Register here</a>
                    </p>
                    <p class="text-center mt-2 small">
                        <a href="forgot_password.php" class="text-muted"><i class="bi bi-key me-1"></i>Forgot password?</a>
                    </p>
                    <p class="text-center mt-1 small">
                        <a href="track_order.php" class="text-muted"><i class="bi bi-box me-1"></i>Track an order without login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======== FOOTER ======== -->
<?php frontend_footer(); ?>

<!-- ======== SCRIPTS ======== -->
<script>
function togglePassword() {
    const field = document.getElementById("password");
    field.type = field.type === "password" ? "text" : "password";
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>