<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['username'])){
    header("Location: ../admin/login.php");
    exit();
}
require_once '../db.php';

if (isset($_GET['action']) && $_GET['action'] ==='delete' && isset($_GET['id'])){
    $id = (int) $_GET['id'];
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

$current_page = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Shoe Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style> body { background-color: #f0f2f5; } </style>
</head>
<body>
    
<div class="container-fluid">
    <div class="row flex-nowrap">

    <?php require_once 'includes/sidebar.php' ?>

    <div class="col py-4 px-4">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-success"></i>Products</h4>
                <a href="add_product.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i> Add Product
                </a>
            </div>

            <form method="GET" action="products.php" class="mb-4">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" name="search" class="form-control"
                        placeholder="Search by name, category or brand..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if (!empty($_GET['search'])): ?>
                        <a href="products.php" class="btn btn-outline-danger">
                            <i class="bi bi-x"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!empty($search)): ?>
                <p class="text-muted small mb-3">
                    Showing <?= mysqli_num_rows($result) ?> result(s) for
                    "<strong><?= htmlspecialchars($search) ?></strong>"
                </p>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Stock</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $sno = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= $sno++ ?></td>
                            <td>
                                <?php if (!empty($row['image'])): ?>
                                    <div style="width:50px;height:50px;border-radius:8px;overflow:hidden;background:#f0f0f0;">
                                        <img src="../<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center text-muted" style="width:50px;height:50px;border-radius:8px;background:#f0f0f0;">
                                        <i class="bi bi-basket"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?></span></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></span></td>
                            <td>
                                $<?= number_format($row['price'], 2) ?>
                                <?php if ($row['discount_price']): ?>
                                    <br><small class="text-success fw-bold">$<?= number_format($row['discount_price'], 2) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($row['size'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($row['color'] ?? '—') ?></td>
                            <td>
                                <?php if ($row['stock'] < 10): ?>
                                    <span class="badge bg-danger"><?= $row['stock'] ?> !</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $row['stock'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="add_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="products.php?action=delete&id=<?= $row['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                        <?php if (mysqli_num_rows($result) === 0): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No Products Found. <a href="add_product.php">Add One?</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.replace('../admin/login.php');
            }
        });
    </script>
</body>
</html>
