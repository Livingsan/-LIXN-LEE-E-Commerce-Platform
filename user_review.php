<?php
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

$message = [];

if ($user_email) {
    $select_wishlist_count_nav = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist_count_nav);

    $select_cart_count_nav = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart_count_nav);
} else {
    $wishlist_count = 0;
    $cart_count = 0;
}

if (!isset($_SESSION['user_email'])) {
    header('location:userlogin.php'); 
    exit();
}

$email = $_SESSION['user_email'];

if (isset($_GET['delete_review'])) {
    $review_id = intval($_GET['delete_review']);
    $delete_query = mysqli_query($conn, "DELETE FROM `reviews` WHERE id = '$review_id' AND user_email = '$email'");
    if ($delete_query) {
        $message[] = ['text' => 'Review deleted successfully!', 'type' => 'success'];
    } else {
        $message[] = ['text' => 'Could not delete review. Please try again.', 'type' => 'error'];
    }
    
}


$user_reviews_result = mysqli_query($conn, "
    SELECT r.id AS review_id, r.rating, r.review_text, r.created_at,
           p.name AS product_name, p.image AS product_image, p.id AS product_id
    FROM `reviews` r
    JOIN `products` p ON r.product_id = p.id
    WHERE r.user_email = '$email'
    ORDER BY r.created_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Reviews - LIXN LEE</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>

    <link href="styles.css" rel="stylesheet">
    <link href="premium_account.css" rel="stylesheet">
</head>
<body>

    <?php
    if(!empty($message)){
        foreach($message as $msg_item){
            echo '<div class="message ' . htmlspecialchars($msg_item['type']) . '"><span>' . htmlspecialchars($msg_item['text']) . '</span><i class="fas fa-times" onclick="this.parentElement.style.display = \'none\';"></i></div>';
        }
    }
    ?>

    <?php include 'header.php'; ?>
    <div class="user-page-wrapper">
        <div class="user-page-container">
            <?php include 'user_sidebar.php'; ?>
            <main class="user-content">
                <section class="user-section active-section">
                    <div class="my-reviews-page" style="padding: 0; min-height: auto;">
                        <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-color);">My Reviews</h2>

        <?php
        if (mysqli_num_rows($user_reviews_result) > 0) {
        ?>
            <div class="reviews-grid">
                <?php
                while ($review = mysqli_fetch_assoc($user_reviews_result)) {
                ?>
                    <div class="review-card">
                        <div class="review-product-info">
                            <img src="uploaded_img/<?php echo htmlspecialchars($review['product_image']); ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>" onerror="this.onerror=null;this.src='https://placehold.co/70x70/A9A9A9/FFFFFF?text=No+Image';">
                            <div class="review-product-details">
                                <h3><?php echo htmlspecialchars($review['product_name']); ?></h3>
                                <span>Reviewed on: <?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="review-rating">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $review['rating']) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>'; 
                                }
                            }
                            ?>
                        </div>

                        <p class="review-text">"<?php echo nl2br(htmlspecialchars($review['review_text'])); ?>"</p>

                        <div class="review-actions">
                            <a href="product_details.php?id=<?php echo htmlspecialchars($review['product_id']); ?>" class="view-product-btn">
                                <i class="fas fa-eye"></i> View Product
                            </a>
                            <a href="user_review.php?delete_review=<?php echo htmlspecialchars($review['review_id']); ?>" class="delete-review-btn" onclick="return confirm('Are you sure you want to delete this review?');">
                                <i class="fas fa-trash"></i> Delete Review
                            </a>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
        <?php
        } else {
        ?>
            <div class="empty-reviews-message">
                <p>You haven't submitted any reviews yet.</p>
                <a href="products.php">Discover products to review!</a>
            </div>
        <?php
        }
        ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 500); 
                }, 5000); 
            });
        });
    </script>
</body>
</html>
