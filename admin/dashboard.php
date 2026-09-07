<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username'])) {
    header("Location: ../admin/login.php");
    exit();
}
    require_once '../db.php';

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products"))['c'];
$total_category = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM categories"))['c'];
$total_brands = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM brands"))['c'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];
$low_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE stock<10"))['c'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='pending'"))['c'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) AS r FROM orders WHERE payment_status='completed'"))['r'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM customers"))['c'];

$recent = mysqli_query($conn, 
  "SELECT p.product_name, p.price, p.stock, c.name As category, b.name AS brand
  FROM products p
  LEFT JOIN categories c ON p.category_id = c.id
  LEFT JOIN brands b ON p.brand_id = b.id
  ORDER BY p.created_at DESC
  LIMIT 5"
);

$recent_orders = mysqli_query($conn,
  "SELECT o.id, o.customer_name, o.total_amount, o.status, o.created_at
   FROM orders o
   ORDER BY o.created_at DESC
   LIMIT 5"
);

$current_page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .stat-card { border: none; border-radius: 12px; transition: transform .2s; }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-icon { font-size: 2.5rem; opacity: .85; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
         <?php require_once 'includes/sidebar.php'; ?>

        <div class="col py-4 px-4">

         <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                </h4>
        <span class="text-muted small">Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            </div>
        
        <div class="row g-4 mb-4">

        <div class="col-sm-6 col-xl-3">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="card stat-card bg-primary text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $total_users ?></div>
                        <div class="small">Total Users</div>
                    </div>
                    <i class="bi bi-people stat-icon"></i>
                </div>
                <a href="users.php" class="text-white-50 small mt-2 d-block text-decoration-none">View all &rarr;</a>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-success text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $total_products ?></div>
                        <div class="small">Total Products</div>
                    </div>
                    <i class="bi bi-box-seam stat-icon"></i>
                </div>
                <a href="products.php" class="text-white-50 small mt-2 d-block text-decoration-none">View all &rarr;</a>
            </div>
        </div>
        
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-info text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $total_category ?></div>
                        <div class="small">Categories</div>
                    </div>
                    <i class="bi bi-tags stat-icon"></i>
                </div>
                <a href="categories.php" class="text-white-50 small mt-2 d-block text-decoration-none">View all &rarr;</a>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-warning text-dark shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $low_stock ?></div>
                        <div class="small">Low Stock Items</div>
                    </div>
                    <i class="bi bi-exclamation-triangle stat-icon"></i>
                </div>
                <span class="text-dark-50 small mt-2 d-block">Stock below 10 units</span>
            </div>
         </div>

    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-secondary text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $total_brands ?></div>
                        <div class="small">Brands</div>
                    </div>
                    <i class="bi bi-award stat-icon"></i>
                </div>
                <a href="brands.php" class="text-white-50 small mt-2 d-block text-decoration-none">View all &rarr;</a>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-danger text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $total_orders ?></div>
                        <div class="small">Total Orders</div>
                    </div>
                    <i class="bi bi-cart stat-icon"></i>
                </div>
                <a href="orders.php" class="text-white-50 small mt-2 d-block text-decoration-none">View all &rarr;</a>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-success text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold">$<?= number_format($total_revenue, 0) ?></div>
                        <div class="small">Revenue (Paid)</div>
                    </div>
                    <i class="bi bi-graph-up-arrow stat-icon"></i>
                </div>
                <a href="reports.php" class="text-white-50 small mt-2 d-block text-decoration-none">View reports &rarr;</a>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-dark text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $pending_orders ?></div>
                        <div class="small">Pending Orders</div>
                    </div>
                    <i class="bi bi-hourglass-split stat-icon"></i>
                </div>
                <a href="orders.php" class="text-white-50 small mt-2 d-block text-decoration-none">View all &rarr;</a>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card bg-info text-white shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-2 fw-bold"><?= $total_customers ?></div>
                        <div class="small">Registered Customers</div>
                    </div>
                    <i class="bi bi-people stat-icon"></i>
                </div>
                <a href="reports.php" class="text-white-50 small mt-2 d-block text-decoration-none">View all &rarr;</a>
            </div>
        </div>
    </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-secondary"></i>Recently Added Products</h6>
                    <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($row = mysqli_fetch_assoc($recent)): ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><span class="badge bg-dark"><?= htmlspecialchars($row['brand'] ?? 'N/A') ?></span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['category'] ?? 'Uncategorized') ?></span></td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php if ($row['stock'] < 10): ?>
                                        <span class="badge bg-danger"><?= $row['stock'] ?> (Low)</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= $row['stock'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
            <div class="card shadow-sm border-0 rounded-3 mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-cart-check me-2 text-danger"></i>Recent Orders</h6>
                    <a href="orders.php" class="btn btn-sm btn-outline-danger">View All</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($o = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td class="ps-4">#<?= $o['id'] ?></td>
                                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                                <td>$<?= number_format($o['total_amount'], 2) ?></td>
                                <td><span class="badge order-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

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
