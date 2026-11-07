<?php
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if ($user_email) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    // Query to get the cart count for the logged-in user
    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart);
} else {
    $wishlist_count = 0; // If not logged in, set wishlist count to 0
    $cart_count = 0; // If not logged in, set cart count to 0
}

if(isset($_POST['add_to_cart'])){
    if(!isset($_SESSION['user_email'])){
        header('location:userlogin.php'); // Redirect if the user is not logged in
        exit();
    }
 
    $user_email = $_SESSION['user_email']; // Get the logged-in user's email
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $product_quantity = 1;
    $product_user = isset($_POST['product_user']) ? $_POST['product_user'] : '';
    $product_size = isset($_POST['product_size']) ? $_POST['product_size'] : '';
    $product_color = isset($_POST['product_color']) ? $_POST['product_color'] : '';
 
    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND email = '$user_email'");
 
    if(mysqli_num_rows($select_cart) > 0){
       $message[] = 'Product already added to cart';
    }else{
        $insert_product = mysqli_query($conn, "INSERT INTO `cart`(name, price, image, quantity, email, user, size, color) VALUES('$product_name', '$product_price', '$product_image', '$product_quantity', '$user_email', '$product_user', '$product_size', '$product_color')");
        $message[] = 'Product added to cart successfully';
    }
 }
 

// Fetch the number of items in the cart


$select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
$cart_count = mysqli_num_rows($select_cart);



// Query for products by category
$hot_sale_query = mysqli_query($conn, "SELECT * FROM `products` WHERE `hot_sale` = 1");
$new_arrival_query = mysqli_query($conn, "SELECT * FROM `products` WHERE `new_arrival` = 1");
$top_sale_query = mysqli_query($conn, "SELECT * FROM `products` WHERE `top_sale` = 1");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Ecommerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style> 
/* Base font for whole site */
body {
    font-family: 'Montserrat', sans-serif;
   
}

/* Head container (top text) */
.head.container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
}

/* Logo text */
.logo h1 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 26px;
    color: #83666e;
    letter-spacing: 1px;
}

/* Head text */
.head-txt h1 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 26px;
    color: #A9A9A9;
    letter-spacing: 1px;
}

.head-txt span {
    display: block;
    font-size: 10px;
    color: #A9A9A9;
    letter-spacing: 2px;
    margin-top: -6px;
    font-weight: 500;
}

/* Navigation links */
.menu-items li a {
    font-family: 'Montserrat', sans-serif;
    font-weight: 500;
    font-size: 15px;
    text-decoration: none;
    color: #A9A9A9;
    padding: 0 10px;
    position: relative;
    transition: color 0.3s ease;
}

.menu-items li a:hover {
    color: #83666e;
}

/* Login/Register links */
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

/* Search form */
.search-form {
    display: flex;
    align-items: center;
}

.search-form input {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px 0 0 4px;
    outline: none;
    font-size: 14px;
}

.search-form button {
    background: none;
    border: none;
    padding: 10px;
    cursor: pointer;
    color: #A9A9A9;
    font-size: 16px;
    transition: color 0.3s ease;
}

.search-form button:hover {
    color: #83666e;
}

/* ===== PRODUCTS SECTIONS ===== */

/* Section title style */
section h2 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 28px;
    color: #83666e;
    text-align: center;
    margin: 40px 0 20px;
}

/* Products container: grid layout for responsive, neat columns */
.products {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    padding: 0 40px 40px 40px;
    max-width: 1200px;
    margin: 0 auto;
    overflow-x: hidden; /* prevent horizontal scroll */
}

/* Individual product card */
.products .product {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 370px;
    transition: box-shadow 0.3s ease;
}

/* Hover shadow effect on product */
.products .product:hover {
    box-shadow: 0 6px 15px rgba(131, 102, 110, 0.3);
}

/* Product image */
.products .product img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
}

/* Product name and price text */
.products .product p {
    font-size: 16px;
    color: #333;
    margin: 5px 0;
    text-align: center;
    min-height: 40px; /* Keep consistent height */
    font-weight: 600;
}

/* Price styling */
.products .product p:nth-child(3) {
    color: #83666e;
    font-weight: 700;
    font-size: 18px;
}

/* Buy Now button */
.products .product button {
    background-color: #83666e;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    align-self: center;
    transition: background-color 0.3s ease;
    margin-top: 10px;
    width: 100%;
    max-width: 180px;
}

/* Button hover */
.products .product button:hover {
    background-color: #6e525a;
}
.l-news.container {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    justify-content: space-between;
}

.l-news1 {
    width: 300px; /* or use flex: 1; */
}

/* Responsive: smaller product cards on very small screens */
@media screen and (max-width: 400px) {
    .products {
        grid-template-columns: 1fr;
        padding: 0 20px;
    }
    .products .product {
        height: auto;
    }
}



