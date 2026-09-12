<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN PRODUCTS
 * --------------------------------------------------------------------------
 * Lists all products in a searchable table with image thumbnails, price,
 * stock and edit/delete actions. Product deletion is handled here via a
 * POST form (with a SweetAlert confirmation). Add/edit is done on
 * add_product.php.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Products';
$current_page = 'products';
require_once 'includes/header.php';

// --- Handle product deletion (POST) ----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $_SESSION['success'] = "Product deleted successfully.";
    header("Location: products.php");
    exit();
}

// --- Read the search query (GET) ---------------------------------------------------
$search = trim($_GET['search'] ?? '');

// --- Fetch products, optionally filtered by search ----------------------------------
if (!empty($search)) {
    // Search across product name, category name and brand name.
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
    // No search - return every product with its category/brand names.
    $result = mysqli_query($conn,
        "SELECT p.*, c.name AS category_name, b.name AS brand_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN brands b ON p.brand_id = b.id
         ORDER BY p.created_at DESC"
    );
}

// --- Flash success message -----------------------------------------------------------
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Store row count before the while loop consumes the result.
$total_rows = mysqli_num_rows($result);
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Products</h2>
        <p class="page-sub">Manage your product catalog</p>
    </div>
    <a class="btn btn-green" href="add_product.php"><i class="bi bi-plus-lg"></i> Add Product</a>
</div>

<!-- ======== PRODUCTS TABLE (panel card) ======== -->
<div class="panel-card">
    <div class="panel-header">
        <h3><i class="bi bi-box-seam"></i> Products <span class="text-muted small">(<?= $total_rows ?>)</span></h3>
        <div class="panel-tools">
            <form method="GET" action="products.php" class="search-row" style="margin:0;">
                <input type="text" name="search" placeholder="Search by name, category or brand..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn btn-small"><i class="bi bi-search"></i> Search</button>
                <?php if (!empty($search)): ?><a class="btn btn-gray btn-small" href="products.php"><i class="bi bi-x-circle"></i> Clear</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="panel-body">

        <!-- Search result summary -->
        <?php if (!empty($search)): ?>
            <p class="small text-muted" style="margin:12px 0 0;">Showing <?= $total_rows ?> result(s) for
                "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>

        <!-- Flash success message -->
        <?php if ($success): ?>
            <div class="msg-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
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
                <img src="../<?= htmlspecialchars($row['image']) ?>" alt="" class="thumb">
            <?php else: ?>
                <span class="text-muted small">No image</span>
            <?php endif; ?>
        </td>
        <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
        <td class="center"><?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?></td>
        <td class="center"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></td>
        <td class="center">
            <strong>रु <?= number_format($row['price'], 2) ?></strong>
            <?php if ($row['discount_price']): ?>
                <br><span class="text-success">रु <?= number_format($row['discount_price'], 2) ?></span>
            <?php endif; ?>
        </td>
        <td class="center"><?= htmlspecialchars($row['size'] ?? '-') ?></td>
        <td class="center"><?= htmlspecialchars($row['color'] ?? '-') ?></td>
        <td class="center">
            <?php if ($row['stock'] < 10): ?>
                <span class="badge badge-danger"><?= $row['stock'] ?> &middot; Low</span>
            <?php else: ?>
                <span class="badge badge-success"><?= $row['stock'] ?></span>
            <?php endif; ?>
        </td>
        <td class="center">
            <a class="btn btn-small" href="add_product.php?id=<?= $row['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
            <form method="POST" action="products.php" style="display:inline;">
                <input type="hidden" name="delete_product" value="1">
                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn btn-red btn-small" data-confirm="Are you sure you want to delete this product?"><i class="bi bi-trash"></i> Delete</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>

    <!-- Empty state when no products match -->
    <?php if ($total_rows === 0): ?>
    <tr>
        <td colspan="10"><div class="empty-state"><i class="bi bi-inbox"></i>No products found. <a href="add_product.php">Add one?</a></div></td>
    </tr>
    <?php endif; ?>
</table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>