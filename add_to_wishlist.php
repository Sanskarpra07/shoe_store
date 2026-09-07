<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$product_id = (int)($_POST['product_id'] ?? 0);
$customer_id = (int)$_SESSION['customer_id'];

if ($product_id > 0) {
    $check = mysqli_prepare($conn, "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($check, "ii", $customer_id, $product_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if (mysqli_stmt_num_rows($check) > 0) {
        $del = mysqli_prepare($conn, "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($del, "ii", $customer_id, $product_id);
        mysqli_stmt_execute($del);
        $_SESSION['wishlist_action'] = 'removed';
    } else {
        $ins = mysqli_prepare($conn, "INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($ins, "ii", $customer_id, $product_id);
        mysqli_stmt_execute($ins);
        $_SESSION['wishlist_action'] = 'added';
    }
    mysqli_stmt_free_result($check);
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'shop.php';
header("Location: " . $referer);
exit();