<?php session_start();
require_once '../db.php';
?>
<html>
<head>
    <title>Login - Shoe Store Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <form method="post" action="../process_login.php">
        <?= csrf_field() ?>
        <?php
        if (!empty($_SESSION['error_message'])) {
        echo '<p style="color: red; text-align: center;">' . htmlspecialchars($_SESSION['error_message']). '</p>';
        unset($_SESSION['error_message']);
    }
    ?>
        <div class="login-form">
            <div class="login-header">
                <header>Shoe Store Login</header>
            </div>
            <div class="input-box">
                <label for="text">Username</label>
                <input type="text" name="username" id="text" class="input-field" placeholder="Username" autocomplete="username" required>
            </div>
            <div class="input-box">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="input-field" placeholder="Password" autocomplete="current-password" required>
            </div>
            <div class="remember">
                <section>
                    <input type="checkbox" id="show-password" onclick="togglePassword()">
                    <label for="show-password">Show Password</label>
                </section>
            </div> 
            <div class="login-button">
                <button type="submit" id="submit">Login</button>
            </div>
            <div style="text-align:center; margin-top:15px;">
                <a href="../index.php" style="color:#555;">← Back to Store</a>
            </div>
        </div>
</form>
<script>
function togglePassword() {
    const passwordField = document.getElementById("password");
    const checkbox = document.getElementById("show-password");
    if (checkbox.checked) {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}
</script>
</body>
</html>
