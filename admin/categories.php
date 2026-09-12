<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN CATEGORIES
 * --------------------------------------------------------------------------
 * Lists all product categories with their product counts and handles
 * category deletion (blocked while products still use the category).
 * Adding/editing a category is done on add_categories.php.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Categories';
$current_page = 'categories';
require_once 'includes/header.php';

$errors = [];

// --- Handle category deletion (POST) ----------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $check = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE category_id = $id")
    );
    if ($check['c'] > 0) {
        $_SESSION['error'] = "Cannot delete: {$check['c']} product(s) use this category. Reassign them first.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['success'] = "Category deleted.";
    }
    header("Location: categories.php");
    exit();
}

// --- Add category via POST (legacy inline form handler) ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = "Category name is required.";
    }

    // Insert the category when validation passes.
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO categories (name, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $name, $description);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Category '$name' added successfully!";
            header("Location: categories.php");
            exit();
        } else {
            $errors[] = "Could not add category. The name might already exist.";
        }
    }
}

// --- Fetch all categories with their product counts --------------------------------
$categories = mysqli_query($conn,
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.created_at DESC"
);

// --- Flash messages -----------------------------------------------------------------
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Categories</h2>
        <p class="page-sub">Organize your products into categories</p>
    </div>
    <a class="btn btn-green" href="add_categories.php"><i class="bi bi-plus-lg"></i> Add Category</a>
</div>

<!-- ======== CATEGORIES TABLE (panel card) ======== -->
<div class="panel-card">
    <div class="panel-header">
        <h3><i class="bi bi-tags"></i> All Categories <span class="text-muted small">(<?= mysqli_num_rows($categories) ?>)</span></h3>
    </div>
    <div class="panel-body">

        <!-- Flash messages -->
        <?php if ($success): ?>
            <div class="msg-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
        <table class="table">
    <tr>
        <th>#</th>
        <th>Name</th>
        <th>Description</th>
        <th>Products</th>
        <th>Action</th>
    </tr>
    <?php $sno = 1; while ($row = mysqli_fetch_assoc($categories)): ?>
    <tr>
        <td class="center"><?= $sno++ ?></td>
        <td>
            <div style="display:flex; align-items:center; gap:10px;">
                <?php if (!empty($row['icon'])): ?>
                    <i class="<?= htmlspecialchars($row['icon']) ?>" style="font-size:18px; color:#f5470d;"></i>
                <?php else: ?>
                    <i class="fa-solid fa-icons text-muted" style="font-size:18px;"></i>
                <?php endif; ?>
                <strong><?= htmlspecialchars($row['name']) ?></strong>
            </div>
        </td>
        <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
        <td class="center"><?= $row['product_count'] ?></td>
        <td class="center">
            <div style="display:flex; gap:6px; justify-content:center;">
                <a class="btn btn-small" href="add_categories.php?action=edit&id=<?= $row['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                <form method="POST" action="categories.php" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-red btn-small" data-confirm="Delete this category?"><i class="bi bi-trash"></i> Delete</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>