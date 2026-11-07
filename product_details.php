<?php
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if ($user_email) {
    $select_wishlist = execute_query($conn, "SELECT * FROM `wishlist` WHERE email = ?", "s", [$user_email]);
    $wishlist_count = mysqli_num_rows($select_wishlist);
    $select_cart = execute_query($conn, "SELECT * FROM `cart` WHERE email = ?", "s", [$user_email]);
    $cart_count = mysqli_num_rows($select_cart);
} else {
    $wishlist_count = 0;
    $cart_count = 0;
}

if (!isset($_GET['id'])) {
    header('location:index.php');
    exit();
}

$product_id = $_GET['id'];
$product_result = execute_query($conn, "SELECT * FROM products WHERE id = ?", "i", [$product_id]);

if (isset($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];
    $timestamp = date("Y-m-d H:i:s");
    $check_activity = execute_query($conn, "SELECT * FROM user_activity WHERE email = ? AND product_id = ? AND action = 'view'", "si", [$email, $product_id]);
    if (mysqli_num_rows($check_activity) == 0) {
        execute_query($conn, "INSERT INTO user_activity (email, product_id, action, timestamp) VALUES (?, ?, 'view', ?)", "sis", [$email, $product_id, $timestamp]);
    }
}

if (mysqli_num_rows($product_result) === 0) {
    echo "Product not found!";
    exit();
}

$row = mysqli_fetch_assoc($product_result);
$category = $row['category'];
$extra_images_string = $row['extra_images'];
$extra_images = !empty($extra_images_string) ? explode(',', $extra_images_string) : [];
$recommended = execute_query($conn, "SELECT * FROM products WHERE category = ? AND id != ? ORDER BY id DESC LIMIT 4", "si", [$category, $product_id]);

$sizes = !empty($row['size']) ? explode(', ', $row['size']) : [];
$colors = !empty($row['color']) ? explode(', ', $row['color']) : [];

$can_review = false;
if ($user_email) {
    $check_completed_order_query = execute_query($conn, "
        SELECT o.id FROM `order` o JOIN `order_items` oi ON o.id = oi.order_id
        WHERE o.email = ? AND o.status = 'completed' AND oi.product_id = ? LIMIT 1
    ", "si", [$user_email, $product_id]);

    if (mysqli_num_rows($check_completed_order_query) > 0) {
        $can_review = true;
    } else {
        $check_completed_order_legacy_query = execute_query($conn, "
            SELECT id FROM `order` WHERE email = ? AND status = 'completed' AND total_products LIKE ? LIMIT 1
        ", "ss", [$user_email, "%{$row['name']}%"]);
        if (mysqli_num_rows($check_completed_order_legacy_query) > 0) {
            $can_review = true;
        }
    }
}

if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_email'])) {
        header('location:userlogin.php');
        exit();
    }

    $user_email = $_SESSION['user_email'];
    $product_name = $row['name'];
    $product_price = $row['price'];
    $product_image = $row['image'];
    $product_quantity = 1;
    $product_user = $_POST['product_user'] ?? '';
    $product_size = $_POST['product_size'] ?? '';
    $product_color = $_POST['product_color'] ?? '';

    $check = execute_query($conn, "SELECT * FROM cart WHERE name = ? AND email = ? AND size = ? AND color = ?", "ssss", [$product_name, $user_email, $product_size, $product_color]);

    if (mysqli_num_rows($check) > 0) {
        $message[] = "Product already in cart!";
    } else {
        execute_query($conn, "INSERT INTO cart(product_id, name, price, image, quantity, email, user, size, color)
            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)", "isdsissss", [$row['id'], $product_name, $product_price, $product_image, $product_quantity, $user_email, $product_user, $product_size, $product_color]);
        $message[] = "Product added to cart!";
    }
}

if (isset($_POST['submit_review']) && isset($_SESSION['user_email']) && $can_review) { 
    $user_email = $_SESSION['user_email'];
    $rating = intval($_POST['rating']);
    $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);

    $check_existing_review = mysqli_query($conn, "SELECT * FROM reviews WHERE product_id = '$product_id' AND user_email = '$user_email'");
    if (mysqli_num_rows($check_existing_review) > 0) {
        $message[] = "You have already submitted a review for this product.";
    } else {
        if ($rating >= 1 && $rating <= 5) {
            mysqli_query($conn, "INSERT INTO reviews(product_id, user_email, rating, review_text)
                VALUES('$product_id', '$user_email', '$rating', '$review_text')");
            $message[] = "Thank you for your review!";
        } else {
            $message[] = "Invalid rating.";
        }
    }
} elseif (isset($_POST['submit_review']) && !isset($_SESSION['user_email'])) {
    $message[] = "Please log in to submit a review.";
} elseif (isset($_POST['submit_review']) && !$can_review) {
    $message[] = "You can only review this product after completing an order for it.";
}

