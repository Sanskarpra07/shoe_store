<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

if (!is_customer_logged_in()) {
    $_SESSION['redirect_after_login'] = 'my_orders.php';
    header("Location: login.php");
    exit();
}

$customer_id = (int) $_SESSION['customer_id'];

$view_order_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$order_detail  = null;
$order_items   = null;

if ($view_order_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $view_order_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $order_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($order_detail) {
        $oi = mysqli_query($conn,
            "SELECT oi.*, p.product_name, p.color, p.size
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = $view_order_id"
        );
        while ($r = mysqli_fetch_assoc($oi)) { $order_items[] = $r; }
    }
}

$orders = mysqli_query($conn,
    "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o WHERE o.customer_id = $customer_id ORDER BY o.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<?php frontend_navbar(); ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="my_account.php">My Account</a></li>
            <li class="breadcrumb-item active">My Orders</li>
        </ol>
    </nav>

    <?php if ($order_detail && $order_items): ?>
    <!-- Order Detail View -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-secondary"></i>Order #<?= $order_detail['id'] ?></h6>
                    <div class="d-flex gap-2">
                        <a href="invoice.php?order_id=<?= $order_detail['id'] ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-receipt me-1"></i>View / Print Invoice</a>
                        <a href="my_orders.php" class="btn btn-sm btn-outline-secondary">Back to Orders</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?>
                                    <?php if ($item['size']): ?><small class="text-muted d-block">Size: <?= htmlspecialchars($item['size']) ?></small><?php endif; ?>
                                </td>
                                <td><?= $item['quantity'] ?></td>
                                <td>रु <?= number_format($item['price'], 2) ?></td>
                                <td class="fw-bold">रु <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Order Total</td>
                                <td class="fw-bold fs-5 text-success">रु <?= number_format($order_detail['total_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-3 mb-3">
                <div class="card-header bg-white fw-semibold py-3"><i class="bi bi-truck me-2"></i>Order Status</div>
                <div class="card-body">
                    <span class="badge fs-6 order-status-<?= $order_detail['status'] ?>"><?= strtoupper($order_detail['status']) ?></span>
                    <hr>
                    <div class="timeline">
                        <?php
                        $steps = ['pending' => 'Order Placed', 'processing' => 'Order Processing', 'shipped' => 'Order Shipped', 'delivered' => 'Order Delivered'];
                        $order_status = $order_detail['status'];
                        $current_idx = array_search($order_status, array_keys($steps));
                        $idx = 0;
                        foreach ($steps as $key => $label):
                            $done = $idx <= $current_idx;
                        ?>
                            <div class="d-flex align-items-center mb-3 <?= $done ? 'text-success' : 'text-muted' ?>">
                                <?php if ($key === 'cancelled'): ?>
                                    <i class="bi bi-x-circle me-2"></i>
                                <?php elseif ($done): ?>
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                <?php else: ?>
                                    <i class="bi bi-circle me-2"></i>
                                <?php endif; ?>
                                <span class="small"><?= $label ?></span>
                                <?php if ($done && $key === $order_status): ?>
                                    <span class="badge bg-success ms-auto">Current</span>
                                <?php endif; ?>
                            </div>
                        <?php $idx++; endforeach; ?>
                        <?php if ($order_status === 'cancelled'): ?>
                            <div class="d-flex align-items-center text-danger">
                                <i class="bi bi-x-circle-fill me-2"></i><span class="small fw-bold">Order Cancelled</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white fw-semibold py-3"><i class="bi bi-credit-card me-2"></i>Payment Details</div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Method</span>
                        <span class="fw-semibold text-uppercase"><?= htmlspecialchars($order_detail['payment_method']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status</span>
                        <span class="badge bg-<?= $order_detail['payment_status'] === 'completed' ? 'success' : ($order_detail['payment_status'] === 'failed' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($order_detail['payment_status']) ?>
                        </span>
                    </div>
                    <?php if ($order_detail['transaction_id']): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Transaction</span>
                            <span class="fw-semibold"><?= htmlspecialchars($order_detail['transaction_id']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order_detail['delivery_slot'])): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Delivery Slot</span>
                            <span class="fw-semibold"><?= htmlspecialchars($order_detail['delivery_slot']) ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Placed On</span>
                        <span><?= date('d M Y, h:i A', strtotime($order_detail['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Order List View -->
    <h2 class="section-title">My Orders</h2>
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Order #</th>
                        <th>Date Placed</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($o = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td class="ps-4 fw-semibold">#<?= $o['id'] ?></td>
                        <td class="small"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
                        <td><?= $o['item_count'] ?></td>
                        <td class="fw-bold">रु <?= number_format($o['total_amount'], 2) ?></td>
                        <td>
                            <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($o['payment_method']) ?></span>
                        </td>
                        <td><span class="badge order-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td class="text-center">
                            <a href="my_orders.php?view=<?= $o['id'] ?>" class="btn btn-sm btn-outline-dark">View Details</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($orders) === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            You haven't placed any orders yet.
                            <a href="shop.php" class="btn btn-accent btn-sm mt-2 d-block mx-auto" style="max-width:200px;">Start Shopping</a>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>