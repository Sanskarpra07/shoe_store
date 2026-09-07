<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

$cart_items = [];
$total = 0;
$ids = implode(',', array_keys($cart));
$result = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($ids)");
while ($row = mysqli_fetch_assoc($result)) {
    $qty = $cart[$row['id']] ?? 1;
    $price = $row['discount_price'] ?: $row['price'];
    $row['qty'] = $qty;
    $row['line_total'] = $price * $qty;
    $total += $row['line_total'];
    $cart_items[] = $row;
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/frontend.css" rel="stylesheet">
    <style>
        .payment-option { border: 2px solid #dee2e6; border-radius: 10px; padding: 15px; cursor: pointer; transition: all .2s; }
        .payment-option:hover { border-color: var(--accent); }
        .payment-option.selected { border-color: var(--accent); background: #fff7f0; }
        .payment-option .form-check-input { cursor: pointer; }
    </style>
</head>
<body>

<?php frontend_navbar(); ?>

<div class="container py-5">
    <h2 class="section-title">Checkout</h2>

    <?php if (!is_customer_logged_in()): ?>
        <div class="alert alert-info py-2 small d-flex justify-content-between align-items-center">
            <span><i class="bi bi-info-circle me-1"></i>You are checking out as a guest.</span>
            <a href="login.php" class="btn btn-sm btn-dark">Login to save address & track orders</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-person me-2"></i>Shipping Information
                </div>
                <div class="card-body">
                    <form method="POST" action="process_order.php" id="checkout_form">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= htmlspecialchars($name) ?>">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= htmlspecialchars($email) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($phone) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Shipping Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="3" required
                                      placeholder="Street, City, State, ZIP, Country"><?= htmlspecialchars($address) ?></textarea>
                        </div>

                        <!-- Payment Method -->
                        <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Select Payment Method</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="payment-option d-block <?= $selected_payment === 'cod' ? 'selected' : '' ?>">
                                    <input type="radio" name="payment_method" value="cod" class="form-check-input me-1"
                                           <?= $selected_payment === 'cod' ? 'checked' : '' ?>>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-cash-coin fs-3 text-success me-2"></i>
                                        <div>
                                            <strong>Cash on Delivery</strong>
                                            <small class="text-muted d-block">Pay when delivered</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="payment-option d-block <?= $selected_payment === 'esewa' ? 'selected' : '' ?>">
                                    <input type="radio" name="payment_method" value="esewa" class="form-check-input me-1"
                                           <?= $selected_payment === 'esewa' ? 'checked' : '' ?>>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-credit-card fs-3 text-danger me-2"></i>
                                        <div>
                                            <strong>eSewa</strong>
                                            <small class="text-muted d-block">Nepal's payment gateway</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="payment-option d-block <?= $selected_payment === 'khalti' ? 'selected' : '' ?>">
                                    <input type="radio" name="payment_method" value="khalti" class="form-check-input me-1"
                                           <?= $selected_payment === 'khalti' ? 'checked' : '' ?>>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-wallet2 fs-3 text-primary me-2"></i>
                                        <div>
                                            <strong>Khalti</strong>
                                            <small class="text-muted d-block">Digital wallet</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-accent btn-lg w-100">
                            <i class="bi bi-shield-check me-1"></i>
                            Place Order - $<?= number_format($total, 2) ?>
                        </button>
                        <p class="text-muted text-center small mt-2 mb-0">
                            <i class="bi bi-lock me-1"></i>Secure checkout. Your payment details are protected.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="summary-card">
                <div class="card-header"><i class="bi bi-bag me-2"></i>Order Summary</div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <div class="cart-item-thumb me-3" style="width:56px;height:56px;">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    <?php else: ?>
                                        <div class="placeholder"><i class="bi bi-basket text-muted"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <small class="fw-semibold d-block"><?= htmlspecialchars($item['product_name']) ?></small>
                                    <small class="text-muted">x<?= $item['qty'] ?></small>
                                </div>
                            </div>
                            <span class="fw-bold">$<?= number_format($item['line_total'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Subtotal</span>
                        <span>$<?= number_format($total, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Shipping</span>
                        <span class="text-success">Free</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span class="text-success">$<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3 mt-3">
                <div class="card-body small text-muted py-3">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Your order data is encrypted & safe. Payments via eSewa & Khalti are processed on their secure gateways.
                </div>
            </div>
        </div>
    </div>
</div>

<?php frontend_footer(); ?>

<script>
// Hightlight selected payment option
document.querySelectorAll('.payment-option input').forEach(input => {
    input.addEventListener('change', function () {
        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
        this.closest('.payment-option').classList.add('selected');
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>