<?php
session_start();
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff']);
session_unset();
session_destroy();
// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
header("Location: " . ($is_admin ? "../admin/login.php" : "index.php"));
exit();
?>
