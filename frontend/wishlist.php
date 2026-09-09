<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

if (!is_customer_logged_in()) {
    $_SESSION['redirect_after_login'] = 'wishlist.php';
    header("Location: login.php");
    exit();
}

$customer_id = (int) $_SESSION['customer_id'];

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
        // Check stock before moving to cart
        $stock_check = mysqli_prepare($conn, "SELECT stock FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stock_check, "i", $pid);
        mysqli_stmt_execute($stock_check);
        $stock_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stock_check));
        if (!$stock_row || $stock_row['stock'] <= 0) {
            $_SESSION['wishlist_action'] = 'out_of_stock';
            header("Location: wishlist.php");
            exit();
        }
        $existing = $_SESSION['cart'][$pid] ?? 0;
        if ($existing + 1 > $stock_row['stock']) {
            $_SESSION['wishlist_action'] = 'stock_limit';
            header("Location: wishlist.php");
            exit();
        }
        $_SESSION['cart'][$pid] = $existing + 1;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - MegaFoot</title>
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
            <li class="breadcrumb-item active">My Wishlist</li>
        </ol>
    </nav>

    <h2 class="section-title">My Wishlist</h2>

    <?php if ($wishlist_action === 'added'): ?>
        <div class="alert alert-success">Item added to your wishlist.</div>
    <?php elseif ($wishlist_action === 'removed'): ?>
        <div class="alert alert-info">Item removed from your wishlist.</div>
    <?php elseif ($wishlist_action === 'moved'): ?>
        <div class="alert alert-success">Item moved to your cart.</div>
    <?php elseif ($wishlist_action === 'out_of_stock'): ?>
        <div class="alert alert-danger">Cannot add to cart: product is out of stock.</div>
    <?php elseif ($wishlist_action === 'stock_limit'): ?>
        <div class="alert alert-warning">Cannot add to cart: insufficient stock.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($wishlist) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($wishlist)): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="cart-item-thumb me-3">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <?php else: ?>
                                            <div class="placeholder"><i class="bi bi-basket text-muted"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="product.php?id=<?= $item['id'] ?>" class="fw-bold text-decoration-none"><?= htmlspecialchars($item['product_name']) ?></a>
                                        <?php if ($item['size']): ?>
                                            <br><small class="text-muted">Size: <?= htmlspecialchars($item['size']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-bold">रु <?= number_format($item['discount_price'] ?: $item['price'], 2) ?></td>
                            <td>
                                <?php if ($item['stock'] > 0): ?>
                                    <span class="badge bg-success">In Stock (<?= $item['stock'] ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <form method="POST" action="wishlist.php" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="move_to_cart" class="btn btn-sm btn-accent"><i class="bi bi-cart-plus me-1"></i>Move to Cart</button>
                                </form>
                                <a href="product.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye me-1"></i>View</a>
                                <form method="POST" action="wishlist.php" class="d-inline" data-confirm="Remove from wishlist?">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="remove_wishlist" class="btn btn-sm btn-outline-danger"><i class="bi bi-heart-broken"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="bi bi-heart fs-1 d-block mb-2"></i>
                            Your wishlist is empty.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="shop.php" class="btn btn-accent"><i class="bi bi-arrow-left me-1"></i>Continue Shopping</a>
    </div>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>