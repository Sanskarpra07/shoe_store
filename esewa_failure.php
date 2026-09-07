<?php
// eSewa failure callback
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$oid = $_GET['oid'] ?? $_GET['pid'] ?? '';
if (is_numeric($oid)) {
    $order_id = (int)$oid;
} else {
    $order_id = (int)str_replace('order_', '', $oid);
}

if ($order_id > 0) {
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
}

site_header('Payment Failed - StepStyle', '');
?>

<h2 class="page-title">Payment Failed</h2>

<div class="form-box" style="text-align:center; width:500px;">
    <div style="font-size:52px; color:#c62828;">&#10005;</div>
    <p style="margin-top:15px; font-size:15px;">Your payment was not completed. You can try again or choose another payment option.</p>
    <hr>
    <a class="btn" href="cart.php">Back to Cart</a>
    <a class="btn btn-green" href="checkout.php">Try Again</a>
</div>

<?php site_footer(); ?>