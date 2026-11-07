<?php
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';


$order_id = isset($_SESSION['last_order_id']) ? $_SESSION['last_order_id'] : (isset($_GET['order_id']) ? intval($_GET['order_id']) : null);

if (isset($_SESSION['last_order_id'])) {
    unset($_SESSION['last_order_id']);
}

$order_details = null;
if ($order_id && $user_email) {
    $order_query = mysqli_query($conn, "SELECT * FROM `order` WHERE id = '$order_id' AND email = '$user_email'");
    $order_details = mysqli_fetch_assoc($order_query);
}

$wishlist_count = 0;
$cart_count = 0;
if ($user_email) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Payment Successful! - LIXN LEE</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>

    <style>
        :root {
            --primary-color: #83666e;
            --grey: #A9A9A9;
            --white: #fff;
            --light-grey-bg: #f8f8f8;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --success-green: #28a745; 
            --heading-font: 'Playfair Display', serif;
            --body-font: 'Montserrat', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--body-font);
            background-color: var(--light-grey-bg);
            color: #333;
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
            font-family: var(--heading-font);
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
            font-family: var(--body-font);
            font-size: 12px;
            color: #A9A9A9;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-decoration: none;
            margin-left: 10px;
            transition: color 0.3s ease;
        }

        .sing_in_up a:hover {
            color: var(--white);
        }

        /* === NAVBAR === */
        .navbar {
            background-color: var(--white);
            box-shadow: 0 4px 6px var(--shadow-light);
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
            font-family: var(--heading-font);
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
            font-family: var(--body-font);
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
        .menu-items li a.active {
            color: var(--primary-color);
        }
        .menu-items li a.active::after,
        .menu-items li a:hover::after {
            content: none; 
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
            transition: color 0.3s ease;
        }

        .cart-info-item i {
            font-size: 16px;
        }

        .cart-info-item span {
            font-size: 13px;
            background: none;
            color: #A9A9A9;
            padding: 0;
            line-height: normal;
            min-width: unset;
            height: unset;
            display: inline;
            position: static;
            font-weight: 400;
        }

        .cart-info-item:hover {
            color: var(--primary-color);
        }
        .cart-info-item:hover span {
             color: var(--primary-color);
        }

        .success-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 128px); 
            text-align: center;
            padding: 40px 20px;
            background-color: var(--white);
            max-width: 700px;
            margin: 40px auto;
            border-radius: 10px;
            box-shadow: 0 10px 30px var(--shadow-light);
            animation: fadeInScale 0.8s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .success-icon {
            font-size: 80px;
            color: var(--success-green);
            margin-bottom: 30px;
            animation: bounceIn 1s ease-out;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.1); opacity: 0; }
            60% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); }
        }

        .success-content h2 {
            font-family: var(--heading-font);
            font-size: 3.5rem; 
            color: var(--success-green);
            margin-bottom: 20px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .success-content p {
            font-size: 1.2rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 500px;
        }

        .success-actions {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .action-button.primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .action-button.primary:hover {
            background-color: #6d545a;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        .action-button.secondary {
            background-color: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .action-button.secondary:hover {
            background-color: var(--primary-color);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }
        .action-button i {
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .success-content {
                padding: 30px 15px;
                margin: 20px auto;
            }
            .success-icon {
                font-size: 60px;
                margin-bottom: 20px;
            }
            .success-content h2 {
                font-size: 2.5rem;
            }
            .success-content p {
                font-size: 1rem;
                margin-bottom: 20px;
            }
            .action-button {
                padding: 12px 25px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .success-content h2 {
                font-size: 2rem;
            }
            .action-button {
                width: 100%; /* Stack buttons on very small screens */
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <section class="top-txt">
        <div class="head container">
            <div class="head-txt">
                <h1>LIXN LEE</h1>
                <span>ONLINE STORE</span>
            </div>
            <div class="sing_in_up">
                <?php if ($user_email): ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="userlogin.php">Login</a>
                    <a href="userreg.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <nav class="navbar">
        <div class="navbar-container container">

            <div class="logo">
                <h1>L/L</h1>
            </div>

            <ul class="menu-items">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Shop</a></li>
                <li><a href="account.php">Account</a></li>
                <li><a href="index.php#contact-section">Contact</a></li>
            </ul>

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

    <main class="main-content container">
        <div class="success-content">
            <i class="fas fa-check-circle success-icon"></i>
            <h2>Payment Successful!</h2>
            <p>Thank you for your purchase! Your order has been placed successfully and is now being processed. You will receive an email confirmation shortly.</p>

            <?php if ($order_details): ?>
                <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order_details['id']); ?></p>
                <p><strong>Total Amount:</strong> Rs. <?php echo number_format($order_details['total_price'], 2); ?></p>
            <?php else: ?>
                <p>We couldn't retrieve specific order details at this moment, but rest assured your order has been placed.</p>
            <?php endif; ?>

            <div class="success-actions">
                <a href="products.php" class="action-button secondary"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
                <a href="userorder.php" class="action-button primary"><i class="fas fa-box"></i> View My Orders</a>
            </div>
        </div>
    </main>

</body>
</html>