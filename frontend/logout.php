<?php
/**
 * --------------------------------------------------------------------------
 * Logout - MegaFoot Storefront
 * --------------------------------------------------------------------------
 * Destroys the current session and clears the session cookie. Redirects
 * admin/staff users to the admin login and regular customers to the home
 * page.
 * --------------------------------------------------------------------------
 */
// ---------- Session bootstrap ----------
session_start();

// ---------- Detect admin/staff before clearing ----------
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff']);

// ---------- Destroy session ----------
session_unset();
session_destroy();

// ---------- Clear session cookie ----------
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// ---------- Redirect based on role ----------
header("Location: " . ($is_admin ? "../admin/login.php" : "index.php"));
exit();
?>
