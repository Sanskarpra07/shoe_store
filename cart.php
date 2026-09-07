<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$remove_id]);
    header("Location: cart.php");
    exit();
}

if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $pid => $qty) {
        $qty = max(1, (int)$qty);
        $_SESSION['cart'][(int)$pid] = $qty;
    }
    header("Location: cart.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id IN ($placeholders)");
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $qty = $cart[$row['id']] ?? 1;
        $price = $row['discount_price'] ?: $row['price'];
        $row['qty'] = $qty;
        $row['line_total'] = $price * $qty;
        $total += $row['line_total'];
        $cart_items[] = $row;
    }
}

$cart_count = array_sum($cart);

site_header('Shopping Cart - StepStyle', '');
?>

<h2 class="page-title">Shopping Cart</h2>

<?php if (!empty($_SESSION['cart_error'])): ?>
    <div class="msg-error"><?= htmlspecialchars($_SESSION['cart_error']) ?></div>
    <?php unset($_SESSION['cart_error']); ?>
<?php endif; ?>

<?php if (empty($cart_items)): ?>
    <div class="text-center mt-20">
        <p>Your cart is empty.</p>
        <a class="btn" href="shop.php">Start Shopping</a>
    </div>
<?php else: ?>
    <form method="POST" action="cart.php">
        <input type="hidden" name="update_cart" value="1">
        <table class="table">
            <tr>
                <th>Product</th>
                <th style="width:120px;">Price</th>
                <th style="width:120px;">Quantity</th>
                <th style="width:120px;">Total</th>
                <th style="width:100px;">Action</th>
            </tr>
            <?php foreach ($cart_items as $item): ?>
            <tr>
                <td>
                    <a href="product.php?id=<?= $item['id'] ?>"><strong><?= htmlspecialchars($item['product_name']) ?></strong></a>
                    <?php if ($item['color']): ?><br><span class="small text-muted">Color: <?= htmlspecialchars($item['color']) ?></span><?php endif; ?>
                    <?php if ($item['size']): ?><br><span class="small text-muted">Size: <?= htmlspecialchars($item['size']) ?></span><?php endif; ?>
                </td>
                <td class="center"><?= price_label($item['discount_price'] ?: $item['price']) ?></td>
                <td class="center">
                    <input type="number" name="quantity[<?= $item['id'] ?>]" value="<?= $item['qty'] ?>" min="1" max="<?= $item['stock'] ?>" style="width:60px; padding:5px;">
                </td>
                <td class="center"><strong><?= price_label($item['line_total']) ?></strong></td>
                <td class="center">
                    <a class="btn btn-red btn-small" href="cart.php?remove=<?= $item['id'] ?>" onclick="return confirm('Remove this item?')">Remove</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p>
            <a class="btn btn-gray" href="shop.php">&laquo; Continue Shopping</a>
            <button type="submit" class="btn">Update Cart</button>
            <a class="btn btn-red" href="cart.php?clear=1" onclick="return confirm('Clear cart?')">Clear Cart</a>
        </p>
    </form>

    <div class="summary" style="width:350px; float:right;">
        <div class="line"><span>Subtotal (<?= $cart_count ?> items)</span><span><?= price_label($total) ?></span></div>
        <div class="line"><span>Shipping</span><span>Free</span></div>
        <div class="line total"><span>Total</span><span><?= price_label($total) ?></span></div>
        <div style="margin-top:12px; text-align:center;">
            <a class="btn btn-green" href="checkout.php" style="width:100%;">Proceed to Checkout</a>
        </div>
    </div>
    <div style="clear:both;"></div>
<?php endif; ?>

<?php site_footer(); ?>