<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

if (!is_customer_logged_in()) {
    $_SESSION['redirect_after_login'] = 'my_account.php';
    header("Location: login.php");
    exit();
}

$customer_id = (int) $_SESSION['customer_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$customer) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$errors = [];
$success = "";

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if (empty($full_name)) $errors[] = "Full name is required.";

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $full_name, $phone, $address, $customer_id);
        mysqli_stmt_execute($stmt);
        $_SESSION['customer_name'] = $full_name;
        $success = "Profile updated successfully!";
        $customer['full_name'] = $full_name;
        $customer['phone']     = $phone;
        $customer['address']   = $address;
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $customer['password_eg'])) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $errors[] = "New passwords do not match.";
    } elseif ($new === $current) {
        $errors[] = "New password cannot be the same as the current password.";
    }

    if (empty($errors)) {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE customers SET password_eg = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $customer_id);
        mysqli_stmt_execute($stmt);
        $success = "Password changed successfully!";
    }
}

// Recent orders for dashboard
$recent_orders = mysqli_query($conn,
    "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o WHERE o.customer_id = $customer_id ORDER BY o.created_at DESC LIMIT 5"
);

$total_spent = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE customer_id = $customer_id AND status != 'cancelled'"
))['total'];

$order_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM orders WHERE customer_id = $customer_id"
))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<?php frontend_navbar(); ?>

<div class="container py-5">
    <?php if (isset($_GET['verified'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i>Your email has been verified successfully! Welcome to StepStyle.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body text-center p-4">
                    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:80px; height:80px; font-size:2rem;">
                        <?= strtoupper($customer['full_name'][0]) ?>
                    </div>
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($customer['full_name']) ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars($customer['email']) ?></p>
                    <?php if ($customer['is_verified']): ?>
                        <span class="badge bg-success"><i class="bi bi-patch-check me-1"></i>Verified</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Not Verified</span>
                    <?php endif; ?>
                    <hr>
                    <a href="my_account.php" class="d-block text-decoration-none mb-2"><i class="bi bi-person me-2"></i>Profile</a>
                    <a href="my_orders.php" class="d-block text-decoration-none"><i class="bi bi-box-seam me-2"></i>My Orders</a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fs-3 fw-bold"><?= $order_count ?></div>
                                <div class="text-muted small">Total Orders</div>
                            </div>
                            <i class="bi bi-bag fs-2 text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fs-3 fw-bold">रु <?= number_format($total_spent, 2) ?></div>
                                <div class="text-muted small">Total Spent</div>
                            </div>
                            <i class="bi bi-cash-stack fs-2 text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fs-3 fw-bold">
                                    <?php
                                    $reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM reviews WHERE customer_id = $customer_id"));
                                    echo $reviews['c'];
                                    ?>
                                </div>
                                <div class="text-muted small">Reviews Written</div>
                            </div>
                            <i class="bi bi-star fs-2 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i>Edit Profile
                </div>
                <div class="card-body">
                    <form method="POST" action="my_account.php">
                        <input type="hidden" name="action" value="profile">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($customer['full_name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Address</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-accent"><i class="bi bi-check me-1"></i>Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-key me-2 text-warning"></i>Change Password
                </div>
                <div class="card-body">
                    <form method="POST" action="my_account.php">
                        <input type="hidden" name="action" value="password">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Current Password <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Confirm New <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark"><i class="bi bi-shield-lock me-1"></i>Change Password</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-secondary"></i>Recent Orders</h6>
                    <a href="my_orders.php" class="btn btn-sm btn-outline-dark">View All</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($o = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td class="ps-4 fw-semibold">
                                    <a href="my_orders.php?view=<?= $o['id'] ?>" class="text-decoration-none">#<?= $o['id'] ?></a>
                                </td>
                                <td class="small"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
                                <td class="fw-bold">रु <?= number_format($o['total_amount'], 2) ?></td>
                                <td><span class="badge bg-secondary"><?= strtoupper($o['payment_method']) ?></span></td>
                                <td><span class="badge order-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($recent_orders) === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No orders yet. <a href="shop.php">Start shopping!</a></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>