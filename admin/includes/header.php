<?php
// Admin panel layout header.
// The including page must set $current_page (e.g. 'dashboard', 'products').
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../../db.php';

$nav_items = [
    'dashboard'      => ['Dashboard', 'bi-speedometer2'],
    'products'       => ['Products', 'bi-box-seam'],
    'categories'     => ['Categories', 'bi-tags'],
    'brands'         => ['Brands', 'bi-buildings'],
    'delivery_slots' => ['Delivery Slots', 'bi-truck'],
    'orders'         => ['Orders', 'bi-bag-check'],
    'reports'        => ['Reports', 'bi-bar-chart-line'],
    'reviews'        => ['Reviews', 'bi-star'],
    'stock_log'      => ['Stock Log', 'bi-clipboard-data'],
];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $nav_items['users'] = ['Users', 'bi-people'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> - Shoe Store Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-wrap">
    <div class="sidebar">
        <div class="side-brand">
            <span class="brand-icon"><i class="bi bi-bag-heart"></i></span>
            <span>
                <span class="brand-title">StepStyle</span><br>
                <span class="brand-sub">Admin Panel</span>
            </span>
        </div>
        <div class="side-label">Menu</div>
        <?php foreach ($nav_items as $key => $item): ?>
            <a href="<?= $key ?>.php" class="<?= $current_page === $key ? 'active' : '' ?>">
                <i class="bi <?= $item[1] ?>"></i><?= $item[0] ?>
            </a>
        <?php endforeach; ?>
        <div class="side-user">
            Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong><br>
            <span class="user-actions">
                <a href="../index.php"><i class="bi bi-store"></i> Store</a>
                <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </span>
        </div>
    </div>
    <div class="admin-main">