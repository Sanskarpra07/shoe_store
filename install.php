<?php
// ==================================================================
//  install.php  -  StepStyle one-click setup
//  Visit this page once after copying the project to a new machine.
//  It creates the database, imports sample data, and verifies setup.
// ==================================================================
error_reporting(E_ALL);
ini_set('display_errors', '1');

$APP_DIR = __DIR__;
$CONFIG_FILE = $APP_DIR . '/db_config.php';
$SETUP_SQL = $APP_DIR . '/sql/setup.sql';

// ---- Default database settings (XAMPP defaults) ----
$cfg = ['servername' => '127.0.0.1', 'username' => 'root', 'password' => '', 'dbName' => 'shoe_store_db'];

// ---- Load existing config if present ----
if (is_file($CONFIG_FILE)) {
    include $CONFIG_FILE;
    $cfg = ['servername' => $servername, 'username' => $username, 'password' => (string)$password, 'dbName' => $dbName];
}

$message = [];
$errors  = [];
$installed = false;
$productCount = 0;

// ---------------- Helper: save config to db_config.php ----------------
function write_config($path, array $cfg) {
    $contents = "<?php\n"
        . "// ------------------------------------------------------------------\n"
        . "// Database configuration.\n"
        . "// Edit these values to match your MySQL/MariaDB setup if needed.\n"
        . "// The web installer (install.php) can also update this file for you.\n"
        . "// ------------------------------------------------------------------\n\n"
        . '$servername = ' . var_export($cfg['servername'], true) . ";\n"
        . '$username   = ' . var_export($cfg['username'], true) . ";\n"
        . '$password   = ' . var_export($cfg['password'], true) . ";\n"
        . '$dbName     = ' . var_export($cfg['dbName'], true) . ";\n";
    return file_put_contents($path, $contents) !== false;
}

function valid_db_name($name) {
    return preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
}

// ---------------- Handle form actions ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbName = trim($_POST['dbName'] ?? 'shoe_store_db');
    if (!valid_db_name($dbName)) {
        $errors[] = 'Database name may only contain letters, numbers and underscores.';
    } else {
        $cfg = [
            'servername' => trim($_POST['servername'] ?? '127.0.0.1'),
            'username'   => trim($_POST['username'] ?? 'root'),
            'password'   => (string)($_POST['password'] ?? ''),
            'dbName'     => $dbName,
        ];
        if (isset($_POST['action']) && $_POST['action'] === 'reset') {
            // Destructive reinstall requested -> wipe database first
            $conn = @mysqli_connect($cfg['servername'], $cfg['username'], $cfg['password']);
            if ($conn) {
                mysqli_query($conn, 'DROP DATABASE IF EXISTS `' . $cfg['dbName'] . '`');
                mysqli_close($conn);
            }
        }
        if (write_config($CONFIG_FILE, $cfg)) {
            $message[] = 'Database settings saved to db_config.php.';
        } else {
            $errors[] = 'Could not write db_config.php. Check file permissions.';
        }
    }
}

