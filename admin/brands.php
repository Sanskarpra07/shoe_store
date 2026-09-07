<?php
session_start();
$page_title = 'Brands';
$current_page = 'brands';
require_once 'includes/header.php';

$errors = [];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = "Brand name is required.";
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO brands (name, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $name, $description);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Brand '$name' added successfully!";
            header("Location: brands.php");
            exit();
        } else {
            $errors[] = "Could not add brand. The name might already exist.";
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

<h2>Brands</h2>
<p><a class="btn" href="brands.php">+ Add Brand</a></p>

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

<div style="float:left; width:260px; margin-right:20px;">
    <div class="form-box" style="width:100%;">
        <h3>Add New Brand</h3>
        <form method="POST" action="brands.php">
            <div class="form-group">
                <label>Brand Name *</label>
                <input type="text" name="name" placeholder="e.g. Nike" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="About this brand..."></textarea>
            </div>
            <button type="submit" class="btn">Add Brand</button>
        </form>
    </div>
</div>

<div style="float:left; width:720px;">
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
            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
            <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
            <td class="center"><?= $row['product_count'] ?></td>
            <td class="center">
                <form method="POST" action="brands.php" onsubmit="return confirm('Delete this brand?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-red btn-small">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<div style="clear:both;"></div>

<?php require_once 'includes/footer.php'; ?>