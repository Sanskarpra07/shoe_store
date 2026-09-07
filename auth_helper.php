<?php
// Common helper functions for the store front
session_start();
require_once __DIR__ . '/db.php';

function is_customer_logged_in() {
    return isset($_SESSION['customer_id']);
}

function get_cart_count() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $count = array_sum($_SESSION['cart']);
    }
    return $count;
}

function price_label($price) {
    return '$' . number_format($price, 2);
}

// Print the common top part of every store page
function site_header($title = 'StepStyle - Online Shoe Store', $active = '') {
    $cart_count = get_cart_count();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="site-wrap">
    <div class="header">
        <div class="brand">StepStyle</div>
        <div class="tagline">Online Shoe Store - Quality Footwear at Your Doorstep</div>
    </div>
    <div class="announce">
        <marquee>Welcome to StepStyle Online Shoe Store. Free delivery inside Kathmandu valley. Cash on Delivery, eSewa &amp; Khalti payment available.</marquee>
    </div>
    <div class="nav">
        <a href="index.php" class="<?= $active === 'home' ? 'active' : '' ?>">Home</a>
        <a href="shop.php" class="<?= $active === 'shop' ? 'active' : '' ?>">Shop</a>
        <a href="track_order.php" class="<?= $active === 'track' ? 'active' : '' ?>">Track Order</a>
        <a href="about.php" class="<?= $active === 'about' ? 'active' : '' ?>">About Us</a>
        <a href="contact.php" class="<?= $active === 'contact' ? 'active' : '' ?>">Contact Us</a>
        <span class="nav-right">
            <a href="cart.php">Cart (<?= $cart_count ?>)</a>
            <?php if (is_customer_logged_in()): ?>
                <a href="my_account.php"><?= htmlspecialchars($_SESSION['customer_name'] ?? 'My Account') ?></a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="register.php">Register</a>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </span>
    </div>
    <div class="content">
    <?php
}

// Print the common footer of every store page
function site_footer() {
    ?>
    </div>
    <div class="footer">
        <p>&copy; 2026 StepStyle Online Shoe Store. All rights reserved.</p>
        <p>Developed for 5th Semester BCA Project | <a href="admin/login.php">Admin Panel</a></p>
    </div>
</div>
</body>
</html>
    <?php
}