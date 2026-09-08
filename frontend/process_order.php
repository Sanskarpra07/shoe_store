<?php
// process_order.php - Creates the order and routes to the chosen payment method
session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/payment_config.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

// --- Gather shipping data ---
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$slot    = trim($_POST['delivery_slot'] ?? '');
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
        'address' => $address, 'payment' => $payment, 'delivery_slot' => $slot
    ];
    header("Location: checkout.php");
    exit();
}

// Validate stock before placing order
foreach ($cart_items as $item) {
    if ($item['qty'] > $item['stock']) {
        $_SESSION['checkout_errors'] = ["Insufficient stock for {$item['product_name']}. Only {$item['stock']} available."];
        header("Location: checkout.php");
        exit();
    }
}

// --- Create the order record ---
$payment_status = ($payment === 'cod') ? 'completed' : 'pending';
$stmt = mysqli_prepare($conn,
    "INSERT INTO orders (customer_id, customer_name, customer_email, customer_phone, customer_address,
                         total_amount, payment_method, payment_status, transaction_id, delivery_slot, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 'pending')"
);
mysqli_stmt_bind_param($stmt, "issssdsss",
    $customer_id, $name, $email, $phone, $address, $total, $payment, $payment_status, $slot);
mysqli_stmt_execute($stmt);
$order_id = mysqli_insert_id($conn);

// --- Insert order items (store unit price, not line total) ---
foreach ($cart_items as $item) {
    $unit_price = $item['discount_price'] ?: $item['price'];
    $istmt = mysqli_prepare($conn,
        "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($istmt, "iiid", $order_id, $item['id'], $item['qty'], $unit_price);
    mysqli_stmt_execute($istmt);
}

// For online payments, hold the stock until payment completes
if ($payment === 'cod') {
    // Reduce stock atomically for COD
    mysqli_begin_transaction($conn);
    foreach ($cart_items as $item) {
        $sstmt = mysqli_prepare($conn,
            "UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ? AND stock >= ?");
        mysqli_stmt_bind_param($sstmt, "iii", $item['qty'], $item['id'], $item['qty']);
        mysqli_stmt_execute($sstmt);
    }
    mysqli_commit($conn);
    $_SESSION['cart'] = []; // clear the cart
    $_SESSION['order_success'] = "Order #$order_id placed successfully! Total: रु " . number_format($total, 2)
        . ". You will pay <strong>Cash on Delivery</strong> when your order arrives.";
    $_SESSION['last_order'] = ['id' => $order_id, 'slot' => $slot, 'total' => $total];
    header("Location: order_success.php");
    exit();
}

// Save the order id so payment callbacks can update this order
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
    <p style="text-align:center; font-family:sans-serif; margin-top:40vh;">Redirecting to eSewa payment gateway...</p>
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

    // Build product_details from cart items (per Khalti docs)
    $product_details = [];
    foreach ($cart_items as $item) {
        $unit_price = (int)round((float)($item['discount_price'] ?: $item['price']) * 100);
        $line_total = $unit_price * (int)$item['qty'];
        $product_details[] = [
            'identity'   => (string)$item['id'],
            'name'       => $item['product_name'],
            'total_price'=> $line_total,
            'quantity'   => (int)$item['qty'],
            'unit_price' => $unit_price
        ];
    }

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
        ],
        'product_details' => $product_details
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, KHALTI_INITIATE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Key ' . KHALTI_SECRET_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!empty($data['payment_url'])) {
        header("Location: " . $data['payment_url']);
        exit();
    }

    // Surface the actual error message if available
    $khalti_error = $data['detail'] ?? $data['error_key'] ?? '';
    $msg = "Khalti payment gateway could not be reached.";
    if (!empty($khalti_error)) {
        $msg .= " (" . htmlspecialchars($khalti_error) . ")";
    } elseif (!empty($curl_err)) {
        $msg .= " (" . htmlspecialchars($curl_err) . ")";
    }
    $_SESSION['checkout_errors'] = [$msg . " Please try again or choose a different payment method."];
    header("Location: checkout.php");
    exit();
}