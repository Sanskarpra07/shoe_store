<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

$featured = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name, b.name AS brand_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.discount_price IS NOT NULL
     ORDER BY p.created_at DESC
     LIMIT 4"
);

$new_arrivals = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name, b.name AS brand_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     ORDER BY p.created_at DESC
     LIMIT 8"
);

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
$brands = mysqli_query($conn, "SELECT * FROM brands ORDER BY name ASC");

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MegaFoot - Premium Shoe Store | Buy Shoes Online in Nepal</title>
    <meta name="description" content="Shop the latest running, casual, sports and formal shoes online. Top brands like Nike, Adidas, Puma and Reebok at MegaFoot.">
    <meta name="keywords" content="shoes, sneakers, buy shoes online, Nike, Adidas, Puma, Reebok, footwear Nepal">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= base_url('') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="MegaFoot - Premium Shoe Store">
    <meta property="og:description" content="Discover the latest trends in footwear from top brands worldwide.">
    <meta property="og:url" content="<?= base_url('') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<?php frontend_navbar('home'); ?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <span class="badge rounded-pill text-bg-light px-3 py-2 mb-4" style="font-weight:600;">
            <i class="bi bi-star-fill text-warning me-1"></i>Top-Rated Shoe Store in Nepal
        </span>
        <h1>Step Into Style</h1>
        <p class="mb-4">Discover the latest trends in footwear from Nike, Adidas, Puma & more</p>
        <a href="shop.php" class="btn btn-lg btn-accent px-5">
            <i class="bi bi-bag me-1"></i>Shop Now
        </a>
    </div>
</section>

<!-- Trust Bar -->
<section class="py-4 border-bottom bg-white">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-truck fs-3 text-success"></i>
                    <div class="text-start">
                        <div class="fw-bold small">Free Shipping</div>
                        <small class="text-muted" style="font-size:.75rem;">On all orders</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-arrow-repeat fs-3 text-primary"></i>
                    <div class="text-start">
                        <div class="fw-bold small">Easy Returns</div>
                        <small class="text-muted" style="font-size:.75rem;">30-day guarantee</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-shield-check fs-3 text-warning"></i>
                    <div class="text-start">
                        <div class="fw-bold small">Secure Payments</div>
                        <small class="text-muted" style="font-size:.75rem;">COD, eSewa & Khalti</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-headset fs-3 text-danger"></i>
                    <div class="text-start">
                        <div class="fw-bold small">24/7 Support</div>
                        <small class="text-muted" style="font-size:.75rem;">We're here to help</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products (On Sale) -->
<?php if (mysqli_num_rows($featured) > 0): ?>
<section class="py-5">
    <div class="container">
        <h2 class="section-title">Hot Deals</h2>
        <div class="row g-4">
            <?php while ($p = mysqli_fetch_assoc($featured)): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card product-card shadow-sm">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['product_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-img-top img-placeholder"><i class="bi bi-basket text-muted" style="font-size:4rem;"></i></div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <span class="badge badge-brand mb-2 align-self-start"><?= htmlspecialchars($p['brand_name'] ?? 'N/A') ?></span>
                        <h6 class="card-title fw-bold mb-1"><?= htmlspecialchars($p['product_name']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($p['category_name'] ?? '') ?></small>
                        <div class="mt-2">
                            <span class="price">रु <?= number_format($p['discount_price'], 2) ?></span>
                            <span class="old-price ms-2">रु <?= number_format($p['price'], 2) ?></span>
                        </div>
                        <div class="mt-2 mb-3">
                            <?php if ($p['stock'] > 0): ?>
                                <span class="badge bg-success stock-badge">In Stock (<?= $p['stock'] ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-danger stock-badge">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-accent btn-sm w-100 mt-auto add-to-cart">View Details</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Categories -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="row g-3">
            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
            <?php
                $cat_icons = ['fa-solid fa-basket-shopping', 'fa-solid fa-bag-shopping', 'fa-solid fa-trophy', 'fa-solid fa-briefcase', 'fa-solid fa-boot'];
                $icon = !empty($cat['icon']) ? $cat['icon'] : $cat_icons[$cat['id'] % 5];
            ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="shop.php?category=<?= urlencode($cat['name']) ?>" class="text-decoration-none">
                    <div class="card category-card text-center p-4 shadow-sm h-100">
                        <i class="<?= $icon ?> fs-1 mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cat['name']) ?></h6>
                        <small class="text-muted d-block"><?= htmlspecialchars(substr($cat['description'] ?? '', 0, 30)) ?></small>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- New Arrivals -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">New Arrivals</h2>
            <a href="shop.php" class="btn btn-outline-dark">View All</a>
        </div>
        <div class="row g-4">
            <?php while ($p = mysqli_fetch_assoc($new_arrivals)): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card product-card shadow-sm">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($p['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['product_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-img-top img-placeholder"><i class="bi bi-basket text-muted" style="font-size:4rem;"></i></div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <span class="badge badge-brand mb-2 align-self-start"><?= htmlspecialchars($p['brand_name'] ?? 'N/A') ?></span>
                        <h6 class="card-title fw-bold mb-1"><?= htmlspecialchars($p['product_name']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($p['category_name'] ?? '') ?></small>
                        <div class="mt-2">
                            <?php if ($p['discount_price']): ?>
                                <span class="price">रु <?= number_format($p['discount_price'], 2) ?></span>
                                <span class="old-price ms-2">रु <?= number_format($p['price'], 2) ?></span>
                            <?php else: ?>
                                <span class="price">रु <?= number_format($p['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                            <?php if ($p['stock'] > 0): ?>
                                <span class="badge bg-success stock-badge">In Stock</span>
                            <?php else: ?>
                                <span class="badge bg-danger stock-badge">Out of Stock</span>
                            <?php endif; ?>
                            <?php if ($p['color']): ?>
                                <small class="text-muted"><i class="bi bi-palette me-1"></i><?= htmlspecialchars($p['color']) ?></small>
                            <?php endif; ?>
                        </div>
                        <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-accent btn-sm w-100 mt-auto add-to-cart">View Details</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Brands -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title">Our Brands</h2>
        <div class="row g-3">
            <?php
            $brand_icons = ['fa-solid fa-certificate', 'fa-solid fa-award', 'fa-solid fa-bolt', 'fa-solid fa-hexagon', 'fa-solid fa-shield-halved'];
            $brand_i = 0;
            while ($br = mysqli_fetch_assoc($brands)):
                $bicon = !empty($br['icon']) ? $br['icon'] : $brand_icons[$brand_i % 5]; ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="shop.php?brand=<?= urlencode($br['name']) ?>" class="text-decoration-none">
                    <div class="card category-card text-center p-4 shadow-sm h-100">
                        <i class="<?= $bicon ?> fs-1 mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($br['name']) ?></h6>
                        <small class="text-muted d-block"><?= htmlspecialchars(substr($br['description'] ?? '', 0, 35)) ?></small>
                    </div>
                </a>
            </div>
            <?php $brand_i++; endwhile; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
