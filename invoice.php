<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) { header("Location: index.php"); exit(); }

$stmt = mysqli_prepare($conn, "SELECT o.*, o.id AS oid FROM orders o WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$order) { header("Location: index.php"); exit(); }

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
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print { .no-print { display: none !important; } }
        .invoice-inner { width: 700px; margin: 0 auto; padding: 25px; }
        .inv-head { border-bottom: 3px double #1a237e; padding-bottom: 12px; margin-bottom: 15px; }
        .inv-head .brand { font-size: 24px; font-weight: bold; color: #1a237e; float: left; }
        .inv-head .inv-right { float: right; text-align: right; font-size: 13px; }
        .clear { clear: both; }
        table.inv-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.inv-table th, table.inv-table td { border: 1px solid #cccccc; padding: 8px; text-align: left; }
        table.inv-table th { background: #eee; }
        .num-right { text-align: right; }
    </style>
</head>
<body>
<div class="invoice-inner">
    <div class="inv-head">
        <div class="brand">StepStyle</div>
        <div class="inv-right">
            <strong>INVOICE</strong><br>
            Invoice #: #<?= $order['oid'] ?><br>
            <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
        </div>
        <div class="clear"></div>
    </div>

    <h3 style="font-size:15px; margin:10px 0;">Bill To</h3>
    <table class="table" style="width:100%; margin-top:0;">
        <tr><th style="width:130px;">Customer</th><td><?= htmlspecialchars($order['customer_name']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($order['customer_email']) ?></td></tr>
        <?php if ($order['customer_phone']): ?>
        <tr><th>Phone</th><td><?= htmlspecialchars($order['customer_phone']) ?></td></tr>
        <?php endif; ?>
        <tr><th>Address</th><td><?= htmlspecialchars($order['customer_address']) ?></td></tr>
        <tr><th>Payment</th><td><?= strtoupper($order['payment_method']) ?> - <?= ucfirst($order['payment_status']) ?></td></tr>
        <tr><th>Order Status</th><td><?= ucfirst($order['status']) ?></td></tr>
        <?php if (!empty($order['delivery_slot'])): ?>
        <tr><th>Delivery Slot</th><td><?= htmlspecialchars($order['delivery_slot']) ?></td></tr>
        <?php endif; ?>
    </table>

    <table class="inv-table">
        <tr>
            <th>Item</th>
            <th class="num-right">Qty</th>
            <th class="num-right">Unit Price</th>
            <th class="num-right">Total</th>
        </tr>
        <?php while ($item = mysqli_fetch_assoc($items)): ?>
        <tr>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td class="num-right"><?= $item['quantity'] ?></td>
            <td class="num-right">$<?= number_format($item['price'], 2) ?></td>
            <td class="num-right">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="3" class="num-right"><strong>Grand Total</strong></td>
            <td class="num-right"><strong>$<?= number_format($order['total_amount'], 2) ?></strong></td>
        </tr>
    </table>

    <p style="text-align:center; margin-top:20px; font-size:13px;">Thank you for shopping with StepStyle!</p>
</div>

<div class="no-print text-center" style="margin-bottom:30px;">
    <button onclick="window.print()" class="btn">Print Invoice</button>
    <a href="my_orders.php" class="btn btn-gray">Back to My Orders</a>
</div>
</body>
</html>