<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

if (isset($_GET['remove'])) {
    $remove_id = (int) $_GET['remove'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-bag-heart me-2"></i>StepStyle</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="cart.php">
                        <i class="bi bi-cart3 me-1"></i>Cart
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-danger"><?= $cart_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (is_customer_logged_in()): ?>
                    <li class="nav-item"><a class="nav-link" href="my_account.php"><i class="bi bi-person me-1"></i><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Account') ?></a></li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="bi bi-person me-1"></i>Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="section-title">Shopping Cart</h2>

    <?php if (!empty($_SESSION['cart_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['cart_error']) ?>
        </div>
        <?php unset($_SESSION['cart_error']); ?>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x fs-1 text-muted"></i>
            <h4 class="text-muted mt-3">Your cart is empty</h4>
            <a href="shop.php" class="btn btn-accent mt-2">Start Shopping</a>
        </div>
    <?php else: ?>
        <form method="POST" action="cart.php">
            <input type="hidden" name="update_cart" value="1">
            <div class="table-responsive">
                <table class="table align-middle cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th style="width:150px;">Quantity</th>
                            <th>Total</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="cart-item-thumb me-3">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="../<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <?php else: ?>
                                            <div class="placeholder"><i class="bi bi-basket text-muted fs-4"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($item['product_name']) ?></h6>
                                        <?php if ($item['color']): ?>
                                            <small class="text-muted"><i class="bi bi-palette me-1"></i><?= htmlspecialchars($item['color']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($item['size']): ?>
                                            <br><small class="text-muted">Size: <?= htmlspecialchars($item['size']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>रु <?= number_format($item['discount_price'] ?: $item['price'], 2) ?></td>
                            <td>
                                <input type="number" name="quantity[<?= $item['id'] ?>]" class="form-control form-control-sm"
                                       value="<?= $item['qty'] ?>" min="1" max="<?= $item['stock'] ?>">
                            </td>
                            <td class="fw-bold">रु <?= number_format($item['line_total'], 2) ?></td>
                            <td class="text-center">
                                <a href="cart.php?remove=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   data-confirm="Remove this item?">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <a href="shop.php" class="btn btn-outline-dark">
                    <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                </a>
                <div class="d-flex gap-2">
                    <a href="cart.php?clear=1" class="btn btn-outline-danger" data-confirm="Clear cart?">Clear Cart</a>
                    <button type="submit" class="btn btn-dark">Update Cart</button>
                </div>
            </div>
        </form>

        <!-- Order Summary -->
        <div class="row justify-content-end mt-4">
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="card-header"><i class="bi bi-receipt me-2"></i>Order Summary</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?= $cart_count ?> items)</span>
                            <span>रु <?= number_format($total, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span class="text-success">Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span class="text-success">रु <?= number_format($total, 2) ?></span>
                        </div>
                        <a href="checkout.php" class="btn btn-accent w-100 mt-3 btn-lg">
                            Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                        </a>
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
