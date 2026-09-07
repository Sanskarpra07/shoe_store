<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (!is_customer_logged_in()) {
    $_SESSION['redirect_after_login'] = 'my_orders.php';
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];

$view_order_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$order_detail  = null;
$order_items   = [];

if ($view_order_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $view_order_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $order_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($order_detail) {
        $oi = mysqli_query($conn,
            "SELECT oi.*, p.product_name, p.color, p.size, p.image
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

site_header("My Orders - StepStyle", '');
?>

<h2 class="page-title">My Orders</h2>
<p><a href="my_account.php">&laquo; Back to My Account</a></p>

<?php if ($order_detail): ?>
    <h3 class="section-title">Order #<?= $order_detail['id'] ?></h3>

    <table class="table">
        <tr>
            <th style="width:200px;">Placed On</th>
            <td><?= date('d M Y, h:i A', strtotime($order_detail['created_at'])) ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><strong><?= ucfirst($order_detail['status']) ?></strong></td>
        </tr>
        <tr>
            <th>Payment Method</th>
            <td><?= strtoupper($order_detail['payment_method']) ?></td>
        </tr>
        <tr>
            <th>Payment Status</th>
            <td><?= ucfirst($order_detail['payment_status']) ?></td>
        </tr>
        <?php if (!empty($order_detail['transaction_id'])): ?>
        <tr>
            <th>Transaction ID</th>
            <td><?= htmlspecialchars($order_detail['transaction_id']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($order_detail['delivery_slot'])): ?>
        <tr>
            <th>Delivery Slot</th>
            <td><?= htmlspecialchars($order_detail['delivery_slot']) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Shipping Address</th>
            <td><?= htmlspecialchars($order_detail['customer_address']) ?></td>
        </tr>
    </table>

    <table class="table">
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
        <?php foreach ($order_items as $item): ?>
        <tr>
            <td>
                <a href="product.php?id=<?= $item['product_id'] ?>"><strong><?= htmlspecialchars($item['product_name']) ?></strong></a>
                <?php if (!empty($item['size'])): ?><br><span class="small text-muted">Size: <?= htmlspecialchars($item['size']) ?></span><?php endif; ?>
            </td>
            <td class="center"><?= $item['quantity'] ?></td>
            <td class="center">$<?= number_format($item['price'], 2) ?></td>
            <td class="center">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3" class="text-right"><strong>Order Total</strong></td>
            <td class="center"><strong>$<?= number_format($order_detail['total_amount'], 2) ?></strong></td>
        </tr>
    </table>
    <p>
        <a class="btn" href="invoice.php?order_id=<?= $order_detail['id'] ?>" target="_blank">View / Print Invoice</a>
        <a class="btn" href="my_orders.php">&laquo; Back to My Orders</a>
    </p>
<?php else: ?>
    <table class="table">
        <tr>
            <th>Order #</th>
            <th>Date Placed</th>
            <th>Items</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($o = mysqli_fetch_assoc($orders)): ?>
        <tr>
            <td class="center"><strong>#<?= $o['id'] ?></strong></td>
            <td class="center"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
            <td class="center"><?= $o['item_count'] ?></td>
            <td class="center">$<?= number_format($o['total_amount'], 2) ?></td>
            <td class="center"><?= strtoupper($o['payment_method']) ?></td>
            <td class="center"><?= ucfirst($o['status']) ?></td>
            <td class="center">
                <a class="btn btn-small" href="my_orders.php?view=<?= $o['id'] ?>">View Details</a>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if (mysqli_num_rows($orders) === 0): ?>
        <tr>
            <td colspan="7" class="center">You haven't placed any orders yet. <a href="shop.php">Start shopping</a></td>
        </tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php site_footer(); ?>