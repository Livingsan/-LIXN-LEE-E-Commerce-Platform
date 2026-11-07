<?php 
@include 'config.php';
session_start();

// Handle add to wishlist
if (isset($_POST['add_to_wishlist'])) {
    if (!isset($_SESSION['user_email'])) {
        header('location: login.php');
        exit();
    }

    $user_email = $_SESSION['user_email'];
    $product_id = $_POST['product_id'];

    $check_wishlist = mysqli_query($conn, "SELECT * FROM wishlist WHERE product_id = '$product_id' AND email = '$user_email'");
    
    if (mysqli_num_rows($check_wishlist) > 0) {
        $message[] = 'Product already added to wishlist';
    } else {
        $insert_wishlist = mysqli_query($conn, "INSERT INTO wishlist (product_id, email) VALUES ('$product_id', '$user_email')");
        $message[] = 'Product added to wishlist successfully';
    }
}

if(isset($_POST['add_to_cart'])){
   if(!isset($_SESSION['user_email'])){
       header('location:login.php');
       exit();
   }

   $user_email = $_SESSION['user_email'];
   $product_name = $_POST['product_name'];
   $product_price = $_POST['product_price'];
   $product_image = $_POST['product_image'];
   $product_quantity = 1;
   $product_user = $_POST['product_user'] ?? '';
   $product_size = $_POST['product_size'] ?? '';
   $product_color = $_POST['product_color'] ?? '';

   $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND email = '$user_email' AND size = '$product_size' AND color = '$product_color' AND user = '$product_user'");

   if(mysqli_num_rows($select_cart) > 0){
      $message[] = 'Product already added to cart';
   }else{
       $insert_product = mysqli_query($conn, "INSERT INTO `cart`(name, price, image, quantity, email, user, size, color) VALUES('$product_name', '$product_price', '$product_image', '$product_quantity', '$user_email', '$product_user', '$product_size', '$product_color')");
       $message[] = 'Product added to cart successfully';
   }
}

// Get wishlist and cart counts for navbar
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if ($user_email) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart);
} else {
    $wishlist_count = 0;
    $cart_count = 0;
}

$categories = ['kids', 'baby', 'men', 'women', 'tech', 'home appliance', 'books', 'others'];
$selected_category = $_GET['category'] ?? '';
$selected_color = $_GET['color'] ?? '';
$selected_size = $_GET['size'] ?? '';

$where = [];
if ($selected_category) $where[] = "category = '" . mysqli_real_escape_string($conn, $selected_category) . "'";
if ($selected_color) $where[] = "color = '" . mysqli_real_escape_string($conn, $selected_color) . "'";
if ($selected_size) $where[] = "size = '" . mysqli_real_escape_string($conn, $selected_size) . "'";
$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM `products` $where_clause ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// Fetch global coupons for banners
$today = date('Y-m-d');
$banner_coupons = [];

