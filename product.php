<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: shop.php");
    exit();
}

$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.name AS category_name, b.name AS brand_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    header("Location: shop.php");
    exit();
}

$related = mysqli_query($conn,
    "SELECT p.*, b.name AS brand_name
     FROM products p
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.category_id = " . (int)$row['category_id'] . " AND p.id != $id
     LIMIT 4"
);

$approved_reviews = mysqli_query($conn,
    "SELECT r.*, c.full_name FROM reviews r
     LEFT JOIN customers c ON r.customer_id = c.id
     WHERE r.product_id = $id AND r.status = 'approved'
     ORDER BY r.created_at DESC LIMIT 20"
);

$review_msg = '';
$review_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!is_customer_logged_in()) {
        $review_err = "Please <a href='login.php'>login</a> to submit a review.";
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $cid = (int)$_SESSION['customer_id'];
        if ($rating < 1 || $rating > 5) {
            $review_err = "Please select a star rating (1 to 5).";
        } elseif (strlen($comment) < 5) {
            $review_err = "Review must be at least 5 characters.";
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO reviews (product_id, customer_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($stmt, "iiis", $id, $cid, $rating, $comment);
            mysqli_stmt_execute($stmt);
            $review_msg = "Thank you! Your review has been submitted and is waiting for approval.";
        }
    }
}

$already_reviewed = false;
if (is_customer_logged_in()) {
    $cid = (int)$_SESSION['customer_id'];
    $chk = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id = $id AND customer_id = $cid");
    $already_reviewed = mysqli_num_rows($chk) > 0;
}

$in_wishlist = false;
if (is_customer_logged_in()) {
    $wid = (int)$_SESSION['customer_id'];
    $wchk = mysqli_query($conn, "SELECT id FROM wishlists WHERE customer_id = $wid AND product_id = $id");
    $in_wishlist = mysqli_num_rows($wchk) > 0;
}

site_header($row['product_name'] . ' - StepStyle');
?>

<p><a href="index.php">&laquo; Home</a> | <a href="shop.php">Shop</a></p>

<table class="table">
    <tr>
        <td style="width:320px;" class="text-center">
            <?php if (!empty($row['image'])): ?>
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" style="max-width:320px;">
            <?php else: ?>
                <span style="color:#999;">No Image</span>
            <?php endif; ?>
        </td>
        <td style="vertical-align:top; padding:15px;">
            <h2 class="page-title" style="border:none; margin-bottom:8px;"><?= htmlspecialchars($row['product_name']) ?></h2>
            <p><strong>Brand:</strong> <?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?><br>
               <strong>Category:</strong> <?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></p>
            <p style="font-size:22px; color:#c62828;">
                <?php if ($row['discount_price']): ?>
                    <span style="text-decoration:line-through; color:#999; font-size:15px;"><?= price_label($row['price']) ?></span>
                    <?= price_label($row['discount_price']) ?>
                    <span class="small" style="color:#2e7d32;">(Save <?= round((($row['price'] - $row['discount_price']) / $row['price']) * 100) ?>%)</span>
                <?php else: ?>
                    <?= price_label($row['price']) ?>
                <?php endif; ?>
            </p>
            <p>
                <?php if ($row['stock'] > 0): ?>
                    <span style="color:#2e7d32;"><strong>In Stock</strong> (<?= $row['stock'] ?> available)</span>
                <?php else: ?>
                    <span style="color:#c62828;"><strong>Out of Stock</strong></span>
                <?php endif; ?>
            </p>
            <?php if ($row['description']): ?>
                <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
            <?php endif; ?>

            <?php if ($row['stock'] > 0): ?>
            <form method="POST" action="add_to_cart.php">
                <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                <label for="qty"><strong>Quantity:</strong></label>
                <input type="number" name="quantity" id="qty" value="1" min="1" max="<?= $row['stock'] ?>" style="width:70px; padding:5px;">
                <button type="submit" class="btn btn-green">Add to Cart</button>
            </form>
            <?php endif; ?>

            <?php if (is_customer_logged_in()): ?>
            <form method="POST" action="add_to_wishlist.php" style="margin-top:10px;">
                <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn <?= $in_wishlist ? 'btn-red' : 'btn' ?>">
                    <?= $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>
                </button>
            </form>
            <?php else: ?>
                <p class="small">Login to add this product to your <a href="login.php">wishlist</a>.</p>
            <?php endif; ?>
        </td>
    </tr>
</table>

<h3 class="section-title">Related Products</h3>
<div class="product-row">
    <?php while ($r = mysqli_fetch_assoc($related)): ?>
    <div class="product-box">
        <div class="p-img">
            <?php if (!empty($r['image'])): ?>
                <img src="<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['product_name']) ?>">
            <?php else: ?>
                <span style="color:#999;">No Image</span>
            <?php endif; ?>
        </div>
        <div class="p-name"><?= htmlspecialchars($r['product_name']) ?></div>
        <div class="p-price"><?= price_label($r['discount_price'] ?: $r['price']) ?></div>
        <a class="btn btn-small" href="product.php?id=<?= $r['id'] ?>">View</a>
    </div>
    <?php endwhile; ?>
</div>

<h3 class="section-title">Customer Reviews</h3>

<?php if ($review_msg): ?>
    <div class="msg-success"><?= $review_msg ?></div>
<?php endif; ?>
<?php if ($review_err): ?>
    <div class="msg-error"><?= $review_err ?></div>
<?php endif; ?>

<?php if (!is_customer_logged_in()): ?>
    <p><a href="login.php">Login</a> to share your review.</p>
<?php elseif ($already_reviewed): ?>
    <p><em>You have already reviewed this product.</em></p>
<?php else: ?>
    <form method="POST" action="product.php?id=<?= $id ?>" class="form-box" style="margin:0 0 20px 0; width:600px;">
        <h3>Write a Review</h3>
        <div class="form-group">
            <label>Rating (1 to 5 stars)</label>
            <select name="rating">
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Very Poor</option>
            </select>
        </div>
        <div class="form-group">
            <label>Your Review</label>
            <textarea name="comment" rows="3" required placeholder="How is the quality, fit, comfort...?"></textarea>
        </div>
        <button type="submit" name="submit_review" class="btn">Submit Review</button>
    </form>
<?php endif; ?>

<table class="table">
    <tr>
        <th style="width:150px;">Customer</th>
        <th style="width:80px;">Rating</th>
        <th>Review</th>
        <th style="width:120px;">Date</th>
    </tr>
    <?php if (mysqli_num_rows($approved_reviews) === 0): ?>
        <tr>
            <td colspan="4" class="text-center">No reviews yet. Be the first to review this product!</td>
        </tr>
    <?php endif; ?>
    <?php while ($rv = mysqli_fetch_assoc($approved_reviews)): ?>
    <tr>
        <td><?= htmlspecialchars($rv['full_name'] ?? 'Customer') ?></td>
        <td class="text-center"><?= str_repeat('★', (int)$rv['rating']) ?><span style="color:#ccc;"><?= str_repeat('☆', 5 - (int)$rv['rating']) ?></span></td>
        <td><?= nl2br(htmlspecialchars($rv['comment'])) ?></td>
        <td class="text-center"><?= date('d M Y', strtotime($rv['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<?php site_footer(); ?>