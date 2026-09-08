<?php
session_start();
$page_title = 'Add/Edit Product';
$current_page = 'products';
require_once 'includes/header.php';

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
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$product) {
        $_SESSION['success'] = "Product not found.";
        header("Location: products.php");
        exit();
    }
    $is_edit = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name   = trim($_POST['product_name'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $price          = $_POST['price'] ?? '';
    $discount_price = !empty($_POST['discount_price']) ? $_POST['discount_price'] : null;
    $stock          = $_POST['stock'] ?? '';
    $size           = trim($_POST['size'] ?? '');
    $color          = trim($_POST['color'] ?? '');
    $category_id    = $_POST['category_id'] ?? null;
    $brand_id       = $_POST['brand_id'] ?? null;
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
            $old       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock FROM products WHERE id = $edit_id"));
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
                    "INSERT INTO stock_log (product_id, change_amount, reason, changed_by) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($log_stmt, "iiss", $new_product_id, $stock_int, $log_reason, $changed_by);
                mysqli_stmt_execute($log_stmt);
            } else {
                $new_stock  = (int)$stock;
                $stock_diff = $new_stock - $old_stock;

                if ($stock_diff !== 0) {
                    $log_reason = "Stock updated via product edit (was $old_stock, now $new_stock)";
                    $changed_by = $_SESSION['username'];

                    $log_stmt = mysqli_prepare($conn,
                        "INSERT INTO stock_log (product_id, change_amount, reason, changed_by) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($log_stmt, "iiss", $edit_id, $stock_diff, $log_reason, $changed_by);
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
?>

<div class="page-header">
    <div>
        <h2><?= $is_edit ? 'Edit Product' : 'Add New Product' ?></h2>
        <p class="page-sub"><?= $is_edit ? 'Update the product details' : 'Create a new product listing' ?></p>
    </div>
    <a class="btn btn-gray" href="products.php"><i class="bi bi-arrow-left"></i> Back to Products</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<div class="form-box" style="max-width:700px;">
    <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" value="<?= htmlspecialchars($product['id']) ?>">

        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.gif">
            <?php if (!empty($product['image'])): ?>
                <div style="margin-top:10px; display:flex; align-items:center; gap:12px;">
                    <img src="../<?= htmlspecialchars($product['image']) ?>" alt="" class="thumb">
                    <label style="font-weight:normal; font-size:12px;">
                        <input type="checkbox" name="remove_image" value="1"> Remove current image
                    </label>
                </div>
            <?php endif; ?>
            <span class="small text-muted">Allowed: JPG, PNG, WEBP, GIF.</span>
        </div>

        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name" required value="<?= htmlspecialchars($product['product_name']) ?>" placeholder="e.g. Nike Air Max 270">
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Short product description..."><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Price ($) *</label>
            <input type="number" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($product['price']) ?>" placeholder="0.00">
        </div>

        <div class="form-group">
            <label>Discount Price ($) (leave empty if none)</label>
            <input type="number" name="discount_price" step="0.01" min="0" value="<?= htmlspecialchars($product['discount_price']) ?>">
        </div>

        <div class="form-group">
            <label>Stock *</label>
            <input type="number" name="stock" min="0" required value="<?= htmlspecialchars($product['stock']) ?>">
        </div>

        <div class="form-group">
            <label>Size</label>
            <input type="text" name="size" value="<?= htmlspecialchars($product['size']) ?>" placeholder="e.g. 8-12">
        </div>

        <div class="form-group">
            <label>Color</label>
            <input type="text" name="color" value="<?= htmlspecialchars($product['color']) ?>" placeholder="e.g. Black">
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category_id">
                <option value="">-- Select Category --</option>
                <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Brand</label>
            <select name="brand_id">
                <option value="">-- Select Brand --</option>
                <?php while ($br = mysqli_fetch_assoc($brands_result)): ?>
                    <option value="<?= $br['id'] ?>" <?= ($product['brand_id'] == $br['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($br['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div style="display:flex; gap:10px; margin-top:8px;">
            <button type="submit" class="btn btn-green" style="flex:1;"><i class="bi bi-check-lg"></i> <?= $is_edit ? 'Update Product' : 'Add Product' ?></button>
            <a href="products.php" class="btn btn-gray"><i class="bi bi-x-lg"></i> Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>