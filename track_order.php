<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$order_found = null;
$items       = [];
$searched    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $email    = trim($_POST['email'] ?? '');

    if ($order_id > 0 && !empty($email)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ? AND customer_email = ?");
        mysqli_stmt_bind_param($stmt, "is", $order_id, $email);
        mysqli_stmt_execute($stmt);
        $order_found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($order_found) {
            $oi = mysqli_query($conn,
                "SELECT oi.*, p.product_name
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = $order_id"
            );
            while ($r = mysqli_fetch_assoc($oi)) { $items[] = $r; }
        }
        $searched = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/frontend.css" rel="stylesheet">
</head>
<body>

<?php frontend_navbar('track'); ?>

<div class="container py-5">
    <h2 class="section-title">Track Your Order</h2>
    <p class="text-muted">Enter your order number and the email you used at checkout to see the latest status.</p>

    <div class="card shadow-sm border-0 rounded-3 mx-auto" style="max-width: 500px;">
        <div class="card-body p-4">
            <form method="POST" action="track_order.php">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Order Number <span class="text-danger">*</span></label>
                    <input type="number" name="order_id" class="form-control" required
                           placeholder="e.g. 1" value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-accent w-100">
                    <i class="bi bi-search me-1"></i>Track Order
                </button>
            </form>
        </div>
    </div>

    <?php if ($searched && !$order_found): ?>
        <div class="alert alert-danger text-center mx-auto mt-4" style="max-width:500px;">
            <i class="bi bi-x-circle me-1"></i>
            No order found matching that order number and email.
        </div>
    <?php elseif ($order_found): ?>
        <div class="card shadow-sm border-0 rounded-3 mt-5">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-box me-2 text-primary"></i>Order #<?= $order_found['id'] ?></h6>
                <span class="badge fs-6 order-status-<?= $order_found['status'] ?>"><?= strtoupper($order_found['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-bag me-2"></i>Order Items</h6>
                        <table class="table table-sm align-middle">
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td>x<?= $item['quantity'] ?></td>
                                    <td class="text-end fw-semibold">$<?= number_format($item['price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold text-success">$<?= number_format($order_found['total_amount'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="col-md-7">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-truck me-2"></i>Delivery Status</h6>
                        <?php
                        $steps = ['pending' => 'Order Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
                        $current_idx = array_search($order_found['status'], array_keys($steps));
                        $idx = 0;
                        foreach ($steps as $key => $label):
                            $done = $order_found['status'] !== 'cancelled' && $idx <= (int)$current_idx;
                        ?>
                            <div class="d-flex align-items-center mb-3 <?= $done ? 'text-success' : 'text-muted' ?>">
                                <?= $done ? '<i class="bi bi-check-circle-fill me-2"></i>' : '<i class="bi bi-circle me-2"></i>' ?>
                                <span><?= $label ?></span>
                                <?php if ($done && $key === $order_found['status']): ?>
                                    <span class="badge bg-success ms-2">Now</span>
                                <?php endif; ?>
                            </div>
                        <?php $idx++; endforeach; ?>
                        <?php if ($order_found['status'] === 'cancelled'): ?>
                            <div class="d-flex align-items-center text-danger">
                                <i class="bi bi-x-circle-fill me-2"></i><span class="fw-bold">Order Cancelled</span>
                            </div>
                        <?php endif; ?>

                        <hr>
                        <div class="row small">
                            <div class="col-6">
                                <span class="text-muted">Payment Method</span>
                                <div class="fw-semibold text-uppercase"><?= htmlspecialchars($order_found['payment_method']) ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Payment Status</span>
                                <div>
                                    <span class="badge bg-<?= $order_found['payment_status'] === 'completed' ? 'success' : ($order_found['payment_status'] === 'failed' ? 'danger' : 'warning') ?>">
                                        <?= ucfirst($order_found['payment_status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <span class="text-muted">Placed On</span>
                                <div class="fw-semibold"><?= date('d M Y, h:i A', strtotime($order_found['created_at'])) ?></div>
                            </div>
                            <div class="col-12 mt-2">
                                <span class="text-muted">Shipping To</span>
                                <div class="fw-semibold"><?= htmlspecialchars($order_found['customer_address']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>