$coupon_q = mysqli_query($conn, "SELECT * FROM coupons 
    WHERE is_active = 1 
    AND expiry_date >= '$today' 
    AND (target_email IS NULL OR target_email = '') 
    ORDER BY expiry_date ASC");

while ($row = mysqli_fetch_assoc($coupon_q)) {
    $banner_coupons[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Products</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>

    <style>
    :root {
      --primary-color: #83666e;
      --grey: #A9A9A9;
      --white: #fff;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Montserrat', sans-serif;
      background-color: #f4f4f4;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* === TOP HEADER === */
    .top-txt {
      background-color: var(--primary-color);
    }

    .head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 40px;
      color: var(--grey);
    }

    .head-txt h1 {
      font-family: 'Playfair Display', serif;
      font-size: 24px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #A9A9A9;
    }

    .head-txt span {
      font-size: 12px;
      font-weight: 400;
      letter-spacing: 1px;
      text-transform: uppercase;
      opacity: 0.9;
      color: #A9A9A9;
    }

    .sing_in_up a {
      font-family: 'Montserrat', sans-serif;
      font-size: 12px;
      color: #A9A9A9;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      text-decoration: none;
      margin-left: 10px;
    }

    .sing_in_up a:hover {
      color: black;
    }

    /* === NAVBAR === */
    .navbar {
      background-color: var(--white);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .navbar-container {
      display: flex;
      align-items: center;
      padding: 0 40px;
      height: 64px;
    }

    .logo {
      flex-shrink: 0;
    }

    .logo h1 {
      font-family: 'Playfair Display', serif;
      font-size: 26px;
      color: var(--primary-color);
      font-weight: 700;
      letter-spacing: 1px;
    }

    .menu-items {
      display: flex;
      list-style: none;
      gap: 2rem;
      margin-left: auto;
    }

    .menu-items li a {
      font-family: 'Montserrat', sans-serif;
      font-weight: 500;
      font-size: 15px;
      text-decoration: none;
      color: var(--grey);
      position: relative;
      padding: 6px 4px;
      transition: color 0.3s ease;
    }

    .menu-items li a:hover {
      color: var(--primary-color);
    }

    .menu-right {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      margin-left: 2rem;
    }

    .cart-info-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: #A9A9A9;
      text-decoration: none;
    }

    .cart-info-item i {
      font-size: 16px;
    }

    .cart-info-item span {
      font-size: 13px;
    }

    .cart-info-item:hover {
      color: var(--primary-color);
    }

    @media (max-width: 768px) {
      .navbar-container {
        flex-wrap: wrap;
        justify-content: center;
      }

      .menu-items, .menu-right {
        flex-direction: column;
        margin-left: 0;
      }

      .menu-right {
        margin-top: 10px;
      }
    }

    /* Sidebar and main content */
    .banner { background-color: #A9A9A9; color: white; padding: 40px 20px; text-align: center; }
    .main { display: flex; max-width: 1200px; margin: 20px auto; }
    .sidebar {
        width: 250px;
        background: #eee;
        padding: 20px;
    }
    .sidebar h3 { margin-bottom: 15px; }
    .sidebar a, .sidebar select {
        display: block;
        color: #333;
        text-decoration: none;
        margin-bottom: 10px;
    }
    .sidebar a:hover { color: var(--primary-color); }
    .products {
        flex: 1;
        padding: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    .product-card {
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: 0.3s ease;
        position: relative;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .product-card img {
        max-width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
    }
    .product-card h4 { margin: 10px 0; font-size: 1.1rem; }
    .price { color: #e63946; font-weight: bold; margin-bottom: 10px; }
    .btns {
        display: flex;
        gap: 10px;
    }
    .btns .btn {
        flex: 1;
        background: var(--primary-color);
        color: white;
        padding: 8px 12px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
    }
    .btns .btn:hover { background: #a0747c; }
    .badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: var(--primary-color);
        color: white;
        padding: 4px 8px;
        font-size: 12px;
        border-radius: 5px;
    }
    .wishlist-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 20px;
        color: #e63946;
    }
    .wishlist-btn:hover {
        transform: scale(1.2);
    }
    .filter-select {
        width: 100%;
        padding: 6px;
        margin-bottom: 15px;
        border-radius: 5px;
        border: 1px solid #aaa;
    }
    .badge.free-delivery {
        position: static;
        display: inline-block;
        margin-bottom: 8px;
        background-color: #4CAF50;
        color: white;
        padding: 4px 8px;
        font-size: 12px;
        border-radius: 5px;
    }
    </style>
</head>
<body>

  <!-- === TOP HEADER === -->
  <section class="top-txt">
    <div class="head container">
      <div class="head-txt">
        <h1>LIXN LEE</h1>
        <span>ONLINE STORE</span>
      </div>
      <div class="sing_in_up">
        <a href="userlogin.php">Login</a>
        <a href="userreg.php">Register</a>
      </div>
    </div>
  </section>

  <!-- === NAVBAR === -->
  <nav class="navbar">
    <div class="navbar-container container">

      <!-- Logo -->
      <div class="logo">
        <h1>L/L</h1>
      </div>

      <!-- Navigation Links -->
      <ul class="menu-items">
        <li><a href="index.php">Home</a></li>
        <li><a href="products.php">Shop</a></li>
        <li><a href="account.php">Account</a></li>
        <li><a href="index.php#contact-section">Contact</a></li>
      </ul>

      <!-- Wishlist and Cart Icons -->
      <div class="menu-right">
        <a href="wishlist.php" class="cart-info-item" title="Wishlist">
          <i class="fas fa-heart"></i>
          <span><?php echo $wishlist_count; ?></span>
        </a>
        <a href="cart.php" class="cart-info-item" title="Cart">
          <i class="fas fa-shopping-cart"></i>
          <span><?php echo $cart_count; ?></span>
        </a>
      </div>

    </div>
  </nav>

  <div class="banner">
      <h1>Explore Our Products</h1>
  </div>

  <?php foreach ($banner_coupons as $banner_coupon): ?>
    <div style="background-color: #fff3f6; color: #000; padding: 15px 20px; text-align: center; font-weight: bold; border-bottom: 2px dashed var(--primary-color);">
        <?php if (!empty($banner_coupon['label'])): ?>
            🎁 <span style="color:#e63946;"><?php echo $banner_coupon['label']; ?></span> —
        <?php else: ?>
            🎁 Limited Time Offer —
        <?php endif; ?>
        Use Code <span style="color: var(--primary-color);"><?php echo $banner_coupon['code']; ?></span> and get
        <span style="color: #e63946;"><?php echo $banner_coupon['discount']; ?>% OFF</span>!
        <small>(Valid till <?php echo date('F j, Y', strtotime($banner_coupon['expiry_date'])); ?>)</small>
    </div>
  <?php endforeach; ?>

  <div class="main">
      <aside class="sidebar">
          <h3>Categories</h3>
          <?php foreach($categories as $cat): ?>
              <a href="?category=<?php echo urlencode($cat); ?>"><?php echo ucfirst($cat); ?></a>
          <?php endforeach; ?>

          <h3>Filter by Color</h3>
          <form method="get" id="filterForm">
              <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
              <select name="color" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                  <option value="">All Colors</option>
                  <?php
                      $colors = mysqli_query($conn, "SELECT DISTINCT color FROM products WHERE color != ''");
                      while($c = mysqli_fetch_assoc($colors)) {
                          $sel = ($selected_color == $c['color']) ? 'selected' : '';
                          echo "<option value='{$c['color']}' $sel>{$c['color']}</option>";
                      }
                  ?>
              </select>

              <h3>Filter by Size</h3>
              <select name="size" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                  <option value="">All Sizes</option>
                  <?php
                      $sizes = mysqli_query($conn, "SELECT DISTINCT size FROM products WHERE size != ''");
                      while($s = mysqli_fetch_assoc($sizes)) {
                          $sel = ($selected_size == $s['size']) ? 'selected' : '';
                          echo "<option value='{$s['size']}' $sel>{$s['size']}</option>";
                      }
                  ?>
              </select>
          </form>
      </aside>

      <section class="products">
          <?php
          if(mysqli_num_rows($result) > 0){
              while($row = mysqli_fetch_assoc($result)){
          ?>
              <div class="product-card">
                  <!-- Wishlist button -->
                  <form action="products.php" method="post" style="position:absolute; top:0; right:0;">
                      <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                      <button type="submit" name="add_to_wishlist" class="wishlist-btn" title="Add to Wishlist">❤️</button>
                  </form>

                  <div class="badge"><?php echo ucfirst($row['category']); ?></div>
                  <img src="uploaded_img/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                  <?php if ($row['free_delivery']) : ?>
                    <div class="badge free-delivery">Free Delivery 🚚</div>
                  <?php endif; ?>
                  <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                  <div class="price">Rs <?php echo $row['price']; ?></div>
                  
                  <form action="" method="post">
                      <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                      <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">
                      <input type="hidden" name="product_image" value="<?php echo $row['image']; ?>">
                      <input type="hidden" name="product_user" value="<?php echo $row['user']; ?>">
                      <input type="hidden" name="product_size" value="<?php echo $row['size']; ?>">
                      <input type="hidden" name="product_color" value="<?php echo $row['color']; ?>">
                      <div class="btns">
                          <input type="submit" name="add_to_cart" value="Add to Cart" class="btn">
                          <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn">View Detail</a>
                      </div>
                  </form>
              </div>
          <?php }} else { echo "<p>No products found.</p>"; } ?>
      </section>
  </div>

</body>
</html>