</style>


<body>
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

    <nav class="navbar">
        <div class="navbar-container">
            <input type="checkbox" id="checkbox">
            <div class="hamburger-lines">
                <span class="line line1"></span>
                <span class="line line2"></span>
                <span class="line line3"></span>
            </div>
            <ul class="menu-items">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Shop</a></li>
                <li><a href="account.php">Account</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li>
      


            <a href="wishlist.php" class="wishlist-icon">
                        <i class="fas fa-heart"></i>
                        <span class="wishlist-count"><?php echo $wishlist_count; ?></span> <!-- Display wishlist count -->
                    </a>
       
    </li>
                <li>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    </a>
                </li>
            </ul>


            <div class="logo">
                <h1>L/L</h1>
            </div>
        

      

        <div class="icons">
  <form action="process_search.php" method="get" class="search-form">
                <input type="text" name="query" placeholder="" required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>




        
</form>





    </nav>

</body>
</html>

        </nav>
    


    <section id="home">
        <div class="home_page ">
            <div class="home_img ">
                <img src="https://i.postimg.cc/t403yfn9/home2.jpg" alt="img ">
            </div>
            <div class="home_txt ">
                <p class="collectio ">Lankan COLLECTION</p>
                <h2>Modern - Traditional<br>Collection 2025</h2>
                <div class="home_label ">
                    <p>A specialist label creating luxury essentials. Ethically crafted<br>with an unwavering commitment to exceptional quality.</p>
                </div>
                <button><a href="#sellers">SHOP NOW</a><i class='bx bx-right-arrow-alt'></i></button>
                <div class="home_social_icons">
                    <a href="#"><i class='bx bxl-facebook'></i></a>
                    <a href="#"><i class='bx bxl-twitter'></i></a>
                    <a href="#"><i class='bx bxl-pinterest'></i></a>
                    <a href="#"><i class='bx bxl-instagram'></i></a>
                </div>
            </div>
        </div>
    </section>

    <section id="collection">
        <div class="collections container">
            <div class="content">
                <img src="https://i.postimg.cc/Xqmwr12c/clothing.webp" alt="img" />
                <div class="img-content">
                    <p>Clothing Collections</p>
                    <button><a href="#sellers">SHOP NOW</a></button>
                </div>
            </div>
            <div class="content2">
                <img src="https://i.postimg.cc/8CmBZH5N/shoes.webp" alt="img" />
                <div class="img-content2">
                    <p>Shoes Spring</p>
                    <button><a href="#sellers">SHOP NOW</a></button>
                </div>
            </div>
            <div class="content3">
                <img src="https://i.postimg.cc/MHv7KJYp/access.webp" alt="img" />
                <div class="img-content3">
                    <p>Accessories</p>
                    <button><a href="#sellers">SHOP NOW</a></button>
                </div>
            </div>
        </div>
    </section>
    
    <section id="home">
        <div class="home_page">
            <h1></h1>
        </div>
    </section>

    <!-- Hot Sale Section -->
   <br>

    <section id="hot_sale">
    <h2>Hot Sale</h2>
    <div class="products">
        <?php while ($product = mysqli_fetch_assoc($hot_sale_query)) { ?>
            <div class="product">
                <img src="uploaded_img/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" />
                <p><?php echo $product['name']; ?></p>
                <p>Price: RS <?php echo $product['price']; ?></p>
                <form method="POST" action="">
    <input type="hidden" name="product_name" value="<?php echo $product['name']; ?>">
    <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
    <input type="hidden" name="product_image" value="<?php echo $product['image']; ?>">
    <input type="hidden" name="product_user" value="<?php echo $product['user']; ?>">
    <input type="hidden" name="product_size" value="<?php echo $product['size']; ?>">
    <input type="hidden" name="product_color" value="<?php echo $product['color']; ?>">
    <button type="submit" name="add_to_cart">Buy Now</button>
</form>
            </div>
        <?php } ?>
    </div>
