<?php 
$current_page = $current_page ?? '';

function nav_link($href, $icon, $label, $current_page) {
    $page_key = pathinfo($href, PATHINFO_FILENAME);
    $active = ($current_page === $page_key) ? 'active fw-bold' : '';
    echo "<li class='nav-item'>
            <a class='nav-link text-white $active' href='$href'>
              <i class='bi $icon me-2'></i>$label
            </a>
          </li>";
}
?>

<!-- Sidebar -->
<div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark min-vh-100 d-flex flex-column">
    <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white flex-grow-1">

        <!-- Brand / Logo -->
        <a href="../admin/dashboard.php" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none border-bottom border-secondary w-100 pb-3">
            <i class="bi bi-bag-heart fs-4 me-2"></i>
            <span class="fs-5 fw-semibold d-none d-sm-inline">Shoe Store CMS</span>
        </a>

        <!-- Nav links -->
        <ul class="nav nav-pills flex-column mb-auto mt-3 w-100">
            <?php nav_link('dashboard.php',  'bi-speedometer2',  'Dashboard',   $current_page); ?>
            <?php nav_link('products.php',   'bi-box-seam',      'Products',    $current_page); ?>
            <?php nav_link('categories.php', 'bi-tags',          'Categories',  $current_page); ?>
            <?php nav_link('brands.php',     'bi-award',         'Brands',      $current_page); ?>
            <?php nav_link('orders.php',     'bi-cart',          'Orders',      $current_page); ?>
            <?php nav_link('reports.php',    'bi-graph-up',      'Reports',     $current_page); ?>
            <?php nav_link('reviews.php',    'bi-star',          'Reviews',     $current_page); ?>
            <?php nav_link('stock_log.php',  'bi-journal-text',  'Stock Log',   $current_page); ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?> 
            <?php nav_link('users.php', 'bi-people', 'Users', $current_page); ?>
            <?php endif; ?>
        </ul>
        
        <!-- Logout at the bottom -->
        <div class="border-top border-secondary w-100 pt-3 pb-3 mt-auto">
            <a href="../logout.php" class="nav-link text-white text-danger-emphasis">
                <i class="bi bi-box-arrow-left me-2"></i>
                <span class="d-none d-sm-inline">Logout</span>
            </a>
            <small class="text-secondary d-none d-sm-block mt-1">
                Logged in as: <strong class="text-light"><?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></strong>
            </small>
        </div>

    </div>
</div>
