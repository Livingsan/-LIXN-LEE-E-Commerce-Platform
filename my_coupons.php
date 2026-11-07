<?php
include 'config.php';
session_start();

$user_email_nav = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if ($user_email_nav) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email_nav'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email_nav'");
    $cart_count = mysqli_num_rows($select_cart);
} else {
    $wishlist_count = 0;
    $cart_count = 0;
}

if (!isset($_SESSION['user_email'])) {
    echo "Please log in to view your coupons.";
    exit();
}

$user_email = $_SESSION['user_email']; 
$today = date('Y-m-d');

$query = "
    SELECT *
    FROM coupons
    WHERE is_active = 1
    AND expiry_date >= '$today'
    AND (target_email IS NULL OR target_email = '' OR target_email = '$user_email')
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Coupons - LIXN LEE</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>

    <link href="styles.css" rel="stylesheet">
    <link href="premium_account.css" rel="stylesheet">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="user-page-wrapper">
        <div class="user-page-container">
            <?php include 'user_sidebar.php'; ?>
            <main class="user-content">
                <section class="user-section active-section">
                    <h1 class="page-title" style="margin-top: 0;">Your Exclusive Offers 🎁</h1>

        <div class="coupons-grid">
            <?php
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='coupon-card'>";
                    echo "<div class='coupon-code-section'>";
                    echo "<h3>" . htmlspecialchars($row['code']) . "</h3>";
                    echo "</div>";
                    echo "<div class='coupon-details'>";
                    echo "<div class='coupon-value'>" . htmlspecialchars($row['discount']) . "% OFF</div>";
                    echo "<div class='coupon-meta'>";
                    echo "<p><i class='fas fa-calendar-alt'></i> Valid: " . date('M j, Y', strtotime($row['valid_from'])) . "</p>";
                    echo "<p><i class='fas fa-clock'></i> Expires: " . date('M j, Y', strtotime($row['expiry_date'])) . "</p>";
                    echo "</div></div>";
                    echo "<div class='coupon-status'>ACTIVE</div>";
                    echo "</div>";
                }
            } else {
                echo "<p class='no-coupons'>Looks like you don't have any active coupons right now. Please check back later for exciting offers! ✨</p>";
            }
            ?>
        </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>