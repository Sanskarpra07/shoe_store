<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN USERS MANAGEMENT
 * --------------------------------------------------------------------------
 * Admin-only page that lists administrator/staff accounts (with delete)
 * and all registered storefront customers (with their order counts).
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Users';
$current_page = 'users';
require_once 'includes/header.php';

// --- Role guard: only administrators may manage users --------------------------
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// --- Handle user deletion (POST) ----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = (int)($_POST['user_id'] ?? 0);

    // Find the currently logged-in user so they cannot delete themselves.
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $_SESSION['username']);
    mysqli_stmt_execute($stmt);
    $current_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($id === (int)$current_user['id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $_SESSION['success'] = "User deleted successfully.";
    }
    header("Location: users.php");
    exit();
}

// --- Fetch admin / staff users --------------------------------------------------
$users = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");

// --- Fetch registered customers (online store accounts) -------------------------
$customers = mysqli_query($conn,
    "SELECT c.id, c.full_name, c.email, c.phone, c.is_verified, c.created_at,
            COUNT(o.id) AS order_count
     FROM customers c
     LEFT JOIN orders o ON o.customer_id = c.id
     GROUP BY c.id
     ORDER BY c.created_at DESC"
);

// --- Flash messages ----------------------------------------------------------------
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Users Management</h2>
        <p class="page-sub">Manage admin &amp; staff accounts and registered customers</p>
    </div>
    <a class="btn btn-green" href="register.php"><i class="bi bi-person-plus"></i> Add User</a>
</div>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- ======== ADMIN / STAFF USERS TABLE ======== -->
<h3 class="section-title">Admin / Staff Users</h3>
<div class="table-responsive">
<table class="table" style="max-width:760px;">
    <tr>
        <th>#</th>
        <th>Username</th>
        <th>Role</th>
        <th>Registered On</th>
        <th>Action</th>
    </tr>
    <?php $sno = 1; while ($row = mysqli_fetch_assoc($users)):
        $is_me = ($row['username'] === $_SESSION['username']);
    ?>
    <tr>
        <td class="center"><?= $sno++ ?></td>
        <td>
            <strong><?= htmlspecialchars($row['username']) ?></strong>
            <?php if ($is_me): ?>
                <span class="badge badge-primary" style="margin-left:6px;">You</span>
            <?php endif; ?>
        </td>
        <td class="center">
            <span class="badge badge-<?= $row['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                <i class="bi bi-<?= $row['role'] === 'admin' ? 'shield-lock' : 'person' ?>" style="font-size:11px;"></i>
                <?= ucfirst($row['role']) ?>
            </span>
        </td>
        <td class="center"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
        <td class="center">
            <?php if ($is_me): ?>
                <span class="small text-muted">Cannot delete own account</span>
            <?php else: ?>
                <form method="POST" action="users.php" style="display:inline;">
                    <input type="hidden" name="delete_user" value="1">
                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-red btn-small" data-confirm="Delete user: <?= htmlspecialchars($row['username']) ?>?"><i class="bi bi-trash"></i> Delete</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>

<!-- ======== REGISTERED CUSTOMERS TABLE ======== -->
<h3 class="section-title">Registered Customers</h3>
<div class="table-responsive">
<table class="table">
    <tr>
        <th>#</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Verified</th>
        <th>Orders</th>
        <th>Registered On</th>
    </tr>
    <?php $sno = 1; while ($c = mysqli_fetch_assoc($customers)): ?>
    <tr>
        <td class="center"><?= $sno++ ?></td>
        <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
        <td><?= htmlspecialchars($c['email']) ?></td>
        <td class="center"><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
        <td class="center">
            <?= $c['is_verified']
                ? '<span class="badge badge-success">Yes</span>'
                : '<span class="badge badge-secondary">No</span>' ?>
        </td>
        <td class="center"><span class="badge badge-primary"><?= $c['order_count'] ?></span></td>
        <td class="center"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($customers) === 0): ?>
    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i>No customers registered yet.</div></td></tr>
    <?php endif; ?>
</table>
</div>

<?php require_once 'includes/footer.php'; ?>