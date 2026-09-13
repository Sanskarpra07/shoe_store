<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN ADD / EDIT PRODUCT
 * --------------------------------------------------------------------------
 * Both creates a new product and edits an existing one (when ?id= is set).
 * Handles image upload/removal, validation, insert/update, and records
 * stock changes in the stock_log table.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Add/Edit Product';
$current_page = 'products';
require_once 'includes/header.php';

// --- Default empty product + validation store -----------------------------------
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

// --- Load product into the form for editing --------------------------------------
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

// --- Handle form submission (create / update) -------------------------------------
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

    // --- Handle new image upload -----------------------------------------------
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

    // Basic field validation ----------------------------------------------------
    if (empty($product_name))              $errors[] = "Product name is required.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Enter a valid price.";
    if (!is_numeric($stock) || $stock < 0) $errors[] = "Enter a valid stock quantity.";

    // --- Save the product (update vs insert) + log the stock change ---------------
    if (empty($errors)) {
        $cat = $category_id ?: null;
        $brd = $brand_id ?: null;

        if ($edit_id > 0) {
            // -- Update mode: remember the old stock to calculate the diff --
            $old       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock FROM products WHERE id = $edit_id"));
            $old_stock = (int)$old['stock'];

            // Convert empty strings to null for nullable columns
            $cat = $category_id !== '' ? (int)$category_id : null;
            $brd = $brand_id !== '' ? (int)$brand_id : null;
            $dprice = $discount_price !== null ? (float)$discount_price : null;
            $img = !empty($image) ? $image : null;

            // Save the updated product.
            $stmt = mysqli_prepare($conn,
                "UPDATE products
                 SET product_name=?, description=?, price=?, discount_price=?, stock=?, size=?, color=?, image=?, category_id=?, brand_id=?
                 WHERE id=?"
            );
            mysqli_stmt_bind_param($stmt, "ssddisssiii",
                $product_name, $description, $price, $dprice, $stock, $size, $color, $img, $cat, $brd, $edit_id
            );
        } else {
            $cat = $category_id !== '' ? (int)$category_id : null;
            $brd = $brand_id !== '' ? (int)$brand_id : null;
            $dprice = $discount_price !== null ? (float)$discount_price : null;
            $img = !empty($image) ? $image : null;

            // -- Insert mode: create a brand-new product --
            $stmt = mysqli_prepare($conn,
                "INSERT INTO products (product_name, description, price, discount_price, stock, size, color, image, category_id, brand_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ssddisssii",
                $product_name, $description, $price, $dprice, $stock, $size, $color, $img, $cat, $brd
            );
        }

        // --- Record the stock change in stock_log -----------------------------------
        if (mysqli_stmt_execute($stmt)) {
            if ($edit_id === 0) {
                // New product: log the initial stock that was created with it.
                $new_product_id = mysqli_insert_id($conn);
                $log_reason     = "Initial stock on product creation";
                $changed_by     = $_SESSION['username'];
                $stock_int      = (int)$stock;

                $log_stmt = mysqli_prepare($conn,
                    "INSERT INTO stock_log (product_id, change_amount, reason, changed_by) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($log_stmt, "iiss", $new_product_id, $stock_int, $log_reason, $changed_by);
                mysqli_stmt_execute($log_stmt);
            } else {
                // Existing product: log the difference between old and new stock.
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
            $errors[] = "Failed to save product. Please try again.";
        }
    }

    // Repopulate the form with the submitted values so nothing is lost.
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

// --- Fetch category & brand options for the dropdowns ----------------------------
$categories_result = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
$brands_result = mysqli_query($conn, "SELECT id, name FROM brands ORDER BY name ASC");
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2><?= $is_edit ? 'Edit Product' : 'Add New Product' ?></h2>
        <p class="page-sub"><?= $is_edit ? 'Update the product details' : 'Create a new product listing' ?></p>
    </div>
    <a class="btn btn-gray" href="products.php"><i class="bi bi-arrow-left"></i> Back to Products</a>
</div>

<!-- Validation errors -->
<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<!-- ======== PRODUCT FORM ======== -->
<div class="form-box" style="max-width:700px;">
    <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" value="<?= htmlspecialchars($product['id']) ?>">

        <!-- Image upload + current image preview -->
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
            <label>Price (NPR) *</label>
            <input type="number" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($product['price']) ?>" placeholder="0.00">
        </div>

        <div class="form-group">
            <label>Discount Price (NPR) (leave empty if none)</label>
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

        <!-- Category + brand dropdowns -->
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

        <!-- Submit / cancel buttons -->
        <div style="display:flex; gap:10px; margin-top:8px;">
            <button type="submit" class="btn btn-green" style="flex:1;"><i class="bi bi-check-lg"></i> <?= $is_edit ? 'Update Product' : 'Add Product' ?></button>
            <a href="products.php" class="btn btn-gray"><i class="bi bi-x-lg"></i> Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>