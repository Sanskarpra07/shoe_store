<?php
require_once __DIR__ . '/db_config.php';

$conn = @mysqli_connect($servername, $username, $password, $dbName);
if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

// ------------------------------------------------------------------
// base_url(): builds the site's root URL automatically so the app
// works from any folder / host / port (no hardcoded localhost paths).
// ------------------------------------------------------------------
function base_url($path = '') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    $scheme   = $https ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docroot  = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs') ?: 'C:/xampp/htdocs'), '/');
    $approot  = rtrim(str_replace('\\', '/', __DIR__), '/');
    $webpath  = '';
    if ($docroot !== '/' && strpos($approot, $docroot) === 0) {
        $webpath = substr($approot, strlen($docroot));
    }
    return $scheme . '://' . $host . $webpath . '/' . ltrim($path, '/');
}

// --- Basic security headers (Availability + Confidentiality hardening) ---
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// --- CSRF protection helpers (defense against cross-site request forgery) ---
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
?>