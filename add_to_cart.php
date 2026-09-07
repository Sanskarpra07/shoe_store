<?php
session_start();
require_once __DIR__ . '/db.php';

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));

if ($product_id > 0 && $quantity > 0) {
    $stmt = mysqli_prepare($conn, "SELECT stock FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p) {
        $_SESSION['cart_error'] = "Product not found.";
    } elseif ($quantity > $p['stock']) {
        $_SESSION['cart_error'] = "Only {$p['stock']} left in stock.";
    } else {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
    }
}

header("Location: cart.php");
exit();