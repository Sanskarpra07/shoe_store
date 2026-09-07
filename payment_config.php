<?php
// Payment gateway configuration (Sandbox / UAT credentials)
// For production, replace these with live merchant credentials.

// ---------- eSewa Sandbox ----------
define('ESEWA_MERCHANT_CODE', 'EPAYTEST');
define('ESEWA_URL', 'https://uat.esewa.com.np/epay/main');
define('ESEWA_SIGNATURE_URL', 'https://uat.esewa.com.np/epay/transrec');
define('ESEWA_SUCCESS_URL', 'http://localhost/shoe_store/esewa_success.php');
define('ESEWA_FAILURE_URL', 'http://localhost/shoe_store/esewa_failure.php');

// ---------- Khalti Sandbox ----------
define('KHALTI_SECRET_KEY', 'test_secret_key');
define('KHALTI_INITIATE_URL', 'https://learners.np.gov.np/api/khalti/initiate'); // placeholder
define('KHALTI_VERIFY_URL', 'https://khalti.com/api/v2/payment/verify/');
define('KHALTI_INIT_CHECKOUT_URL', 'https://khalti.com/api/v2/epayment/initiate/');
define('KHALTI_CALLBACK_URL', 'http://localhost/shoe_store/khalti_callback.php');