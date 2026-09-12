<?php
/**
 * --------------------------------------------------------------------------
 * eSewa Failure Callback - MegaFoot Storefront
 * --------------------------------------------------------------------------
 * Handles the eSewa failure redirect: marks the order as payment-failed
 * and displays a friendly error page with retry options.
 * --------------------------------------------------------------------------
 */
// ---------- Session bootstrap ----------
session_start();

// ---------- Shared requires ----------
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

// ---------- Mark order as failed ----------
$oid = $_GET['oid'] ?? $_GET['pid'] ?? '';
if (is_numeric($oid)) {
    $order_id = (int)$oid;
} else {
    $order_id = (int)str_replace('order_', '', $oid);
}

if ($order_id > 0) {
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
}
?>
<!DOCTYPE html>
<html lang="en">
<!-- ======== <head> ======== -->
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <title>Payment Failed - MegaFoot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<!-- ======== Navbar ======== -->
<?php frontend_navbar(); ?>

<!-- ======== Page Content ======== -->
<div class="container py-4 text-center">
    <div class="card shadow-sm border-0 rounded-3 mx-auto" style="max-width: 450px;">
        <div class="card-body p-5">
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>
            <h2 class="fw-bold text-danger mt-3">Payment Failed</h2>
            <p class="text-muted mt-2">Your payment was not completed. You can try again or choose another payment option.</p>
            <div class="mt-4 d-flex gap-2 justify-content-center">
                <a href="cart.php" class="btn btn-accent">Back to Cart</a>
                <a href="checkout.php" class="btn btn-outline-dark">Try Again</a>
            </div>
        </div>
    </div>
</div>

<!-- ======== Footer ======== -->
<?php frontend_footer(); ?>

<!-- ======== Scripts ======== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
