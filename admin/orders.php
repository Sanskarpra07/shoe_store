<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN ORDERS
 * --------------------------------------------------------------------------
 * Lists all customer orders with search, shows a full order-detail view,
 * updates order status, cancels orders (restoring stock for paid orders)
 * and processes Khalti wallet refunds for completed Khalti payments.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Orders';
$current_page = 'orders';
require_once 'includes/header.php';
require_once __DIR__ . '/../backend/payment_config.php';

// --- Helper: restore sold quantities back into stock ---------------------------
// Used when an order is cancelled/refunded so the inventory is updated.
function restore_order_stock($conn, $order_id) {
    $items = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($it = mysqli_fetch_assoc($items)) {
        $sstmt = mysqli_prepare($conn, "UPDATE products SET stock = stock + ? WHERE id = ?");
        mysqli_stmt_bind_param($sstmt, "ii", $it['quantity'], $it['product_id']);
        mysqli_stmt_execute($sstmt);
    }
}

// ==============================================================================
// POST ACTIONS: Khalti refund / cancel order / update status
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Action 1: Process a Khalti wallet refund -----------------------------------
    if (($_POST['action'] ?? '') === 'refund_khalti' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $order_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($order_row && $order_row['payment_method'] === 'khalti' && $order_row['payment_status'] === 'completed' && !empty($order_row['transaction_id'])) {
            $refund_amount = (int)round((float)$order_row['total_amount'] * 100);
            $ch = curl_init(KHALTI_REFUND_URL . urlencode($order_row['transaction_id']) . '/refund/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Key ' . KHALTI_SECRET_KEY,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['amount' => $refund_amount]));
            $resp = curl_exec($ch);
            $curl_err = curl_error($ch);
            curl_close($ch);

            $resp_data = json_decode($resp, true);
            if (!empty($curl_err)) {
                $_SESSION['error'] = "Refund request failed: " . $curl_err;
            } elseif (!empty($resp_data['detail'])) {
                // Restore stock and mark order refunded
                restore_order_stock($conn, $id);
                $stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'cancelled', payment_status = 'refunded' WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $_SESSION['success'] = "Refund processed successfully for Order #$id.";
            } else {
                $err_msg = $resp_data['detail'] ?? $resp_data['error'] ?? json_encode($resp_data);
                $_SESSION['error'] = "Khalti refund failed: " . htmlspecialchars($err_msg);
            }
        } else {
            $_SESSION['error'] = "This order is not eligible for a Khalti refund.";
        }
        header("Location: orders.php?view=$id");
        exit();
    }

    // --- Action 2: Cancel an order (invalidate it) -----------------------------------
    if (($_POST['action'] ?? '') === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'cancelled' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $order_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_status, payment_method FROM orders WHERE id = $id"));
        // Restore stock for already-paid orders (COD or completed gateway payments)
        if ($order_row && ($order_row['payment_status'] === 'completed' || $order_row['payment_status'] === 'refunded')) {
            restore_order_stock($conn, $id);
        }
        $_SESSION['success'] = "Order cancelled.";
        header("Location: orders.php");
        exit();
    }

    // --- Action 3: Update the order lifecycle status ----------------------------------
    if (($_POST['action'] ?? '') === 'update_status' && isset($_POST['id']) && isset($_POST['status'])) {
        $id     = (int)$_POST['id'];
        $status = $_POST['status'];
        $valid  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array($status, $valid)) {
            $prev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status, payment_status FROM orders WHERE id = $id"));
            $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            mysqli_stmt_execute($stmt);
            $msg = "Order #$id status updated to $status.";
            // Restore stock when cancelling a previously paid order that still had stock deducted
            if ($status === 'cancelled' && $prev && $prev['status'] !== 'cancelled'
                && ($prev['payment_status'] === 'completed' || $prev['payment_status'] === 'refunded')) {
                restore_order_stock($conn, $id);
                $msg .= " Stock restored to inventory.";
            }
            $_SESSION['success'] = $msg;
        }
        header("Location: orders.php");
        exit();
    }
}

// ==============================================================================
// ORDER DETAIL VIEW (when ?view=<id> is provided)
// ==============================================================================
$view_order_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$order_detail  = null;
$order_items   = [];

