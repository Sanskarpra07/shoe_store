<?php
// Payment gateway configuration (Sandbox / UAT credentials)
// For production, replace these with live merchant credentials.

// Ensure base_url() is available (normally defined in db.php).
if (!function_exists('base_url')) {
    function base_url($path = '') {
        $https   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
        $scheme  = $https ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $docroot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs') ?: 'C:/xampp/htdocs'), '/');
        $approot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
        $webpath = '';
        if ($docroot !== '/' && strpos($approot, $docroot) === 0) {
            $webpath = substr($approot, strlen($docroot));
        }
        return $scheme . '://' . $host . $webpath . '/' . ltrim($path, '/');
    }
}

// ---------- eSewa Sandbox ----------
define('ESEWA_MERCHANT_CODE', 'EPAYTEST');
define('ESEWA_URL', 'https://uat.esewa.com.np/epay/main');
define('ESEWA_SIGNATURE_URL', 'https://uat.esewa.com.np/epay/transrec');
define('ESEWA_SUCCESS_URL', base_url('esewa_success.php'));
define('ESEWA_FAILURE_URL', base_url('esewa_failure.php'));

// ---------- Khalti Sandbox ----------
define('KHALTI_SECRET_KEY', '0959556036f34cce88ef419e71d21f7d');
define('KHALTI_PUBLIC_KEY', '75b788f8af8a43beb77f41d4790b746f');
// KPG-2 API endpoints (sandbox)
define('KHALTI_API_BASE', 'https://dev.khalti.com/api/v2');
define('KHALTI_INITIATE_URL', KHALTI_API_BASE . '/epayment/initiate/');
define('KHALTI_LOOKUP_URL', KHALTI_API_BASE . '/epayment/lookup/');
define('KHALTI_REFUND_URL', 'https://dev.khalti.com/api/merchant-transaction/');
define('KHALTI_CALLBACK_URL', base_url('khalti_callback.php'));