<?php
@include 'config.php'; 
session_start();

if (!isset($_SESSION['user_email'])) {
    header('location:userlogin.php');
    exit();
}

$user_email = $_SESSION['user_email'];

// Add Item via GET (from Search)
if (isset($_GET['add'])) {
    $p_id = intval($_GET['add']);
    $p_query = execute_query($conn, "SELECT * FROM products WHERE id = ?", "i", [$p_id]);
    if ($p_row = mysqli_fetch_assoc($p_query)) {
        $p_name = $p_row['name'];
        $p_price = $p_row['price'];
        $p_image = $p_row['image'];
        
        $check_cart = execute_query($conn, "SELECT * FROM cart WHERE product_id = ? AND email = ?", "is", [$p_id, $user_email]);
        if (mysqli_num_rows($check_cart) == 0) {
            execute_query($conn, "INSERT INTO cart (product_id, name, price, image, quantity, email) VALUES (?, ?, ?, ?, 1, ?)", "isdss", [$p_id, $p_name, $p_price, $p_image, $user_email]);
        }
    }
    header('location:cart.php');
    exit();
}

// Initial Counts
$select_cart = execute_query($conn, "SELECT * FROM `cart` WHERE email = ?", "s", [$user_email]);
$cart_count = mysqli_num_rows($select_cart); 

$subtotal = 0;
$total_items_in_cart = 0;

if ($cart_count > 0) {
    while ($fetch_summary_cart = mysqli_fetch_assoc($select_cart)) {
        $item_total = $fetch_summary_cart['price'] * $fetch_summary_cart['quantity'];
        $subtotal += $item_total;
        $total_items_in_cart += $fetch_summary_cart['quantity'];
    }
}

// Remove Item Logic
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    execute_query($conn, "DELETE FROM `cart` WHERE id = ? AND email = ?", "is", [$remove_id, $user_email]);
    header('location:cart.php'); 
    exit();
}

// Update Cart Logic
if (isset($_POST['update_cart'])) {
    $cart_id = intval($_POST['cart_id']);
    $new_quantity = max(1, intval($_POST['new_quantity'])); 
    execute_query($conn, "UPDATE `cart` SET quantity = ? WHERE id = ? AND email = ?", "iis", [$new_quantity, $cart_id, $user_email]);
    header('location:cart.php'); 
    exit();
}

// Clear All Logic
if (isset($_GET['clear_all'])) {
    execute_query($conn, "DELETE FROM `cart` WHERE email = ?", "s", [$user_email]);
    header('location:cart.php');
    exit();
}

// Re-fetch for display
$select_cart = execute_query($conn, "SELECT * FROM `cart` WHERE email = ?", "s", [$user_email]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link href="sections-storefront.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="cart-page container">
        <h1><i class="fas fa-shopping-bag"></i>Shopping Cart</h1>
        
        <div class="cart-grid">
            <div class="cart-items-card">
                <?php if (mysqli_num_rows($select_cart) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($select_cart)): ?>
                        <div class="cart-item">
                            <img src="uploaded_img/<?php echo $item['image']; ?>" alt="">
                            <div>
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cart-item-meta">
                                    <?php if(!empty($item['color'])): ?>Color: <?php echo $item['color']; ?><?php endif; ?>
                                    <?php if(!empty($item['size'])): ?> | Size: <?php echo $item['size']; ?><?php endif; ?>
                                </div>
                                <div class="cart-item-price">RS <?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            <form action="" method="post">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <div class="qty-control">
                                    <input type="number" name="new_quantity" value="<?php echo $item['quantity']; ?>" min="1" onchange="this.form.submit()">
                                </div>
                                <input type="hidden" name="update_cart" value="1">
                            </form>
                            <div style="text-align:right;">
                                <div class="cart-item-total">RS <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                <a href="?remove=<?php echo $item['id']; ?>" class="cart-item-remove" onclick="return confirm('Remove item?')"><i class="fas fa-trash-alt"></i> Remove</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <div class="cart-actions">
                        <a href="products.php" class="cart-continue"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                        <a href="?clear_all" class="cart-clear" onclick="return confirm('Clear all items?')"><i class="fas fa-trash"></i> Clear Cart</a>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:60px 20px;">
                        <i class="fas fa-shopping-bag" style="font-size:3rem; color:rgba(139,109,119,0.15); margin-bottom:20px; display:block;"></i>
                        <p style="color:#999; font-size:1.1rem;">Your cart is empty.</p>
                        <a href="products.php" class="btn-cart" style="margin-top:20px; display:inline-flex;">Go Shop <i class="fas fa-arrow-right"></i></a>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="cart-summary-card">
                <h2 class="summary-title">Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal (<?php echo $total_items_in_cart; ?> items)</span>
                    <span>RS <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span style="font-size:0.8rem; color:#999;">Calculated at checkout</span>
                </div>
                <div class="summary-row summary-divider">
                    <span>Subtotal</span>
                    <span>RS <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <a href="checkout.php" class="btn-checkout"><i class="fas fa-lock" style="margin-right:8px;"></i>Proceed to Checkout</a>
            </aside>
        </div>
    </div>

</body>
</html>
