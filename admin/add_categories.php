<?php
session_start();
$page_title = 'Add/Edit Category';
$current_page = 'categories';
require_once 'includes/header.php';

$errors = [];
$is_edit = false;
$category = ['id' => '', 'name' => '', 'description' => ''];

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM categories WHERE id = $id");
    if ($row = mysqli_fetch_assoc($res)) {
        $is_edit = true;
        $category = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cat_id = $_POST['category_id'] ?? '';

    if (empty($name)) {
        $errors[] = "Category name is required.";
    }

    if (empty($errors)) {
        if (!empty($cat_id)) {
            $stmt = mysqli_prepare($conn, "UPDATE categories SET name = ?, description = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $cat_id);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO categories (name, description) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $name, $description);
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Category saved successfully!";
            header("Location: categories.php");
            exit();
        } else {
            $errors[] = "Error saving category. It might already exist.";
        }
    }
}
?>

<h2><?= $is_edit ? 'Edit Category' : 'Add New Category' ?></h2>
<p><a href="categories.php">&laquo; Back to Categories</a></p>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<div class="form-box">
    <form method="POST" action="add_categories.php">
        <input type="hidden" name="category_id" value="<?= $category['id'] ?>">

        <div class="form-group">
            <label>Category Name *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($is_edit ? $category['name'] : ($_POST['name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="2"><?= htmlspecialchars($is_edit ? $category['description'] : ($_POST['description'] ?? '')) ?></textarea>
        </div>

        <button type="submit" class="btn"><?= $is_edit ? 'Update Category' : 'Add Category' ?></button>
        <?php if ($is_edit): ?>
            <a href="categories.php" class="btn btn-gray">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>