<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

$success = $_SESSION['order_success'] ?? '';
unset($_SESSION['order_success']);
$last_order = $_SESSION['last_order'] ?? null;
unset($_SESSION['last_order']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<?php frontend_navbar(); ?>

<div class="container py-5 text-center">
    <div class="card shadow-sm border-0 rounded-3 mx-auto" style="max-width: 500px;">
        <div class="card-body p-5">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
            </div>
            <h2 class="fw-bold text-success">Order Placed Successfully!</h2>
            <?php if ($success): ?>
                <p class="mt-3 fs-5"><?= htmlspecialchars($success) ?></p>
            <?php else: ?>
                <p class="mt-3 fs-5">Thank you for your purchase!</p>
            <?php endif; ?>
            <?php if ($last_order): ?>
                <p class="text-muted mb-1">
                    <?php if (!empty($last_order['slot'])): ?>
                        <strong>Delivery Slot:</strong> <?= htmlspecialchars($last_order['slot']) ?><br>
                    <?php endif; ?>
                    <strong>Order Total:</strong> रु <?= number_format($last_order['total'], 2) ?>
                </p>
                <a href="invoice.php?order_id=<?= $last_order['id'] ?>" target="_blank" class="btn btn-outline-dark btn-sm px-3 mb-3">
                    <i class="bi bi-receipt me-1"></i>View Invoice
                </a><br>
            <?php endif; ?>
            <div class="mt-2 d-flex gap-2 justify-content-center">
                <a href="track_order.php" class="btn btn-outline-success px-3"><i class="bi bi-box me-1"></i>Track Order</a>
                <a href="shop.php" class="btn btn-accent px-4">Continue Shopping</a>
                <a href="index.php" class="btn btn-outline-dark px-4">Home</a>
            </div>
        </div>
    </div>
</div>

<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>