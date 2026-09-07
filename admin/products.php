<?php
session_start();
$page_title = 'Products';
$current_page = 'products';
require_once 'includes/header.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $_SESSION['success'] = "Product deleted successfully.";
    header("Location: products.php");
    exit();
}

$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $stmt = mysqli_prepare($conn,
        "SELECT p.*, c.name AS category_name, b.name AS brand_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN brands b ON p.brand_id = b.id
         WHERE p.product_name LIKE ? OR c.name LIKE ? OR b.name LIKE ?
         ORDER BY p.created_at DESC"
    );
    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn,
        "SELECT p.*, c.name AS category_name, b.name AS brand_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN brands b ON p.brand_id = b.id
         ORDER BY p.created_at DESC"
    );
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>

<h2>Products</h2>
<p><a class="btn btn-green" href="add_product.php">+ Add Product</a></p>

<form method="GET" action="products.php" style="margin-bottom:10px;">
    <input type="text" name="search" placeholder="Search by name, category or brand..."
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="padding:7px; width:300px;">
    <button type="submit" class="btn btn-small">Search</button>
    <?php if (!empty($search)): ?><a class="btn btn-gray btn-small" href="products.php">Clear</a><?php endif; ?>
</form>

<?php if (!empty($search)): ?>
    <p class="small text-muted">Showing <?= mysqli_num_rows($result) ?> result(s) for
        "<strong><?= htmlspecialchars($search) ?></strong>"</p>
<?php endif; ?>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<table class="table">
    <tr>
        <th>#</th>
        <th>Image</th>
        <th>Product Name</th>
        <th>Brand</th>
        <th>Category</th>
        <th>Price</th>
        <th>Size</th>
        <th>Color</th>
        <th>Stock</th>
        <th>Actions</th>
    </tr>
    <?php $sno = 1; while ($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td class="center"><?= $sno++ ?></td>
        <td class="center">
            <?php if (!empty($row['image'])): ?>
                <img src="../<?= htmlspecialchars($row['image']) ?>" alt="" style="width:50px; height:50px; border:1px solid #ddd;">
            <?php else: ?>
                <span class="text-muted">No image</span>
            <?php endif; ?>
        </td>
        <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
        <td class="center"><?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?></td>
        <td class="center"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
        <td class="center">
            $<?= number_format($row['price'], 2) ?>
            <?php if ($row['discount_price']): ?>
                <br><span style="color:#2e7d32;">$<?= number_format($row['discount_price'], 2) ?></span>
            <?php endif; ?>
        </td>
        <td class="center"><?= htmlspecialchars($row['size'] ?? '-') ?></td>
        <td class="center"><?= htmlspecialchars($row['color'] ?? '-') ?></td>
        <td class="center">
            <?php if ($row['stock'] < 10): ?>
                <span style="color:#c62828;"><strong><?= $row['stock'] ?> (Low)</strong></span>
            <?php else: ?>
                <span style="color:#2e7d32;"><strong><?= $row['stock'] ?></strong></span>
            <?php endif; ?>
        </td>
        <td class="center">
            <a class="btn btn-small" href="add_product.php?id=<?= $row['id'] ?>">Edit</a>
            <a class="btn btn-red btn-small" href="products.php?action=delete&id=<?= $row['id'] ?>"
               onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($result) === 0): ?>
    <tr>
        <td colspan="10" class="center">No products found. <a href="add_product.php">Add one?</a></td>
    </tr>
    <?php endif; ?>
</table>

<?php require_once 'includes/footer.php'; ?>