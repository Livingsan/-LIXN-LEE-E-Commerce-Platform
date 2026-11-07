<?php 
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

// Wishlist Logic
if (isset($_POST['add_to_wishlist'])) {
    if (!$user_email) {
        header('location: userlogin.php');
        exit();
    }
    $product_id = $_POST['product_id'];

    $check_wishlist = execute_query($conn, "SELECT * FROM wishlist WHERE product_id = ? AND email = ?", "is", [$product_id, $user_email]);
    
    if (mysqli_num_rows($check_wishlist) > 0) {
        $message[] = 'Product already added to wishlist';
    } else {
        execute_query($conn, "INSERT INTO wishlist (product_id, email) VALUES (?, ?)", "is", [$product_id, $user_email]);
        $message[] = 'Product added to wishlist successfully';
    }
}

// Cart Logic
if(isset($_POST['add_to_cart'])){
   if(!$user_email){
       header('location:userlogin.php');
       exit();
   }

   $product_id = $_POST['product_id'];
   $product_name = $_POST['product_name'];
   $product_price = $_POST['product_price'];
   $product_image = $_POST['product_image'];
   $product_quantity = 1;
   $product_user = $_POST['product_user'] ?? '';
   $product_size = $_POST['product_size'] ?? '';
   $product_color = $_POST['product_color'] ?? '';

   $select_cart = execute_query($conn, "SELECT * FROM `cart` WHERE product_id = ? AND email = ? AND size = ? AND color = ?", "isss", [$product_id, $user_email, $product_size, $product_color]);

   if(mysqli_num_rows($select_cart) > 0){
        $message[] = 'Product already added to cart';
    } else {
        execute_query($conn, "INSERT INTO `cart`(product_id, name, price, image, quantity, email, user, size, color) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)", "isdsissss", [$product_id, $product_name, $product_price, $product_image, $product_quantity, $user_email, $product_user, $product_size, $product_color]);
        $message[] = 'Product added to cart successfully';
    }
}

$wishlist_count = 0;
$cart_count = 0;
if ($user_email) {
    $wishlist_count = mysqli_num_rows(execute_query($conn, "SELECT * FROM `wishlist` WHERE email = ?", "s", [$user_email]));
    $cart_count = mysqli_num_rows(execute_query($conn, "SELECT * FROM `cart` WHERE email = ?", "s", [$user_email]));
}

// Filter Logic
$selected_category = $_GET['category'] ?? '';
$selected_color = $_GET['color'] ?? '';
$selected_size = $_GET['size'] ?? '';

$query = "SELECT DISTINCT name, image, price, id, category, free_delivery, color, size, user FROM `products` WHERE 1=1";
$params = [];
$types = "";

if ($selected_category) {
    $query .= " AND category = ?";
    $params[] = $selected_category;
    $types .= "s";
}
if ($selected_color) {
    $query .= " AND color LIKE ?";
    $params[] = "%$selected_color%";
    $types .= "s";
}
if ($selected_size) {
    $query .= " AND size LIKE ?";
    $params[] = "%$selected_size%";
    $types .= "s";
}
$query .= " ORDER BY id DESC";

$result = execute_query($conn, $query, $types, $params);

