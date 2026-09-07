<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: ../admin/login.php");
    exit();
}

require_once '../db.php';

$errors = [];

if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    if (!csrf_check()) { die('Invalid request'); }
    $id = (int) $_POST['id'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { die('Invalid request'); }
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = "Category name is required.";
    }

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

$categories = mysqli_query($conn, "
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

$current_page = 'categories';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Shoe Store Admin</title>
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
                <i class="bi bi-tags me-2 text-info"></i>Categories
            </h4>
                <a href="add_categories.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Category
                </a>
        </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th class="text-center">Products</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sno = 1;
                                while ($row = mysqli_fetch_assoc($categories)):
                                ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $sno++ ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill"><?= $row['product_count'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="add_categories.php?action=edit&id=<?= $row['id'] ?>"
                                               class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form method="POST" action="categories.php" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
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
