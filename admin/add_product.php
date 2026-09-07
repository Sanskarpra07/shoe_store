<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../admin/login.php");
    exit();
}

require_once '../db.php';

$errors = [];
$is_edit = false;
$product = [
    'id'             => '',
    'product_name'   => '',
    'description'    => '',
    'price'          => '',
    'discount_price' => '',
    'stock'          => '',
    'size'           => '',
    'color'          => '',
    'image'          => '',
    'category_id'    => '',
    'brand_id'       => ''
];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result  = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit();
    }
    $is_edit = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name   = trim($_POST['product_name'] ?? '');
    $description    = trim($_POST['description']  ?? '');
    $price          = $_POST['price']          ?? '';
    $discount_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : null;
    $stock          = $_POST['stock']          ?? '';
    $size           = trim($_POST['size']      ?? '');
    $color          = trim($_POST['color']     ?? '');
    $category_id    = $_POST['category_id']    ?? null;
    $brand_id       = $_POST['brand_id']       ?? null;
    $edit_id        = (int)($_POST['edit_id'] ?? 0);
    $image          = $product['image'] ?? null;

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../assets/img/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                // Delete old image if replacing
                if ($edit_id > 0 && !empty($image) && file_exists(__DIR__ . '/../' . $image)) {
                    @unlink(__DIR__ . '/../' . $image);
                }
                $image = 'assets/img/' . $filename;
            }
        } else {
            $errors[] = "Only JPG, PNG, WEBP, or GIF images are allowed.";
        }
    } elseif (!empty($_POST['remove_image']) && $edit_id > 0) {
        if (!empty($image) && file_exists(__DIR__ . '/../' . $image)) {
            @unlink(__DIR__ . '/../' . $image);
        }
        $image = null;
    }

    if (empty($product_name))              $errors[] = "Product name is required.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Enter a valid price.";
    if (!is_numeric($stock) || $stock < 0) $errors[] = "Enter a valid stock quantity.";

    if (empty($errors)) {
        $cat = $category_id ?: null;
        $brd = $brand_id ?: null;

        if ($edit_id > 0) {
            $old       = mysqli_fetch_assoc(
                mysqli_query($conn, "SELECT stock FROM products WHERE id = $edit_id")
            );
            $old_stock = (int)$old['stock'];

            $stmt = mysqli_prepare($conn,
                "UPDATE products
                 SET product_name=?, description=?, price=?, discount_price=?, stock=?, size=?, color=?, image=?, category_id=?, brand_id=?
                 WHERE id=?"
            );
            mysqli_stmt_bind_param($stmt, "ssddissisii",
                $product_name, $description, $price, $discount_price, $stock, $size, $color, $image, $cat, $brd, $edit_id
            );

        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO products (product_name, description, price, discount_price, stock, size, color, image, category_id, brand_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ssddisssii",
                $product_name, $description, $price, $discount_price, $stock, $size, $color, $image, $cat, $brd
            );
        }

        if (mysqli_stmt_execute($stmt)) {

            if ($edit_id === 0) {
                $new_product_id = mysqli_insert_id($conn);
                $log_reason     = "Initial stock on product creation";
                $changed_by     = $_SESSION['username'];
                $stock_int      = (int)$stock;

                $log_stmt = mysqli_prepare($conn,
                    "INSERT INTO stock_log (product_id, change_amount, reason, changed_by)
                     VALUES (?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($log_stmt, "iiss",
                    $new_product_id, $stock_int, $log_reason, $changed_by
                );
                mysqli_stmt_execute($log_stmt);

            } else {
                $new_stock  = (int)$stock;
                $stock_diff = $new_stock - $old_stock;

                if ($stock_diff !== 0) {
                    $log_reason = "Stock updated via product edit (was $old_stock, now $new_stock)";
                    $changed_by = $_SESSION['username'];

                    $log_stmt = mysqli_prepare($conn,
                        "INSERT INTO stock_log (product_id, change_amount, reason, changed_by)
                         VALUES (?, ?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($log_stmt, "iiss",
                        $edit_id, $stock_diff, $log_reason, $changed_by
                    );
                    mysqli_stmt_execute($log_stmt);
                }
            }

            $_SESSION['success'] = $edit_id > 0
                ? "Product updated successfully!"
                : "Product added successfully!";
            header("Location: products.php");
            exit();

        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }

    $product = [
        'id'             => $_POST['edit_id'] ?? '',
        'product_name'   => $product_name,
        'description'    => $description,
        'price'          => $price,
        'discount_price' => $discount_price ?? '',
        'stock'          => $stock,
        'size'           => $size,
        'color'          => $color,
        'image'          => $image,
        'category_id'    => $category_id,
        'brand_id'       => $brand_id,
    ];
    $is_edit = !empty($product['id']);
}

$categories_result = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
$brands_result = mysqli_query($conn, "SELECT id, name FROM brands ORDER BY name ASC");

$current_page = 'products';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Add' ?> Product - Shoe Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style> body { background-color: #f0f2f5; } </style>
</head>
<body>

<div class="container-fluid">
    <div class="row flex-nowrap">

        <?php require_once 'includes/sidebar.php'; ?>

        <div class="col py-4 px-4">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-<?= $is_edit ? 'pencil-square' : 'plus-circle' ?> me-2 text-success"></i>
                    <?= $is_edit ? 'Edit Product' : 'Add New Product' ?>
                </h4>
                <a href="products.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Products
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-3" style="max-width: 800px;">
                <div class="card-body p-4">
                    <form method="POST" action="add_product.php" enctype="multipart/form-data">

                        <input type="hidden" name="edit_id" value="<?= htmlspecialchars($product['id']) ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Product Image</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif">
                            <?php if (!empty($product['image'])): ?>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <div style="width:60px;height:60px;border-radius:8px;overflow:hidden;">
                                        <img src="../<?= htmlspecialchars($product['image']) ?>" alt="Current image" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <div class="form-check mb-0">
                                        <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="removeImage">
                                        <label class="form-check-label small" for="removeImage">Remove current image</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-1">Allowed: JPG, PNG, WEBP, GIF. Max ~2MB.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Product Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="product_name" class="form-control"
                                   value="<?= htmlspecialchars($product['product_name']) ?>"
                                   placeholder="e.g. Nike Air Max 270" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Short product description..."
                            ><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Price ($) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="price" class="form-control"
                                       step="0.01" min="0"
                                       value="<?= htmlspecialchars($product['price']) ?>"
                                       placeholder="0.00" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Discount Price ($)
                                </label>
                                <input type="number" name="discount_price" class="form-control"
                                       step="0.01" min="0"
                                       value="<?= htmlspecialchars($product['discount_price']) ?>"
                                       placeholder="Leave empty if none">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Stock <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="stock" class="form-control" min="0"
                                       value="<?= htmlspecialchars($product['stock']) ?>"
                                       placeholder="0" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Size</label>
                                <input type="text" name="size" class="form-control"
                                       value="<?= htmlspecialchars($product['size']) ?>"
                                       placeholder="e.g. 8-12">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Color</label>
                                <input type="text" name="color" class="form-control"
                                       value="<?= htmlspecialchars($product['color']) ?>"
                                       placeholder="e.g. Black">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">-- Select Category --</option>
                                    <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                        <option value="<?= $cat['id'] ?>"
                                            <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Brand</label>
                                <select name="brand_id" class="form-select">
                                    <option value="">-- Select Brand --</option>
                                    <?php while ($br = mysqli_fetch_assoc($brands_result)): ?>
                                        <option value="<?= $br['id'] ?>"
                                            <?= ($product['brand_id'] == $br['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($br['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-check-circle me-1"></i>
                                <?= $is_edit ? 'Update Product' : 'Add Product' ?>
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.replace('../admin/login.php');
        }
    });
</script>
</body>
</html>
