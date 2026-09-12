<?php
/**
 * --------------------------------------------------------------------------
 * Add to Wishlist - MegaFoot Storefront
 * --------------------------------------------------------------------------
 * Toggles a product in the logged-in customer's wishlist: adds it if absent,
 * removes it if already present. Redirects back to the referring page.
 * --------------------------------------------------------------------------
 */
// ---------- Session bootstrap ----------
session_start();

// ---------- Shared requires ----------
require_once __DIR__ . '/../backend/db.php';

// ---------- Auth guard ----------
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

// ---------- Parse input ----------
$product_id = (int)($_POST['product_id'] ?? 0);
$customer_id = (int)$_SESSION['customer_id'];

// ---------- Toggle wishlist entry ----------
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

// ---------- Redirect to referring page ----------
$referer = $_SERVER['HTTP_REFERER'] ?? '';
// Sanitize: only allow same-site redirects
if (empty($referer) || parse_url($referer, PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
    $referer = 'shop.php';
}
header("Location: " . $referer);
exit();
