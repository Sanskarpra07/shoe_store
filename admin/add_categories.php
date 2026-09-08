<?php
session_start();
$page_title = 'Add/Edit Category';
$current_page = 'categories';
require_once 'includes/header.php';

$errors = [];
$is_edit = false;
$category = ['id' => '', 'name' => '', 'description' => '', 'icon' => ''];

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
    $icon = trim($_POST['icon'] ?? '');
    $cat_id = $_POST['category_id'] ?? '';

    if (strlen($icon) > 100) {
        $icon = substr($icon, 0, 100);
    }
    if (!empty($icon) && !preg_match('/^[a-zA-Z0-9\s\-]+$/', $icon)) {
        $icon = '';
    }

    if (empty($name)) {
        $errors[] = "Category name is required.";
    }

    if (empty($errors)) {
        if (!empty($cat_id)) {
            $stmt = mysqli_prepare($conn, "UPDATE categories SET name = ?, description = ?, icon = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $icon, $cat_id);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $description, $icon);
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

$selected_icon = $is_edit ? ($category['icon'] ?? '') : ($_POST['icon'] ?? '');
?>

<div class="page-header">
    <div>
        <h2><?= $is_edit ? 'Edit Category' : 'Add New Category' ?></h2>
        <p class="page-sub"><?= $is_edit ? 'Update this category' : 'Create a new category' ?></p>
    </div>
    <a class="btn btn-gray" href="categories.php"><i class="bi bi-arrow-left"></i> Back to Categories</a>
</div>

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

        <div class="form-group">
            <label>Icon Code (Font Awesome)</label>
            <input type="text" name="icon" id="icon-input" value="<?= htmlspecialchars($selected_icon ?? '') ?>"
                   placeholder="e.g. fa-solid fa-running" oninput="previewIcon()">
            <p class="small text-muted" style="margin:6px 0 0;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Pick an icon from
                <a href="https://fontawesome.com/icons" target="_blank" rel="noopener">Font Awesome</a>,
                copy the icon code, and paste it into this field.
            </p>
            <div style="margin-top:10px; display:flex; align-items:center; gap:12px;">
                <i id="icon-preview" class="<?= !empty($selected_icon) ? htmlspecialchars($selected_icon) : 'fa-solid fa-icons' ?> fs-3" style="color:#f5470d;"></i>
                <span class="small text-muted">Live preview</span>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:8px;">
            <button type="submit" class="btn" style="flex:1;"><i class="bi bi-check-lg"></i> <?= $is_edit ? 'Update Category' : 'Add Category' ?></button>
            <?php if ($is_edit): ?>
                <a href="categories.php" class="btn btn-gray"><i class="bi bi-x-lg"></i> Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function previewIcon() {
    var input = document.getElementById('icon-input');
    var p = document.getElementById('icon-preview');
    p.className = (input.value.trim() || 'fa-solid fa-icons') + ' fs-3';
}
</script>

<?php require_once 'includes/footer.php'; ?>