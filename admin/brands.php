<?php
session_start();
$page_title = 'Brands';
$current_page = 'brands';
require_once 'includes/header.php';

$errors = [];
$is_edit = false;
$brand = ['id' => '', 'name' => '', 'description' => '', 'icon' => ''];

if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $check = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE brand_id = $id")
    );
    if ($check['c'] > 0) {
        $_SESSION['error'] = "Cannot delete: {$check['c']} product(s) use this brand. Reassign them first.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM brands WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['success'] = "Brand deleted.";
    }
    header("Location: brands.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM brands WHERE id = $id");
    if ($row = mysqli_fetch_assoc($res)) {
        $is_edit = true;
        $brand = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand_id    = $_POST['brand_id'] ?? '';
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon'] ?? '');
    $is_edit     = !empty($brand_id);
    $brand       = ['id' => $brand_id, 'name' => $name, 'description' => $description, 'icon' => $icon];

    if (strlen($icon) > 100) {
        $icon = substr($icon, 0, 100);
    }
    if (!empty($icon) && !preg_match('/^[a-zA-Z0-9\s\-]+$/', $icon)) {
        $icon = '';
    }

    if (empty($name)) {
        $errors[] = "Brand name is required.";
    }

    if (empty($errors)) {
        if ($is_edit) {
            $stmt = mysqli_prepare($conn, "UPDATE brands SET name = ?, description = ?, icon = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $icon, $brand_id);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO brands (name, description, icon) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $description, $icon);
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = $is_edit
                ? "Brand '$name' updated successfully!"
                : "Brand '$name' added successfully!";
            header("Location: brands.php");
            exit();
        } else {
            $errors[] = $is_edit
                ? "Could not update brand. The name might already exist."
                : "Could not add brand. The name might already exist.";
        }
    }
}

$brands = mysqli_query($conn,
    "SELECT b.*, COUNT(p.id) AS product_count
     FROM brands b
     LEFT JOIN products p ON p.brand_id = b.id
     GROUP BY b.id
     ORDER BY b.created_at DESC"
);

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="page-header">
    <div>
        <h2>Brands</h2>
        <p class="page-sub">Manage the brands in your catalog</p>
    </div>
    <a class="btn btn-green" href="brands.php"><i class="bi bi-plus-lg"></i> Add Brand</a>
</div>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<div class="split">
    <div class="form-box" id="brand-form">
        <h3><?= $is_edit ? 'Edit Brand' : 'Add New Brand' ?></h3>
        <form method="POST" action="brands.php">
            <input type="hidden" name="brand_id" value="<?= (int)$brand['id'] ?>">
            <div class="form-group">
                <label>Brand Name *</label>
                <input type="text" name="name" placeholder="e.g. Nike" required value="<?= htmlspecialchars($brand['name']) ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="About this brand..."><?= htmlspecialchars($brand['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Icon Code (Font Awesome)</label>
                <input type="text" name="icon" id="icon-input" placeholder="e.g. fa-solid fa-bolt" value="<?= htmlspecialchars($brand['icon']) ?>" oninput="previewIcon()">
                <p class="small text-muted" style="margin:6px 0 0;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Pick an icon from
                    <a href="https://fontawesome.com/icons" target="_blank" rel="noopener">Font Awesome</a>,
                    copy the icon code, and paste it into this field.
                </p>
                <div style="margin-top:10px; display:flex; align-items:center; gap:12px;">
                    <i id="icon-preview" class="<?= !empty($brand['icon']) ? htmlspecialchars($brand['icon']) : 'fa-solid fa-icons' ?> fs-3" style="color:#f5470d;"></i>
                    <span class="small text-muted">Live preview</span>
                </div>
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn" style="flex:1;"><i class="bi <?= $is_edit ? 'bi-check-lg' : 'bi-plus-lg' ?>"></i> <?= $is_edit ? 'Update Brand' : 'Add Brand' ?></button>
                <?php if ($is_edit): ?>
                    <a href="brands.php" class="btn btn-gray"><i class="bi bi-x-lg"></i> Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div>
        <table class="table">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Description</th>
                <th>Products</th>
                <th>Action</th>
            </tr>
            <?php $sno = 1; while ($row = mysqli_fetch_assoc($brands)): ?>
            <tr>
                <td class="center"><?= $sno++ ?></td>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <?php if (!empty($row['icon'])): ?>
                            <i class="<?= htmlspecialchars($row['icon']) ?>" style="font-size:18px; color:#f5470d;"></i>
                        <?php else: ?>
                            <i class="bi bi-image text-muted" style="font-size:18px;"></i>
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($row['name']) ?></strong>
                    </div>
                </td>
                <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                <td class="center"><span class="badge badge-secondary"><?= $row['product_count'] ?></span></td>
                <td class="center">
                    <div style="display:flex; gap:6px; justify-content:center;">
                        <a class="btn btn-small" href="brands.php?action=edit&id=<?= $row['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                        <form method="POST" action="brands.php" onsubmit="return confirm('Delete this brand?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-red btn-small"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($brands) === 0): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i>No brands added yet.</div></td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function previewIcon() {
    var input = document.getElementById('icon-input');
    var p = document.getElementById('icon-preview');
    p.className = (input.value.trim() || 'fa-solid fa-icons') + ' fs-3';
}
</script>

<?php if ($is_edit): ?>
<script>
document.getElementById('brand-form').scrollIntoView({ behavior: 'smooth' });
document.getElementById('icon-input').focus();
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>