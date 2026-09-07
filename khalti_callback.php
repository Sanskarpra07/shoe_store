<?php
// Khalti callback - verifies the transaction and completes the order
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$order_id = (int)($_GET['order_id'] ?? 0);
$pidx     = $_GET['pidx'] ?? '';
$status   = $_GET['status'] ?? '';
$txn_id   = $_GET['transaction_id'] ?? '';

if ($order_id === 0) {
    die("Missing order reference.");
}

$order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id"));
if (!$order) {
    die("Order not found.");
}

// Verify with Khalti API
if (!empty($pidx)) {
    $ch = curl_init('https://khalti.com/api/v2/payment/verify/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Key test_secret_key',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pidx' => $pidx]));
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $verified = !empty($data['idx']);
    $txn_id = $data['transaction_id'] ?? $txn_id;
} else {
    $verified = ($status === 'Completed') || ($status === 'completed');
}

if ($verified) {
    $stmt = mysqli_prepare($conn,
        "UPDATE orders SET payment_status = 'completed', transaction_id = ?, status = 'pending' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $txn_id, $order_id);
    mysqli_stmt_execute($stmt);

    // Decrement stock
    $items = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($it = mysqli_fetch_assoc($items)) {
        $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock FROM products WHERE id = {$it['product_id']}"));
        $new_stock = max(0, $prod['stock'] - (int)$it['quantity']);
        mysqli_query($conn, "UPDATE products SET stock = $new_stock WHERE id = {$it['product_id']}");
    }

    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = "Payment successful via Khalti! Order #$order_id confirmed. Total: $" . number_format($order['total_amount'], 2);
    header("Location: order_success.php");
    exit();
} else {
    mysqli_query($conn, "UPDATE orders SET payment_status = 'failed' WHERE id = $order_id");
    header("Location: cart.php");
    exit();
}