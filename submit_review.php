<?php
@include 'config.php';
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_email = $_SESSION['user_email'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);

    // Check product is purchased by user
    $product = mysqli_query($conn, "SELECT name FROM products WHERE id = '$product_id'");
    if (!$product || mysqli_num_rows($product) == 0) {
        die("Invalid product.");
    }

    $product_name = mysqli_fetch_assoc($product)['name'];
    $escaped_name = mysqli_real_escape_string($conn, $product_name);

    $check_order = mysqli_query($conn, "SELECT 1 FROM `order` WHERE email = '$user_email' AND total_products LIKE '%$escaped_name%'");

    if (mysqli_num_rows($check_order) === 0) {
        die("You cannot review this product because you haven't purchased it.");
    }

    $existing = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id = '$product_id' AND user_email = '$user_email'");
    if (mysqli_num_rows($existing) > 0) {
        die("You’ve already submitted a review for this product.");
    }

    $insert = mysqli_query($conn, "INSERT INTO reviews (product_id, user_email, rating, review_text) 
                                   VALUES ('$product_id', '$user_email', '$rating', '$review_text')");

    if ($insert) {
        header("Location: product_details.php?id=$product_id&review=success");
    } else {
        echo "Failed to submit review. Please try again.";
    }
}
?>
