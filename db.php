<?php
// Database connection
require_once __DIR__ . '/db_config.php';

$conn = mysqli_connect($servername, $username, $password, $dbName);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Build the site's base URL so the payment gateway return
// links work no matter which folder the project is placed in.
function base_url($path = '') {
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $docroot = rtrim(str_replace('\\', '/',
        realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs') ?: 'C:/xampp/htdocs'), '/');
    $approot = rtrim(str_replace('\\', '/', __DIR__), '/');

    $webpath = '';
    if ($docroot !== '/' && strpos($approot, $docroot) === 0) {
        $webpath = substr($approot, strlen($docroot));
    }

    return $scheme . '://' . $host . $webpath . '/' . ltrim($path, '/');
}