<?php
session_start();

if (!isset($_SESSION['username'])){
    header("Location: ../admin/login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once '../db.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

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

$current_page = 'users';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Shoe Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row flex-nowrap">

        <?php require_once 'includes/sidebar.php'; ?>

        <div class="col py-4 px-4">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-people me-2 text-primary"></i>Users
                </h4>
                <a href="register.php" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Add User
                </a>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>User</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Registered On</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sno = 1;
                        while ($row = mysqli_fetch_assoc($users)):
                            $is_me = ($row['username'] === $_SESSION['username']);
                            $initial = strtoupper($row['username'][0]);
                        ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $sno++ ?></td>
                                <td>
                                    <span class="avatar me-2"><?= $initial ?></span>
                                </td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($row['username']) ?>
                                    <?php if ($is_me): ?>
                                        <span class="badge bg-primary ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $row['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                                        <?= ucfirst($row['role']) ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($is_me): ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete your own account">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="users.php?action=delete&id=<?= $row['id'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete user: <?= htmlspecialchars($row['username']) ?>?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
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
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.replace('../admin/login.php');
            }
        });
    </script>
</body>
</html>
