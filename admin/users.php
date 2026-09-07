<?php
session_start();
$page_title = 'Users';
$current_page = 'users';
require_once 'includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $current_user = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT id FROM users WHERE username = '" . mysqli_real_escape_string($conn, $_SESSION['username']) . "'")
    );

    if ($id == (int)$current_user['id']) {
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

$users = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");

// Registered customers (online store accounts)
$customers = mysqli_query($conn,
    "SELECT c.id, c.full_name, c.email, c.phone, c.is_verified, c.created_at,
            COUNT(o.id) AS order_count
     FROM customers c
     LEFT JOIN orders o ON o.customer_id = c.id
     GROUP BY c.id
     ORDER BY c.created_at DESC"
);

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<h2>Users Management</h2>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h3 class="section-title">Admin / Staff Users</h3>
<p><a class="btn" href="register.php">+ Add User</a></p>
<table class="table" style="width:700px;">
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
                <span style="color:#1a237e;">(You)</span>
            <?php endif; ?>
        </td>
        <td class="center">
            <?= ucfirst($row['role']) ?>
        </td>
        <td class="center"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
        <td class="center">
            <?php if ($is_me): ?>
                <span class="small text-muted">Cannot delete own account</span>
            <?php else: ?>
                <a class="btn btn-red btn-small" href="users.php?action=delete&id=<?= $row['id'] ?>"
                   onclick="return confirm('Delete user: <?= htmlspecialchars($row['username']) ?>?')">Delete</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<h3 class="section-title">Registered Customers</h3>
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
        <td class="center"><?= $c['is_verified'] ? 'Yes' : 'No' ?></td>
        <td class="center"><?= $c['order_count'] ?></td>
        <td class="center"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($customers) === 0): ?>
    <tr><td colspan="7" class="center">No customers registered yet.</td></tr>
    <?php endif; ?>
</table>

<?php require_once 'includes/footer.php'; ?>