<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../admin/login.php");
    exit();
}

require_once '../db.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'cancelled' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $_SESSION['success'] = "Order cancelled.";
    header("Location: orders.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id     = (int) $_GET['id'];
    $status = $_GET['status'];
    $valid  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($status, $valid)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['success'] = "Order #$id status updated to $status.";
    }
    header("Location: orders.php");
    exit();
}

$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM orders WHERE customer_name LIKE ? OR customer_email LIKE ? OR id LIKE ? ORDER BY created_at DESC"
    );
    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);
} else {
    $orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");
}

$current_page = 'orders';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Shoe Store Admin</title>
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
                <h4 class="fw-bold mb-0"><i class="bi bi-cart me-2 text-danger"></i>Orders</h4>
            </div>

            <form method="GET" action="orders.php" class="mb-4">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" name="search" class="form-control"
                        placeholder="Search by customer name, email or order #..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                    <?php if (!empty($_GET['search'])): ?>
                        <a href="orders.php" class="btn btn-outline-danger"><i class="bi bi-x"></i></a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $sno = 1; while ($row = mysqli_fetch_assoc($orders)): ?>
                        <tr>
                            <td class="ps-4 fw-semibold">#<?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td class="small"><?= htmlspecialchars($row['customer_email']) ?></td>
                            <td>$<?= number_format($row['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge order-status-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        Status
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?= $row['id'] ?>&status=pending">Pending</a></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?= $row['id'] ?>&status=processing">Processing</a></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?= $row['id'] ?>&status=shipped">Shipped</a></li>
                                        <li><a class="dropdown-item" href="orders.php?action=update_status&id=<?= $row['id'] ?>&status=delivered">Delivered</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="orders.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Cancel this order?')">Cancel</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($orders) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
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
