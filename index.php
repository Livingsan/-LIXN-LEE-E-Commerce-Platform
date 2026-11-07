<?php
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

$wishlist_count = 0;
$cart_count = 0;

if ($user_email) {
    $select_wishlist = execute_query($conn, "SELECT * FROM `wishlist` WHERE email = ?", "s", [$user_email]);
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart = execute_query($conn, "SELECT * FROM `cart` WHERE email = ?", "s", [$user_email]);
    $cart_count = mysqli_num_rows($select_cart);
}

if (isset($_POST['add_to_cart'])) {
    if (!$user_email) {
        header('location:userlogin.php');
        exit();
    }

    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $product_quantity = 1;

    $check_cart = execute_query($conn, "SELECT * FROM `cart` WHERE name = ? AND email = ?", "ss", [$product_name, $user_email]);

    if (mysqli_num_rows($check_cart) > 0) {
        $message[] = 'Product already added to cart';
    } else {
        $insert_product = execute_query($conn, "INSERT INTO `cart`(name, price, image, quantity, email) VALUES(?, ?, ?, ?, ?)", "sdsss", [$product_name, $product_price, $product_image, $product_quantity, $user_email]);
        $message[] = 'Product added to cart successfully';
    }
}

$hot_sale_query = execute_query($conn, "SELECT * FROM `products` WHERE `hot_sale` = 1");
$new_arrival_query = execute_query($conn, "SELECT * FROM `products` WHERE `new_arrival` = 1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIXN LEE - Premium Shopping</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="design-system.css" rel="stylesheet">
    <link href="components.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link href="sections-hero.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="sections-products.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="sections-redesign.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="responsive.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <?php
    if (isset($message)) {
        foreach ($message as $msg) {
            echo '<div class="message">' . $msg . '</div>';
        }
    }
    ?>

    <section id="home" class="premium-hero">
        <div class="hero-bg-wrapper">
            <!-- Included a uniform soft white overlay to ensure the newly centered dark text remains fully legible -->
            <div class="hero-bg" style="background: linear-gradient(rgba(255, 255, 255, 0.65), rgba(255, 255, 255, 0.65)), url('https://i.postimg.cc/t403yfn9/home2.jpg'); background-size: cover; background-position: center;"></div>
        </div>
        <div class="container hero-premium-content">
            <div class="hero-main-alignment">
                <div class="hero-text-section">
                    <h1 class="hero-title-premium-new">Upgrade Your Style</h1>
                    <p class="hero-subtitle-premium-new">Discover our curated selection of fashion, tech, and lifestyle essentialis designed to everyday love.</p>
                    <div class="hero-buttons-new">
                        <button class="btn-primary-hero-new" onclick="location.href='products.php'">Shop Now</button>
                        <button class="btn-secondary-hero-new" onclick="location.href='#hot-sale'">View Deals</button>
                    </div>
                </div>
            </div>

            <div class="hero-glass-benefits-new">
                <div class="benefit-glass-item">
                    <div class="benefit-icon-mauve">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="benefit-text-white">
                        <strong>Free Shipping</strong>
                        <span>Orders above RS 2,500</span>
                    </div>
                </div>
                <div class="benefit-divider"></div>
                <div class="benefit-glass-item">
                    <div class="benefit-icon-mauve">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="benefit-text-white">
                        <strong>4.9/5 Rating</strong>
                        <span>Verified customer reviews</span>
                    </div>
                </div>
                <div class="benefit-divider"></div>
                <div class="benefit-glass-item">
                    <div class="benefit-icon-mauve">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="benefit-text-white">
                        <strong>Fast Delivery</strong>
                        <span>24-48 hours in cities</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust / Features Bar -->
    <div class="trust-bar">
        <div class="container">
            <div class="trust-bar-inner">
                <div class="trust-item">
                    <div class="trust-icon"><i class="fas fa-shipping-fast"></i></div>
                    <div class="trust-text">
                        <strong>Free Shipping</strong>
                        <span>Orders above RS 2,500</span>
                    </div>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-item">
                    <div class="trust-icon"><i class="fas fa-undo-alt"></i></div>
                    <div class="trust-text">
                        <strong>Easy Returns</strong>
                        <span>30-day hassle-free return</span>
                    </div>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-item">
                    <div class="trust-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="trust-text">
                        <strong>Secure Payments</strong>
                        <span>256-bit SSL encryption</span>
                    </div>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-item">
                    <div class="trust-icon"><i class="fas fa-headset"></i></div>
                    <div class="trust-text">
                        <strong>24/7 Support</strong>
                        <span>Always here to help</span>
                    </div>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-item">
                    <div class="trust-icon"><i class="fas fa-star"></i></div>
                    <div class="trust-text">
                        <strong>4.9/5 Rating</strong>
                        <span>From 50K+ reviews</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="brand-story-section">
        <div class="container">
            <div class="brand-story-shell">
                <div class="brand-story-intro">
                    <span class="section-kicker">Our Promise</span>
                    <h2>A premium shopping experience, from discovery to your doorstep.</h2>
                    <p>Every section of LIXN LEE is designed with intention — refined spacing, curated products, and dependable service so each order feels effortless and rewarding.</p>
                    <div class="brand-story-metrics">
                        <div class="story-metric">
                            <strong>50K+</strong>
                            <span>Happy Customers</span>
                        </div>
                        <div class="story-metric">
                            <strong>24-48h</strong>
                            <span>City Delivery</span>
                        </div>
                        <div class="story-metric">
                            <strong>4.9/5</strong>
                            <span>Shopper Rating</span>
                        </div>
                    </div>
                </div>
                <div class="brand-story-grid">
                    <article class="story-card featured">
                        <span class="story-label">Signature Promise</span>
                        <h3>Premium quality with the ease of modern online shopping.</h3>
                        <p>We combine elevated presentation, trusted checkout, and responsive service so each order feels smooth and intentional from start to finish.</p>
                    </article>
                    <article class="story-card">
                        <div class="story-icon"><i class="fas fa-bolt"></i></div>
                        <h3>Fast Islandwide Delivery</h3>
                        <p>Quick dispatch, careful packaging, and real-time order tracking from cart to doorstep.</p>
                    </article>
                    <article class="story-card">
                        <div class="story-icon"><i class="fas fa-shield-alt"></i></div>
                        <h3>Trusted Shopping Flow</h3>
                        <p>Encrypted payments, easy returns, and 24/7 support keep your experience stress-free.</p>
                    </article>
                    <article class="story-card">
                        <div class="story-icon"><i class="fas fa-gem"></i></div>
                        <h3>Curated Collections</h3>
                        <p>Fashion, lifestyle, and essentials handpicked and presented with a strong editorial feel.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="promo-premium-section">
        <div class="container">
            <div class="promo-premium-wrapper">
                <div class="promo-content-box">
                    <div class="promo-tag">Season Highlight</div>
                    <h2 class="promo-title">Premium deals with limited-time prices on our best-loved collections.</h2>
                    <p class="promo-description">Every featured pick is selected for quality, style, and value — curated to elevate your wardrobe and lifestyle without compromising on experience.</p>
                    <div class="promo-feature-grid">
                        <div class="promo-feature-card">
                            <span>01</span>
                            <strong>Editorial Selection</strong>
                            <p>Products chosen for design, quality, and relevance.</p>
                        </div>
                        <div class="promo-feature-card">
                            <span>02</span>
                            <strong>Genuine Savings</strong>
                            <p>Real discounts — not inflated before markdown.</p>
                        </div>
                        <div class="promo-feature-card">
                            <span>03</span>
                            <strong>Limited Availability</strong>
                            <p>High-demand items move fast. Don't miss out.</p>
                        </div>
                    </div>
                    <button class="btn-promo-premium" onclick="location.href='#hot-sale'">
                        <i class="fas fa-fire"></i> Explore Hot Deals
                    </button>
                </div>
                <div class="promo-visual-panel">
                    <div class="promo-insight-card">
                        <span class="promo-insight-label">Trending This Week</span>
                        <h3>High-demand picks are moving fast — shop before they're gone.</h3>
                        <div class="promo-insight-list">
                            <div>
                                <strong>🔥 Hot Sale</strong>
                                <span>Best-value products with premium finishing</span>
                            </div>
                            <div>
                                <strong>✨ New Arrivals</strong>
                                <span>Fresh additions shaped for modern living</span>
                            </div>
                            <div>
                                <strong>👑 Member Access</strong>
                                <span>Exclusive deals and early launch previews</span>
                            </div>
                        </div>
                    </div>
                    <div class="promo-badge-wrapper">
                        <div class="promo-badge-circle">
                            <div class="promo-badge-inner">
                                <span class="promo-percent">50%</span>
                                <span class="promo-text">OFF</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="hot-sale" class="featured-section">
        <div class="container">
            <div class="section-header-premium">
                <span class="section-kicker">Featured Offers</span>
                <h2 class="section-title-premium">Hot Deals</h2>
                <p class="section-subtitle-premium">Exclusive offers on bestselling products with a stronger premium layout and a cleaner shopping flow.</p>
                <a href="products.php" class="view-all-link">View all products <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="collection-showcase">
                <div class="collection-copy">
                    <span class="collection-label">Deal Focus</span>
                    <h3>Statement finds with limited-time pricing.</h3>
                    <p>Explore standout items selected for shoppers who want quality, style, and immediate value without losing the premium feel.</p>
                </div>
                <div class="collection-tags">
                    <span>Best Sellers</span>
                    <span>Fast Shipping</span>
                    <span>Premium Finish</span>
                </div>
            </div>
            <div class="products-elegant-grid">
                <?php while ($row = mysqli_fetch_assoc($hot_sale_query)): ?>
                    <form action="" method="post" class="product-elegant-card">
                        <div class="product-image-box">
                            <img src="uploaded_img/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-elegant-image">
                            <div class="product-action-overlay">
                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn-view-product">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                            <span class="product-badge-deal">Hot Deal</span>
                            <?php if ($row['free_delivery'] ?? false): ?>
                                <span class="product-badge-ship">Free Shipping</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-details-box">
                            <h4 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h4>
                            <div class="product-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                                <span>(128 reviews)</span>
                            </div>
                            <div class="product-pricing">
                                <span class="price-tag">RS <?php echo number_format($row['price'], 2); ?></span>
                            </div>
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($row['image']); ?>">
                            <button type="submit" name="add_to_cart" class="btn-cart-elegant">
                                <i class="fas fa-shopping-bag"></i> Add to Cart
                            </button>
                        </div>
                    </form>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section id="new-arrival" class="featured-section alt-bg">
        <div class="container">
            <div class="section-header-premium">
                <span class="section-kicker">Fresh Collection</span>
                <h2 class="section-title-premium">Latest Arrivals</h2>
                <p class="section-subtitle-premium">Fresh collections added regularly, presented in a softer editorial section that still matches the same brand mood.</p>
                <a href="products.php" class="view-all-link">Explore new arrivals <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="collection-showcase soft">
                <div class="collection-copy">
                    <span class="collection-label">New This Season</span>
                    <h3>Clean silhouettes, elevated details, and fresh discovery moments.</h3>
                    <p>These latest picks keep the browsing experience dynamic while staying visually connected to the navigation and hero sections.</p>
                </div>
                <div class="collection-tags">
                    <span>Just Added</span>
                    <span>Modern Style</span>
                    <span>Weekly Updates</span>
                </div>
            </div>
            <div class="products-elegant-grid">
                <?php while ($row = mysqli_fetch_assoc($new_arrival_query)): ?>
                    <form action="" method="post" class="product-elegant-card">
                        <div class="product-image-box">
                            <img src="uploaded_img/<?php echo $row['image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-elegant-image">
                            <div class="product-action-overlay">
                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn-view-product">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                            <span class="product-badge-new-arrival">New</span>
                        </div>
                        <div class="product-details-box">
                            <h4 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h4>
                            <div class="product-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <span>(95 reviews)</span>
                            </div>
                            <div class="product-pricing">
                                <span class="price-tag">RS <?php echo number_format($row['price'], 2); ?></span>
                            </div>
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $row['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($row['image']); ?>">
                            <button type="submit" name="add_to_cart" class="btn-cart-elegant">
                                <i class="fas fa-shopping-bag"></i> Add to Cart
                            </button>
                        </div>
                    </form>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section class="reviews-premium-section">
        <div class="container">
            <div class="reviews-premium-layout">
                <div class="reviews-summary-card">
                    <span class="section-kicker">Client Love</span>
                    <h2>Real feedback from shoppers who choose LIXN LEE again and again.</h2>
                    <p>Our customers remember the whole journey — the quality, the speed, and the care. Here's what they have to say.</p>
                    <div class="review-summary-stats">
                        <div>
                            <strong>4.9/5</strong>
                            <span>Average Rating across all orders</span>
                        </div>
                        <div>
                            <strong>50K+</strong>
                            <span>Verified reviews from real buyers</span>
                        </div>
                        <div>
                            <strong>92%</strong>
                            <span>Repeat buyers within 90 days</span>
                        </div>
                    </div>
                </div>
                <div class="reviews-container">
                    <div class="review-card-premium">
                        <div class="review-header-premium">
                            <div class="review-stars">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                            <span class="verified-check">Verified</span>
                        </div>
                        <p class="review-text">"Amazing quality and fast delivery. The product felt premium from the packaging to the finish, and the whole experience matched the brand perfectly."</p>
                        <div class="review-author-premium">
                            <div class="author-circle">SA</div>
                            <div>
                                <strong>Livi</strong>
                                <small>Colombo &bull; 1 week ago</small>
                            </div>
                        </div>
                    </div>
                    <div class="review-card-premium">
                        <div class="review-header-premium">
                            <div class="review-stars">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                            <span class="verified-check">Verified</span>
                        </div>
                        <p class="review-text">"Customer service was excellent. I had a question about my order and got a helpful response in minutes. That kind of attention builds real trust."</p>
                        <div class="review-author-premium">
                            <div class="author-circle">MJ</div>
                            <div>
                                <strong>Afrah</strong>
                                <small>Kandy &bull; 2 weeks ago</small>
                            </div>
                        </div>
                    </div>
                    <div class="review-card-premium">
                        <div class="review-header-premium">
                            <div class="review-stars">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                            <span class="verified-check">Verified</span>
                        </div>
                        <p class="review-text">"The site is polished, prices are fair, and delivery was smooth. It genuinely feels like shopping from a premium boutique — I keep coming back."</p>
                        <div class="review-author-premium">
                            <div class="author-circle">PR</div>
                            <div>
                                <strong>Silva</strong>
                                <small>Galle &bull; 3 weeks ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="newsletter-premium-section">
        <div class="container">
            <div class="newsletter-premium-content">
                <div class="newsletter-text-premium">
                    <span class="section-kicker">Private Access</span>
                    <h2>Stay ahead with exclusive drops, early access, and member-only offers.</h2>
                    <p>Join our mailing list and experience curation at its finest — collection launches, seasonal previews, and deals reserved for our inner circle.</p>
                    <div class="newsletter-benefits">
                        <div class="benefit-check"><i class="fas fa-check"></i> Exclusive member-only discounts</div>
                        <div class="benefit-check"><i class="fas fa-check"></i> Early access to new arrivals</div>
                        <div class="benefit-check"><i class="fas fa-check"></i> Curated style and product updates</div>
                        <div class="benefit-check"><i class="fas fa-check"></i> Priority customer support</div>
                    </div>
                </div>
                <div class="newsletter-form-premium">
                    <h3>Join the Inner Circle</h3>
                    <p>Get first access to our premium collections and exclusive savings.</p>
                    <form onsubmit="event.preventDefault(); this.querySelector('.btn-subscribe-premium').textContent='Subscribed ✓'; this.querySelector('input').disabled=true;">
                        <div class="form-group-newsletter">
                            <input type="email" placeholder="Your email address" required>
                            <button type="submit" class="btn-subscribe-premium">Subscribe Now</button>
                        </div>
                        <small class="form-note"><i class="fas fa-lock" style="margin-right:5px;opacity:0.5"></i>We respect your privacy. Unsubscribe anytime.</small>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section id="contact">
        <div class="container">
            <div class="contact-shell">
                <div class="contact-container">
                    <div class="contact-info">
                        <span class="section-kicker">Concierge Support</span>
                        <h2>We're here to keep your shopping experience personal and seamless.</h2>
                        <p>Questions about products, orders, or delivery? Our team is ready to help every step of the way.</p>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Our Studio</strong>
                                <p>123 Luxury Lane, Colombo, Sri Lanka</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Call Us</strong>
                                <p>+94 11 234 5678</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email Us</strong>
                                <p>hello@lixnlee.com</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Working Hours</strong>
                                <p>Mon – Sat: 9AM – 7PM</p>
                            </div>
                        </div>
                    </div>
                    <form action="contact_process.php" method="POST" class="contact-form">
                        <div class="form-group">
                            <label>Your Name</label>
                            <input type="text" name="name" placeholder="Enter your full name" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="you@example.com" required>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" placeholder="What's this about?">
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" rows="5" placeholder="Tell us how we can help..." required></textarea>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>LIXN<span>LEE</span></h4>
                    <p>A premium shopping destination built for stylish discovery, seamless checkout, and dependable service — from Colombo to your doorstep.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest-p"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Shop</h4>
                    <ul>
                        <li><a href="products.php"><i class="fas fa-angle-right"></i> All Products</a></li>
                        <li><a href="#hot-sale"><i class="fas fa-angle-right"></i> Hot Deals</a></li>
                        <li><a href="#new-arrival"><i class="fas fa-angle-right"></i> New Arrivals</a></li>
                        <li><a href="wishlist.php"><i class="fas fa-angle-right"></i> Wishlist</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Account</h4>
                    <ul>
                        <li><a href="account.php"><i class="fas fa-angle-right"></i> My Account</a></li>
                        <li><a href="cart.php"><i class="fas fa-angle-right"></i> My Cart</a></li>
                        <li><a href="userorder.php"><i class="fas fa-angle-right"></i> My Orders</a></li>
                        <li><a href="return.php"><i class="fas fa-angle-right"></i> Returns</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Connect</h4>
                    <p><i class="fas fa-envelope" style="color:#d4545c;width:16px"></i> hello@lixnlee.com</p>
                    <p><i class="fas fa-phone" style="color:#d4545c;width:16px"></i> +94 11 234 5678</p>
                    <p><i class="fas fa-map-marker-alt" style="color:#d4545c;width:16px"></i> Colombo, Sri Lanka</p>
                    <p><a href="admin_login.php"><i class="fas fa-lock" style="width:16px"></i> Admin Portal</a></p>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; 2025 LIXN LEE. All rights reserved. Designed with &hearts; in Sri Lanka.</span>
                <span>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </span>
            </div>
        </div>
    </footer>

</body>
</html>
