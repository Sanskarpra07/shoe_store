<?php
session_start();
$page_title = 'Dashboard';
$current_page = 'dashboard';
require_once 'includes/header.php';

$total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products"))['c'];
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM categories"))['c'];
$total_brands   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM brands"))['c'];
$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];
$total_customers= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM customers"))['c'];
$low_stock      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE stock < 10"))['c'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='pending'"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) AS r FROM orders WHERE payment_status='completed'"))['r'];

$recent = mysqli_query($conn,
    "SELECT p.product_name, p.price, p.stock, c.name AS category, b.name AS brand
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
?>

<h2>Dashboard</h2>
<p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!</p>

<h3 class="section-title">Statistics</h3>
<table class="table">
    <tr>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <td class="center" style="width:16%;"><strong><?= $total_users ?></strong><br>Total Users<br><a href="users.php">&raquo;</a></td>
        <?php endif; ?>
        <td class="center" style="width:16%;"><strong><?= $total_products ?></strong><br>Total Products<br><a href="products.php">&raquo;</a></td>
        <td class="center" style="width:16%;"><strong><?= $total_categories ?></strong><br>Categories<br><a href="categories.php">&raquo;</a></td>
        <td class="center" style="width:16%;"><strong><?= $total_brands ?></strong><br>Brands<br><a href="brands.php">&raquo;</a></td>
    </tr>
    <tr>
        <td class="center" style="width:16%;"><strong><?= $total_orders ?></strong><br>Total Orders<br><a href="orders.php">&raquo;</a></td>
        <td class="center" style="width:16%;"><strong><?= $total_customers ?></strong><br>Registered Customers</td>
        <td class="center" style="width:16%;"><strong><?= $low_stock ?></strong><br>Low Stock Items<br><a href="stock_log.php">&raquo;</a></td>
        <td class="center" style="width:16%;"><strong><?= $pending_orders ?></strong><br>Pending Orders<br><a href="orders.php">&raquo;</a></td>
    </tr>
</table>

<h3 class="section-title">Revenue (Paid Orders): <span style="color:#2e7d32;">$<?= number_format($total_revenue, 2) ?></span></h3>

<h3 class="section-title">Recently Added Products</h3>
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
        <td><?= htmlspecialchars($row['product_name']) ?></td>
        <td class="center"><?= htmlspecialchars($row['brand'] ?? 'N/A') ?></td>
        <td class="center"><?= htmlspecialchars($row['category'] ?? 'Uncategorized') ?></td>
        <td class="center">$<?= number_format($row['price'], 2) ?></td>
        <td class="center">
            <?php if ($row['stock'] < 10): ?>
                <span style="color:#c62828;"><strong><?= $row['stock'] ?> (Low)</strong></span>
            <?php else: ?>
                <span style="color:#2e7d32;"><strong><?= $row['stock'] ?></strong></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
<h3 class="section-title">Recent Orders</h3>
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
        <td class="center">$<?= number_format($o['total_amount'], 2) ?></td>
        <td class="center"><?= ucfirst($o['status']) ?></td>
        <td class="center"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
</table>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>