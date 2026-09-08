<?php
session_start();
$page_title = 'Orders';
$current_page = 'orders';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'cancelled' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['success'] = "Order cancelled.";
        header("Location: orders.php");
        exit();
    }

    if (($_POST['action'] ?? '') === 'update_status' && isset($_POST['id']) && isset($_POST['status'])) {
        $id     = (int)$_POST['id'];
        $status = $_POST['status'];
        $valid  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array($status, $valid)) {
            $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            mysqli_stmt_execute($stmt);
            $_SESSION['success'] = "Order #$id status updated to $status.";
        }
        header("Location: orders.php");
        exit();
    }
}

// Order detail view
$view_order_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$order_detail  = null;
$order_items   = [];

if ($view_order_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $view_order_id);
    mysqli_stmt_execute($stmt);
    $order_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($order_detail) {
        $oi = mysqli_query($conn,
            "SELECT oi.*, p.product_name, p.size, p.color
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = $view_order_id"
        );
        while ($r = mysqli_fetch_assoc($oi)) { $order_items[] = $r; }
    }
}

$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM orders WHERE customer_name LIKE ? OR customer_email LIKE ? OR id LIKE ? ORDER BY created_at DESC");
    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);
} else {
    $orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>

<div class="page-header">
    <div>
        <h2>Orders</h2>
        <p class="page-sub">Track and manage customer orders</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($order_detail): ?>
    <a class="breadcrumb-back" href="orders.php"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    <div class="page-header">
        <div>
            <h2>Order #<?= $order_detail['id'] ?> Details</h2>
            <p class="page-sub">Customer &amp; payment information</p>
        </div>
        <span class="badge badge-<?=
            ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'][$order_detail['status']] ?? 'secondary'
        ?>" style="font-size:14px; padding:8px 16px;"><i class="bi bi-circle-fill" style="font-size:8px;"></i> <?= ucfirst($order_detail['status']) ?></span>
    </div>
    <div style="overflow-x:auto;">
    <table class="table detail" style="max-width:640px;">
        <tr><td>Customer</td><td><strong><?= htmlspecialchars($order_detail['customer_name']) ?></strong></td></tr>
        <tr><td>Email</td><td><?= htmlspecialchars($order_detail['customer_email']) ?></td></tr>
        <tr><td>Phone</td><td><?= htmlspecialchars($order_detail['customer_phone'] ?? '-') ?></td></tr>
        <tr><td>Address</td><td><?= htmlspecialchars($order_detail['customer_address']) ?></td></tr>
        <tr><td>Delivery Slot</td><td><?= htmlspecialchars($order_detail['delivery_slot'] ?: '-') ?></td></tr>
        <tr><td>Payment Method</td><td><span class="badge badge-secondary"><?= strtoupper($order_detail['payment_method']) ?></span></td></tr>
        <tr><td>Payment Status</td><td><?= ucfirst($order_detail['payment_status']) ?></td></tr>
        <?php if ($order_detail['transaction_id']): ?>
        <tr><td>Transaction ID</td><td><?= htmlspecialchars($order_detail['transaction_id']) ?></td></tr>
        <?php endif; ?>
        <tr><td>Order Date</td><td><?= date('d M Y, h:i A', strtotime($order_detail['created_at'])) ?></td></tr>
    </table>
    </div>

    <table class="table">
        <tr>
            <th>Product</th>
            <th>Size</th>
            <th>Color</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
        <?php foreach ($order_items as $item): ?>
        <tr>
            <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
            <td class="center"><?= htmlspecialchars($item['size'] ?? '-') ?></td>
            <td class="center"><?= htmlspecialchars($item['color'] ?? '-') ?></td>
            <td class="center"><?= $item['quantity'] ?></td>
            <td class="center">$<?= number_format($item['price'], 2) ?></td>
            <td class="center">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="5" class="text-right"><strong>Order Total</strong></td>
            <td class="center"><strong style="font-size:16px;">$<?= number_format($order_detail['total_amount'], 2) ?></strong></td>
        </tr>
    </table>

    <h3 class="section-title">Update Status</h3>
    <div class="form-box" style="max-width:420px; margin:0;">
        <form method="POST" action="orders.php">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" value="<?= $order_detail['id'] ?>">
            <div class="form-group">
                <label>Order Status</label>
                <select name="status">
                    <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order_detail['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-green"><i class="bi bi-check-lg"></i> Update Status</button>
        </form>
    </div>
<?php else: ?>
    <form method="GET" action="orders.php" class="search-row">
        <input type="text" name="search" placeholder="Search by customer name, email or order #..."
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit" class="btn btn-small"><i class="bi bi-search"></i> Search</button>
        <?php if (!empty($search)): ?><a class="btn btn-gray btn-small" href="orders.php"><i class="bi bi-x-circle"></i> Clear</a><?php endif; ?>
    </form>

    <table class="table">
        <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <?php $sno = 1; while ($row = mysqli_fetch_assoc($orders)): ?>
        <tr>
            <td class="center"><strong>#<?= $row['id'] ?></strong></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['customer_email']) ?></td>
            <td class="center"><strong>$<?= number_format($row['total_amount'], 2) ?></strong></td>
            <td class="center"><span class="badge badge-secondary"><?= strtoupper($row['payment_method']) ?></span></td>
            <td class="center">
                <span class="badge badge-<?=
                    ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'][$row['status']] ?? 'secondary'
                ?>"><?= ucfirst($row['status']) ?></span>
            </td>
            <td class="center"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td class="center">
                <a class="btn btn-small" href="orders.php?view=<?= $row['id'] ?>"><i class="bi bi-eye"></i> View / Update</a>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (mysqli_num_rows($orders) === 0): ?>
        <tr>
            <td colspan="8"><div class="empty-state"><i class="bi bi-inbox"></i>No orders found.</div></td>
        </tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>