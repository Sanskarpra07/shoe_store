<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

// Load cart items
$cart_items = [];
$total = 0;
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

if (empty($cart_items)) {
    header("Location: shop.php");
    exit();
}

// Load active delivery slots
$slots = mysqli_query($conn,
    "SELECT * FROM delivery_slots WHERE is_active = 1 ORDER BY slot_time ASC");

$errors    = $_SESSION['checkout_errors'] ?? [];
$prev      = $_SESSION['checkout_data'] ?? [];
unset($_SESSION['checkout_errors'], $_SESSION['checkout_data']);

// Pre-fill from logged-in customer
if (is_customer_logged_in()) {
    $cid = (int)$_SESSION['customer_id'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $cid);
    mysqli_stmt_execute($stmt);
    $cust = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($cust) {
        $name    = $prev['name']  ?? $cust['full_name'];
        $email   = $prev['email'] ?? $cust['email'];
        $phone   = $prev['phone'] ?? $cust['phone'];
        $address = $prev['address'] ?? $cust['address'];
    }
} else {
    $name    = $prev['name'] ?? '';
    $email   = $prev['email'] ?? '';
    $phone   = $prev['phone'] ?? '';
    $address = $prev['address'] ?? '';
}
$selected_payment = $prev['payment'] ?? 'cod';
$selected_slot    = $prev['delivery_slot'] ?? '';

site_header('Checkout - StepStyle', '');
?>

<h2 class="page-title">Checkout</h2>

<?php if (!is_customer_logged_in()): ?>
    <div class="msg-info">
        You are checking out as a guest.
        <a href="login.php"><strong>Login</strong></a> to save your address and track orders.
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<form method="POST" action="process_order.php">
    <table class="table" style="width:100%;">
        <tr>
            <th>Full Name *</th>
            <td><input type="text" name="name" required style="width:100%; padding:7px;"
                       value="<?= htmlspecialchars($name) ?>"></td>
        </tr>
        <tr>
            <th style="width:220px;">Email *</th>
            <td><input type="email" name="email" required style="width:100%; padding:7px;"
                       value="<?= htmlspecialchars($email) ?>"></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td><input type="text" name="phone" style="width:100%; padding:7px;"
                       value="<?= htmlspecialchars($phone) ?>"></td>
        </tr>
        <tr>
            <th>Shipping Address *</th>
            <td><textarea name="address" rows="3" required style="width:100%; padding:7px;"
                          placeholder="Street, City, District, etc."><?= htmlspecialchars($address) ?></textarea></td>
        </tr>
        <tr>
            <th>Delivery Slot</th>
            <td>
                <select name="delivery_slot" style="width:100%; padding:7px;">
                    <option value="">-- Select a delivery slot (optional) --</option>
                    <?php while ($s = mysqli_fetch_assoc($slots)): ?>
                        <option value="<?= htmlspecialchars($s['slot_name']) ?>"
                            <?= $selected_slot === $s['slot_name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['slot_name'] . ' (' . $s['slot_time'] . ')') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Payment Method *</th>
            <td>
                <label><input type="radio" name="payment_method" value="cod" <?= $selected_payment === 'cod' ? 'checked' : '' ?>> Cash on Delivery (COD)</label><br>
                <label><input type="radio" name="payment_method" value="esewa" <?= $selected_payment === 'esewa' ? 'checked' : '' ?>> eSewa</label><br>
                <label><input type="radio" name="payment_method" value="khalti" <?= $selected_payment === 'khalti' ? 'checked' : '' ?>> Khalti</label>
            </td>
        </tr>
    </table>

    <div class="summary" style="float:right; width:340px;">
        <div class="line"><span>Subtotal (<?= count($cart_items) ?> items)</span><span>$<?= number_format($total, 2) ?></span></div>
        <div class="line"><span>Shipping</span><span>Free</span></div>
        <div class="line total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>
        <div style="margin-top:12px;">
            <button type="submit" class="btn btn-green" style="width:100%;">Place Order - $<?= number_format($total, 2) ?></button>
        </div>
    </div>

    <div style="margin-top:12px; overflow:hidden;">
        <?php foreach ($cart_items as $item): ?>
            <div class="summary" style="margin-bottom:6px;">
                <?= htmlspecialchars($item['product_name']) ?> x <?= $item['qty'] ?>
                = $<?= number_format($item['line_total'], 2) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="clear:both;"></div>
</form>

<?php site_footer(); ?>