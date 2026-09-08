<?php
session_start();
require_once __DIR__ . '/../backend/db.php';

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));

if ($product_id > 0 && $quantity > 0) {
    $stmt = mysqli_prepare($conn, "SELECT stock FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p) {
        $_SESSION['cart_error'] = "Product not found.";
    } else {
        // Check cumulative quantity (existing cart + new qty)
        $existing_qty = $_SESSION['cart'][$product_id] ?? 0;
        $total_qty = $existing_qty + $quantity;
        if ($total_qty > $p['stock']) {
            if ($p['stock'] <= 0) {
                $_SESSION['cart_error'] = "This product is out of stock.";
            } else {
                $_SESSION['cart_error'] = "Only {$p['stock']} left in stock. You already have {$existing_qty} in your cart.";
            }
        } else {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            $_SESSION['cart'][$product_id] = $total_qty;
        }
    }
}

header("Location: cart.php");
exit();