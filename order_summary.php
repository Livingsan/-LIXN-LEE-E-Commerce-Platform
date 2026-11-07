<?php
@include 'config.php';
session_start();

if (!isset($_SESSION['user_email'])) {
    header('location:userlogin.php');
    exit();
}

$user_email = $_SESSION['user_email'];
$message = '';

$cart_items = [];
$cart_total = 0;

$cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
while ($cart_item = mysqli_fetch_assoc($cart_query)) {
    $cart_items[] = $cart_item;
    $cart_total += ($cart_item['price'] * $cart_item['quantity']);
}

if (empty($cart_items)) {
    $message = "Your cart is empty!";
}

$wishlist_count = 0;
$cart_count = 0;

if ($user_email) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart_for_navbar = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart_for_navbar);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Summary - LEE E-Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #83666e; 
            --grey: #A9A9A9;
            --white: #fff;
            --text-dark: #333333;
            --text-light: #666666;
            --background-light: #f8f8f8; 
            --border-light: #E0E0E0;
            --shadow-light: rgba(0, 0, 0, 0.05);
            --error-color: #E74C3C;
            --accent-green: #A9B280; 
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--background-light);
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px; 
        }

        .top-txt {
            background-color: var(--primary-color);
            padding: 10px 0; 
        }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--grey);
            padding: 0 20px; 
        }

        .head-txt h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px; 
            font-weight: 700;
            letter-spacing: 2px; 
            text-transform: uppercase;
            color: var(--white); 
        }

        .head-txt span {
            font-size: 11px; 
            font-weight: 400;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: 0.9;
            color: var(--white); 
        }

        .sing_in_up a {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px; 
            color: var(--white); 
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            margin-left: 15px; 
            transition: color 0.3s ease;
        }

        .sing_in_up a:hover {
            color: var(--grey); 
        }

        .navbar {
            background-color: var(--white);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); 
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            display: flex;
            align-items: center;
            padding: 0 20px; 
            height: 70px; 
        }

        .logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 30px; 
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .menu-items {
            display: flex;
            list-style: none;
            gap: 2.5rem; 
            margin-left: auto;
        }

        .menu-items li a {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600; 
            font-size: 16px; 
            text-decoration: none;
            color: var(--text-light); 
            position: relative;
            padding: 6px 4px;
            transition: color 0.3s ease;
        }

        .menu-items li a::after { 
            content: '';
            position: absolute;
            width: 0%;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--primary-color); 
            transition: width 0.3s ease-out;
        }

        .menu-items li a:hover {
            color: var(--primary-color);
        }

        .menu-items li a:hover::after {
            width: 100%;
        }

        .menu-right {
            display: flex;
            align-items: center;
            gap: 1.8rem; 
            margin-left: 2.5rem; 
        }

        .cart-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px; 
            color: var(--text-light); 
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .cart-info-item i {
            font-size: 18px; 
        }

        .cart-info-item span {
            font-size: 14px; 
            font-weight: 600;
        }

        .cart-info-item:hover {
            color: var(--primary-color);
        }

        .order-summary-container {
            max-width: 960px; 
            margin: 60px auto;
            background: var(--white);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08); 
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        h2.page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
            color: var(--primary-color);
            font-weight: 700;
            position: relative;
            padding-bottom: 10px;
        }
        h2.page-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--accent-green); 
            border-radius: 5px;
        }

        .cart-items-list {
            border: 1px solid var(--border-light);
            border-radius: 10px;
            overflow: hidden;
            background-color: #fcfcfc;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 18px 25px;
            gap: 25px;
            border-bottom: 1px solid var(--border-light);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .cart-item-details h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .cart-item-details p {
            margin: 3px 0;
            font-size: 14px;
            color: var(--text-light);
        }

        .cart-item-price-qty {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 120px;
            text-align: right;
        }

        .cart-item-price-qty .item-total {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 18px;
            margin-bottom: 5px;
        }
        .cart-item-price-qty .single-price {
            font-size: 13px;
            color: var(--text-light);
        }

        .summary-footer {
            padding-top: 25px;
            border-top: 1px solid var(--border-light);
            text-align: right;
        }

        .total-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 25px;
            display: flex;
            justify-content: flex-end;
            align-items: baseline;
            gap: 10px;
        }
        .total-price span {
            font-size: 18px;
            font-weight: 500;
            color: var(--text-light);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: var(--accent-green); 
            color: var(--white);
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background: #9BB367; 
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .message {
            text-align: center;
            margin-top: 25px;
            padding: 20px;
            background-color: #FFF3CD;
            border: 1px solid #FFECB3;
            border-radius: 10px;
            color: #856404;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .message i {
            font-size: 24px;
            color: #FFC107;
        }

        @media (max-width: 1024px) {
            .head, .navbar-container {
                padding: 0 20px;
            }
        }

        @media (max-width: 768px) {
            .head {
                flex-direction: column;
                text-align: center;
                gap: 10px;
                padding: 15px 20px;
            }
            .head-txt h1 {
                font-size: 24px;
            }
            .head-txt span {
                font-size: 10px;
            }
            .sing_in_up a {
                margin: 0 8px;
            }

            .navbar-container {
                flex-direction: column;
                height: auto;
                padding: 15px 20px;
            }
            .menu-items {
                flex-direction: column;
                gap: 1rem;
                margin-top: 15px;
                width: 100%;
                text-align: center;
            }
            .menu-right {
                flex-direction: row;
                margin-top: 15px;
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }
            .cart-info-item {
                margin: 0 10px;
            }

            .order-summary-container {
                margin: 30px 15px;
                padding: 25px;
            }

            h2.page-title {
                font-size: 28px;
                margin-bottom: 20px;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 15px;
            }
            .cart-item img {
                width: 80px;
                height: 80px;
            }
            .cart-item-details h3 {
                font-size: 16px;
            }
            .cart-item-details p {
                font-size: 13px;
            }
            .cart-item-price-qty {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px dashed var(--border-light);
            }
            .cart-item-price-qty .item-total {
                font-size: 16px;
            }
            .cart-item-price-qty .single-price {
                font-size: 12px;
            }

            .total-price {
                font-size: 20px;
                flex-direction: column;
                align-items: flex-end;
            }
            .total-price span {
                font-size: 16px;
            }

            .btn {
                font-size: 16px;
                padding: 14px;
            }
            .message {
                font-size: 16px;
                padding: 15px;
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

    <div class="order-summary-container">
        <h2 class="page-title">Your Order Summary</h2>

        <?php if ($message): ?>
            <div class="message">
                <i class="fas fa-shopping-basket"></i>
                <?php echo $message; ?>
            </div>
        <?php else: ?>
            <div class="cart-items-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="uploaded_img/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Size: <?php echo htmlspecialchars($item['size']); ?></p>
                            <p>Color: <?php echo htmlspecialchars($item['color']); ?></p>
                            <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                        </div>
                        <div class="cart-item-price-qty">
                            <span class="item-total">
                                ₨<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </span>
                            <span class="single-price">
                                (₨<?php echo number_format($item['price'], 2); ?> each)
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-footer">
                <div class="total-price">
                    <span>Total Amount:</span> ₨<?php echo number_format($cart_total, 2); ?>
                </div>

                <form action="checkout.php" method="post">
                    <button type="submit" class="btn">Proceed to Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>