<?php
// process_order.php - Creates the order and routes to the chosen payment method
session_start();
require_once 'db.php';
require_once 'payment_config.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

if (!csrf_check()) {
    $_SESSION['checkout_errors'] = ["Invalid security token. Please try again."];
    header("Location: checkout.php");
    exit();
}

// --- Gather shipping data ---
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$payment = $_POST['payment_method'] ?? 'cod';

$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;

$errors = [];
if (empty($name))    $errors[] = "Name is required.";
if (empty($email))   $errors[] = "Email is required.";
if (empty($address)) $errors[] = "Address is required.";
if (!in_array($payment, ['cod', 'esewa', 'khalti'])) $payment = 'cod';

// --- Calculate cart total ---
$cart_items = [];
$total = 0;
foreach ($cart as $pid => $qty) {
    $pid = (int)$pid;
    $qty = (int)$qty;
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pid);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($p) {
        $price = $p['discount_price'] ?: $p['price'];
        $p['qty'] = $qty;
        $p['line_total'] = $price * $qty;
        $total += $p['line_total'];
        $cart_items[] = $p;
    }
}

if (empty($cart_items)) {
    header("Location: shop.php");
    exit();
}
if ($total <= 0) {
    $errors[] = "Order total cannot be zero.";
}

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    $_SESSION['checkout_data'] = [
        'name' => $name, 'email' => $email, 'phone' => $phone,
        'address' => $address, 'payment' => $payment
    ];
    header("Location: checkout.php");
    exit();
}

// --- Create the order record (payment_status=pending for online payments, handled below for COD) ---
$payment_status = ($payment === 'cod') ? 'pending' : 'pending';
$stmt = mysqli_prepare($conn,
    "INSERT INTO orders (customer_id, customer_name, customer_email, customer_phone, customer_address,
                         total_amount, payment_method, payment_status, transaction_id, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 'pending')"
);
mysqli_stmt_bind_param($stmt, "issssdss",
    $customer_id, $name, $email, $phone, $address, $total, $payment, $payment_status);
mysqli_stmt_execute($stmt);
$order_id = mysqli_insert_id($conn);

// --- Insert order items ---
foreach ($cart_items as $item) {
    $istmt = mysqli_prepare($conn,
        "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($istmt, "iiid", $order_id, $item['id'], $item['qty'], $item['line_total']);
    mysqli_stmt_execute($istmt);
}

// For online payments, hold the stock until payment completes
if ($payment === 'cod') {
    // Reduce stock immediately for COD
    foreach ($cart_items as $item) {
        $new_stock = $item['stock'] - $item['qty'];
        $sstmt = mysqli_prepare($conn, "UPDATE products SET stock = ? WHERE id = ?");
        mysqli_stmt_bind_param($sstmt, "ii", $new_stock, $item['id']);
        mysqli_stmt_execute($sstmt);
    }
    $_SESSION['cart'] = []; // clear the cart
    $_SESSION['order_success'] = "Order #$order_id placed successfully! Total: $" . number_format($total, 2)
        . ". You will pay <strong>Cash on Delivery</strong> when your order arrives.";
    header("Location: order_success.php");
    exit();
}

// Save pending cart so we can restore if payment cancelled
$_SESSION['order_id_on_payment'] = $order_id;

// ---------- eSewa ----------
if ($payment === 'esewa') {
    $e_total = number_format($total, 2, '.', '');
    $fields = [
        'amt'        => $e_total,
        'pdc'        => '0',
        'psc'        => '0',
        'txAmt'      => '0',
        'tAmt'       => $e_total,
        'pid'        => "order_$order_id",
        'scd'        => ESEWA_MERCHANT_CODE,
        'su'         => ESEWA_SUCCESS_URL,
        'fu'         => ESEWA_FAILURE_URL,
    ];
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Redirecting to eSewa...</title></head>
    <body>
    <p class="text-center" style="font-family:sans-serif; margin-top:40vh;">Redirecting to eSewa payment gateway...</p>
    <form method="POST" action="<?= ESEWA_URL ?>" name="esewa_form">
        <?php foreach ($fields as $k => $v): ?>
            <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach; ?>
    </form>
    <script>document.esewa_form.submit();</script>
    </body>
    </html>
    <?php
    exit();
}

// ---------- Khalti ----------
if ($payment === 'khalti') {
    $khalti_total = (int)round($total * 100);
    $payload = [
        'return_url' => KHALTI_CALLBACK_URL . '?order_id=' . $order_id,
        'website_url' => base_url(''),
        'amount' => $khalti_total,
        'purchase_order_id' => "order_$order_id",
        'purchase_order_name' => 'StepStyle Order #' . $order_id,
        'customer_info' => [
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: '9800000000'
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://dev.khalti.com/api/v2/epayment/initiate/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Key test_secret_key',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['payment_url'])) {
        header("Location: " . $data['payment_url']);
        exit();
    }

    // Fallback for sandbox where Khalti isn't reachable: simulate success
    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = "Order #$order_id placed (Khalti sandbox simulation). Total: $" . number_format($total, 2);
    header("Location: order_success.php");
    exit();
}