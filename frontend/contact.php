<?php
require_once __DIR__ . '/../backend/auth_helper.php';
$active = 'contact';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - StepStyle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/frontend.css" rel="stylesheet">
</head>
<body>
    <?php frontend_navbar('contact'); ?>
    <div class="container py-5">
        <h2 class="fw-bold mb-2">Contact Us</h2>
        <p class="text-muted mb-4">Have a question? Reach out to us anytime.</p>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Our Location</h5>
                    <p class="mb-1">Durbar Marg, Kathmandu, Nepal</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-envelope-fill text-primary me-2"></i>Email</h5>
                    <p class="mb-1">info@stepstyle.com</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-telephone-fill text-success me-2"></i>Phone</h5>
                    <p class="mb-1">+977-1-456789</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-clock-fill text-warning me-2"></i>Hours</h5>
                    <p class="mb-1">Sun - Fri: 9:00 AM - 8:00 PM</p>
                </div>
            </div>
        </div>
    </div>
    <?php frontend_footer(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>