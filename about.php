<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

site_header('About Us - StepStyle', 'about');
?>

<h2 class="page-title">About StepStyle</h2>
<p>StepStyle is an online shoe store developed as a 5th semester BCA project. It is your one-stop
destination for premium footwear - running, casual, sports, formal shoes and boots from the
world's best brands, delivered to your doorstep.</p>

<h3 class="section-title">Our Story</h3>
<p>Founded in Kathmandu, StepStyle brings the world's best footwear brands to your doorstep.
From everyday sneakers to premium formal wear, we curate shoes that match your style and comfort.</p>

<h3 class="section-title">Our Mission</h3>
<p>To make premium footwear accessible to everyone in Nepal through a seamless, trustworthy
online shopping experience.</p>

<h3 class="section-title">Why Choose Us</h3>
<table class="table" style="width:600px;">
    <tr><td>&#10003; Premium Brands (Nike, Adidas, Puma, Reebok, New Balance)</td></tr>
    <tr><td>&#10003; Authentic Products</td></tr>
    <tr><td>&#10003; Secure Payments (Cash on Delivery, eSewa, Khalti)</td></tr>
    <tr><td>&#10003; Free Delivery Inside Kathmandu Valley</td></tr>
    <tr><td>&#10003; Easy Order Tracking</td></tr>
</table>

<h3 class="section-title">This Project</h3>
<p>StepStyle is built with <strong>HTML, CSS, JavaScript, PHP and MySQL</strong> running on
Apache (XAMPP). It includes customer registration with email (OTP) verification, product and
category management, wishlist, shopping cart, online payments, delivery slots, order tracking
and a complete admin panel.</p>

<?php site_footer(); ?>