// ---------------- Try to connect & install ----------------
$conn = @mysqli_connect($cfg['servername'], $cfg['username'], $cfg['password']);
if (!$conn) {
    $errors[] = 'Cannot connect to MySQL on ' . $cfg['servername'] . '. ' .
        (function_exists('mysqli_connect_error') ? mysqli_connect_error() : '');
} else {
    // Create the database if it does not exist
    $exists = mysqli_query($conn, "SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '"
        . mysqli_real_escape_string($conn, $cfg['dbName']) . "'");
    $dbExists = ($exists && mysqli_num_rows($exists) > 0);

    if (!$dbExists) {
        if (!mysqli_query($conn, 'CREATE DATABASE `' . $cfg['dbName'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')) {
            $errors[] = 'Could not create database: ' . mysqli_error($conn);
        } else {
            $message[] = "Database '" . $cfg['dbName'] . "' created.";
        }
    }

    if (empty($errors) && is_file($SETUP_SQL)) {
        // Import schema & sample data
        $sql = file_get_contents($SETUP_SQL);
        $sql = str_replace('shoe_store_db', $cfg['dbName'], $sql); // honor a custom DB name
        mysqli_select_db($conn, $cfg['dbName']);
        if (mysqli_multi_query($conn, $sql)) {
            do {
                if ($res = mysqli_store_result($conn)) { mysqli_free_result($res); }
            } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            $message[] = 'Schema imported: tables, sample products, users and customer created.';
        } else {
            $errors[] = 'Import failed: ' . mysqli_error($conn);
        }
        while (mysqli_more_results($conn) && mysqli_next_result($conn)) { /* drain */ }
    } elseif (empty($errors)) {
        $errors[] = 'Missing sql/setup.sql - the installer could not import the schema.';
    }

    // ---- Verify installation ----
    mysqli_select_db($conn, $cfg['dbName']);
    $chk = @mysqli_query($conn, 'SELECT COUNT(*) AS c FROM products');
    if ($chk) {
        $row = mysqli_fetch_assoc($chk);
        $productCount = (int)$row['c'];
        $installed = $productCount > 0;
    } else {
        // products table missing -> not installed
        $tables = @mysqli_query($conn, 'SHOW TABLES');
        if ($tables && mysqli_num_rows($tables) > 0) {
            $installed = true; // some tables exist, partial
        }
    }
    mysqli_close($conn);
}

// ---------------- Environment checks ----------------
$phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
$ext = [
    'mysqli' => extension_loaded('mysqli'),
    'curl'   => extension_loaded('curl'),
    'mbstring' => extension_loaded('mbstring'),
];
$baseUrl = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
    $scheme = $https ? 'https' : 'http';
    $docroot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs') ?: 'C:/xampp/htdocs'), '/');
    $approot = rtrim(str_replace('\\', '/', $APP_DIR), '/');
    $webpath = '';
    if ($docroot !== '/' && strpos($approot, $docroot) === 0) {
        $webpath = substr($approot, strlen($docroot));
    }
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $webpath;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StepStyle - Setup</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #1a1a2e; padding: 24px; }
  .wrap { max-width: 720px; margin: 0 auto; }
  .card { background: #fff; border-radius: 12px; padding: 28px 32px; margin-bottom: 18px;
          box-shadow: 0 2px 8px rgba(26,26,46,.08); }
  h1 { font-size: 22px; margin-bottom: 4px; color: #1a1a2e; }
  h2 { font-size: 15px; margin: 18px 0 10px; text-transform: uppercase; letter-spacing: .4px; color: #666; }
  p.sub { color: #777; font-size: 13px; margin-bottom: 16px; }
  .ok { color: #0a7d32; font-weight: 600; }
  .bad { color: #b3000f; font-weight: 600; }
  .warn { color: #b36b00; font-weight: 600; }
  ul.checks { list-style: none; }
  ul.checks li { padding: 4px 0; font-size: 14px; }
  label { display: block; font-size: 13px; font-weight: 600; margin: 10px 0 4px; }
  input { width: 100%; padding: 9px 11px; border: 1px solid #ccd2e0; border-radius: 8px; font-size: 14px; }
  .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  button { margin-top: 18px; background: #f5470d; color: #fff; border: 0; border-radius: 8px;
           padding: 11px 18px; font-size: 14px; font-weight: 600; cursor: pointer; }
  button:hover { background: #d93f06; }
  button.ghost { background: #e8ebf3; color: #1a1a2e; }
  button.danger { background: #b3000f; }
  .msg { padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 12px; }
  .msg.good { background: #e6f6ec; color: #0a7d32; }
  .msg.err { background: #fdecea; color: #b3000f; }
  .links a { display: inline-block; margin: 6px 10px 0 0; color: #1a1a2e; font-weight: 600; font-size: 14px; }
  .found { font-size: 14px; color: #444; }
  code { background: #f0f2f8; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
  form.inline { display: inline; }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>StepStyle &mdash; Setup</h1>
    <p class="sub">Guided installation for new machines. This page creates the database and imports sample data.</p>

    <?php foreach ($message as $m): ?><div class="msg good">&#10003; <?= htmlspecialchars($m) ?></div><?php endforeach; ?>
    <?php foreach ($errors as $e): ?><div class="msg err">&#10007; <?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <h2>1. Environment checks</h2>
    <ul class="checks">
      <li>PHP version: <span class="<?= $phpOk ? 'ok' : 'bad' ?>"><?= PHP_VERSION . ($phpOk ? ' (OK)' : ' (7.4+ required)') ?></span></li>
      <li>mysqli extension: <span class="<?= $ext['mysqli'] ? 'ok' : 'bad' ?>"><?= $ext['mysqli'] ? 'enabled' : 'missing' ?></span></li>
      <li>curl extension (needed for Khalti payments): <span class="<?= $ext['curl'] ? 'ok' : 'warn' ?>"><?= $ext['curl'] ? 'enabled' : 'missing (payments will fall back to sandbox)' ?></span></li>
      <li>mbstring extension: <span class="<?= $ext['mbstring'] ? 'ok' : 'warn' ?>"><?= $ext['mbstring'] ? 'enabled' : 'missing' ?></span></li>
      <li>Database config file: <code>db_config.php</code>
        <span class="<?= is_file($CONFIG_FILE) ? 'ok' : 'warn' ?>"><?= is_file($CONFIG_FILE) ? 'found' : 'will be created' ?></span></li>
    </ul>

    <h2>2. Database settings</h2>
    <form method="post" autocomplete="off">
      <div class="row2">
        <div>
          <label for="servername">Host</label>
          <input id="servername" name="servername" value="<?= htmlspecialchars($cfg['servername']) ?>">
        </div>
        <div>
          <label for="dbName">Database name</label>
          <input id="dbName" name="dbName" value="<?= htmlspecialchars($cfg['dbName']) ?>">
        </div>
      </div>
      <div class="row2">
        <div>
          <label for="username">Username</label>
          <input id="username" name="username" value="<?= htmlspecialchars($cfg['username']) ?>">
        </div>
        <div>
          <label for="password">Password</label>
          <input id="password" name="password" type="password" value="<?= htmlspecialchars($cfg['password']) ?>">
        </div>
      </div>
      <button type="submit" name="action" value="install">Save &amp; run installation</button>
    </form>

    <h2>3. Installation status</h2>
    <?php if ($installed): ?>
      <div class="msg good">Database <code><?= htmlspecialchars($cfg['dbName']) ?></code> is installed with
       <strong><?= $productCount ?></strong> sample products.
       <?php if ($productCount === 0): ?><span class="warn">(0 products - schema may be partial)</span><?php endif; ?></div>
      <p class="found">Your store is ready. Open:</p>
      <div class="links">
        <a href="<?= htmlspecialchars($baseUrl) ?>/index.php" target="_blank">&#9654; Storefront</a>
        <a href="<?= htmlspecialchars($baseUrl) ?>/admin/login.php" target="_blank">&#9654; Admin panel</a>
      </div>
    <?php elseif (empty($errors)): ?>
      <div class="msg err">Not installed yet - the database did not import. Check the settings above and try again.</div>
    <?php else: ?>
      <div class="msg err">Database not reachable. Fix the error above (usually start MySQL in XAMPP), then re-submit.</div>
    <?php endif; ?>

    <?php if ($installed): ?>
    <h2>4. Reset (optional)</h2>
    <p class="sub">Wipes the <code><?= htmlspecialchars($cfg['dbName']) ?></code> database and re-imports fresh sample data. All orders and users added during testing are removed.</p>
    <form method="post" onsubmit="return confirm('This DROPS the entire database and reinstalls sample data. Continue?');">
      <input type="hidden" name="servername" value="<?= htmlspecialchars($cfg['servername']) ?>">
      <input type="hidden" name="username" value="<?= htmlspecialchars($cfg['username']) ?>">
      <input type="hidden" name="password" value="<?= htmlspecialchars($cfg['password']) ?>">
      <input type="hidden" name="dbName" value="<?= htmlspecialchars($cfg['dbName']) ?>">
      <button type="submit" name="action" value="reset" class="danger">Reset database to sample data</button>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>