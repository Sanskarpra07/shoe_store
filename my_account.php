<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (!is_customer_logged_in()) {
    $_SESSION['redirect_after_login'] = 'my_account.php';
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $customer['password_eg'])) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $errors[] = "New passwords do not match.";
    }

    if (empty($errors)) {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE customers SET password_eg = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $customer_id);
        mysqli_stmt_execute($stmt);
        $success = "Password changed successfully!";
    }
}

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

$reviews_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM reviews WHERE customer_id = $customer_id"
))['c'];

site_header("My Account - StepStyle", '');
?>

<h2 class="page-title">My Account</h2>

<?php if (isset($_GET['verified'])): ?>
    <div class="msg-success">Your email has been verified successfully! Welcome to StepStyle.</div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<div class="summary" style="float:left; width:300px; margin-right:20px; text-align:center;">
    <div style="font-size:48px; color:#1a237e; font-weight:bold;"><?= strtoupper(substr($customer['full_name'], 0, 1)) ?></div>
    <h3><?= htmlspecialchars($customer['full_name']) ?></h3>
    <p class="small text-muted"><?= htmlspecialchars($customer['email']) ?></p>
    <p><span class="btn btn-green btn-small">Verified</span></p>
    <hr>
    <p><a href="my_orders.php"><strong>My Orders</strong></a></p>
    <p><a href="wishlist.php"><strong>My Wishlist</strong></a></p>
    <p><a href="logout.php">Logout</a></p>
</div>

<div style="float:left; width:620px;">
    <p>
        <span class="btn btn-gray" style="cursor:default;">Total Orders: <strong><?= $order_count ?></strong></span>
        <span class="btn btn-gray" style="cursor:default;">Total Spent: <strong>$<?= number_format($total_spent, 2) ?></strong></span>
        <span class="btn btn-gray" style="cursor:default;">Reviews Written: <strong><?= $reviews_count ?></strong></span>
    </p>

    <h3 class="section-title">Edit Profile</h3>
    <div class="form-box" style="width:100%;">
        <form method="POST" action="my_account.php">
            <input type="hidden" name="action" value="profile">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($customer['full_name']) ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($customer['email']) ?>" disabled>
                <span class="small text-muted">Email cannot be changed</span>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-green">Save Changes</button>
        </form>
    </div>

    <h3 class="section-title">Change Password</h3>
    <div class="form-box" style="width:100%;">
        <form method="POST" action="my_account.php">
            <input type="hidden" name="action" value="password">
            <div class="form-group">
                <label>Current Password *</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password * (min 6 characters)</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm New Password *</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="btn">Change Password</button>
        </form>
    </div>

    <h3 class="section-title">Recent Orders</h3>
    <table class="table">
        <tr>
            <th>Order #</th>
            <th>Date</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
        </tr>
        <?php while ($o = mysqli_fetch_assoc($recent_orders)): ?>
        <tr>
            <td class="center"><a href="my_orders.php?view=<?= $o['id'] ?>"><strong>#<?= $o['id'] ?></strong></a></td>
            <td class="center"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
            <td class="center">$<?= number_format($o['total_amount'], 2) ?></td>
            <td class="center"><?= strtoupper($o['payment_method']) ?></td>
            <td class="center"><?= ucfirst($o['status']) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (mysqli_num_rows($recent_orders) === 0): ?>
        <tr><td colspan="5" class="center">No orders yet. <a href="shop.php">Start shopping!</a></td></tr>
        <?php endif; ?>
    </table>
    <p><a href="my_orders.php">View all orders &raquo;</a></p>
</div>
<div style="clear:both;"></div>

<?php site_footer(); ?>