<?php
session_start();
$page_title = 'Sales Reports';
$current_page = 'reports';
require_once 'includes/header.php';

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

<h2>Sales Reports</h2>

<form method="GET" action="reports.php" style="margin-bottom:10px;">
    <label>From:</label>
    <input type="date" name="from" value="<?= $from ?>">
    <label>To:</label>
    <input type="date" name="to" value="<?= $to ?>">
    <button type="submit" class="btn btn-small">Filter</button>
    <a class="btn btn-green btn-small" href="reports.php?from=<?= $from ?>&to=<?= $to ?>&export=csv">Download CSV</a>
</form>

<table class="table" style="width:800px;">
    <tr>
        <td class="center"><strong>$<?= number_format($range_revenue, 2) ?></strong><br>Revenue (Completed)</td>
        <td class="center"><strong>$<?= number_format($range_pending_pay, 2) ?></strong><br>Pending Payments</td>
        <td class="center"><strong><?= $range_orders ?></strong><br>Orders Placed</td>
        <td class="center"><strong>$<?= number_format($avg_order_value, 2) ?></strong><br>Avg Order Value</td>
    </tr>
</table>

<h3 class="section-title">Orders by Status</h3>
<table class="table" style="width:400px;">
    <tr>
        <th>Status</th>
        <th>Orders</th>
    </tr>
    <?php while ($s = mysqli_fetch_assoc($status_data)): ?>
    <tr>
        <td><?= ucfirst($s['status']) ?></td>
        <td class="center"><?= $s['c'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<h3 class="section-title">Payment Methods</h3>
<table class="table" style="width:500px;">
    <tr>
        <th>Method</th>
        <th>Orders</th>
        <th>Revenue</th>
    </tr>
    <?php while ($p = mysqli_fetch_assoc($payment_data)): ?>
    <tr>
        <td><?= htmlspecialchars($p['payment_method']) ?></td>
        <td class="center"><?= $p['c'] ?></td>
        <td class="center">$<?= number_format($p['rev'], 2) ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<h3 class="section-title">Top Selling Products</h3>
<table class="table" style="width:600px;">
    <tr>
        <th>#</th>
        <th>Product</th>
        <th>Quantity Sold</th>
        <th>Revenue</th>
    </tr>
    <?php $i = 1; while ($r = mysqli_fetch_assoc($top_products)): ?>
    <tr>
        <td class="center"><?= $i++ ?></td>
        <td><?= htmlspecialchars($r['product_name']) ?></td>
        <td class="center"><?= $r['qty_sold'] ?></td>
        <td class="center">$<?= number_format($r['revenue'], 2) ?></td>
    </tr>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($top_products) === 0): ?>
    <tr><td colspan="4" class="center">No sales in this period.</td></tr>
    <?php endif; ?>
</table>

<?php require_once 'includes/footer.php'; ?>