<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (is_customer_logged_in()) {
    header("Location: my_account.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($email))     $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            $otp        = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $hashed     = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn,
                "INSERT INTO customers (full_name, email, phone, password_eg, otp_code, otp_expires_at, is_verified)
                 VALUES (?, ?, ?, ?, ?, ?, 0)"
            );
            mysqli_stmt_bind_param($stmt, "ssssss", $full_name, $email, $phone, $hashed, $otp, $otp_expiry);

            if (mysqli_stmt_execute($stmt)) {
                @mail($email, "StepStyle Email Verification",
                    "Your OTP verification code is: $otp\nIt expires in 10 minutes.\n\n- StepStyle");

                $_SESSION['pending_otp_email'] = $email;
                $_SESSION['pending_otp_code']  = $otp; // Demo display fallback
                $_SESSION['pending_otp_mode']  = 'register';
                header("Location: verify_otp.php");
                exit();
            } else {
                $errors[] = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

site_header('Register - StepStyle', '');
?>

<h2 class="page-title">Create Account</h2>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<div class="form-box">
    <h3>Register & Get OTP</h3>
    <form method="POST" action="register.php">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Password * (min 6 characters)</label>
            <input type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" required minlength="6">
        </div>
        <button type="submit" class="btn">Register & Get OTP</button>
    </form>
    <p style="text-align:center; margin-top:15px; font-size:13px;">
        Already have an account? <a href="login.php"><strong>Login here</strong></a>
    </p>
</div>

<?php site_footer(); ?>