if ($view_order_id > 0) {
    // Load the order header.
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $view_order_id);
    mysqli_stmt_execute($stmt);
    $order_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Load the order line items when the order exists.
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

// ==============================================================================
// ORDER LIST (with optional search)
// ==============================================================================
$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    // Search across customer name, email and order number.
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM orders WHERE customer_name LIKE ? OR customer_email LIKE ? OR id LIKE ? ORDER BY created_at DESC");
    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);
} else {
    // No search - list every order newest first.
    $orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");
}

// --- Flash messages ----------------------------------------------------------------
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Orders</h2>
        <p class="page-sub">Track and manage customer orders</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($order_detail): ?>
    <a class="breadcrumb-back" href="orders.php"><i class="bi bi-arrow-left"></i> Back to Orders</a>

    <!-- ======== ORDER DETAIL HEADER ======== -->
    <div class="page-header">
        <div>
            <h2>Order #<?= $order_detail['id'] ?> Details</h2>
            <p class="page-sub">Customer &amp; payment information</p>
        </div>
        <span class="badge badge-<?=
            ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'][$order_detail['status']] ?? 'secondary'
        ?>" style="font-size:14px; padding:8px 16px;"><i class="bi bi-circle-fill" style="font-size:8px;"></i> <?= ucfirst($order_detail['status']) ?></span>
    </div>

    <!-- Customer + payment details panel -->
    <div class="panel-card" style="max-width:640px;">
        <div class="panel-header">
            <h3><i class="bi bi-person-vcard"></i> Customer &amp; Payment Details</h3>
        </div>
        <div class="panel-body">
    <div style="overflow-x:auto;" class="table-responsive">
    <table class="table detail">
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
        </div>
    </div>

    <!-- ======== ORDER ITEMS TABLE ======== -->
    <div class="panel-card">
        <div class="panel-header">
            <h3><i class="bi bi-bag-check"></i> Order Items <span class="text-muted small">(<?= count($order_items) ?>)</span></h3>
        </div>
        <div class="panel-body">
    <div class="table-responsive">
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
            <td class="center">रु <?= number_format($item['price'], 2) ?></td>
            <td class="center">रु <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="5" class="text-right"><strong>Order Total</strong></td>
            <td class="center"><strong style="font-size:16px;">रु <?= number_format($order_detail['total_amount'], 2) ?></strong></td>
        </tr>
    </table>
    </div>
        </div>
    </div>

    <!-- ======== UPDATE ORDER STATUS ======== -->
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

    <!-- ======== KHALTI REFUND ======== -->
    <?php if ($order_detail['payment_method'] === 'khalti' && $order_detail['payment_status'] === 'completed' && $order_detail['status'] !== 'cancelled'): ?>
    <h3 class="section-title">Payment Refund</h3>
    <div class="form-box" style="max-width:420px; margin:0;">
        <form method="POST" action="orders.php">
            <input type="hidden" name="action" value="refund_khalti">
            <input type="hidden" name="id" value="<?= $order_detail['id'] ?>">
            <p class="small text-muted">Process a full refund of रु <?= number_format($order_detail['total_amount'], 2) ?> to the customer's Khalti wallet. Order will be cancelled and stock restored.</p>
            <button type="submit" class="btn btn-red" data-confirm="Process a full Khalti refund for this order? Stock will be restored."><i class="bi bi-arrow-counterclockwise"></i> Refund via Khalti</button>
        </form>
    </div>
    <?php endif; ?>
<?php else: ?>
    <!-- ======== ORDERS TABLE (panel card) ======== -->
    <div class="panel-card">
        <div class="panel-header">
            <h3><i class="bi bi-receipt-cutoff"></i> All Orders</h3>
            <div class="panel-tools">
                <form method="GET" action="orders.php" class="search-row" style="margin:0;">
                    <input type="text" name="search" placeholder="Search by name, email or order #..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-small"><i class="bi bi-search"></i> Search</button>
                    <?php if (!empty($search)): ?><a class="btn btn-gray btn-small" href="orders.php"><i class="bi bi-x-circle"></i> Clear</a><?php endif; ?>
                </form>
            </div>
        </div>
        <div class="panel-body">
    <div class="table-responsive">
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
            <td class="center"><strong>रु <?= number_format($row['total_amount'], 2) ?></strong></td>
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
    </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>