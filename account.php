<?php
@include 'config.php'; 
session_start();

if (!isset($_SESSION['user_email'])) {
    header('Location: userlogin.php');
    exit();
}

$user_email = $_SESSION['user_email'];

if (isset($_POST['update_payment'])) {
    $method = $_POST['preferred_payment'];
    $stmt_update = $conn->prepare("UPDATE users SET preferred_payment=? WHERE email=?");
    if ($stmt_update) {
        $stmt_update->bind_param("ss", $method, $user_email);
        $stmt_update->execute();
        $stmt_update->close();
        header('Location: account.php');
        exit();
    } else {
        error_log("Failed to prepare statement for update_payment: " . $conn->error);
    }
}

$stmt = $conn->prepare("SELECT name, email, role, phone, gender, dob, street, city, country, preferred_payment, loyalty_points FROM users WHERE email = ?");
if ($stmt) {
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        session_unset();
        session_destroy();
        header('Location: userlogin.php');
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    error_log("Failed to prepare statement for fetching user details: " . $conn->error);
    $user = [
        'name' => 'Guest', 'email' => 'N/A', 'role' => 'N/A', 'phone' => 'N/A',
        'gender' => 'N/A', 'dob' => 'N/A', 'street' => 'N/A', 'city' => 'N/A',
        'country' => 'N/A', 'preferred_payment' => 'N/A', 'loyalty_points' => 0
    ];
}


$wishlist_count = 0;
$cart_count = 0;

$stmt_wishlist = $conn->prepare("SELECT COUNT(*) FROM `wishlist` WHERE email = ?");
if ($stmt_wishlist) {
    $stmt_wishlist->bind_param("s", $user_email);
    $stmt_wishlist->execute();
    $stmt_wishlist->bind_result($wishlist_count);
    $stmt_wishlist->fetch();
    $stmt_wishlist->close();
} else {
    error_log("Failed to prepare statement for wishlist count: " . $conn->error);
}

$stmt_cart = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE email = ?");
if ($stmt_cart) {
    $stmt_cart->bind_param("s", $user_email);
    $stmt_cart->execute();
    $stmt_cart->bind_result($cart_count);
    $stmt_cart->fetch();
    $stmt_cart->close();
} else {
    error_log("Failed to prepare statement for cart count: " . $conn->error);
}

$recent_orders = [];
$stmt_orders = $conn->prepare("SELECT id, status, order_date FROM `order` WHERE email = ? ORDER BY order_date DESC LIMIT 3");

if ($stmt_orders) {
    $stmt_orders->bind_param("s", $user_email);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    while ($row = $result_orders->fetch_assoc()) {
        $recent_orders[] = $row;
    }
    $stmt_orders->close();
} else {
    error_log("Failed to fetch recent orders: " . $conn->error);
}

$recommended_products = [];

$result_products = mysqli_query($conn, "SELECT id, name, price, image FROM products ORDER BY RAND() LIMIT 5");
if ($result_products) {
    while ($row = mysqli_fetch_assoc($result_products)) {
        $recommended_products[] = $row;
    }
} else {
    error_log("Failed to fetch recommended products: " . mysqli_error($conn));
}


$notifications = [];
$stmt_shipped_orders = $conn->prepare("SELECT id, status_shipped_date FROM `order` WHERE email = ? AND status = 'shipped' AND status_shipped_date IS NOT NULL ORDER BY status_shipped_date DESC LIMIT 5");

if ($stmt_shipped_orders) {
    $stmt_shipped_orders->bind_param("s", $user_email);
    $stmt_shipped_orders->execute();
    $result_shipped_orders = $stmt_shipped_orders->get_result();

    while ($row = $result_shipped_orders->fetch_assoc()) {
        $notifications[] = [
            'message' => "Your order <strong>#LL" . htmlspecialchars($row['id']) . "</strong> has been <strong>shipped!</strong>",
            'icon' => 'fa-truck', 
            'timestamp' => date("M d, Y h:i A", strtotime($row['status_shipped_date'])),
            'unread' => true 
        ];
    }
    $stmt_shipped_orders->close();
} else {
    error_log("Failed to fetch shipped order notifications: " . $conn->error);
}

