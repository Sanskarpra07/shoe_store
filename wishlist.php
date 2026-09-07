<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if (!is_customer_logged_in()) {
    $_SESSION['redirect_after_login'] = 'wishlist.php';
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_wishlist'])) {
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($pid > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $pid);
        mysqli_stmt_execute($stmt);
        $_SESSION['wishlist_action'] = 'removed';
    }
    header("Location: wishlist.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_to_cart'])) {
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($pid > 0) {
        $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + 1;
        $stmt = mysqli_prepare($conn, "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $pid);
        mysqli_stmt_execute($stmt);
        $_SESSION['wishlist_action'] = 'moved';
    }
    header("Location: cart.php");
    exit();
}

$wishlist_action = $_SESSION['wishlist_action'] ?? '';
unset($_SESSION['wishlist_action']);

$stmt = mysqli_prepare($conn,
    "SELECT w.id AS wishlist_id, w.created_at, p.*
     FROM wishlists w
     JOIN products p ON w.product_id = p.id
     WHERE w.customer_id = ?
     ORDER BY w.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$wishlist = mysqli_stmt_get_result($stmt);

site_header("My Wishlist - StepStyle", '');
?>

<h2 class="page-title">My Wishlist</h2>
<p><a href="my_account.php">&laquo; Back to My Account</a></p>

<?php if ($wishlist_action === 'added'): ?>
    <div class="msg-success">Product added to your wishlist.</div>
<?php elseif ($wishlist_action === 'removed'): ?>
    <div class="msg-info">Product removed from your wishlist.</div>
<?php elseif ($wishlist_action === 'moved'): ?>
    <div class="msg-success">Product moved to your shopping cart.</div>
<?php endif; ?>

<table class="table">
    <tr>
        <th>Product</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Actions</th>
    </tr>
    <?php if (mysqli_num_rows($wishlist) > 0): ?>
        <?php while ($item = mysqli_fetch_assoc($wishlist)): ?>
        <tr>
            <td>
                <a href="product.php?id=<?= $item['id'] ?>"><strong><?= htmlspecialchars($item['product_name']) ?></strong></a>
                <?php if (!empty($item['size'])): ?><br><span class="small text-muted">Size: <?= htmlspecialchars($item['size']) ?></span><?php endif; ?>
            </td>
            <td class="center">$<?= number_format($item['discount_price'] ?: $item['price'], 2) ?></td>
            <td class="center">
                <?php if ($item['stock'] > 0): ?>
                    <span style="color:#2e7d32;">In Stock (<?= $item['stock'] ?>)</span>
                <?php else: ?>
                    <span style="color:#c62828;">Out of Stock</span>
                <?php endif; ?>
            </td>
            <td class="center">
                <form method="POST" action="wishlist.php" style="display:inline;">
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                    <button type="submit" name="move_to_cart" class="btn btn-green btn-small">Move to Cart</button>
                </form>
                <form method="POST" action="wishlist.php" style="display:inline;">
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                    <button type="submit" name="remove_wishlist" class="btn btn-red btn-small" onclick="return confirm('Remove from wishlist?')">Remove</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="4" class="center">Your wishlist is empty. <a href="shop.php"><strong>Browse products</strong></a></td>
        </tr>
    <?php endif; ?>
</table>

<p class="mt-20"><a class="btn" href="shop.php">&laquo; Continue Shopping</a></p>

<?php site_footer(); ?>