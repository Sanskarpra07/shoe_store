<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

site_header('Contact Us - StepStyle', 'contact');
?>

<h2 class="page-title">Contact Us</h2>
<p>Have a question about an order, a product or our services? Reach out to us anytime.</p>

<table class="table" style="width:550px;">
    <tr>
        <th style="width:200px;">Our Location</th>
        <td>Kathmandu, Nepal</td>
    </tr>
    <tr>
        <th>Email</th>
        <td>info@stepstyle.com</td>
    </tr>
    <tr>
        <th>Phone</th>
        <td>+977-1-456789</td>
    </tr>
    <tr>
        <th>Hours</th>
        <td>Sun - Fri: 9:00 AM - 8:00 PM</td>
    </tr>
</table>

<div class="form-box" style="width:550px;">
    <h3>Send Us a Message</h3>
    <form method="POST" action="contact.php">
        <div class="form-group">
            <label>Your Name *</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Your Email *</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Message *</label>
            <textarea name="message" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn">Send Message</button>
    </form>
</div>

<?php site_footer(); ?>