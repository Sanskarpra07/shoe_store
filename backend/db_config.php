<?php
/**
 * --------------------------------------------------------------------------
 * DATABASE CONFIGURATION
 * --------------------------------------------------------------------------
 * Edit these values to match your local MySQL / MariaDB setup.
 * The connection is opened in backend/db.php using these constants.
 * --------------------------------------------------------------------------
 */

// Environment variables (set by the hosting panel) win when present, so the
// same file works locally on XAMPP and on a deployed server untouched.
// Falls back to the local XAMPP defaults below.
$servername = getenv('DB_HOST') ?: '127.0.0.1';     // Database server host (localhost / 127.0.0.1)
$username   = getenv('DB_USER') ?: 'root';          // Database user
$password   = (getenv('DB_PASS') !== false) ? getenv('DB_PASS') : ''; // Database password (empty for local XAMPP)
$dbName     = getenv('DB_NAME') ?: 'shoe_store_db'; // Name of the shoe store database