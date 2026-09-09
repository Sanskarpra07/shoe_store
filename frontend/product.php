<?php
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: shop.php");
    exit();
}

$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.name AS category_name, b.name AS brand_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$product = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($product);

if (!$row) {
    header("Location: shop.php");
    exit();
}

$related = mysqli_query($conn,
    "SELECT p.*, b.name AS brand_name
     FROM products p
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.category_id = {$row['category_id']} AND p.id != $id
     LIMIT 4"
);

// Up-selling: higher-value / premium alternatives in the same category
$current_price = $row['discount_price'] ?: $row['price'];
$upsell_stmt = mysqli_prepare($conn,
    "SELECT p.*, b.name AS brand_name
     FROM products p
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.category_id = ? AND p.id != ?
       AND IFNULL(p.discount_price, p.price) > ?
     ORDER BY IFNULL(p.discount_price, p.price) DESC
     LIMIT 4"
);
$cat_id = $row['category_id'];
mysqli_stmt_bind_param($upsell_stmt, "iid", $cat_id, $id, $current_price);
mysqli_stmt_execute($upsell_stmt);
$upsell = mysqli_stmt_get_result($upsell_stmt);

// ---- Reviews ----
$review_meta = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c, IFNULL(AVG(rating),0) AS avg, SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS r5,
            SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS r4, SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS r3,
            SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS r2, SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS r1
     FROM reviews WHERE product_id = $id AND status = 'approved'"));
$review_count = (int)$review_meta['c'];
$review_avg = round((float)$review_meta['avg'], 1);
$rating_bars = [$review_meta['r1'], $review_meta['r2'], $review_meta['r3'], $review_meta['r4'], $review_meta['r5']];

$approved_reviews = mysqli_query($conn,
    "SELECT r.*, c.full_name FROM reviews r
     LEFT JOIN customers c ON r.customer_id = c.id
     WHERE r.product_id = $id AND r.status = 'approved'
     ORDER BY r.created_at DESC LIMIT 20");

// Handle review submission
$review_msg = '';
$review_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!is_customer_logged_in()) {
        $review_err = "Please <a href='login.php'>login</a> to submit a review.";
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $cid = (int)$_SESSION['customer_id'];
        if ($rating < 1 || $rating > 5) {
            $review_err = "Please select a star rating.";
        } elseif (strlen($comment) < 5) {
            $review_err = "Review must be at least 5 characters.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO reviews (product_id, customer_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($stmt, "iiis", $id, $cid, $rating, $comment);
            mysqli_stmt_execute($stmt);
            $review_msg = "Thank you! Your review has been submitted and is awaiting moderation.";
        }
    }
}

// Check if current customer already reviewed this product
$already_reviewed = false;
if (is_customer_logged_in()) {
    $cid = (int)$_SESSION['customer_id'];
    $chk = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id = $id AND customer_id = $cid");
    $already_reviewed = mysqli_num_rows($chk) > 0;
}

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
    <title><?= htmlspecialchars($row['product_name']) ?> - MegaFoot</title>
    <meta name="description" content="Buy <?= htmlspecialchars($row['product_name']) ?> online. <?= htmlspecialchars(mb_substr(strip_tags($row['description'] ?? ''), 0, 150)) ?><?= $row['brand_name'] ? ' ' . htmlspecialchars($row['brand_name']) . '.' : '' ?>">
    <meta name="keywords" content="<?= htmlspecialchars($row['product_name']) ?>, shoes, sneakers, <?= htmlspecialchars($row['brand_name'] ?? '') ?>, footwear">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="product">
    <meta property="og:title" content="<?= htmlspecialchars($row['product_name']) ?> - MegaFoot">
    <meta property="og:description" content="<?= htmlspecialchars(mb_substr(strip_tags($row['description'] ?? ''), 0, 150)) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>
<body>

