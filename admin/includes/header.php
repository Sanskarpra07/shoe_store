<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN LAYOUT : HEADER
 * --------------------------------------------------------------------------
 * Shared layout header for every admin panel page.
 *
 * The including page must define two variables BEFORE requiring this file:
 *   $page_title   - string used in the <title> tag
 *   $current_page - key that matches one of $nav_items, used to mark the
 *                   active link in the sidebar (e.g. 'dashboard').
 *
 * Access control: the file redirects back to the login page when the
 * current session does not belong to an 'admin' or 'staff' role.
 * --------------------------------------------------------------------------
 */

// --- Session + role guard ------------------------------------------------
// Only logged-in admin / staff users may view admin pages.
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit();
}

// --- Shared database connection -------------------------------------------
require_once __DIR__ . '/../../backend/db.php';

// --- Main navigation definition -------------------------------------------
// Each entry maps a page key to its display label and Bootstrap icon class.
// The 'active' sidebar item is resolved from $current_page below.
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

// The Users page is reserved for administrators only.
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $nav_items['users'] = ['Users', 'bi-people'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Document metadata -->
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> - Shoe Store Admin</title>

    <!-- Third-party stylesheets (Bootstrap icons, Font Awesome, custom theme) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<!-- ================================================================
     ADMIN WRAPPER
     Flex container holding the sidebar (left) and main content (right)
     ================================================================ -->
<div class="admin-wrap">

    <!-- ============================================================
         SIDEBAR
         Brand block, navigation links and user footer.
         ============================================================ -->
    <aside class="sidebar">
        <!-- Brand block -->
        <div class="side-brand">
            <img src="../assets/img/megafoot.jpg" alt="MegaFoot" class="brand-logo">
            <span>
                <span class="brand-title">MegaFoot</span><br>
                <span class="brand-sub">Admin Panel</span>
            </span>
        </div>

        <!-- Navigation menu -->
        <div class="side-label">Menu</div>
        <?php foreach ($nav_items as $key => $item): ?>
            <a href="<?= $key ?>.php" class="<?= $current_page === $key ? 'active' : '' ?>">
                <i class="bi <?= $item[1] ?>"></i><?= $item[0] ?>
            </a>
        <?php endforeach; ?>

        <!-- Logged-in user footer -->
        <div class="side-user">
            Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong><br>
            <span class="user-actions">
                <a href="../index.php"><i class="bi bi-store"></i> Store</a>
                <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </span>
        </div>
    </aside>

    <!-- ============================================================
         ADMIN MAIN CONTENT
         Each page echos its own content here; the footer closes the
         wrapper divs.
         ============================================================ -->
    <main class="admin-main">