<?php
/**
 * --------------------------------------------------------------------------
 * eSewa Success Callback - MegaFoot Storefront
 * --------------------------------------------------------------------------
 * Handles the eSewa ePay V2 success redirect: verifies the HMAC-SHA256
 * signature on the response, cross-checks the amount, then calls the
 * eSewa status API for final confirmation before completing the order.
 * --------------------------------------------------------------------------
 */
// ---------- Session bootstrap ----------
session_start();

// ---------- Shared requires ----------
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/payment_config.php';
require_once __DIR__ . '/../backend/auth_helper.php';

// ---------- Parse eSewa response data ----------
$data = $_GET['data'] ?? '';
if ($data === '') {
    die("Missing payment parameters.");
}

// eSewa sends the response as base64-encoded JSON in the "data" query param.
// Normalize URL-safe variants and the "+" -> space side effect of GET decoding.
$data = strtr($data, '-_', '+/');
$data = str_replace(' ', '+', $data);
$response = json_decode(base64_decode($data), true);
if (!is_array($response) || empty($response['transaction_uuid'])) {
    die("Invalid payment response.");
}

// ---------- Step 1: verify HMAC-SHA256 signature ----------
// eSewa signs every field listed in signed_field_names (excluding "signature").
$signed_parts = [];
foreach (explode(',', $response['signed_field_names'] ?? '') as $field) {
    if ($field === 'signature' || !array_key_exists($field, $response)) {
        continue;
    }
    $signed_parts[] = $field . '=' . $response[$field];
}
$expected = base64_encode(hash_hmac('sha256', implode(',', $signed_parts), ESEWA_SECRET_KEY, true));
if (!hash_equals($expected, $response['signature'] ?? '')) {
    die("Payment signature verification failed.");
}

// ---------- Step 2: resolve order from transaction_uuid ----------
if (!preg_match('/^ord-(\d+)-/', (string)$response['transaction_uuid'], $m)) {
    die("Unknown transaction reference.");
}
$order_id = (int)$m[1];

$order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
if (!$order) {
    die("Order not found.");
}

// ---------- Step 3: cross-check paid amount ----------
$paid = (float)($response['total_amount'] ?? 0);
if (abs($paid - (float)$order['total_amount']) > 0.01) {
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
    die("Payment amount mismatch detected. Please contact support.");
}

// ---------- Idempotency guard ----------
if ($order['payment_status'] === 'completed') {
    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = "Payment already confirmed for Order #$order_id. Total: NPR " . number_format($order['total_amount'], 2);
    header("Location: order_success.php");
    exit();
}

// ---------- Step 4: confirm with eSewa status API ----------
$status_url = ESEWA_STATUS_URL . '?' . http_build_query([
    'product_code'     => ESEWA_MERCHANT_CODE,
    'total_amount'     => $response['total_amount'],
    'transaction_uuid' => $response['transaction_uuid'],
]);
$ch = curl_init($status_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$status = json_decode(curl_exec($ch), true);
curl_close($ch);

$ref_id = $status['ref_id'] ?? '';
if (($status['status'] ?? '') !== 'COMPLETE' || $ref_id === '') {
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
    $_SESSION['checkout_errors'] = ["eSewa payment could not be confirmed. Please try again or choose a different payment method."];
    header("Location: cart.php");
    exit();
}

// ---------- Step 5: complete order and decrement stock atomically ----------
mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn,
        "UPDATE orders SET payment_status = 'completed', transaction_id = ?, status = 'pending' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $ref_id, $order_id);
    mysqli_stmt_execute($stmt);

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

// ---------- Redirect to order success ----------
$_SESSION['cart'] = [];
$_SESSION['order_success'] = "Payment successful via eSewa! Order #$order_id confirmed. Total: NPR " . number_format($order['total_amount'], 2);
header("Location: order_success.php");
exit();
