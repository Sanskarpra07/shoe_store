<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN ADD USER
 * --------------------------------------------------------------------------
 * Admin-only form to create a new admin or staff account. Passwords are
 * hashed with password_hash() before being stored.
 * --------------------------------------------------------------------------
 */

// --- Session bootstrap + shared layout header --------------------------------
session_start();
$page_title = 'Add User';
$current_page = 'users';
require_once 'includes/header.php';

// --- Role guard: only administrators may register users --------------------------
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$success = "";

// --- Handle user creation (POST) ----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'staff';

    // Basic validation.
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (!in_array($role, ['admin', 'staff'])) $role = 'staff';

    if (empty($errors)) {
        // Make sure the username is not already taken.
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Username already taken.";
        } else {
            // Hash the password and insert the new account.
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password_eg, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $role);

            if (mysqli_stmt_execute($stmt)) {
                $success = "User '$username' registered successfully!";
            } else {
                $errors[] = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!-- ======== PAGE HEADER ======== -->
<div class="page-header">
    <div>
        <h2>Add New User</h2>
        <p class="page-sub">Create an admin or staff account</p>
    </div>
    <a class="btn btn-gray" href="users.php"><i class="bi bi-arrow-left"></i> Back to Users</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- ======== ADD USER FORM ======== -->
<div class="form-box" style="max-width:420px;">
    <form method="POST" action="register.php">
        <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" placeholder="Enter username" required>
        </div>
        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div style="display:flex; gap:10px; margin-top:8px;">
            <button type="submit" class="btn" style="flex:1;"><i class="bi bi-person-plus"></i> Create User</button>
            <a href="users.php" class="btn btn-gray"><i class="bi bi-x-lg"></i> Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>