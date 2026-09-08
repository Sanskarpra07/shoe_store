<?php
// Khalti KPG-2 callback - verifies the transaction via Lookup API and completes the order
// Docs: https://docs.khalti.com/khalti-epayment/#payment-verification-lookup
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/payment_config.php';
require_once __DIR__ . '/../backend/auth_helper.php';

$order_id = (int)($_GET['order_id'] ?? 0);
$pidx     = $_GET['pidx'] ?? '';
$cb_status = $_GET['status'] ?? '';
$cb_txn   = $_GET['transaction_id'] ?? '';

if ($order_id === 0) {
    die("Missing order reference.");
}

$order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
if (!$order) {
    die("Order not found.");
}

// Helper to call the Khalti Lookup API (recommended final validation per docs)
function khalti_lookup($pidx) {
    $ch = curl_init(KHALTI_LOOKUP_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Key ' . KHALTI_SECRET_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pidx' => $pidx]));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// The docs require the merchant to hit the Lookup API for final validation.
// Only status "Completed" must be treated as success.
$lookup = null;
if (!empty($pidx)) {
    $lookup = khalti_lookup($pidx);
}

$status     = $lookup['status'] ?? '';
$txn_id     = $lookup['transaction_id'] ?? $cb_txn;
$paid_paisa = $lookup['total_amount'] ?? 0;
$expected_paisa = (int)round((float)$order['total_amount'] * 100);

// ----- Idempotency: skip if order already completed -----
if ($order['payment_status'] === 'completed') {
    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = "Payment already confirmed for Order #$order_id. Total: रु " . number_format($order['total_amount'], 2);
    header("Location: order_success.php");
    exit();
}

// ----- Handle user canceled / expired callbacks (no pidx or lookup pending) -----
if ($status === 'User canceled' || $status === 'user canceled' || $status === 'Expired' || (!empty($cb_status) && strtolower($cb_status) === 'canceled')) {
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
    $_SESSION['checkout_errors'] = ["Khalti payment was canceled. Your order was not completed. You can try again or choose a different payment method."];
    header("Location: cart.php");
    exit();
}

// ----- Handle pending / initiated statuses (hold, do not provide service) -----
if ($status === 'Pending' || $status === 'Initiated') {
    $_SESSION['checkout_errors'] = ["Your Khalti payment is still pending. We will confirm once the payment is completed."];
    header("Location: cart.php");
    exit();
}

// ----- Success only when Lookup API reports Completed -----
if ($status === 'Completed' || $status === 'completed') {
    // Verify the paid amount matches the order total (must equal, paisa)
    if ((int)$paid_paisa !== $expected_paisa) {
        mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
        $_SESSION['checkout_errors'] = ["Payment amount mismatch detected. Please contact support."];
        header("Location: cart.php");
        exit();
    }

    // Use transaction for atomic stock decrement
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn,
            "UPDATE orders SET payment_status = 'completed', transaction_id = ?, status = 'pending' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $txn_id, $order_id);
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
    $_SESSION['order_success'] = "Payment successful via Khalti! Order #$order_id confirmed. Total: रु " . number_format($order['total_amount'], 2);
    header("Location: order_success.php");
    exit();
} else {
    // Lookup failed / no pidx / unknown status - do NOT mark paid
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
    $_SESSION['checkout_errors'] = ["Khalti payment verification failed. Your order was not completed. Please try again or choose a different payment method."];
    header("Location: cart.php");
    exit();
}