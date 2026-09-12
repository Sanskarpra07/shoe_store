<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN STOCK LOG
 * --------------------------------------------------------------------------
 * Lets staff add or remove stock for any product (recording the reason and
 * who made the change) and shows the latest 50 adjustments in a history table.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Stock Log';
$current_page = 'stock_log';
require_once 'includes/header.php';

$errors = [];
$success = "";

// --- Handle stock adjustment form (POST) ------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id    = (int)($_POST['product_id'] ?? 0);
    $change_amount = (int)($_POST['change_amount'] ?? 0);
    $type          = $_POST['type'] ?? 'add';
    $reason        = trim($_POST['reason'] ?? '');
    $changed_by    = $_SESSION['username'];

    // Normalize the amount: negative for remove, positive for add.
    if ($type === 'remove') {
        $change_amount = -abs($change_amount);
    } else {
        $change_amount = abs($change_amount);
    }

    // Basic validation.
    if ($product_id === 0)    $errors[] = "Please select a product.";
    if ($change_amount === 0) $errors[] = "Please enter a valid amount.";

    // Apply the adjustment when valid.
    if (empty($errors)) {
        $current = mysqli_fetch_assoc(
            mysqli_query($conn, "SELECT stock FROM products WHERE id = $product_id")
        );

        $new_stock = $current['stock'] + $change_amount;

        // Block removals that would push stock below zero.
        if ($new_stock < 0) {
            $errors[] = "Cannot remove more than current stock ({$current['stock']} units).";
        } else {
            // Update the product's stock.
            $stmt = mysqli_prepare($conn, "UPDATE products SET stock = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $new_stock, $product_id);
            mysqli_stmt_execute($stmt);

            // Record the change in the stock_log table.
            $stmt = mysqli_prepare($conn,
                "INSERT INTO stock_log (product_id, change_amount, reason, changed_by) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $product_id, $change_amount, $reason, $changed_by);
            mysqli_stmt_execute($stmt);

            $success = "Stock updated successfully!";
        }
    }
}

// --- Fetch product choices for the dropdown ---------------------------------------
$products = mysqli_query($conn, "SELECT id, product_name, stock FROM products ORDER BY product_name ASC");

// --- Fetch the latest 50 stock adjustments ------------------------------------------
$logs = mysqli_query($conn,
    "SELECT sl.*, p.product_name
     FROM stock_log sl
     JOIN products p ON sl.product_id = p.id
     ORDER BY sl.created_at DESC
     LIMIT 50");
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Stock Adjustment Log</h2>
        <p class="page-sub">Add or remove stock for products and keep a record of every change.</p>
    </div>
</div>

<!-- Validation errors -->
<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<!-- Flash success message -->
<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- ======== EVENLY SPLIT: ADJUSTMENT FORM + LOG ======== -->
<div class="split">
    <!-- Stock adjustment form -->
    <div class="form-box">
        <h3>Adjust Stock</h3>
        <form method="POST" action="stock_log.php">
            <div class="form-group">
                <label>Product *</label>
                <select name="product_id" required>
                    <option value="">-- Select product --</option>
                    <?php while ($p = mysqli_fetch_assoc($products)): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['product_name']) ?> (Stock: <?= $p['stock'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type *</label>
                <select name="type" required>
                    <option value="add">+ Add Stock</option>
                    <option value="remove">- Remove Stock</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount *</label>
                <input type="number" name="change_amount" min="1" placeholder="e.g. 50" required>
            </div>
            <div class="form-group">
                <label>Reason</label>
                <input type="text" name="reason" placeholder="e.g. New shipment arrived">
            </div>
            <button type="submit" class="btn"><i class="bi bi-check-lg"></i> Apply Adjustment</button>
        </form>
    </div>

    <!-- Recent adjustments history -->
    <div class="panel-card">
        <div class="panel-header">
            <h3><i class="bi bi-clock-history"></i> Recent Adjustments <span class="text-muted small">(last 50)</span></h3>
        </div>
        <div class="panel-body">
        <div class="table-responsive">
        <table class="table">
            <tr>
                <th>Product</th>
                <th>Change</th>
                <th>Reason</th>
                <th>By</th>
                <th>Date</th>
            </tr>
            <?php while ($log = mysqli_fetch_assoc($logs)): ?>
            <tr>
                <td><strong><?= htmlspecialchars($log['product_name']) ?></strong></td>
                <td class="center">
                    <?php if ($log['change_amount'] > 0): ?>
                        <span class="badge badge-success">+<?= $log['change_amount'] ?></span>
                    <?php else: ?>
                        <span class="badge badge-danger"><?= $log['change_amount'] ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($log['reason'] ?? '-') ?></td>
                <td class="center"><?= htmlspecialchars($log['changed_by']) ?></td>
                <td class="center"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($logs) === 0): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i>No stock adjustments recorded yet.</div></td></tr>
            <?php endif; ?>
        </table>
        </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>