<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$current_page = 'reports';

// Date range filter
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$from_s = date('Y-m-d 00:00:00', strtotime($from));
$to_s   = date('Y-m-d 23:59:59', strtotime($to));

$export = $_GET['export'] ?? '';

function row_count($conn, $sql, $from_s, $to_s) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
}

// Metrics within range
$range_orders   = row_count($conn, "SELECT COUNT(*) AS c FROM orders WHERE created_at BETWEEN ? AND ?", $from_s, $to_s);
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(total_amount),0) AS r FROM orders WHERE created_at BETWEEN ? AND ? AND payment_status='completed'");
mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
mysqli_stmt_execute($stmt);
$range_revenue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['r'];
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(total_amount),0) AS r FROM orders WHERE created_at BETWEEN ? AND ? AND payment_status='pending'");
mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
mysqli_stmt_execute($stmt);
$range_pending_pay = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['r'];
$avg_order_value = $range_orders > 0 ? $range_revenue / $range_orders : 0;

// Orders by status
$stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) AS c FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status");
mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
mysqli_stmt_execute($stmt);
$status_data = mysqli_stmt_get_result($stmt);

// Payment method breakdown
$stmt = mysqli_prepare($conn, "SELECT payment_method, COUNT(*) AS c, IFNULL(SUM(total_amount),0) AS rev FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY payment_method");
mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
mysqli_stmt_execute($stmt);
$payment_data = mysqli_stmt_get_result($stmt);

// Top selling products
$stmt = mysqli_prepare($conn,
  "SELECT p.product_name, SUM(oi.quantity) AS qty_sold, SUM(oi.price) AS revenue
   FROM order_items oi
   JOIN orders o ON oi.order_id = o.id
   JOIN products p ON oi.product_id = p.id
   WHERE o.created_at BETWEEN ? AND ?
   GROUP BY oi.product_id, p.product_name
   ORDER BY qty_sold DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
mysqli_stmt_execute($stmt);
$top_products = mysqli_stmt_get_result($stmt);

// Daily revenue for chart (last 14 days within range)
$stmt = mysqli_prepare($conn,
  "SELECT DATE(created_at) AS d, IFNULL(SUM(CASE WHEN payment_status='completed' THEN total_amount ELSE 0 END),0) AS rev, COUNT(*) AS orders
   FROM orders WHERE created_at BETWEEN ? AND ?
   GROUP BY DATE(created_at) ORDER BY d ASC");
mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
mysqli_stmt_execute($stmt);
$chart_q = mysqli_stmt_get_result($stmt);

$chart_labels = []; $chart_rev = []; $chart_orders = [];
while ($r = mysqli_fetch_assoc($chart_q)) {
    $chart_labels[] = date('d M', strtotime($r['d']));
    $chart_rev[] = round($r['rev'], 2);
    $chart_orders[] = (int)$r['orders'];
}

// CSV Export
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . $from . '_to_' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['StepStyle Sales Report', $from, 'to', $to]);
    fputcsv($out, []);
    fputcsv($out, ['Metric', 'Value']);
    fputcsv($out, ['Orders', $range_orders]);
    fputcsv($out, ['Revenue (Paid)', $range_revenue]);
    fputcsv($out, ['Pending Payments', $range_pending_pay]);
    fputcsv($out, ['Avg Order Value', round($avg_order_value, 2)]);
    fputcsv($out, []);
    fputcsv($out, ['Product', 'Qty Sold', 'Revenue']);
    $stmt = mysqli_prepare($conn,
      "SELECT p.product_name, SUM(oi.quantity) AS qty_sold, SUM(oi.price) AS revenue
       FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id
       WHERE o.created_at BETWEEN ? AND ?
       GROUP BY oi.product_id, p.product_name ORDER BY qty_sold DESC");
    mysqli_stmt_bind_param($stmt, "ss", $from_s, $to_s);
    mysqli_stmt_execute($stmt);
    $t = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($t)) {
        fputcsv($out, [$r['product_name'], $r['qty_sold'], $r['revenue']]);
    }
    fclose($out);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .stat-card { border: none; border-radius: 12px; }
        .stat-icon { font-size: 2rem; opacity: .85; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php require_once 'includes/sidebar.php'; ?>

            <div class="col py-4 px-4">
                <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-graph-up me-2 text-primary"></i>Sales Reports
                    </h4>
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <label class="text-muted small">From</label>
                        <input type="date" name="from" value="<?= $from ?>" class="form-control form-control-sm">
                        <label class="text-muted small">To</label>
                        <input type="date" name="to" value="<?= $to ?>" class="form-control form-control-sm">
                        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                        <a href="reports.php?from=<?= $from ?>&to=<?= $to ?>&export=csv" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-download me-1"></i>CSV
                        </a>
                    </form>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card stat-card bg-primary text-white shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-2 fw-bold">$<?= number_format($range_revenue, 0) ?></div>
                                    <div class="small">Revenue (Completed)</div>
                                </div>
                                <i class="bi bi-cash-stack stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card stat-card bg-warning text-dark shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-2 fw-bold">$<?= number_format($range_pending_pay, 0) ?></div>
                                    <div class="small">Pending Payments</div>
                                </div>
                                <i class="bi bi-hourglass-split stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card stat-card bg-success text-white shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-2 fw-bold"><?= $range_orders ?></div>
                                    <div class="small">Orders Placed</div>
                                </div>
                                <i class="bi bi-cart stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card stat-card bg-dark text-white shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-2 fw-bold">$<?= number_format($avg_order_value, 2) ?></div>
                                    <div class="small">Avg Order Value</div>
                                </div>
                                <i class="bi bi-calculator stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-8">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white py-3 fw-semibold">
                                <i class="bi bi-bar-chart-line me-2 text-primary"></i>Daily Revenue
                            </div>
                            <div class="card-body">
                                <canvas id="revenueChart" height="110"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white py-3 fw-semibold">
                                <i class="bi bi-pie-chart me-2 text-success"></i>Orders by Status
                            </div>
                            <div class="card-body">
                                <?php
                                $colors = ['pending' => 'warning', 'processing' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
                                ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($s = mysqli_fetch_assoc($status_data)): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= ucfirst($s['status']) ?>
                                            <span class="badge bg-<?= $colors[$s['status']] ?? 'secondary' ?>"><?= $s['c'] ?></span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                                <hr>
                                <h6 class="fw-semibold mb-2">Payment Methods</h6>
                                <?php while ($p = mysqli_fetch_assoc($payment_data)): ?>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-capitalize"><?= $p['payment_method'] ?></span>
                                        <span><strong><?= $p['c'] ?></strong> orders · $<?= number_format($p['rev'], 2) ?></span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-trophy me-2 text-warning"></i>Top Selling Products</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Product</th>
                                    <th>Quantity Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $i = 1; while ($r = mysqli_fetch_assoc($top_products)): ?>
                                <tr>
                                    <td class="ps-4"><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($r['product_name']) ?></td>
                                    <td><span class="badge bg-dark"><?= $r['qty_sold'] ?></span></td>
                                    <td>$<?= number_format($r['revenue'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?= json_encode($chart_rev) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,.12)',
                    fill: true,
                    tension: .35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>