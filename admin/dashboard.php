<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN DASHBOARD
 * --------------------------------------------------------------------------
 * Home page of the admin panel. Displays key business metrics as stat cards
 * (users, products, categories, brands, orders, customers, low stock,
 * pending orders, revenue) plus the five most recent products and orders.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Dashboard';
$current_page = 'dashboard';
require_once 'includes/header.php';

// --- Fetch summary metrics -----------------------------------------------------
// Aggregated counts shown in the stat cards at the top of the page.
$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products"))['c'];
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM categories"))['c'];
$total_brands   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM brands"))['c'];
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];
$total_customers= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM customers"))['c'];
$low_stock      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE stock < 10"))['c'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='pending'"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) AS r FROM orders WHERE payment_status='completed'"))['r'];

// --- Fetch the 5 most recently added products ----------------------------------
$recent = mysqli_query($conn,
    "SELECT p.product_name, p.price, p.stock, c.name AS category, b.name AS brand
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     ORDER BY p.created_at DESC
     LIMIT 5"
);

// --- Fetch the 5 most recent orders ----------------------------------------------
$recent_orders = mysqli_query($conn,
    "SELECT o.id, o.customer_name, o.total_amount, o.status, o.created_at
     FROM orders o
     ORDER BY o.created_at DESC
     LIMIT 5"
);
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Dashboard</h2>
        <p class="page-sub">Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! Here's what's happening in your store.</p>
    </div>
</div>

<!-- ======== STAT CARDS GRID ======== -->
<div class="stat-grid">
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="stat-card">
            <div class="stat-icon icon-indigo"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-value"><?= $total_users ?></div>
                <div class="stat-label">Total Users <a href="users.php"><i class="bi bi-arrow-right-circle"></i></a></div>
            </div>
        </div>
    <?php endif; ?>
    <div class="stat-card">
        <div class="stat-icon icon-orange"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="stat-value"><?= $total_products ?></div>
            <div class="stat-label">Products <a href="products.php"><i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-sky"><i class="bi bi-tags"></i></div>
        <div>
            <div class="stat-value"><?= $total_categories ?></div>
            <div class="stat-label">Categories <a href="categories.php"><i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-pink"><i class="bi bi-buildings"></i></div>
        <div>
            <div class="stat-value"><?= $total_brands ?></div>
            <div class="stat-label">Brands <a href="brands.php"><i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green"><i class="bi bi-bag-check"></i></div>
        <div>
            <div class="stat-value"><?= $total_orders ?></div>
            <div class="stat-label">Total Orders <a href="orders.php"><i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-amber"><i class="bi bi-person-check"></i></div>
        <div>
            <div class="stat-value"><?= $total_customers ?></div>
            <div class="stat-label">Registered Customers</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-danger"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
            <div class="stat-value"><?= $low_stock ?></div>
            <div class="stat-label">Low Stock Items <a href="stock_log.php"><i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-warning"><i class="bi bi-hourglass-split"></i></div>
        <div>
            <div class="stat-value"><?= $pending_orders ?></div>
            <div class="stat-label">Pending Orders <a href="orders.php"><i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
</div>

<!-- ======== REVENUE CARD ======== -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon icon-green"><i class="bi bi-currency-dollar"></i></div>
        <div>
            <div class="stat-value">रु <?= number_format($total_revenue, 2) ?></div>
            <div class="stat-label">Revenue (Paid Orders)</div>
        </div>
    </div>
</div>

<!-- ======== RECENTLY ADDED PRODUCTS ======== -->
<h3 class="section-title">Recently Added Products</h3>
<div class="table-responsive">
<table class="table">
    <tr>
        <th>Product</th>
        <th>Brand</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
    <tr>
        <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
        <td class="center"><?= htmlspecialchars($row['brand'] ?? 'N/A') ?></td>
        <td class="center"><?= htmlspecialchars($row['category'] ?? 'Uncategorized') ?></td>
        <td class="center">रु <?= number_format($row['price'], 2) ?></td>
        <td class="center">
            <?php if ($row['stock'] < 10): ?>
                <span class="badge badge-danger"><?= $row['stock'] ?> &middot; Low</span>
            <?php else: ?>
                <span class="badge badge-success"><?= $row['stock'] ?></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

<!-- ======== RECENT ORDERS ======== -->
<?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
<h3 class="section-title">Recent Orders</h3>
<div class="table-responsive">
<table class="table">
    <tr>
        <th>Order #</th>
        <th>Customer</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
    </tr>
    <?php while ($o = mysqli_fetch_assoc($recent_orders)): ?>
    <tr>
        <td class="center"><a href="orders.php?view=<?= $o['id'] ?>"><strong>#<?= $o['id'] ?></strong></a></td>
        <td><?= htmlspecialchars($o['customer_name']) ?></td>
        <td class="center">रु <?= number_format($o['total_amount'], 2) ?></td>
        <td class="center">
            <?php
            // Map each order status to the correct badge color.
            $status_badge = [
                'pending'    => 'badge-warning',
                'processing' => 'badge-info',
                'shipped'    => 'badge-primary',
                'delivered'  => 'badge-success',
                'cancelled'  => 'badge-danger',
            ];
            ?>
            <span class="badge <?= $status_badge[$o['status']] ?? 'badge-secondary' ?>"><?= ucfirst($o['status']) ?></span>
        </td>
        <td class="center"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
</table>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>