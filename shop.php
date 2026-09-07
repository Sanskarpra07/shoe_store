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
$brands = mysqli_query($conn, "SELECT * FROM brands ORDER BY name ASC");

site_header('Shop - StepStyle Online Shoe Store', 'shop');
?>

<h2 class="page-title">Shop Our Collection</h2>

<form method="GET" action="shop.php" class="mb-20">
    <table class="table">
        <tr>
            <th colspan="2">Search / Filter Products</th>
        </tr>
        <tr>
            <td style="width:130px;"><label><strong>Search:</strong></label></td>
            <td><input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search shoes..." style="width:60%;padding:6px;"></td>
        </tr>
        <tr>
            <td><strong>Category:</strong></td>
            <td>
                <select name="category" style="padding:6px;">
                    <option value="">All Categories</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $category_filter === $cat['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><strong>Brand:</strong></td>
            <td>
                <select name="brand" style="padding:6px;">
                    <option value="">All Brands</option>
                    <?php while ($br = mysqli_fetch_assoc($brands)): ?>
                        <option value="<?= htmlspecialchars($br['name']) ?>" <?= $brand_filter === $br['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($br['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="text-center">
                <button type="submit" class="btn">Search</button>
                <a class="btn btn-gray" href="shop.php">Clear Filters</a>
            </td>
        </tr>
    </table>
</form>

<?php if (!empty($search) || !empty($category_filter) || !empty($brand_filter)): ?>
    <p class="small">Showing <?= mysqli_num_rows($products) ?> result(s)
        <?php if ($search): ?> for "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
        <?php if ($category_filter): ?> in "<strong><?= htmlspecialchars($category_filter) ?></strong>"<?php endif; ?>
        <?php if ($brand_filter): ?> by "<strong><?= htmlspecialchars($brand_filter) ?></strong>"<?php endif; ?>.
    </p>
<?php endif; ?>

<div class="product-row">
    <?php if (mysqli_num_rows($products) === 0): ?>
        <p>No products found. Please try a different search.</p>
    <?php endif; ?>
    <?php while ($p = mysqli_fetch_assoc($products)): ?>
    <div class="product-box">
        <div class="p-img">
            <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
            <?php else: ?>
                <span style="color:#999;">No Image</span>
            <?php endif; ?>
        </div>
        <div class="p-name"><?= htmlspecialchars($p['product_name']) ?></div>
        <div class="p-cat">
            <?= htmlspecialchars($p['brand_name'] ?? 'N/A') ?> - <?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?>
        </div>
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

<?php site_footer(); ?>