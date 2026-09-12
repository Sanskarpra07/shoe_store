<?php
/**
 * --------------------------------------------------------------------------
 * DATABASE CONNECTION
 * --------------------------------------------------------------------------
 * Opens a single mysqli connection shared by every page in the project
 * via the global $conn variable, and provides the base_url() helper used
 * by the payment callbacks to build absolute return URLs.
 * --------------------------------------------------------------------------
 */

// --- Load database credentials ---------------------------------------------
require_once __DIR__ . '/db_config.php';

// --- Open the shared connection ---------------------------------------------
$conn = mysqli_connect($servername, $username, $password, $dbName);

// Abort with a readable error when the connection fails.
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// --- Base URL helper --------------------------------------------------------
// Builds the site's absolute URL so the payment gateway return links work
// no matter which folder the project is deployed into (works for both
// the XAMPP web root and a sub folder).
function base_url($path = '') {
    // Detect HTTPS so we can build http:// vs https:// links.
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Determine the web-accessible part of the project path
    // (e.g. '' when deployed at /, or '/shoe_store' under the htdocs root).
    $docroot = rtrim(str_replace('\\', '/',
        realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs') ?: 'C:/xampp/htdocs'), '/');
    $approot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

    $webpath = '';
    if ($docroot !== '/' && strpos($approot, $docroot) === 0) {
        $webpath = substr($approot, strlen($docroot));
    }

    // Combine scheme + host + app folder + requested path.
    return $scheme . '://' . $host . $webpath . '/' . ltrim($path, '/');
}