<?php
/**
 * --------------------------------------------------------------------------
 * FRONTEND AUTH & LAYOUT HELPERS
 * --------------------------------------------------------------------------
 * Shared helpers for the storefront:
 *   - session bootstrapping
 *   - customer login checks
 *   - cart count / price formatting
 *   - the storefront navbar and footer rendered by frontend_navbar() and
 *     frontend_footer().
 *
 * Every storefront page includes this file after backend/db.php.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap -----------------------------------------------------
// Make sure a session is available for cart / login state.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Shared database connection ---------------------------------------------
require_once __DIR__ . '/db.php';

/**
 * Whether the current visitor is a logged-in customer.
 * @return bool True when a customer session exists.
 */
function is_customer_logged_in() {
    return isset($_SESSION['customer_id']);
}

/**
 * Total number of items currently in the cart session.
 * @return int Sum of all quantities (0 when the cart is empty).
 */
function get_cart_count() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $count = array_sum($_SESSION['cart']);
    }
    return $count;
}

/**
 * Format a price in Nepali Rupees.
 * @param float $price Raw numeric price.
 * @return string "NPR 1,234.56" style label.
 */
function price_label($price) {
    return 'NPR ' . number_format($price, 2);
}

/**
 * Render the responsive storefront navigation bar.
 * @param string $active Key of the current page ('home','shop','track',...)
 *                       used to highlight the matching menu item.
 */
function frontend_navbar($active = '') {
    $cart_count = get_cart_count();
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <!-- Brand / logo -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/img/megafoot.jpg" alt="MegaFoot" class="navbar-logo"></a>

            <!-- Mobile toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Primary page links -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link <?= $active === 'home' ? 'active' : '' ?>" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active === 'shop' ? 'active' : '' ?>" href="shop.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active === 'track' ? 'active' : '' ?>" href="track_order.php">Track Order</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active === 'about' ? 'active' : '' ?>" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active === 'contact' ? 'active' : '' ?>" href="contact.php">Contact</a></li>
                </ul>

                <!-- Right side: cart + account/login links -->
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
                        <!-- Logged-in customer dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Account') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="my_account.php"><i class="bi bi-person me-2"></i>My Account</a></li>
                                <li><a class="dropdown-item" href="my_orders.php"><i class="bi bi-box-seam me-2"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="wishlist.php"><i class="bi bi-heart me-2"></i>My Wishlist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest login / register links -->
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

/**
 * Render the storefront footer plus the shared alert scripts
 * (SweetAlert2 + notify.js) at the end of every page.
 */
function frontend_footer() {
    ?>
    <footer class="footer pt-5 mt-5">
        <div class="container">
            <div class="row gy-4">
                <!-- About / brand blurb + social links -->
                <div class="col-lg-4 col-md-6 text-center text-md-start">
                    <h5 class="mb-3"><i class="bi bi-bag-heart me-2"></i>MegaFoot</h5>
                    <p class="small pe-lg-4">Your one-stop destination for premium footwear from the world's best brands. Quality you can feel, style you can trust.</p>
                    <div class="d-flex gap-2 justify-content-center justify-content-md-start mt-3">
                        <a href="https://facebook.com" class="social-link" title="Facebook" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
                        <a href="https://instagram.com" class="social-link" title="Instagram" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
                        <a href="https://twitter.com" class="social-link" title="Twitter" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://youtube.com" class="social-link" title="YouTube" target="_blank" rel="noopener"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick links column -->
                <div class="col-lg-4 col-md-6 text-center text-md-start">
                    <h6 class="mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>Home</a></li>
                        <li class="mb-2"><a href="shop.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>Shop All Shoes</a></li>
                        <li class="mb-2"><a href="track_order.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>Track Order</a></li>
                        <li class="mb-2"><a href="contact.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>Contact Us</a></li>
                        <li class="mb-2"><a href="about.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>About Us</a></li>
                        <li class="mb-2"><a href="my_account.php" class="small"><i class="bi bi-chevron-right me-1" style="font-size:.7rem;"></i>My Account</a></li>
                    </ul>
                </div>

                <!-- Contact info column -->
                <div class="col-lg-4 col-md-12 text-center text-lg-start">
                    <h6 class="mb-3">Contact Us</h6>
                    <p class="small mb-2"><i class="bi bi-geo-alt me-2"></i>Durbar Marg, Kathmandu, Nepal</p>
                    <p class="small mb-2"><i class="bi bi-envelope me-2"></i>info@megafoot.com</p>
                    <p class="small mb-2"><i class="bi bi-telephone me-2"></i>+977-1-456789</p>
                    <p class="small"><i class="bi bi-clock me-2"></i>Sun - Fri: 9:00 AM - 8:00 PM</p>
                </div>
            </div>
        </div>

        <!-- Copyright bar -->
        <div class="border-top border-secondary mt-4 py-3">
            <div class="container">
                <p class="text-center small mb-0">&copy; 2026 MegaFoot. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Global alert scripts (also used by data-confirm prompts) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/notify.js"></script>
    <?php
}