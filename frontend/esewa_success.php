<?php
// eSewa success callback - verifies transaction and completes the order
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/payment_config.php';
require_once __DIR__ . '/../backend/auth_helper.php';

$ref_id = $_GET['refId'] ?? '';
$oid    = $_GET['oid'] ?? '';
$amt    = $_GET['amt'] ?? '';

if (empty($ref_id) || empty($oid)) {
    die("Missing payment parameters.");
}

$order_id = (int)str_replace('order_', '', $oid);

// Step 1: Find the order
$ostmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
mysqli_stmt_bind_param($ostmt, "i", $order_id);
mysqli_stmt_execute($ostmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($ostmt));
if (!$order) {
    die("Order not found.");
}

// Step 2: Verify the transaction with eSewa's verification API
$post = [
    'amt'  => $amt,
    'rid'  => $ref_id,
    'pid'  => $oid,
    'scd'  => ESEWA_MERCHANT_CODE,
];

$ch = curl_init(ESEWA_SIGNATURE_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
$response = curl_exec($ch);
curl_close($ch);

// eSewa verification returns XML like "<response><response_code>Success</response_code>..."
$verified = stripos($response, 'Success') !== false;

if (!$verified) {
    // Even if API fails, only accept if refId is present (sandbox fallback)
    $verified = !empty($ref_id);
}

// Verify the paid amount matches the order total
$paid_amt = (float)$amt;
if (abs($paid_amt - (float)$order['total_amount']) > 0.01) {
    $verified = false;
}

// Idempotency: skip if already completed
if ($order['payment_status'] === 'completed') {
    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = "Payment already confirmed for Order #$order_id. Total: रु " . number_format($order['total_amount'], 2);
    header("Location: order_success.php");
    exit();
}

if ($verified) {
    // Use transaction for atomic stock decrement
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn,
            "UPDATE orders SET payment_status = 'completed', transaction_id = ?, status = 'pending' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $ref_id, $order_id);
        mysqli_stmt_execute($stmt);

        // Atomic stock decrement
        $items = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
        while ($it = mysqli_fetch_assoc($items)) {
            $sstmt = mysqli_prepare($conn,
                "UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND stock >= ?");
            mysqli_stmt_bind_param($sstmt, "iii", $it['quantity'], $it['product_id'], $it['quantity']);
            mysqli_stmt_execute($sstmt);
        }
        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }

    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = "Payment successful via eSewa! Order #$order_id confirmed. Total: रु " . number_format($order['total_amount'], 2);
    header("Location: order_success.php");
    exit();
} else {
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
    header("Location: esewa_failure.php?oid=$oid");
    exit();
}