$notifications[] = [
    'message' => "Earn <strong>double points</strong> on your next purchase this weekend!",
    'icon' => 'fa-star',
    'timestamp' => "1 hour ago",
    'unread' => true
];
$notifications[] = [
    'message' => "Price drop on your saved item: <strong>Vintage Leather Bag!</strong>",
    'icon' => 'fa-tag',
    'timestamp' => "Yesterday",
    'unread' => false
];
$notifications[] = [
    'message' => "Your support ticket <strong>#TSK-9876</strong> has been updated.",
    'icon' => 'fa-life-ring',
    'timestamp' => "July 10, 2025",
    'unread' => false
];



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Account - LIXN LEE</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="styles.css" rel="stylesheet">
    <link href="premium_account.css" rel="stylesheet">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="user-page-wrapper">
        <div class="user-page-container">

            <?php include 'user_sidebar.php'; ?>
            <main class="user-content">
                <section id="dashboard" class="user-section active-section">
                    <h2>Dashboard</h2>
                    <div class="dashboard-grid">
                        <div class="card dashboard-card recent-orders-card">
                            <h4><i class="fas fa-box"></i> Recent Orders</h4>
                            <?php if (!empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="order-item">
                                        <span>Order #LL<?php echo htmlspecialchars($order['id']); ?></span>
                                        <span class="status <?php echo strtolower(htmlspecialchars($order['status'])); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 14px; color: var(--grey);">No recent orders found.</p>
                            <?php endif; ?>
                            <a href="userorder.php" class="btn btn-outline btn-sm" style="margin-top: 15px;">View All Orders</a>
                        </div>

                        <div class="card dashboard-card loyalty-card">
                            <h4><i class="fas fa-award"></i> Your Loyalty Points</h4>
                            <div class="loyalty-points"><?php echo htmlspecialchars($user['loyalty_points'] ?? 0); ?></div>
                            <div class="loyalty-tier">
                                <?php
                                    $points = $user['loyalty_points'] ?? 0;
                                    if ($points >= 3000) echo "Platinum Member";
                                    else if ($points >= 1500) echo "Gold Member";
                                    else echo "Silver Member";
                                ?>
                            </div>
                            <div class="loyalty-progress-bar">
                                <?php
                                    $max_points_for_next_tier = 3000;
                                    $progress_percentage = ($points / $max_points_for_next_tier) * 100;
                                    if ($progress_percentage > 100) $progress_percentage = 100;
                                ?>
                                <div class="loyalty-progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
                            </div>
                            <p class="loyalty-progress-label">You need <?php echo max(0, 3000 - ($user['loyalty_points'] ?? 0)); ?> more points for Platinum!</p>
                            <a href="#loyalty-rewards" class="btn btn-outline btn-sm" style="margin-top: 20px;">Redeem Rewards</a>
                        </div>

                        <div class="card dashboard-card recommendations-card" style="grid-column: 1 / -1;">
                            <h4><i class="fas fa-magic"></i> Recommended for You</h4>
                            <div class="product-carousel">
                                <?php if (!empty($recommended_products)): ?>
                                    <?php foreach ($recommended_products as $product): ?>
                                        <div class="product-item">
    <img src="uploaded_img/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    <h5><?php echo htmlspecialchars($product['name']); ?></h5>
    <p class="price">RS <?php echo number_format(htmlspecialchars($product['price']), 2); ?></p>    <button class="btn btn-primary btn-sm">Add to Cart</button>
</div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="font-size: 14px; color: var(--grey);">No recommendations available at the moment.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card dashboard-card quick-links-card">
                            <h4><i class="fas fa-external-link-alt"></i> Quick Actions</h4>
                            <ul>
                                <li><a href="edit_account.php"><i class="fas fa-edit"></i> Update Profile</a></li>
                                <li><a href="#payment-methods"><i class="fas fa-credit-card"></i> Manage Payments</a></li>
                                <li><a href="#addresses"><i class="fas fa-home"></i> Update Addresses</a></li>
                                <li><a href="#support"><i class="fas fa-question-circle"></i> Get Support</a></li>
                            </ul>
                        </div>

                        <div class="card dashboard-card notifications-summary-card">
                            <h4><i class="fas fa-bell"></i> Latest Notifications</h4>
                            <?php if (!empty($notifications)): ?>
                                <?php
                                $display_notifications = array_slice($notifications, 0, 3);
                                foreach ($display_notifications as $notification): ?>
                                    <div class="notification-item <?php echo $notification['unread'] ? 'unread' : ''; ?>">
                                        <i class="fas <?php echo htmlspecialchars($notification['icon']); ?>"></i>
                                        <div class="notification-content">
                                            <p><?php echo $notification['message']; ?></p>
                                            <span class="notification-timestamp"><?php echo htmlspecialchars($notification['timestamp']); ?></span>
                                        </div>
                                        <button class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 14px; color: var(--grey);">No new notifications.</p>
                            <?php endif; ?>
                            <a href="#notifications" class="btn btn-outline btn-sm" style="margin-top: 15px;">View All Notifications</a>
                        </div>

                    </div>
                </section>

                <section id="profile-details" class="user-section hidden">
                    <h2>My Details</h2>
                    <div class="card">
                        <h3><i class="fas fa-info-circle"></i> Personal Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
                        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['dob']); ?></p>
                        <p><strong>Street:</strong> <?php echo htmlspecialchars($user['street']); ?></p>
                        <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
                        <p><strong>Country:</strong> <?php echo htmlspecialchars($user['country']); ?></p>
                        <a href="edit account.php" class="btn btn-primary btn-sm" style="margin-top: 20px;">Edit My Details</a>
                    </div>
                </section>

                <section id="orders" class="user-section hidden">
                    <h2>My Orders</h2>
                    <div class="order-filters">
                        <select>
                            <option>All Orders</option>
                            <option>Delivered</option>
                            <option>Processing</option>
                            <option>Cancelled</option>
                        </select>
                        <div class="search-group">
                            <input type="text" placeholder="Search orders by ID or product name">
                            <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="order-list">
                        <div class="card order-card-detailed">
                            <div class="order-header">
                                <span class="order-id">Order #LL7890</span>
                                <span class="order-date">Placed: July 15, 2025</span>
                                <span class="order-total">Total: <strong>$125.00</strong></span>
                                <span class="order-status-badge processing">Processing</span>
                            </div>
                            <div class="order-products-summary">
                                <img src="https://placehold.co/60/f0f0f0/333333?text=P1" alt="Product 1" class="product-summary-thumb">
                                <img src="https://placehold.co/60/f0f0f0/333333?text=P2" alt="Product 2" class="product-summary-thumb">
                                <span class="product-count">+1 more item</span>
                            </div>
                            <div class="order-actions">
                                <button class="btn btn-secondary btn-sm">View Details</button>
                                <button class="btn btn-outline btn-sm">Track Order</button>
                                <button class="btn btn-outline btn-sm">Need Help?</button>
                            </div>
                        </div>
                        <div class="card order-card-detailed">
                            <div class="order-header">
                                <span class="order-id">Order #LL7889</span>
                                <span class="order-date">Placed: July 12, 2025</span>
                                <span class="order-total">Total: <strong>$210.50</strong></span>
                                <span class="order-status-badge">Shipped</span>
                            </div>
                            <div class="order-products-summary">
                                <img src="https://placehold.co/60/f0f0f0/333333?text=P3" alt="Product 3" class="product-summary-thumb">
                                <span class="product-count">+2 more items</span>
                            </div>
                            <div class="order-actions">
                                <button class="btn btn-secondary btn-sm">View Details</button>
                                <button class="btn btn-outline btn-sm">Track Order</button>
                                <button class="btn btn-primary btn-sm">Leave Review</button>
                            </div>
                        </div>
                    </div>
                    <div class="pagination">
                        <a href="#">Prev</a>
                        <a href="#" class="active">1</a>
                        <a href="#">2</a>
                        <a href="#">3</a>
                        <a href="#">Next</a>
                    </div>
                </section>

                <section id="wishlist" class="user-section hidden">
                    <h2>My Wishlist</h2>
                    <div class="wishlist-grid">
                        <div class="card wishlist-item">
                            <img src="https://placehold.co/250x180/f0f0f0/333333?text=Product+X" alt="Product X">
                            <h4>Luxury Silk Eye Mask</h4>
                            <p class="price">$45.00</p>
                            <p class="availability in-stock">In Stock</p>
                            <div class="wishlist-actions">
                                <button class="btn btn-primary btn-sm">Add to Cart</button>
                                <button class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Remove</button>
                            </div>
                        </div>
                        <div class="card wishlist-item">
                            <img src="https://placehold.co/250x180/f0f0f0/333333?text=Product+Y" alt="Product Y">
                            <h4>Designer Ceramic Coasters (Set of 4)</h4>
                            <p class="price">$28.00</p>
                            <p class="availability out-of-stock">Out of Stock</p>
                            <div class="wishlist-actions">
                                <button class="btn btn-secondary btn-sm" disabled>Notify Me</button>
                                <button class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Remove</button>
                            </div>
                        </div>
                        <div class="card wishlist-item">
                            <img src="https://placehold.co/250x180/f0f0f0/333333?text=Product+Z" alt="Product Z">
                            <h4>Vintage Pendant Necklace</h4>
                            <p class="price">$72.00</p>
                            <p class="availability in-stock">In Stock</p>
                            <div class="wishlist-actions">
                                <button class="btn btn-primary btn-sm">Add to Cart</button>
                                <button class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Remove</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="addresses" class="user-section hidden">
                    <h2>My Addresses</h2>
                    <div class="address-list">
                        <div class="card address-card">
                            <h4>Default Shipping Address</h4>
                            <p><?php echo htmlspecialchars($user['name']); ?></p>
                            <p><?php echo htmlspecialchars($user['street']); ?></p>
                            <p><?php echo htmlspecialchars($user['city']); ?></p>
                            <p><?php echo htmlspecialchars($user['country']); ?></p>
                            <p>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                            <div class="actions">
                                <button class="btn btn-secondary btn-sm">Edit</button>
                                <button class="btn btn-outline btn-sm">Delete</button>
                            </div>
                        </div>
                        <div class="card address-card">
                            <h4>Billing Address (Same as Shipping)</h4>
                            <p><?php echo htmlspecialchars($user['name']); ?></p>
                            <p><?php echo htmlspecialchars($user['street']); ?></p>
                            <p><?php echo htmlspecialchars($user['city']); ?></p>
                            <p><?php echo htmlspecialchars($user['country']); ?></p>
                            <p>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                            <div class="actions">
                                <button class="btn btn-secondary btn-sm">Edit</button>
                                <button class="btn btn-outline btn-sm">Delete</button>
                                <button class="btn btn-primary btn-sm">Set as Default Shipping</button>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary add-new-btn"><i class="fas fa-plus"></i> Add New Address</button>
                </section>

                <section id="payment-methods" class="user-section hidden">
                    <h2>Payment Methods</h2>
                    <div class="payment-list">
                        <div class="card payment-card">
                            <h4>Preferred Payment Method</h4>
                            <div class="card-info">
                                <?php if (($user['preferred_payment'] ?? '') == 'Card'): ?>
                                    <i class="fas fa-credit-card" style="font-size: 30px; color: var(--primary-color);"></i>
                                <?php else: ?>
                                    <i class="fas fa-money-bill-wave" style="font-size: 30px; color: var(--primary-color);"></i>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($user['preferred_payment'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="actions">
                                <form method="post" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                    <label for="preferred_payment" style="margin-right: 5px; font-weight: 500; color: var(--dark-grey);">Change:</label>
                                    <select name="preferred_payment" id="preferred_payment" class="form-control" style="flex-grow: 1; max-width: 200px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--border-radius-soft); font-size: 15px;">
                                        <option value="Cash on Delivery" <?php if (($user['preferred_payment'] ?? '') == 'Cash on Delivery') echo 'selected'; ?>>Cash on Delivery</option>
                                        <option value="Card" <?php if (($user['preferred_payment'] ?? '') == 'Card') echo 'selected'; ?>>Card</option>
                                    </select>
                                    <button type="submit" name="update_payment" class="btn btn-primary btn-sm">Update</button>
                                </form>
                            </div>
                        </div>
                        <div class="card payment-card">
                            <h4>Other Saved Card</h4>
                            <div class="card-info">
                                <img src="https://placehold.co/50x30/FF5722/FFFFFF?text=MC" alt="MasterCard" class="card-logo">
                                <p>MasterCard **** **** **** 5678</p>
                            </div>
                            <p>Expires: 06/26</p>
                            <div class="actions">
                                <button class="btn btn-secondary btn-sm">Edit</button>
                                <button class="btn btn-outline btn-sm">Remove</button>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary add-new-btn"><i class="fas fa-plus"></i> Add New Payment Method</button>
                </section>

                <section id="loyalty-rewards" class="user-section hidden">
                    <h2>Loyalty & Rewards</h2>
                    <div class="card loyalty-card" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); color: var(--white); text-align: center;">
                        <h4><i class="fas fa-award"></i> Your Current Loyalty Status</h4>
                        <div class="loyalty-points"><?php echo htmlspecialchars($user['loyalty_points'] ?? 0); ?></div>
                        <div class="loyalty-tier">
                            <?php
                                $points = $user['loyalty_points'] ?? 0;
                                if ($points >= 3000) echo "Platinum Member";
                                else if ($points >= 1500) echo "Gold Member";
                                else echo "Silver Member";
                            ?>
                        </div>
                        <div class="loyalty-progress-bar" style="margin-bottom: 10px;">
                            <?php
                                $max_points_for_next_tier = 3000;
                                $progress_percentage = ($points / $max_points_for_next_tier) * 100;
                                if ($progress_percentage > 100) $progress_percentage = 100;
                            ?>
                            <div class="loyalty-progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
                        </div>
                        <p class="loyalty-progress-label">You need <?php echo max(0, 3000 - ($user['loyalty_points'] ?? 0)); ?> more points for Platinum!</p>
                        <button class="btn btn-outline btn-sm" style="margin-top: 20px;">Explore Rewards</button>
                    </div>

                    <div class="card" style="margin-top: 30px;">
                        <h3><i class="fas fa-gift"></i> Available Rewards</h3>
                        <p>Here you can see and redeem your earned rewards.</p>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 10px; font-size: 15px; color: var(--dark-grey);"><i class="fas fa-check-circle" style="color: var(--success-color); margin-right: 10px;"></i> Free Standard Shipping (Available Now!)</li>
                            <li style="margin-bottom: 10px; font-size: 15px; color: var(--dark-grey);"><i class="fas fa-hourglass-half" style="color: var(--warning-color); margin-right: 10px;"></i> 10% Off Next Purchase (Requires 500 more points)</li>
                        </ul>
                    </div>
                </section>

                <section id="coupons" class="user-section hidden">
                    <h2>My Coupons</h2>
                    <div class="coupon-grid">
                        <div class="card coupon-card">
                            <div class="coupon-details">
                                <h4>WELCOME15</h4>
                                <p>Get 15% off your first order!</p>
                                <p class="expiration">Expires: Dec 31, 2025</p>
                            </div>
                            <button class="btn btn-primary btn-sm">Copy Code</button>
                        </div>
                        <div class="card coupon-card">
                            <div class="coupon-details">
                                <h4>FREESHIPJULY</h4>
                                <p>Enjoy free shipping on all orders this July.</p>
                                <p class="expiration">Expires: July 31, 2025</p>
                            </div>
                            <button class="btn btn-primary btn-sm">Copy Code</button>
                        </div>
                        <div class="card coupon-card" style="opacity: 0.7;">
                            <div class="coupon-details">
                                <h4>SUMMERSALE20</h4>
                                <p>20% off selected summer items.</p>
                                <p class="expiration" style="color: var(--grey);">Expired: June 30, 2025</p>
                            </div>
                            <button class="btn btn-secondary btn-sm" disabled>Expired</button>
                        </div>
                    </div>
                </section>


                <section id="reviews" class="user-section hidden">
                    <h2>My Reviews</h2>
                    <div class="review-list">
                        <div class="card review-item">
                            <h4><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i> (4.5/5)</h4>
                            <p class="product-name">Product: Elegant Silk Scarf</p>
                            <p>"Absolutely beautiful scarf! The silk feels luxurious and the colors are vibrant. Highly recommend."</p>
                            <span class="timestamp">Reviewed on: July 10, 2025</span>
                            <button class="btn btn-outline btn-sm" style="align-self: flex-end; margin-top: 10px;">Edit Review</button>
                        </div>
                        <div class="card review-item">
                            <h4><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i> (4/5)</h4>
                            <p class="product-name">Product: Handcrafted Leather Wallet</p>
                            <p>"A very well-made wallet, the leather is soft and durable. Only wish it had more card slots."</p>
                            <span class="timestamp">Reviewed on: June 28, 2025</span>
                            <button class="btn btn-outline btn-sm" style="align-self: flex-end; margin-top: 10px;">Edit Review</button>
                        </div>
                    </div>
                </section>

                <section id="profile-settings" class="user-section hidden">
                    <h2>Profile Settings</h2>
                    <div class="card profile-form">
                        <h4>Account Information</h4>
                        <p style="margin-bottom: 20px;">To edit your personal details, please click the button below. You will be redirected to the dedicated edit page.</p>
                        <a href="edit account.php" class="btn btn-primary" style="align-self: flex-start;">Go to Edit Profile</a>
                    </div>

                    <div class="card notification-preferences">
                        <h4>Notification Preferences</h4>
                        <div class="checkbox-group">
                            <input type="checkbox" id="emailNotifications" checked>
                            <label for="emailNotifications">Email Notifications</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="smsNotifications">
                            <label for="smsNotifications">SMS Notifications</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="appNotifications" checked>
                            <label for="appNotifications">In-App Notifications</label>
                        </div>
                        <button class="btn btn-primary btn-sm">Update Preferences</button>
                    </div>

                    <div class="card account-actions">
                        <h4>Account Actions</h4>
                        <button class="btn btn-danger">Deactivate Account</button>
                        <button class="btn btn-outline btn-danger">Delete Account</button>
                    </div>
                </section>

                <section id="notifications" class="user-section hidden">
                    <h2>Notifications</h2>
                    <div class="notification-list">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo $notification['unread'] ? 'unread' : ''; ?>">
                                    <i class="fas <?php echo htmlspecialchars($notification['icon']); ?>"></i>
                                    <div class="notification-content">
                                        <p><?php echo $notification['message']; ?></p>
                                        <span class="notification-timestamp"><?php echo htmlspecialchars($notification['timestamp']); ?></span>
                                    </div>
                                    <div class="notification-actions">
                                        <button class="btn btn-sm btn-primary">View</button>
                                        <button class="btn btn-sm btn-outline"><i class="fas fa-check"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size: 14px; color: var(--grey);">No new notifications.</p>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-outline" style="margin-top: 20px;">Mark All As Read</button>
                </section>

                <section id="returns" class="user-section hidden">
                    <h2>Returns & Refunds</h2>
                    <p style="margin-bottom: 20px;">Easily manage your returns and view your refund history here.</p>
                    <div class="card">
                        <h3>Initiate a New Return</h3>
                        <p style="margin-bottom: 15px;">Select an order to start the return process:</p>
                        <select style="width: 100%; padding: 10px; border-radius: var(--border-radius-soft); border: 1px solid var(--border-color); margin-bottom: 20px;">
                            <option value="">Select Order...</option>
                            <option value="LL7890">Order #LL7890 - Placed July 15, 2025</option>
                            <option value="LL7889">Order #LL7889 - Placed July 12, 2025</option>
                        </select>
                        <button class="btn btn-primary" disabled>Start Return</button>
                    </div>

                    <div class="card" style="margin-top: 30px;">
                        <h3>Recent Returns</h3>
                        <div class="notification-list" style="gap:10px;">
                            <div class="notification-item">
                                <i class="fas fa-undo"></i>
                                <div class="notification-content">
                                    <p>Return Request #R-1025 for Order #LL7888</p>
                                    <span class="notification-timestamp" style="color:var(--warning-color); font-weight:600;">Status: Pending Approval</span>
                                </div>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-secondary">Details</button>
                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-check-circle"></i>
                                <div class="notification-content">
                                    <p>Refund Processed for Order #LL7870</p>
                                    <span class="notification-timestamp" style="color:var(--success-color); font-weight:600;">Status: Completed ($49.99)</span>
                                </div>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-secondary">View</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="support" class="user-section hidden">
                    <h2>Help & Support</h2>
                    <p style="margin-bottom: 20px;">We're here to help! Find quick answers or connect with our support team.</p>

                    <div class="contact-options">
                        <div class="card contact-card">
                            <i class="fas fa-envelope"></i>
                            <h4>Email Support</h4>
                            <p>For non-urgent inquiries.</p>
                            <a href="mailto:support@lixnlee.com" class="btn btn-outline btn-sm">Send Email</a>
                        </div>
                        <div class="card contact-card">
                            <i class="fas fa-comments"></i>
                            <h4>Live Chat</h4>
                            <p>Instant help during business hours.</p>
                            <button class="btn btn-primary btn-sm">Start Chat</button>
                        </div>
                        <div class="card contact-card">
                            <i class="fas fa-phone-alt"></i>
                            <h4>Call Us</h4>
                            <p>+94 11 123 4567</p>
                            <button class="btn btn-outline btn-sm">Call Now</button>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 30px;">
                        <h3>My Support Tickets</h3>
                        <div class="notification-list" style="gap:10px;">
                            <div class="notification-item">
                                <i class="fas fa-ticket-alt"></i>
                                <div class="notification-content">
                                    <p>Ticket #TSK-9876: Issue with order #LL7880</p>
                                    <span class="notification-timestamp" style="color:var(--primary-light); font-weight:600;">Status: Open</span>
                                </div>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-secondary">View</button>
                                </div>
                            </div>
                            <div class="notification-item">
                                <i class="fas fa-check-circle"></i>
                                <div class="notification-content">
                                    <p>Ticket #TSK-9875: Inquiry about product returns</p>
                                    <span class="notification-timestamp" style="color:var(--success-color); font-weight:600;">Status: Closed</span>
                                </div>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-secondary">View</button>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary add-new-btn"><i class="fas fa-plus"></i> Submit New Ticket</button>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <footer class="site-footer">
        <p>&copy; <?php echo date("Y"); ?> LIXN LEE. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userNavLinks = document.querySelectorAll('.user-nav a');
            const userSections = document.querySelectorAll('.user-section');
            const defaultActiveSectionId = 'dashboard'; 

            const showSection = (targetId) => {
                userNavLinks.forEach(link => {
                    link.parentElement.classList.remove('active');
                });
                userSections.forEach(section => {
                    section.classList.remove('active-section');
                    section.classList.add('hidden'); 
                });

                const targetLink = document.querySelector(`.user-nav a[href="#${targetId}"]`);
                const targetSection = document.getElementById(targetId);

                if (targetLink) {
                    targetLink.parentElement.classList.add('active');
                }
                if (targetSection) {
                    targetSection.classList.remove('hidden'); 
                    void targetSection.offsetWidth; 
                    targetSection.classList.add('active-section'); 
                }
            };

            // Handle initial section display (from URL hash or default)
            const initialHash = window.location.hash ? window.location.hash.substring(1) : defaultActiveSectionId;
            showSection(initialHash);

            userNavLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (link.getAttribute('href').startsWith('#')) {
                        e.preventDefault(); 
                        const targetId = link.getAttribute('href').substring(1);
                        showSection(targetId);
                        history.pushState(null, '', link.getAttribute('href')); 
                    } else {
                        
                    }
                });
            });

            window.addEventListener('popstate', () => {
                const newHash = window.location.hash ? window.location.hash.substring(1) : defaultActiveSectionId;
                showSection(newHash);
            });
        });
    </script>
</body>
</html>