// Coupons
$today = date('Y-m-d');
$coupon_q = execute_query($conn, "SELECT * FROM coupons WHERE is_active = 1 AND expiry_date >= ? AND (target_email IS NULL OR target_email = '') ORDER BY expiry_date ASC", "s", [$today]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="sections-storefront.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="sections-products.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="sections-redesign.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <?php
    if(isset($message)){
        foreach($message as $msg){
            echo '<div class="message">'.$msg.'</div>';
        }
    }
    ?>

    <div class="shop-container container">
        <aside class="shop-sidebar">
            <div class="filter-card">
                <h3><i class="fas fa-th-large"></i> Categories</h3>
                <a href="products.php" class="<?php echo !$selected_category ? 'active' : ''; ?>">All Products</a>
                <?php 
                $cats = ['kids', 'baby', 'men', 'women', 'tech', 'home appliance', 'books', 'others'];
                foreach($cats as $cat): 
                ?>
                    <a href="?category=<?php echo $cat; ?>" class="<?php echo $selected_category == $cat ? 'active' : ''; ?>"><?php echo ucfirst($cat); ?></a>
                <?php endforeach; ?>
            </div>

            <form method="get" class="filter-card">
                <h3><i class="fas fa-sliders-h"></i> Filters</h3>
                <?php if($selected_category): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
                <?php endif; ?>
                
                <label style="font-size:0.8rem; font-weight:600; color:#999; text-transform:uppercase; letter-spacing:1px;">Color</label>
                <select name="color" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Colors</option>
                    <?php
                    $colors_res = execute_query($conn, "SELECT color FROM products WHERE color != ''");
                    $all_colors = [];
                    while($c = mysqli_fetch_assoc($colors_res)) {
                        $parts = array_map('trim', explode(',', $c['color']));
                        $all_colors = array_merge($all_colors, $parts);
                    }
                    $all_colors = array_unique(array_filter($all_colors));
                    foreach($all_colors as $color_opt) {
                        $sel = ($selected_color == $color_opt) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($color_opt)."' $sel>".htmlspecialchars(ucfirst($color_opt))."</option>";
                    }
                    ?>
                </select>

                <div style="margin-top:16px;">
                    <label style="font-size:0.8rem; font-weight:600; color:#999; text-transform:uppercase; letter-spacing:1px;">Size</label>
                    <select name="size" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Sizes</option>
                        <?php
                        $sizes_res = execute_query($conn, "SELECT size FROM products WHERE size != ''");
                        $all_sizes = [];
                        while($s = mysqli_fetch_assoc($sizes_res)) {
                            $parts = array_map('trim', explode(',', $s['size']));
                            $all_sizes = array_merge($all_sizes, $parts);
                        }
                        $all_sizes = array_unique(array_filter($all_sizes));
                        foreach($all_sizes as $size_opt) {
                            $sel = ($selected_size == $size_opt) ? 'selected' : '';
                            echo "<option value='".htmlspecialchars($size_opt)."' $sel>".htmlspecialchars(strtoupper($size_opt))."</option>";
                        }
                        ?>
                    </select>
                </div>
            </form>
        </aside>

        <main class="products-area">
            <?php while($row = mysqli_fetch_assoc($coupon_q)): ?>
                <div class="coupon-banner">
                    🎁 Use Code <strong><?php echo $row['code']; ?></strong> for <strong><?php echo $row['discount']; ?>% OFF</strong>!
                </div>
            <?php endwhile; ?>

            <div class="products-elegant-grid">
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <div class="product-elegant-card">
                            <div class="product-image-box">
                                <?php if($row['free_delivery'] ?? false): ?>
                                    <span class="product-badge-ship">Free Delivery</span>
                                <?php endif; ?>
                                <form action="" method="post" style="position:absolute; top:20px; left:20px; z-index:10;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="add_to_wishlist" class="wishlist-btn" style="background:var(--nav-bg); border:none; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(0,0,0,0.1); cursor:pointer;">
                                        <i class="far fa-heart" style="color:var(--nav-dark);"></i>
                                    </button>
                                </form>
                                <img src="uploaded_img/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-elegant-image">
                                <div class="product-action-overlay">
                                    <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn-view-product">View Details</a>
                                </div>
                            </div>
                            <div class="product-details-box">
                                <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <div class="product-pricing" style="margin-top: 8px; margin-bottom: 16px;">
                                    <span class="price-tag">RS <?php echo number_format($row['price'], 2); ?></span>
                                </div>
                                
                                <form action="" method="post" style="margin-top: auto;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">
                                    <input type="hidden" name="product_image" value="<?php echo $row['image']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn-cart-elegant" style="width: 100%; border: 1px solid var(--nav-dark); background: transparent; padding: 12px; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='var(--nav-dark)'; this.style.color='var(--nav-bg)';" onmouseout="this.style.background='transparent'; this.style.color='var(--nav-dark)';">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; grid-column: 1/-1; padding: 60px; color: #999; font-size: 1.1rem;"><i class="fas fa-search" style="font-size:2rem; display:block; margin-bottom:16px; opacity:0.3;"></i>No products found matching your criteria.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
