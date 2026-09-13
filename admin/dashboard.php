<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN DASHBOARD
 * --------------------------------------------------------------------------
 * Home page of the admin panel. Displays key business metrics as stat cards
 * (users, products, categories, brands, orders, customers, low stock,
 * pending orders, revenue) plus a 30-day revenue trend chart powered by
 * Chart.js and the five most recent products/orders in side-by-side panels.
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
    "SELECT p.product_name, p.price, p.stock, p.image, c.name AS category, b.name AS brand
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

// --- Revenue trend over the last 30 days -------------------------------------------
// Seeds every day of the window with zeros so days without sales still show
// up on the chart, then overwrites them with any recorded paid orders.
$trend_days = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $trend_days[$day] = ['revenue' => 0, 'orders' => 0];
}
$trend_result = mysqli_query($conn,
    "SELECT DATE(created_at) AS d, IFNULL(SUM(total_amount), 0) AS revenue, COUNT(*) AS orders
     FROM orders
     WHERE payment_status = 'completed'
       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(created_at)
     ORDER BY d ASC"
);
while ($t_row = mysqli_fetch_assoc($trend_result)) {
    if (isset($trend_days[$t_row['d']])) {
        $trend_days[$t_row['d']]['revenue'] = (float)$t_row['revenue'];
        $trend_days[$t_row['d']]['orders']  = (int)$t_row['orders'];
    }
}

// --- Build flat arrays consumed by the Chart.js line chart -------------------------
$chart_labels  = array_map(fn($k) => date('j M', strtotime($k)), array_keys($trend_days));
$chart_revenue = array_column($trend_days, 'revenue');
$chart_orders  = array_column($trend_days, 'orders');
$trend_total   = number_format(array_sum($chart_revenue), 2);
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

<!-- ======== REVENUE CARD + 30-DAY TREND CHART ======== -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon icon-green"><i class="bi bi-currency-dollar"></i></div>
        <div>
            <div class="stat-value">NPR <?= number_format($total_revenue, 2) ?></div>
            <div class="stat-label">Revenue (Paid Orders)</div>
        </div>
    </div>
</div>

<div class="panel-card chart-card">
    <div class="panel-header">
        <h3><i class="bi bi-graph-up"></i> Revenue Trend (Last 30 Days)</h3>
        <span class="badge badge-success">NPR <?= $trend_total ?> in 30 days</span>
    </div>
    <div class="panel-body">
        <div class="revenue-chart-wrap">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- ======== RECENT ACTIVITY PANELS ======== -->
<div class="dash-grid">

    <!-- Recently added products panel -->
    <div class="panel-card">
        <div class="panel-header">
            <h3><i class="bi bi-box-seam"></i> Recently Added Products</h3>
            <a href="products.php" class="small text-muted">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="panel-body">
            <div class="dash-list">
                <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                <div class="list-row">
                    <div class="list-thumb">
                        <?php if (!empty($row['image'])): ?>
                            <img src="../<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                        <?php else: ?>
                            <i class="bi bi-box-seam text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="list-main">
                        <div class="list-title"><?= htmlspecialchars($row['product_name']) ?></div>
                        <div class="list-meta"><?= htmlspecialchars($row['brand'] ?? 'N/A') ?> &middot; <?= htmlspecialchars($row['category'] ?? 'Uncategorized') ?></div>
                    </div>
                    <div class="list-side">
                        <div class="list-meta"><strong>NPR <?= number_format($row['price'], 2) ?></strong></div>
                        <?php if ($row['stock'] < 10): ?>
                            <span class="badge badge-danger"><?= $row['stock'] ?> &middot; Low</span>
                        <?php else: ?>
                            <span class="badge badge-success"><?= $row['stock'] ?> in stock</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Recent orders panel -->
    <?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
    <div class="panel-card">
        <div class="panel-header">
            <h3><i class="bi bi-bag-check"></i> Recent Orders</h3>
            <a href="orders.php" class="small text-muted">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="panel-body">
            <div class="dash-list">
                <?php while ($o = mysqli_fetch_assoc($recent_orders)): ?>
                <div class="list-row">
                    <div class="list-thumb">
                        <i class="bi bi-receipt text-muted" style="font-size:20px;"></i>
                    </div>
                    <div class="list-main">
                        <div class="list-title"><a href="orders.php?view=<?= $o['id'] ?>">Order #<?= $o['id'] ?></a></div>
                        <div class="list-meta"><?= htmlspecialchars($o['customer_name']) ?> &middot; <?= date('d M Y', strtotime($o['created_at'])) ?></div>
                    </div>
                    <div class="list-side">
                        <div class="list-meta"><strong>NPR <?= number_format($o['total_amount'], 2) ?></strong></div>
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
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="panel-card">
        <div class="panel-header">
            <h3><i class="bi bi-bag-check"></i> Recent Orders</h3>
        </div>
        <div class="panel-body">
            <p class="text-muted small">No orders recorded yet.</p>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ======== REVENUE CHART SCRIPT ======== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
// Chart.js line chart for the last 30 days: revenue (filled line) + order count.
document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('revenueChart');
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Revenue (NPR)',
                    data: <?= json_encode($chart_revenue) ?>,
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 5
                },
                {
                    label: 'Orders',
                    data: <?= json_encode($chart_orders) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.08)',
                    fill: false,
                    borderDash: [5, 4],
                    tension: 0.35,
                    pointRadius: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { boxWidth: 14, font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            if (ctx.datasetIndex === 0) {
                                return ' Revenue: NPR ' + Number(ctx.raw).toLocaleString('en-IN', { maximumFractionDigits: 0 });
                            }
                            return ' Orders: ' + ctx.raw;
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { maxTicksLimit: 10, font: { size: 10 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#eef2f7' } }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>