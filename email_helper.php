<?php
function send_order_email($email, $name, $order_id, $total) {
    $subject = "StepStyle - Order #$order_id Confirmed";
    $message = "Dear $name,\n\nThank you for your order #$order_id!\nTotal: $" . number_format($total, 2) . "\n\nYou can track your order at: " . base_url("track_order.php") . "\n\nThank you,\nStepStyle Team";
    $headers = "From: noreply@stepstyle.com\r\n";
    $result = @mail($email, $subject, $message, $headers);
    return $result;
}

function send_welcome_email($email, $name) {
    $subject = "Welcome to StepStyle!";
    $message = "Dear $name,\n\nThank you for registering at StepStyle!\nBrowse our collection: " . base_url("shop.php") . "\n\nThank you,\nStepStyle Team";
    $headers = "From: noreply@stepstyle.com\r\n";
    return @mail($email, $subject, $message, $headers);
}