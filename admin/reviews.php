<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$current_page = 'reviews';

// Approve / reject / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { die('Invalid request'); }
    $action = $_POST['action'] ?? '';
    $rid = (int)($_POST['review_id'] ?? 0);
    if ($action === 'approve') {
        mysqli_query($conn, "UPDATE reviews SET status='approved' WHERE id=$rid");
    } elseif ($action === 'reject') {
        mysqli_query($conn, "UPDATE reviews SET status='rejected' WHERE id=$rid");
    } elseif ($action === 'delete') {
        mysqli_query($conn, "DELETE FROM reviews WHERE id=$rid");
    }
    header("Location: reviews.php");
    exit();
}

$reviews = mysqli_query($conn,
  "SELECT r.*, p.product_name, c.full_name, c.email
   FROM reviews r
   JOIN products p ON r.product_id = p.id
   LEFT JOIN customers c ON r.customer_id = c.id
   ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Shoe Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .review-avatar { width: 38px; height: 38px; border-radius: 50%; background: #6c757d; color: #fff;
                         display: inline-flex; align-items: center; justify-content: center; font-weight: bold; }
        .stars { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <?php require_once 'includes/sidebar.php'; ?>

            <div class="col py-4 px-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-star me-2 text-warning"></i>Product Reviews
                    </h4>
                    <span class="text-muted small">Manage customer reviews & ratings</span>
                </div>

                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white py-3 fw-semibold">
                        <i class="bi bi-chat-quote me-2 text-primary"></i>All Reviews
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Customer</th>
                                    <th>Product</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($r = mysqli_fetch_assoc($reviews)): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="review-avatar"><?= strtoupper(substr($r['full_name'] ?? ($r['email'] ?? 'G'), 0, 1)) ?></div>
                                            <div>
                                                <div class="fw-semibold small"><?= htmlspecialchars($r['full_name'] ?? 'Guest') ?></div>
                                                <div class="text-muted fw-normal"><?= htmlspecialchars($r['email'] ?? '') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($r['product_name']) ?></td>
                                    <td><span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span><span class="text-muted"><?= str_repeat('☆', 5 - (int)$r['rating']) ?></span></td>
                                    <td class="small" style="max-width:280px;"><?= htmlspecialchars($r['comment']) ?></td>
                                    <td>
                                        <?php
                                        $badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                                        echo "<span class='badge bg-{$badge[$r['status']]}'>" . ucfirst($r['status']) . "</span>";
                                        ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                            <?php if ($r['status'] !== 'approved'): ?>
                                                <button name="action" value="approve" class="btn btn-sm btn-success" title="Approve">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($r['status'] !== 'rejected'): ?>
                                                <button name="action" value="reject" class="btn btn-sm btn-warning" title="Reject">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button name="action" value="delete" class="btn btn-sm btn-outline-danger" title="Delete"
                                                    onclick="return confirm('Delete this review?');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>