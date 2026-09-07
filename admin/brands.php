<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: ../admin/login.php");
    exit();
}

require_once '../db.php';

$errors = [];

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
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

$brands = mysqli_query($conn, "
    SELECT b.*, COUNT(p.id) AS product_count
    FROM brands b
    LEFT JOIN products p ON p.brand_id = b.id
    GROUP BY b.id
    ORDER BY b.created_at DESC
");

$current_page = 'brands';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands - Shoe Store Admin</title>
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
                <i class="bi bi-award me-2 text-secondary"></i>Brands
            </h4>
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

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-white fw-semibold py-3">
                            <i class="bi bi-plus-circle me-2 text-secondary"></i>Add New Brand
                        </div>
                        <div class="card-body">
                            <form method="POST" action="brands.php">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Brand Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Nike" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Description</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="About this brand..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-secondary w-100 text-white">
                                    <i class="bi bi-plus-circle me-1"></i>Add Brand
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
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
                                while ($row = mysqli_fetch_assoc($brands)):
                                ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $sno++ ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill"><?= $row['product_count'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="brands.php?action=delete&id=<?= $row['id'] ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this brand?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
