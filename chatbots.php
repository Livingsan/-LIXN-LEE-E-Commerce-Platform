<?php 
@include 'config.php';
session_start();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Custom Navigation Bar</title>

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
      background-color: #f8f8f8;
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
        /* <a href="account.php">Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?></a> */

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
        <a href="wishlist.php" class="cart-info-item">
          <i class="fas fa-heart"></i>
          <span><?php echo $wishlist_count; ?></span>
        </a>
        <a href="cart.php" class="cart-info-item">
          <i class="fas fa-shopping-cart"></i>
          <span><?php echo $cart_count; ?></span>
        </a>
      </div>

    </div>
  </nav>

</body>
</html>
