<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT o.*, o.id AS oid FROM orders o WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$order) { header("Location: index.php"); exit(); }

// Auth check: logged-in customer can view own orders, or anyone with tracking email match
$is_owner = false;
if (isset($_SESSION['customer_id'])) {
    $is_owner = ((int)$order['customer_id'] === (int)$_SESSION['customer_id']);
}
if (!$is_owner) {
    // Allow access via track_order style (no auth required for invoice) but only for completed orders
    if ($order['payment_status'] !== 'completed' && $order['status'] === 'cancelled') {
        header("Location: index.php");
        exit();
    }
}

$items_stmt = mysqli_prepare($conn,
    "SELECT oi.*, p.product_name, p.image FROM order_items oi
     JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items = mysqli_stmt_get_result($items_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order['oid'] ?> - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } body { font-size: 12px; } }
        .invoice-box { max-width: 700px; margin: 0 auto; padding: 30px; }
        .brand { font-weight: 700; font-size: 20px; color: #1a1a2e; }
        .line-total { font-weight: 600; }
    </style>
</head>
<body>
<div class="invoice-box">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="brand">StepStyle</div>
            <div class="small text-muted">Durbar Marg, Kathmandu, Nepal</div>
            <div class="small text-muted">info@stepstyle.com | +977-1-456789</div>
        </div>
        <div class="text-end">
            <h4 class="fw-bold mb-1">INVOICE</h4>
            <div class="small">Invoice #: <strong>#<?= $order['oid'] ?></strong></div>
            <div class="small text-muted"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
        </div>
    </div>
    <hr>
    <div class="row my-4">
        <div class="col-6">
            <h6 class="text-muted">BILL TO</h6>
            <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
            <?= htmlspecialchars($order['customer_email']) ?><br>
            <?php if ($order['customer_phone']): ?><?= htmlspecialchars($order['customer_phone']) ?><br><?php endif; ?>
            <?= htmlspecialchars($order['customer_address']) ?>
        </div>
        <div class="col-6 text-end">
            <h6 class="text-muted">PAYMENT</h6>
            <div>Method: <strong class="text-uppercase"><?= strtoupper($order['payment_method']) ?></strong></div>
            <div>Status: <span class="badge bg-<?= $order['payment_status']==='completed'?'success':'warning' ?>"><?= ucfirst($order['payment_status']) ?></span></div>
            <div>Order Status: <span class="badge bg-info"><?= ucfirst($order['status']) ?></span></div>
            <?php if (!empty($order['delivery_slot'])): ?>
            <div>Delivery Slot: <strong><?= htmlspecialchars($order['delivery_slot']) ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
    <table class="table">
        <thead class="table-light">
            <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr>
        </thead>
        <tbody>
        <?php while ($item = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-end">रु <?= number_format($item['price'], 2) ?></td>
                <td class="text-end line-total">रु <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="text-end fw-bold">Grand Total</td><td class="text-end fw-bold">रु <?= number_format($order['total_amount'], 2) ?></td></tr>
        </tfoot>
    </table>
    <div class="text-center text-muted small mt-4">Thank you for shopping with StepStyle!</div>
</div>
<div class="text-center no-print my-4">
    <button onclick="window.print()" class="btn btn-dark"><i class="bi bi-printer me-1"></i>Print Invoice</button>
    <a href="my_orders.php" class="btn btn-outline-secondary ms-2">Back to My Orders</a>
</div>
</body>
</html>