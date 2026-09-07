<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

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

site_header('StepStyle - Online Shoe Store', 'home');
?>

<h2 class="page-title">Welcome to StepStyle</h2>
<p>Shop the latest school of running, casual, sports, formal shoes and boots from top brands like
Nike, Adidas, Puma and Reebok. Choose <strong>Cash on Delivery</strong> or pay online with
<strong>eSewa</strong> / <strong>Khalti</strong>.</p>

<h3 class="section-title">Hot Deals (On Sale)</h3>
<div class="product-row">
    <?php while ($p = mysqli_fetch_assoc($featured)): ?>
    <div class="product-box">
        <div class="p-img">
            <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
            <?php else: ?>
                <span style="color:#999;">No Image</span>
            <?php endif; ?>
        </div>
        <div class="p-name"><?= htmlspecialchars($p['product_name']) ?></div>
        <div class="p-cat"><?= htmlspecialchars($p['brand_name'] ?? '') ?> - <?= htmlspecialchars($p['category_name'] ?? '') ?></div>
        <div class="p-price">
            <span class="p-old"><?= price_label($p['price']) ?></span>
            <?= price_label($p['discount_price']) ?>
        </div>
        <div class="p-stock">
            <?php if ($p['stock'] > 0): ?>
                <span style="color:#2e7d32;"><strong>In Stock</strong> (<?= $p['stock'] ?>)</span>
            <?php else: ?>
                <span style="color:#c62828;"><strong>Out of Stock</strong></span>
            <?php endif; ?>
        </div>
        <a class="btn btn-small" href="product.php?id=<?= $p['id'] ?>">View Details</a>
    </div>
    <?php endwhile; ?>
</div>

<h3 class="section-title">Shop by Category</h3>
<table class="table">
    <tr>
        <th colspan="2">Category</th>
        <th>Description</th>
    </tr>
    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
    <tr>
        <td class="row-num" style="width:40px;"><?= $cat['id'] ?></td>
        <td style="width:200px;"><a href="shop.php?category=<?= urlencode($cat['name']) ?>"><strong><?= htmlspecialchars($cat['name']) ?></strong></a></td>
        <td><?= htmlspecialchars($cat['description'] ?? '') ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<h3 class="section-title">New Arrivals</h3>
<div class="product-row">
    <?php while ($p = mysqli_fetch_assoc($new_arrivals)): ?>
    <div class="product-box">
        <div class="p-img">
            <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
            <?php else: ?>
                <span style="color:#999;">No Image</span>
            <?php endif; ?>
        </div>
        <div class="p-name"><?= htmlspecialchars($p['product_name']) ?></div>
        <div class="p-cat"><?= htmlspecialchars($p['brand_name'] ?? '') ?> - <?= htmlspecialchars($p['category_name'] ?? '') ?></div>
        <div class="p-price">
            <?php if ($p['discount_price']): ?>
                <span class="p-old"><?= price_label($p['price']) ?></span>
                <?= price_label($p['discount_price']) ?>
            <?php else: ?>
                <?= price_label($p['price']) ?>
            <?php endif; ?>
        </div>
        <div class="p-stock">
            <?php if ($p['stock'] > 0): ?>
                <span style="color:#2e7d32;">In Stock (<?= $p['stock'] ?>)</span>
            <?php else: ?>
                <span style="color:#c62828;">Out of Stock</span>
            <?php endif; ?>
        </div>
        <a class="btn btn-small" href="product.php?id=<?= $p['id'] ?>">View Details</a>
    </div>
    <?php endwhile; ?>
</div>

<div class="mt-20 text-center">
    <a class="btn" href="shop.php">View All Products</a>
</div>

<?php site_footer(); ?>