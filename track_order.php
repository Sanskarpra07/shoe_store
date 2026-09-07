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
                "SELECT oi.*, p.product_name, p.color, p.size
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = $order_id"
            );
            while ($r = mysqli_fetch_assoc($oi)) { $items[] = $r; }
        }
        $searched = true;
    }
}

site_header('Track Order - StepStyle', 'track');
?>

<h2 class="page-title">Track Your Order</h2>
<p>Enter your order number and the email you used at checkout to see the latest status.</p>

<div class="form-box">
    <h3>Search Your Order</h3>
    <form method="POST" action="track_order.php">
        <div class="form-group">
            <label>Order Number *</label>
            <input type="number" name="order_id" required placeholder="e.g. 1" value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <button type="submit" class="btn">Track Order</button>
    </form>
</div>

<?php if ($searched && !$order_found): ?>
    <div class="msg-error mt-20">No order found matching that order number and email.</div>
<?php elseif ($order_found): ?>
    <h3 class="section-title">Order #<?= $order_found['id'] ?> - <span style="color:#c62828;"><?= strtoupper($order_found['status']) ?></span></h3>

    <table class="table" style="width:500px;">
        <tr>
            <th style="width:160px;">Status</th>
            <td><strong><?= ucfirst($order_found['status']) ?></strong></td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td><?= strtoupper($order_found['payment_method']) ?></td>
        </tr>
        <tr>
            <th>Payment Status</th>
            <td><?= ucfirst($order_found['payment_status']) ?></td>
        </tr>
        <?php if (!empty($order_found['delivery_slot'])): ?>
        <tr>
            <th>Delivery Slot</th>
            <td><?= htmlspecialchars($order_found['delivery_slot']) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Placed On</th>
            <td><?= date('d M Y, h:i A', strtotime($order_found['created_at'])) ?></td>
        </tr>
        <tr>
            <th>Shipping To</th>
            <td><?= htmlspecialchars($order_found['customer_address']) ?></td>
        </tr>
    </table>

    <h3 class="section-title">Order Items</h3>
    <table class="table" style="width:500px;">
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
        </tr>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td class="center">x<?= $item['quantity'] ?></td>
            <td class="center">$<?= number_format($item['price'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2" class="text-right"><strong>Total</strong></td>
            <td class="center"><strong>$<?= number_format($order_found['total_amount'], 2) ?></strong></td>
        </tr>
    </table>
<?php endif; ?>

<?php site_footer(); ?>