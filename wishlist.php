<?php
@include 'config.php';
session_start();

$user_email = $_SESSION['user_email'] ?? '';

if (!$user_email) {
    header('location:userlogin.php');
    exit();
}

// Remove from Wishlist
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    execute_query($conn, "DELETE FROM wishlist WHERE product_id = ? AND email = ?", "is", [$remove_id, $user_email]);
    header('location:wishlist.php');
    exit();
}

// Add to Cart from Wishlist
if (isset($_GET['add_to_cart'])) {
    $product_id = intval($_GET['add_to_cart']);
    $res = execute_query($conn, "SELECT * FROM products WHERE id = ?", "i", [$product_id]);
    if ($row = mysqli_fetch_assoc($res)) {
        // Simple check for existing cart item
        $check = execute_query($conn, "SELECT * FROM cart WHERE product_id = ? AND email = ?", "is", [$product_id, $user_email]);
        if (mysqli_num_rows($check) == 0) {
            execute_query($conn, "INSERT INTO cart (product_id, name, price, image, quantity, email) VALUES (?, ?, ?, ?, 1, ?)", "isdss", [$product_id, $row['name'], $row['price'], $row['image'], $user_email]);
        }
        execute_query($conn, "DELETE FROM wishlist WHERE product_id = ? AND email = ?", "is", [$product_id, $user_email]);
    }
    header('location:cart.php');
    exit();
}

$wishlist_items = execute_query($conn, "SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.email = ?", "s", [$user_email]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link href="premium_account.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="user-page-wrapper">
        <div class="user-page-container">
            <?php include 'user_sidebar.php'; ?>
            <main class="user-content">
                <section class="user-section active-section">
                    <h1 style="font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 30px; color: var(--primary-color); text-align: center;">My Wishlist</h1>
                    
                    <div class="wishlist-grid">
                        <?php if (mysqli_num_rows($wishlist_items) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($wishlist_items)): ?>
                                <div class="wishlist-card">
                                    <a href="?remove=<?php echo $row['id']; ?>" class="btn-remove-wishlist" onclick="return confirm('Remove from wishlist?')"><i class="fas fa-times"></i></a>
                                    <img src="uploaded_img/<?php echo $row['image']; ?>" alt="" class="wishlist-item-image">
                                    <div class="wishlist-content">
                                        <h3 class="wishlist-item-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                                        <p class="wishlist-item-price">RS <?php echo number_format($row['price'], 2); ?></p>
                                        <div class="wishlist-actions">
                                            <a href="?add_to_cart=<?php echo $row['id']; ?>" class="btn-premium btn-sm" style="display:block; text-align:center; padding: 10px; border-radius: 4px; text-decoration: none;">Add to Cart</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; grid-column: 1/-1; padding: 40px; color: #777;">Your wishlist is empty. <a href="products.php" style="color: var(--primary-color);">Discover products</a></p>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>