<?php frontend_navbar(); ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($row['product_name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Product Image -->
        <div class="col-md-5">
            <div class="product-image-container shadow-sm">
                <?php if (!empty($row['image'])): ?>
                    <img src="<?= htmlspecialchars($row['image']) ?>" class="img-fluid" alt="<?= htmlspecialchars($row['product_name']) ?>">
                <?php else: ?>
                    <div class="placeholder"><i class="bi bi-basket text-muted" style="font-size: 8rem;"></i></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($row['color'])): ?>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <small class="text-muted"><i class="bi bi-palette me-1"></i>Color:</small>
                    <span class="badge badge-brand"><?= htmlspecialchars($row['color']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="col-md-7">
            <span class="badge badge-brand mb-2 fs-6"><?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?></span>
            <h2 class="fw-bold"><?= htmlspecialchars($row['product_name']) ?></h2>
            <p class="text-muted"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></p>

            <div class="mb-3">
                <?php if ($row['discount_price']): ?>
                    <span class="detail-price">रु <?= number_format($row['discount_price'], 2) ?></span>
                    <span class="old-price ms-2 fs-5">रु <?= number_format($row['price'], 2) ?></span>
                    <?php
                    $savings = $row['price'] - $row['discount_price'];
                    $percent = round(($savings / $row['price']) * 100);
                    ?>
                    <span class="badge bg-danger ms-2">Save <?= $percent ?>%</span>
                <?php else: ?>
                    <span class="detail-price">रु <?= number_format($row['price'], 2) ?></span>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <?php if ($row['stock'] > 0): ?>
                    <span class="badge bg-success fs-6">In Stock (<?= $row['stock'] ?> available)</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6">Out of Stock</span>
                <?php endif; ?>
            </div>

            <?php if ($row['description']): ?>
                <p class="mt-3"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
            <?php endif; ?>

            <table class="table detail-table mt-3" style="max-width: 430px;">
                <?php if ($row['size']): ?>
                    <tr><td class="fw-semibold">Size</td><td><?= htmlspecialchars($row['size']) ?></td></tr>
                <?php endif; ?>
                <?php if ($row['color']): ?>
                    <tr><td class="fw-semibold">Color</td><td><?= htmlspecialchars($row['color']) ?></td></tr>
                <?php endif; ?>
                <tr><td class="fw-semibold">Brand</td><td><?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?></td></tr>
                <tr><td class="fw-semibold">Category</td><td><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></td></tr>
            </table>

            <?php if ($row['stock'] > 0): ?>
            <form method="POST" action="add_to_cart.php" class="d-flex gap-3 align-items-center mt-4">
                <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                <div class="input-group" style="width: 130px;">
                    <span class="input-group-text">Qty</span>
                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?= $row['stock'] ?>">
                </div>
                <button type="submit" class="btn btn-accent btn-lg px-4">
                    <i class="bi bi-cart-plus me-1"></i>Add to Cart
                </button>
            </form>
            <?php endif; ?>
            <?php if (is_customer_logged_in()):
                $wid = (int)$_SESSION['customer_id'];
                $wchk = mysqli_query($conn, "SELECT id FROM wishlists WHERE customer_id = $wid AND product_id = {$row['id']}");
                $in_wishlist = mysqli_num_rows($wchk) > 0;
            ?>
                <form method="POST" action="add_to_wishlist.php" class="d-inline ms-2">
                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn <?= $in_wishlist ? 'btn-danger' : 'btn-outline-danger' ?> btn-lg" title="<?= $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
                        <i class="bi bi-heart<?= $in_wishlist ? '-fill' : '' ?>"></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Up-selling: Premium / higher-value alternatives -->
    <?php if ($upsell && mysqli_num_rows($upsell) > 0): ?>
    <section class="mt-5 pt-5 border-top">
        <h3 class="section-title">Premium Options <small class="text-muted fs-6">Upgrade for more performance and style</small></h3>
        <div class="row g-4">
            <?php while ($u = mysqli_fetch_assoc($upsell)): ?>
            <div class="col-md-3">
                <div class="card product-card shadow-sm border-primary">
                    <?php if (!empty($u['image'])): ?>
                        <img src="<?= htmlspecialchars($u['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($u['product_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-img-top img-placeholder"><i class="bi bi-basket text-muted" style="font-size:3rem;"></i></div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-primary mb-2 align-self-start"><?= htmlspecialchars($u['brand_name'] ?? '') ?></span>
                        <h6 class="card-title fw-bold"><?= htmlspecialchars($u['product_name']) ?></h6>
                        <span class="price text-primary fw-bold">रु <?= number_format($u['discount_price'] ?: $u['price'], 2) ?></span>
                        <a href="product.php?id=<?= $u['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-auto add-to-cart">View Upgrade</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if ($related && mysqli_num_rows($related) > 0): ?>
    <section class="mt-5 pt-5 border-top">
        <h3 class="section-title">Related Products</h3>
        <div class="row g-4">
            <?php while ($r = mysqli_fetch_assoc($related)): ?>
            <div class="col-md-3">
                <div class="card product-card shadow-sm">
                    <?php if (!empty($r['image'])): ?>
                        <img src="<?= htmlspecialchars($r['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($r['product_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-img-top img-placeholder"><i class="bi bi-basket text-muted" style="font-size:3rem;"></i></div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <span class="badge badge-brand mb-2 align-self-start"><?= htmlspecialchars($r['brand_name'] ?? '') ?></span>
                        <h6 class="card-title fw-bold"><?= htmlspecialchars($r['product_name']) ?></h6>
                        <span class="price">रु <?= number_format($r['discount_price'] ?: $r['price'], 2) ?></span>
                        <a href="product.php?id=<?= $r['id'] ?>" class="btn btn-accent btn-sm w-100 mt-auto add-to-cart">View</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Reviews Section -->
    <section class="mt-5 pt-5 border-top">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-3 h-100">
                    <div class="card-body text-center p-4">
                        <h5 class="fw-bold mb-3">Customer Reviews</h5>
                        <div class="display-4 fw-bold"><?= $review_count > 0 ? $review_avg : '—' ?></div>
                        <div class="fs-5 text-warning my-1">
                            <?= str_repeat('★', (int)round($review_avg)) ?><span class="text-muted"><?= str_repeat('☆', 5 - (int)round($review_avg)) ?></span>
                        </div>
                        <p class="text-muted mb-3"><?= $review_count ?> review<?= $review_count === 1 ? '' : 's' ?></p>
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                            <div class="d-flex align-items-center gap-2 small mb-1">
                                <span class="text-warning" style="min-width:28px;"><?= $s ?>★</span>
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar bg-warning" style="width: <?= $review_count > 0 ? (int)($rating_bars[$s - 1] / $review_count * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-header bg-white fw-semibold py-3">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>Write a Review
                    </div>
                    <div class="card-body">
                        <?php if ($review_msg): ?>
                            <div class="alert alert-success py-2"><?= $review_msg ?></div>
                        <?php endif; ?>
                        <?php if ($review_err): ?>
                            <div class="alert alert-danger py-2"><?= $review_err ?></div>
                        <?php endif; ?>

                        <?php if (!is_customer_logged_in()): ?>
                            <p class="text-muted mb-0"><a href="login.php">Login</a> to share your review and rating.</p>
                        <?php elseif ($already_reviewed): ?>
                            <p class="text-success mb-0"><i class="bi bi-check-circle me-1"></i>You have already reviewed this product.</p>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="fw-semibold d-block mb-2">Your Rating</label>
                                    <div class="star-input">
                                        <?php for ($s = 5; $s >= 1; $s--): ?>
                                            <input type="radio" name="rating" value="<?= $s ?>" id="star<?= $s ?>" class="d-none">
                                            <label for="star<?= $s ?>" class="star-label fs-3" style="cursor:pointer; color:#ddd;" onclick="fillStars(<?= $s ?>)">
                                                <i class="bi bi-star-fill"></i>
                                            </label>
                                        <?php endfor; ?>
                                        <input type="hidden" id="rating_hidden" value="0">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-semibold d-block mb-2">Your Review</label>
                                    <textarea name="comment" class="form-control" rows="3" minlength="5" required placeholder="How is the quality, fit, comfort...?"></textarea>
                                </div>
                                <button name="submit_review" class="btn btn-accent">
                                    <i class="bi bi-send me-1"></i>Submit Review
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($review_count > 0): ?>
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white fw-semibold py-3">
                        <i class="bi bi-chat-quote me-2 text-success"></i>Recent Reviews
                    </div>
                    <div class="card-body">
                        <?php while ($rv = mysqli_fetch_assoc($approved_reviews)): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong><?= htmlspecialchars($rv['full_name'] ?? 'Customer') ?></strong>
                                    <small class="text-muted"><?= date('d M Y', strtotime($rv['created_at'])) ?></small>
                                </div>
                                <div class="text-warning small mb-2">
                                    <?= str_repeat('★', (int)$rv['rating']) ?><span class="text-muted"><?= str_repeat('☆', 5 - (int)$rv['rating']) ?></span>
                                </div>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php frontend_footer(); ?>

<script>
function fillStars(rating) {
    const labels = document.querySelectorAll('.star-input .star-label');
    labels.forEach(function (label) {
        const input = label.previousElementSibling;
        const value = parseInt(input.value, 10);
        label.style.color = value <= rating ? '#ffc107' : '#ddd';
        if (input.value == rating) input.checked = true;
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
