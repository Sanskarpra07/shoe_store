<?php
$servername="127.0.0.1";
$username="root";
$password="";
$dbName="shoe_store_db";

$conn=mysqli_connect($servername,$username,$password,$dbName);
if (!$conn) {
    die("Connection Failed:" . mysqli_connect_error());
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