<?php
session_start();

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));

if ($product_id > 0) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

header("Location: cart.php");
exit();
