<?php
/**
 * --------------------------------------------------------------------------
 * PAYMENT GATEWAY CONFIGURATION
 * --------------------------------------------------------------------------
 * Holds the eSewa (ePay V2) and Khalti credentials used by the storefront.
 * Both are currently SANDBOX / UAT credentials - replace them with live
 * merchant credentials and production URLs before going live.
 * --------------------------------------------------------------------------
 */

// --- base_url() fallback ----------------------------------------------------
// base_url() lives in backend/db.php, but a few pages load this file on its
// own. Provide it here so the payment callback URLs still resolve.
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

// ---------- eSewa Sandbox (ePay V2) ----------
define('ESEWA_MERCHANT_CODE', 'EPAYTEST');          // Sandbox merchant key
// Sandbox secret used for HMAC-SHA256 signature generation/verification.
// Production: replace with your live secret and the epay.esewa.com.np URLs.
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
define('ESEWA_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'); // ePay v2 initiation form
define('ESEWA_STATUS_URL', 'https://rc-epay.esewa.com.np/api/epay/transaction/status/'); // txn verification
// Return URLs that eSewa redirects the customer to after payment.
define('ESEWA_SUCCESS_URL', base_url('esewa_success.php'));
define('ESEWA_FAILURE_URL', base_url('esewa_failure.php'));

// ---------- Khalti Sandbox ----------
define('KHALTI_SECRET_KEY', '0959556036f34cce88ef419e71d21f7d'); // Sandbox secret
define('KHALTI_PUBLIC_KEY', '75b788f8af8a43beb77f41d4790b746f'); // Sandbox public key
// KPG-2 API endpoints (sandbox). Switch to non-dev endpoints in production.
define('KHALTI_API_BASE', 'https://dev.khalti.com/api/v2');
define('KHALTI_INITIATE_URL', KHALTI_API_BASE . '/epayment/initiate/'); // start a payment
define('KHALTI_LOOKUP_URL', KHALTI_API_BASE . '/epayment/lookup/');     // verify a payment
define('KHALTI_REFUND_URL', 'https://dev.khalti.com/api/merchant-transaction/'); // refund API
// Return URL Khalti redirects the customer to after payment.
define('KHALTI_CALLBACK_URL', base_url('khalti_callback.php'));