</section>


    <!-- New Arrivals Section -->
    <section id="new_arrival">
        <h2>New Arrivals</h2>
        <div class="products">
            <?php while ($product = mysqli_fetch_assoc($new_arrival_query)) { ?>
                <div class="product">

                    <img src="uploaded_img/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" />

                    <p><?php echo $product['name']; ?></p>
                    <p>Price: RS <?php echo $product['price']; ?></p>

                    <form method="POST" action="">
    <input type="hidden" name="product_name" value="<?php echo $product['name']; ?>">
    <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
    <input type="hidden" name="product_image" value="<?php echo $product['image']; ?>">
    <input type="hidden" name="product_user" value="<?php echo $product['user']; ?>">
    <input type="hidden" name="product_size" value="<?php echo $product['size']; ?>">
    <input type="hidden" name="product_color" value="<?php echo $product['color']; ?>">
    <button type="submit" name="add_to_cart">Buy Now</button>
</form>

                </div>
            <?php } ?>
        </div>
    </section>

    <!-- Top Sales Section -->
    <section id="top_sale">
        <h2>Top Sales</h2>
        <div class="products">
            <?php while ($product = mysqli_fetch_assoc($top_sale_query)) { ?>
                <div class="product">
                <img src="uploaded_img/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" />
                    <p><?php echo $product['name']; ?></p>
                    <p>Price: RS <?php echo $product['price']; ?></p>

                    <form method="POST" action="">
    <input type="hidden" name="product_name" value="<?php echo $product['name']; ?>">
    <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
    <input type="hidden" name="product_image" value="<?php echo $product['image']; ?>">
    <input type="hidden" name="product_user" value="<?php echo $product['user']; ?>">
    <input type="hidden" name="product_size" value="<?php echo $product['size']; ?>">
    <input type="hidden" name="product_color" value="<?php echo $product['color']; ?>">
    <button type="submit" name="add_to_cart">Buy Now</button>
</form>
                </div>
            <?php } ?>
        

    
        </div>
    </section>
    <section id="news">
    <div class="news-heading">
        <p>LATEST NEWS</p>
        <h2>Fashion New Trends</h2>
    </div>

    <!-- ✅ Only ONE container -->
    <div class="l-news container">
        <div class="l-news1">
            <div class="news1-img">
                <img src="images/hop1.jpg" alt="img">
            </div>
            <div class="news1-conte">
                <div class="date-news1">
                    <p><i class='bx bxs-calendar'></i> 12 February 2025</p>
                    <h4>What Curling Irons Are The Best Ones</h4>
                </div>
            </div>
        </div>

        <div class="l-news1">
            <div class="news1-img">
                <img src="images/hp.jpg" alt="img">
            </div>
            <div class="news1-conte">
                <div class="date-news1">
                    <p><i class='bx bxs-calendar'></i> 12 February 2025</p>
                    <h4>What Curling Irons Are The Best Ones</h4>
                </div>
            </div>
        </div>

        <div class="l-news1">
            <div class="news1-img">
                <img src="images/sun.jpg" alt="img">
            </div>
            <div class="news1-conte">
                <div class="date-news1">
                    <p><i class='bx bxs-calendar'></i> 12 February 2025</p>
                    <h4>What Curling Irons Are The Best Ones</h4>
                </div>
            </div>
        </div>
    </div> <!-- ✅ Close ONE container here -->
</section>

   
        <form action="" method="POST">
            <div class="form">
                <div class="form-txt">
                    <h4>INFORMATION</h4>
                    <h1>Contact Us</h1>
                    <span>As you might expect of a company that began as a high-end interiors contractor, we pay strict
                        attention.</span>
                    <h3>SRILANKA</h3>
                    <p>Colombo<br>0771756151</p>
                  
                </div>
                <div class="form-details">
                    <input type="text" name="name" id="name" placeholder="Name" required>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                    <textarea name="message" id="message" cols="52" rows="7" placeholder="Message" required></textarea>
                    <button>SEND MESSAGE</button>
                </div>
            </div>
        </form>
    </div>
    </section>
    <footer>
        <div class="footer-container container">
            <div class="content_1">
                <h1>LL</h1>
                <p>The customer is at the heart of our<br>unique business model, which includes<br>design.</p>
                <img src="https://i.postimg.cc/Nj9dgJ98/cards.png" alt="cards">
            </div>
            <div class="content_2">
                <h4>SHOPPING</h4>
                <a href="#sellers">Clothing Store</a>
                <a href="#sellers">Trending Shoes</a>
                <a href="#sellers">Accessories</a>
                <a href="#sellers">Sale</a>
            </div>
            <div class="content_3">
                <h4>SHOPPING</h4>
                <a href="./contact.html">Contact Us</a>
                <a href="https://payment-method-sb.netlify.app/" target="_blank">Payment Method</a>
                <a href="https://delivery-status-sb.netlify.app/" target="_blank">Delivery</a>
                <a href="https://codepen.io/sandeshbodake/full/Jexxrv" target="_blank">Return and Exchange</a>
            </div>
            <div class="content_4">
                <h4>NEWLETTER</h4>
                <p>Be the first to know about new<br>arrivals, look books, sales & promos!</p>
                <div class="f-mail">
                    <input type="email" placeholder="Your Email">
                    <i class='bx bx-envelope'></i>
                </div>
                <hr>
            </div>
        </div>
        <div class="f-design">
            <div class="f-design-txt container">
                <p>Design and Code by Livingsan</p>
            </div>
        </div>
    </footer>
    <script src="script.js" ></script>
</body>


</html>




