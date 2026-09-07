<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$brand_filter = trim($_GET['brand'] ?? '');

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(p.product_name LIKE ? OR p.description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if (!empty($category_filter)) {
    $where[] = "c.name LIKE ?";
    $params[] = "%$category_filter%";
    $types .= 's';
}
if (!empty($brand_filter)) {
    $where[] = "b.name LIKE ?";
    $params[] = "%$brand_filter%";
    $types .= 's';
}

$sql = "SELECT p.*, c.name AS category_name, b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY p.created_at DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $products = mysqli_stmt_get_result($stmt);
} else {
    $products = mysqli_query($conn, $sql);
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
$brands_list = mysqli_query($conn, "SELECT * FROM brands ORDER BY name ASC");

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Shoes - StepStyle</title>
    <meta name="description" content="Browse and filter our full collection of running, casual, sports, formal shoes and boots by category, brand, price and color.">
    <meta name="keywords" content="shoes, sneakers, running shoes, casual shoes, sports shoes, footwear">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="http://localhost/shoe_store/shop.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Shop Shoes - StepStyle">
    <meta property="og:url" content="http://localhost/shoe_store/shop.php">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/frontend.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<?php frontend_navbar('shop'); ?>

<div class="container py-5">
    <h2 class="section-title">Shop Our Collection</h2>

    <!-- Search & Filters -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <form method="GET" action="shop.php">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search shoes..."
                           value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
        <div class="col-md-3">
            <select class="form-select" onchange="window.location='shop.php?category='+this.value">
                <option value="">All Categories</option>
                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $category_filter === $cat['name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" onchange="window.location='shop.php?brand='+this.value">
                <option value="">All Brands</option>
                <?php
                mysqli_data_seek($brands_list, 0);
                while ($br = mysqli_fetch_assoc($brands_list)): ?>
                    <option value="<?= htmlspecialchars($br['name']) ?>" <?= $brand_filter === $br['name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($br['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <a href="shop.php" class="btn btn-outline-secondary w-100">Clear Filters</a>
        </div>
    </div>

    <?php if (!empty($search) || !empty($category_filter) || !empty($brand_filter)): ?>
        <p class="text-muted mb-3">
            Showing <?= mysqli_num_rows($products) ?> result(s)
            <?php if (!empty($search)): ?> for "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
            <?php if (!empty($category_filter)): ?> in category "<strong><?= htmlspecialchars($category_filter) ?></strong>"<?php endif; ?>
            <?php if (!empty($brand_filter)): ?> by brand "<strong><?= htmlspecialchars($brand_filter) ?></strong>"<?php endif; ?>
        </p>
    <?php endif; ?>

    <!-- Products Grid -->
    <div class="row g-4">
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card product-card shadow-sm">
                <?php if (!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['product_name']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="card-img-top img-placeholder"><i class="bi bi-basket text-muted" style="font-size:4rem;"></i></div>
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge badge-brand align-self-start"><?= htmlspecialchars($p['brand_name'] ?? 'N/A') ?></span>
                        <?php if ($p['discount_price']): ?>
                            <?php $pct = round((($p['price'] - $p['discount_price']) / $p['price']) * 100); ?>
                            <span class="badge bg-danger">-<?= $pct ?>%</span>
                        <?php endif; ?>
                    </div>
                    <h6 class="card-title fw-bold mb-1"><?= htmlspecialchars($p['product_name']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></small>
                    <?php if ($p['color']): ?>
                        <small class="text-muted mt-1"><i class="bi bi-palette me-1"></i><?= htmlspecialchars($p['color']) ?></small>
                    <?php endif; ?>
                    <div class="mt-2">
                        <?php if ($p['discount_price']): ?>
                            <span class="price">$<?= number_format($p['discount_price'], 2) ?></span>
                            <span class="old-price ms-2">$<?= number_format($p['price'], 2) ?></span>
                        <?php else: ?>
                            <span class="price">$<?= number_format($p['price'], 2) ?></span>
                        <?php endif; ?>
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

        <?php if (mysqli_num_rows($products) === 0): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-search fs-1 text-muted"></i>
                <h4 class="text-muted mt-3">No products found</h4>
                <a href="shop.php" class="btn btn-accent mt-2">Browse All Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<?php frontend_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
