<?php
session_start();
require_once 'db.php';

function is_customer_logged_in() {
    return isset($_SESSION['customer_id']);
}

function get_cart_count() {
    $c = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $c = array_sum($_SESSION['cart']);
    }
    return $c;
}

function frontend_navbar($active = '') {
    $cart_count = get_cart_count();
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-bag-heart me-2"></i>StepStyle</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active === 'shop' ? 'active' : '' ?>" href="shop.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active === 'track' ? 'active' : '' ?>" href="track_order.php">Track Order</a></li>
                </ul>
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart3 me-1"></i>Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="badge bg-danger"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (is_customer_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Account') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="my_account.php"><i class="bi bi-person me-2"></i>My Account</a></li>
                                <li><a class="dropdown-item" href="my_orders.php"><i class="bi bi-box-seam me-2"></i>My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-person me-1"></i>Login</a></li>
                        <li class="nav-item ms-lg-1">
                            <a class="btn btn-outline-light btn-sm px-3" href="register.php"><i class="bi bi-person-plus me-1"></i>Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php
}

function frontend_footer() {
    ?>
    <footer class="footer pt-5 mt-5">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-6 text-center text-md-start">
                    <h5 class="mb-3"><i class="bi bi-bag-heart me-2"></i>StepStyle</h5>
                    <p class="small pe-lg-4">Your one-stop destination for premium footwear from the world's best brands. Quality you can feel, style you can trust.</p>
                    <div class="d-flex gap-2 justify-content-center justify-content-md-start mt-3">
                        <a href="https://facebook.com" class="social-link" title="Facebook" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
                        <a href="https://instagram.com" class="social-link" title="Instagram" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
                        <a href="https://twitter.com" class="social-link" title="Twitter" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://youtube.com" class="social-link" title="YouTube" target="_blank" rel="noopener"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 text-center text-md-start">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>Home</a></li>
                        <li class="mb-2"><a href="shop.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>Shop All Shoes</a></li>
                        <li class="mb-2"><a href="track_order.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>Track Order</a></li>
                        <li class="mb-2"><a href="my_account.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>My Account</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-12 text-center text-lg-start">
                    <h6 class="mb-3">Contact Us</h6>
                    <p class="small mb-2"><i class="bi bi-geo-alt me-2"></i>Durbar Marg, Kathmandu, Nepal</p>
                    <p class="small mb-2"><i class="bi bi-envelope me-2"></i>info@stepstyle.com</p>
                    <p class="small mb-2"><i class="bi bi-telephone me-2"></i>+977-1-456789</p>
                    <p class="small"><i class="bi bi-clock me-2"></i>Sun - Fri: 9:00 AM - 8:00 PM</p>
                </div>
            </div>
        </div>
        <div class="border-top border-secondary mt-4 py-3">
            <div class="container">
                <p class="text-center small mb-0">&copy; 2026 StepStyle. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <?php
}
?>
