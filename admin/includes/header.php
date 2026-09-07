<?php
// Admin panel layout header.
// The including page must set $current_page (e.g. 'dashboard', 'products').
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../../db.php';

$nav_items = [
    'dashboard'      => 'Dashboard',
    'products'       => 'Products',
    'categories'     => 'Categories',
    'brands'         => 'Brands',
    'delivery_slots' => 'Delivery Slots',
    'orders'         => 'Orders',
    'reports'        => 'Reports',
    'reviews'        => 'Reviews',
    'stock_log'      => 'Stock Log',
];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $nav_items['users'] = 'Users';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> - Shoe Store Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="admin-wrap">
    <div class="sidebar">
        <div class="side-brand">Shoe Store Admin</div>
        <?php foreach ($nav_items as $key => $label): ?>
            <a href="<?= $key ?>.php" class="<?= $current_page === $key ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
        <div class="side-user">
            Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong><br>
            <a href="../logout.php">Logout</a> | <a href="../index.php">Visit Store</a>
        </div>
    </div>
    <div class="admin-main">