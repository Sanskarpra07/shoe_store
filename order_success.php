<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$success = $_SESSION['order_success'] ?? '';
unset($_SESSION['order_success']);
$last_order = $_SESSION['last_order'] ?? null;
unset($_SESSION['last_order']);

site_header('Order Placed - StepStyle', '');
?>

<h2 class="page-title">Order Placed Successfully!</h2>

<div class="form-box" style="text-align:center; width:500px;">
    <div style="font-size:52px; color:#2e7d32;">&#10004;</div>
    <?php if ($success): ?>
        <p style="margin-top:15px; font-size:15px;"><?= htmlspecialchars($success) ?></p>
    <?php else: ?>
        <p style="margin-top:15px; font-size:15px;">Thank you for your purchase!</p>
    <?php endif; ?>
    <hr>
    <?php if ($last_order): ?>
        <p style="font-size:14px;">
            <?php if (!empty($last_order['slot'])): ?>
                <strong>Delivery Slot:</strong> <?= htmlspecialchars($last_order['slot']) ?><br>
            <?php endif; ?>
            <strong>Order Total:</strong> $<?= number_format($last_order['total'], 2) ?><br><br>
        </p>
        <a class="btn" href="invoice.php?order_id=<?= $last_order['id'] ?>" target="_blank">View Invoice</a>
    <?php endif; ?>
    <hr>
    <a class="btn btn-green" href="track_order.php">Track Your Order</a>
    <a class="btn" href="shop.php">Continue Shopping</a>
    <a class="btn btn-gray" href="index.php">Home</a>
</div>

<?php site_footer(); ?>