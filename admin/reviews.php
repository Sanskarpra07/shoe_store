<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN PRODUCT REVIEWS
 * --------------------------------------------------------------------------
 * Reviews the customer reviews table: approve, reject or delete each
 * submission so only approved reviews are shown on the product pages.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Reviews';
$current_page = 'reviews';
require_once 'includes/header.php';

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// --- Handle approve / reject / delete actions (POST) ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rid = (int)($_POST['review_id'] ?? 0);

    if ($action === 'approve') {
        // Make the review visible on the storefront.
        mysqli_query($conn, "UPDATE reviews SET status='approved' WHERE id=$rid");
        $_SESSION['success'] = "Review approved.";
    } elseif ($action === 'reject') {
        // Hide the review from the storefront.
        mysqli_query($conn, "UPDATE reviews SET status='rejected' WHERE id=$rid");
        $_SESSION['success'] = "Review rejected.";
    } elseif ($action === 'delete') {
        // Remove the review permanently.
        mysqli_query($conn, "DELETE FROM reviews WHERE id=$rid");
        $_SESSION['success'] = "Review deleted.";
    }
    header("Location: reviews.php");
    exit();
}

// --- Fetch all reviews with product and customer info ----------------------------
$reviews = mysqli_query($conn,
    "SELECT r.*, p.product_name, c.full_name, c.email
     FROM reviews r
     JOIN products p ON r.product_id = p.id
     LEFT JOIN customers c ON r.customer_id = c.id
     ORDER BY r.created_at DESC");
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Product Reviews</h2>
        <p class="page-sub">Manage customer reviews and ratings</p>
    </div>
</div>

<!-- ======== REVIEWS TABLE (panel) ======== -->
<div class="panel-card">
    <div class="panel-header">
        <h3><i class="bi bi-star"></i> All Reviews <span class="text-muted small">(<?= mysqli_num_rows($reviews) ?>)</span></h3>
    </div>
    <div class="panel-body">

    <!-- Flash success message -->
    <?php if ($success): ?>
        <div class="msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
    <table class="table">
    <tr>
        <th>Customer</th>
        <th>Product</th>
        <th>Rating</th>
        <th>Review</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    <?php while ($r = mysqli_fetch_assoc($reviews)): ?>
    <tr>
        <td>
            <strong><?= htmlspecialchars($r['full_name'] ?? 'Guest') ?></strong><br>
            <span class="small text-muted"><?= htmlspecialchars($r['email'] ?? '') ?></span>
        </td>
        <td><?= htmlspecialchars($r['product_name']) ?></td>
        <!-- Star rating display -->
        <td class="center"><span class="stars"><?= str_repeat('&#9733;', (int)$r['rating']) ?><?= str_repeat('&#9734;', 5 - (int)$r['rating']) ?></span></td>
        <td style="max-width:340px;"><?= htmlspecialchars($r['comment'] ?? '-') ?></td>
        <td class="center">
            <span class="badge badge-<?= $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($r['status']) ?></span>
        </td>
        <td class="center">
            <div style="display:flex; gap:4px; justify-content:center; flex-wrap:wrap;">
            <form method="POST" action="reviews.php">
                <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                <?php if ($r['status'] !== 'approved'): ?>
                    <button type="submit" name="action" value="approve" class="btn btn-green btn-small"><i class="bi bi-check-lg"></i> Approve</button>
                <?php endif; ?>
                <?php if ($r['status'] !== 'rejected'): ?>
                    <button type="submit" name="action" value="reject" class="btn btn-gray btn-small"><i class="bi bi-x-lg"></i> Reject</button>
                <?php endif; ?>
                <button type="submit" name="action" value="delete" class="btn btn-red btn-small"
                        data-confirm="Delete this review?"><i class="bi bi-trash"></i> Delete</button>
            </form>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>

    <!-- Empty state -->
    <?php if (mysqli_num_rows($reviews) === 0): ?>
    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i>No reviews yet.</div></td></tr>
    <?php endif; ?>
    </table>
    </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>