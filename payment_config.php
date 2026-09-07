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
        $approot = rtrim(str_replace('\\', '/', __DIR__), '/');
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
define('KHALTI_SECRET_KEY', 'test_secret_key');
define('KHALTI_INITIATE_URL', 'https://learners.np.gov.np/api/khalti/initiate'); // placeholder
define('KHALTI_VERIFY_URL', 'https://khalti.com/api/v2/payment/verify/');
define('KHALTI_INIT_CHECKOUT_URL', 'https://khalti.com/api/v2/epayment/initiate/');
define('KHALTI_CALLBACK_URL', base_url('khalti_callback.php'));