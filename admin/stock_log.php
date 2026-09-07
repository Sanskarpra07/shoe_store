<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../admin/login.php");
    exit();
}

require_once '../db.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id    = (int)($_POST['product_id'] ?? 0);
    $change_amount = (int)($_POST['change_amount'] ?? 0);
    $type          = $_POST['type'] ?? 'add';
    $reason        = trim($_POST['reason'] ?? '');
    $changed_by    = $_SESSION['username'];

    if ($type === 'remove') {
        $change_amount = -abs($change_amount);
    } else {
        $change_amount = abs($change_amount);
    }

    if ($product_id === 0)   $errors[] = "Please select a product.";
    if ($change_amount === 0) $errors[] = "Please enter a valid amount.";

    if (empty($errors)) {
        $current = mysqli_fetch_assoc(
            mysqli_query($conn, "SELECT stock FROM products WHERE id = $product_id")
        );

        $new_stock = $current['stock'] + $change_amount;

        if ($new_stock < 0) {
            $errors[] = "Cannot remove more than current stock ({$current['stock']} units).";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE products SET stock = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $new_stock, $product_id);
            mysqli_stmt_execute($stmt);

            $stmt = mysqli_prepare($conn,
                "INSERT INTO stock_log (product_id, change_amount, reason, changed_by) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "iiss", $product_id, $change_amount, $reason, $changed_by);
            mysqli_stmt_execute($stmt);

            $success = "Stock updated successfully!";
        }
    }
}

$products = mysqli_query($conn, "SELECT id, product_name, stock FROM products ORDER BY product_name ASC");

$logs = mysqli_query($conn,
    "SELECT sl.*, p.product_name
     FROM stock_log sl
     JOIN products p ON sl.product_id = p.id
     ORDER BY sl.created_at DESC
     LIMIT 50"
);

$current_page = 'stock_log';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Adjustment - Shoe Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style> body { background-color: #f0f2f5; } </style>
</head>
<body>

<div class="container-fluid">
    <div class="row flex-nowrap">

        <?php require_once 'includes/sidebar.php'; ?>

        <div class="col py-4 px-4">

            <h4 class="fw-bold mb-4">
                <i class="bi bi-journal-text me-2 text-warning"></i>Stock Adjustment Log
            </h4>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-white fw-semibold py-3">
                            <i class="bi bi-plus-slash-minus me-2 text-warning"></i>Adjust Stock
                        </div>
                        <div class="card-body">

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger py-2 small">
                                    <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success py-2 small"><?= $success ?></div>
                            <?php endif; ?>

                            <form method="POST" action="stock_log.php">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Product <span class="text-danger">*</span></label>
                                    <select name="product_id" class="form-select" required>
                                        <option value="">-- Select product --</option>
                                        <?php while ($p = mysqli_fetch_assoc($products)): ?>
                                            <option value="<?= $p['id'] ?>">
                                                <?= htmlspecialchars($p['product_name']) ?> 
                                                (Stock: <?= $p['stock'] ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Type <span class="text-danger">*</span></label>
                                    <select name="type" class="form-select" required>
                                        <option value="add">+ Add Stock</option>
                                        <option value="remove">- Remove Stock</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="change_amount" class="form-control" 
                                           min="1" placeholder="e.g. 50" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold small">Reason</label>
                                    <input type="text" name="reason" class="form-control"
                                           placeholder="e.g. New shipment arrived">
                                </div>

                                <button type="submit" class="btn btn-warning w-100 fw-semibold">
                                    <i class="bi bi-check-circle me-1"></i>Apply Adjustment
                                </button>

                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-white fw-semibold py-3">
                            <i class="bi bi-clock-history me-2 text-secondary"></i>Recent Adjustments
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-4">Product</th>
                                        <th class="text-center">Change</th>
                                        <th>Reason</th>
                                        <th>By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold">
                                            <?= htmlspecialchars($log['product_name']) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($log['change_amount'] > 0): ?>
                                                <span class="badge bg-success">+<?= $log['change_amount'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?= $log['change_amount'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= htmlspecialchars($log['reason'] ?? '—') ?>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($log['changed_by']) ?></td>
                                        <td class="text-muted small">
                                            <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
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
