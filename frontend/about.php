<?php
/**
 * --------------------------------------------------------------------------
 * About Us - MegaFoot Storefront
 * --------------------------------------------------------------------------
 * Static informational page describing the MegaFoot brand story, mission,
 * and key reasons customers should choose the store.
 * --------------------------------------------------------------------------
 */
// ---------- Shared bootstrap ----------
require_once __DIR__ . '/../backend/auth_helper.php';
$active = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<!-- ======== <head> ======== -->
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - MegaFoot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>
<body>
    <!-- ======== Navbar ======== -->
    <?php frontend_navbar('about'); ?>

    <!-- ======== Page Content ======== -->
    <div class="container py-4">
        <h2 class="section-title">About MegaFoot</h2>
        <p class="text-muted mb-4">Your one-stop destination for premium footwear.</p>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bag-heart text-danger me-2"></i>Our Story</h5>
                    <p class="mb-1">Founded in Kathmandu, MegaFoot brings the world's best footwear brands to your doorstep. From everyday sneakers to premium formal wear, we curate shoes that match your style and comfort.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bullseye text-primary me-2"></i>Our Mission</h5>
                    <p class="mb-1">To make premium footwear accessible to everyone in Nepal through a seamless, trustworthy online shopping experience.</p>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-patch-check text-success me-2"></i>Why Choose Us</h5>
                    <ul class="mb-0">
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Premium Brands</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Authentic Products</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Secure Payments (eSewa/Khalti)</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Fast Delivery</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Easy Returns</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ======== Footer ======== -->
    <?php frontend_footer(); ?>

    <!-- ======== Scripts ======== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