$reviews = mysqli_query($conn, "SELECT * FROM reviews WHERE product_id = '$product_id' ORDER BY created_at DESC");
$avg_result = mysqli_query($conn, "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = '$product_id'");
$avg_data = mysqli_fetch_assoc($avg_result);
$average_rating = round($avg_data['avg_rating'], 1);
$total_reviews = mysqli_num_rows($reviews);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $row['name']; ?> - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="styles.css" rel="stylesheet">
    <link href="sections-storefront.css" rel="stylesheet">
</head>
<body>

<?php include 'header.php'; ?>

<?php
if (isset($message)) {
    foreach ($message as $msg) {
        echo '<div class="message">' . $msg . '</div>';
    }
}
?>

<main class="pd-container">
    <div class="pd-breadcrumb">
        <a href="index.php">Home</a> &rsaquo; <a href="products.php">Products</a> &rsaquo; <?php echo $row['name']; ?>
    </div>

    <div class="pd-section">
        <div class="pd-images">
            <div class="pd-thumbs">
                <div class="pd-thumb active">
                    <img src="uploaded_img/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                </div>
                <?php
                foreach ($extra_images as $extra_img) {
                    $extra_img = trim($extra_img);
                    if (!empty($extra_img)) {
                        echo '<div class="pd-thumb"><img src="uploaded_img/' . htmlspecialchars($extra_img) . '" alt="Product image"></div>';
                    }
                }
                ?>
            </div>
            <div class="pd-main-img">
                <img id="mainProductImage" src="uploaded_img/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
            </div>
        </div>

        <div class="pd-info">
            <h1 class="pd-title"><?php echo htmlspecialchars($row['name']); ?></h1>
            <div class="pd-brand">LIXN LEE Store</div>

            <div class="pd-rating">
                <div class="stars">
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $average_rating) echo '<i class="fas fa-star star"></i>';
                        elseif ($i - $average_rating < 1) echo '<i class="fas fa-star-half-alt star"></i>';
                        else echo '<i class="far fa-star star empty"></i>';
                    }
                    ?>
                </div>
                <span class="rating-count"><?php echo $total_reviews; ?> <?php echo $total_reviews == 1 ? 'rating' : 'ratings'; ?></span>
            </div>

            <div class="pd-price">
                <span class="currency">RS</span> <?php echo number_format($row['price'], 2); ?>
            </div>

            <div class="pd-desc">
                <?php echo htmlspecialchars($row['description'] ?? "No description available."); ?>
            </div>

            <div class="pd-features">
                <h4>Product Features:</h4>
                <ul>
                    <li>High quality materials</li>
                    <li>Excellent craftsmanship</li>
                    <li>Perfect for everyday use</li>
                    <li>Available in multiple options</li>
                </ul>
            </div>
        </div>

        <div class="pd-buybox">
            <div class="bb-price">RS <?php echo number_format($row['price'], 2); ?></div>
            <div class="bb-shipping"><i class="fas fa-truck"></i> FREE delivery by tomorrow</div>
            <div class="bb-stock"><i class="fas fa-check-circle"></i> In Stock</div>

            <form action="" method="post">
                <input type="hidden" name="product_user" value="<?php echo htmlspecialchars($row['user']); ?>">

                <?php if (!empty($sizes)): ?>
                    <span class="bb-label">Size</span>
                    <select name="product_size" required>
                        <?php foreach ($sizes as $size): ?>
                            <option value="<?php echo htmlspecialchars(trim($size)); ?>"><?php echo ucfirst(htmlspecialchars(trim($size))); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <?php if (!empty($colors)): ?>
                    <span class="bb-label">Color</span>
                    <select name="product_color" required>
                        <?php foreach ($colors as $color): ?>
                            <option value="<?php echo htmlspecialchars(trim($color)); ?>"><?php echo ucfirst(htmlspecialchars(trim($color))); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <button type="submit" name="add_to_cart" class="btn-add-cart"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
                <button type="button" class="btn-buy-now" onclick="this.form.submit()"><i class="fas fa-bolt"></i> Buy Now</button>
            </form>

            <div class="bb-delivery">
                <div><i class="fas fa-shipping-fast"></i> Fast, FREE delivery</div>
                <div><i class="fas fa-undo"></i> FREE Returns</div>
                <div><i class="fas fa-shield-alt"></i> Secure transaction</div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="pd-reviews">
        <div class="pd-reviews-header">
            <div>
                <h2 class="pd-reviews-title">Customer Reviews</h2>
                <div class="pd-rating" style="margin-top:8px;">
                    <div class="stars">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $average_rating) echo '<i class="fas fa-star star"></i>';
                            elseif ($i - $average_rating < 1) echo '<i class="fas fa-star-half-alt star"></i>';
                            else echo '<i class="far fa-star star empty"></i>';
                        }
                        ?>
                    </div>
                    <span class="rating-count"><?php echo $average_rating > 0 ? $average_rating . " out of 5" : "No ratings yet"; ?></span>
                </div>
            </div>
            <?php if (isset($_SESSION['user_email'])): ?>
                <?php if ($can_review): ?>
                    <button class="btn-write-review" onclick="toggleReviewForm()"><i class="fas fa-pen"></i> Write a Review</button>
                <?php else: ?>
                    <span style="font-size:0.85rem; color:#999;">Review available after purchase.</span>
                <?php endif; ?>
            <?php else: ?>
                <span style="font-size:0.85rem; color:#999;">Please <a href="userlogin.php" style="color:#8b6d77; font-weight: 600;">sign in</a> to review.</span>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['user_email']) && $can_review): ?>
            <div class="pd-review-form" id="reviewForm" style="display: none;">
                <h3 style="font-family:'Playfair Display',serif; margin-bottom:16px;">Write a Review</h3>
                <form action="" method="post">
                    <div style="margin-bottom:14px;">
                        <span class="bb-label">Overall Rating</span>
                        <select name="rating" required style="width:100%; padding:10px 14px; border:1px solid #eee; border-radius:10px; font-family:'Montserrat',sans-serif;">
                            <option value="">Select rating</option>
                            <option value="5">5 stars</option>
                            <option value="4">4 stars</option>
                            <option value="3">3 stars</option>
                            <option value="2">2 stars</option>
                            <option value="1">1 star</option>
                        </select>
                    </div>
                    <div style="margin-bottom:14px;">
                        <span class="bb-label">Your Review</span>
                        <textarea name="review_text" placeholder="What did you like or dislike?" required style="width:100%; padding:12px; border:1px solid #eee; border-radius:10px; min-height:80px; font-family:'Montserrat',sans-serif; resize:vertical;"></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn-add-cart">Submit Review</button>
                </form>
            </div>
        <?php endif; ?>

        <div>
            <?php if (mysqli_num_rows($reviews) > 0): ?>
                <?php while ($rev = mysqli_fetch_assoc($reviews)): ?>
                    <div class="pd-review-item">
                        <div class="pd-reviewer">
                            <div class="pd-reviewer-avatar"><?php echo strtoupper(substr($rev['user_email'], 0, 1)); ?></div>
                            <div>
                                <div class="pd-reviewer-name"><?php echo htmlspecialchars($rev['user_email']); ?></div>
                                <div class="pd-review-date"><?php echo date("F j, Y", strtotime($rev['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="pd-review-stars">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rev['rating'] ? '<i class="fas fa-star star"></i>' : '<i class="far fa-star star empty"></i>';
                            }
                            ?>
                        </div>
                        <div class="pd-review-text"><?php echo htmlspecialchars($rev['review_text']); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px 20px; color:#999;">
                    <i class="fas fa-comments" style="font-size:2.5rem; opacity:0.2; margin-bottom:16px; display:block;"></i>
                    <p>No customer reviews yet. Be the first!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recommended Products -->
    <?php
    $recommended_products = [];
    if (mysqli_num_rows($recommended) > 0) {
        while ($rec = mysqli_fetch_assoc($recommended)) {
            $recommended_products[] = $rec;
        }
    }
    ?>

    <?php if (!empty($recommended_products)): ?>
    <div class="pd-recommended">
        <h2>You May Also Like</h2>
        <div class="pd-rec-grid">
            <?php foreach ($recommended_products as $rec): ?>
            <a href="product_details.php?id=<?php echo htmlspecialchars($rec['id']); ?>" class="pd-rec-card">
                <img src="uploaded_img/<?php echo htmlspecialchars($rec['image']); ?>" alt="<?php echo htmlspecialchars($rec['name']); ?>">
                <div class="rec-body">
                    <h4><?php echo htmlspecialchars($rec['name']); ?></h4>
                    <div class="price">RS <?php echo number_format($rec['price'], 2); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
function toggleReviewForm() {
    const form = document.getElementById('reviewForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

window.onload = function() {
    const messageDivs = document.querySelectorAll('.message');
    messageDivs.forEach(div => {
        setTimeout(() => { div.style.display = 'none'; }, 5000);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    const thumbnails = document.querySelectorAll('.pd-thumbs .pd-thumb');
    const mainImage = document.getElementById('mainProductImage');
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', () => {
            thumbnails.forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
            mainImage.src = thumbnail.querySelector('img').src;
        });
    });
});
</script>

</body>
</html>
