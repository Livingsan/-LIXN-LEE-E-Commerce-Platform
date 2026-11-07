<?php
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if (!isset($_SESSION['user_email'])) {
    // Using JavaScript for redirection and message, as alert() is discouraged in Canvas for direct user interaction.
    // In a production environment, this would typically be a server-side redirect with a session message.
    echo "<script>alert('Please log in first!'); window.location.href='userlogin.php';</script>";
    exit();
}

// Fetch wishlist and cart counts for the navbar
$wishlist_count = 0;
$cart_count = 0;
if ($user_email) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart);
}

// --- PHP Logic for fetching user orders ---
// This section fetches data from your `order` table and categorizes it.
// IMPORTANT: For 'items' array within each order, placeholder data is used here
// because the provided PHP only has `total_products` as a string.
// In a real application, you would need to fetch detailed product information for each order
// from your database (e.g., from an `order_items` table linked to your `order` table).

$orders_query = mysqli_query($conn, "SELECT * FROM `order` WHERE email='$user_email' ORDER BY id DESC");

$order_data = [
    'ongoing' => [],
    'completed' => [],
    'canceled' => []
];

while ($row = mysqli_fetch_assoc($orders_query)) {
    $order_id = 'ORD-' . date('Y') . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
    $status = strtolower($row['status']);

    // Map database statuses to display badge classes
    $badge_class = '';
    if ($status === 'completed' || $status === 'delivered') {
        $badge_class = 'status-completed';
    } elseif ($status === 'pending') {
        $badge_class = 'status-pending';
    } elseif ($status === 'shipped' || $status === 'out for delivery' || $status === 'processing') {
        $badge_class = 'status-ongoing';
    } elseif ($status === 'canceled') {
        $badge_class = 'status-canceled';
    } else {
        $badge_class = 'status-ongoing'; // Default for unknown statuses
    }

    // Placeholder for detailed order items (replace with actual DB fetch)
    $items_placeholder = [];
    // Example placeholder data for demonstration purposes
    if ($order_id == 'ORD-2024-001') {
        $items_placeholder = [
            ['product_name' => 'Elegant Silk Scarf', 'quantity' => 1, 'price' => 45.00, 'image_url' => 'https://placehold.co/60x60/83666e/white?text=Scarf'],
            ['product_name' => 'Leather Handbag', 'quantity' => 1, 'price' => 75.50, 'image_url' => 'https://placehold.co/60x60/A9A9A9/white?text=Bag']
        ];
    } elseif ($order_id == 'ORD-2024-002') {
        $items_placeholder = [
            ['product_name' => 'Classic Watch', 'quantity' => 1, 'price' => 85.00, 'image_url' => 'https://placehold.co/60x60/83666e/white?text=Watch']
        ];
    } elseif ($order_id == 'ORD-2024-003') {
        $items_placeholder = [
            ['product_name' => 'Designer Dress', 'quantity' => 1, 'price' => 150.00, 'image_url' => 'https://placehold.co/60x60/A9A9A9/white?text=Dress'],
            ['product_name' => 'High Heels', 'quantity' => 1, 'price' => 100.00, 'image_url' => 'https://placehold.co/60x60/83666e/white?text=Heels']
        ];
    } elseif ($order_id == 'ORD-2024-004') {
        $items_placeholder = [
            ['product_name' => 'Bluetooth Headphones', 'quantity' => 1, 'price' => 55.99, 'image_url' => 'https://placehold.co/60x60/83666e/white?text=Headphones']
        ];
    } elseif ($order_id == 'ORD-2024-005') {
        $items_placeholder = [
            ['product_name' => 'Graphic T-Shirt', 'quantity' => 1, 'price' => 30.00, 'image_url' => 'https://placehold.co/60x60/A9A9A9/white?text=T-Shirt']
        ];
    }
    // End placeholder data

    $order = [
        'id' => $order_id,
        'trackingNumber' => isset($row['tracking_number']) ? $row['tracking_number'] : 'N/A', // Assuming tracking_number column exists
        'date' => $row['order_date'],
        'total' => 'Rs.' . number_format($row['total_price'], 2), // Format total price
        'items_summary' => $row['total_products'], // Keep summary string for fallback
        'items' => $items_placeholder, // Use placeholder items array
        'estimatedDelivery' => $row['delivery_date'] ?? 'TBD',
        'status' => ucfirst($status),
        'badge' => $badge_class,
        'db_id' => $row['id'],
        'cancellation_reason' => $row['cancellation_reason'] ?? '' // Assuming cancellation_reason column exists
    ];

    // Categorize orders based on the mapped badge class
    if ($badge_class === 'status-completed') {
        $order_data['completed'][] = $order;
    } elseif ($badge_class === 'status-canceled') {
        $order_data['canceled'][] = $order;
    } else {
        $order_data['ongoing'][] = $order;
    }
}
// --- END PHP Logic for fetching user orders ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Orders - LIXN LEE</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <!-- Using Bootstrap for grid system and basic form elements, but overriding styles for theme consistency -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <style>
        :root {
            --primary-color: #83666e;
            --secondary-color: #a0747c; /* A slightly lighter shade for hover/accents */
            --grey: #A9A9A9;
            --light-grey: #e0e0e0;
            --white: #fff;
            --text-color: #333;
            --background-color: #f8f8f8;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --pending-color: #fd7e14; /* Orange for pending */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        /* === TOP HEADER (EXACTLY AS PROVIDED) === */
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
            color: var(--grey);
        }

        .head-txt span {
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.9;
            color: var(--grey);
        }

        .sing_in_up a {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            color: var(--grey);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-decoration: none;
            margin-left: 10px;
            transition: color 0.3s ease;
        }

        .sing_in_up a:hover {
            color: black;
        }

        /* === NAVBAR (EXACTLY AS PROVIDED) === */
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
            color: var(--grey);
            text-decoration: none;
            transition: color 0.3s ease;
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

        /* Main content wrapper to align with navbar's container */
        .main-content-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* General Page Title */
        h1 {
            color: var(--primary-color);
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            text-align: center;
            margin-bottom: 40px;
        }

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--light-grey);
        }

        .tab-button {
            background-color: transparent;
            border: none;
            padding: 15px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--grey);
            cursor: pointer;
            transition: color 0.3s ease, border-bottom 0.3s ease;
            position: relative;
            top: 2px; /* To align with border-bottom */
        }

        .tab-button:hover {
            color: var(--primary-color);
        }

        .tab-button.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }

        /* Order List and Cards */
        .order-list {
            display: none; /* Hidden by default */
            padding-top: 20px;
        }

        .order-list.active {
            display: block; /* Show when active */
        }

        .order-card {
            background-color: var(--white);
            border: 1px solid var(--light-grey);
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--light-grey);
        }

        .order-header h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.3rem;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-header .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--white);
        }

        .status-badge.status-completed { background-color: var(--success-color); }
        .status-badge.status-ongoing { background-color: var(--warning-color); }
        .status-badge.status-canceled { background-color: var(--danger-color); }
        .status-badge.status-pending { background-color: var(--pending-color); }

        .order-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .order-details p {
            margin: 0;
            font-size: 0.95rem;
            color: var(--text-color);
        }

        .order-details p strong {
            color: #555;
        }

        .order-items-toggle {
            display: block;
            width: 100%;
            background: none;
            border: none;
            color: var(--primary-color);
            font-weight: 600;
            padding: 10px 0;
            text-align: left;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .order-items-toggle:hover {
            color: var(--secondary-color);
        }

        .order-items-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out;
        }

        .order-items-container.expanded {
            max-height: 500px; /* Adjust based on expected content height */
            transition: max-height 0.5s ease-in;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 0;
            border-top: 1px dotted var(--light-grey);
        }

        .order-item:first-child {
            border-top: none;
        }

        .order-item img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--light-grey);
            flex-shrink: 0; /* Prevent image from shrinking */
        }

        .item-info {
            flex-grow: 1;
        }

        .item-info p {
            margin: 0;
            font-size: 0.9rem;
        }

        .item-info .product-name {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed var(--light-grey);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-success { background-color: var(--success-color); }
        .btn-success:hover { background-color: #218838; }
        .btn-info { background-color: var(--info-color); } /* Track button color */
        .btn-info:hover { background-color: #138496; }
        .btn-secondary { background-color: var(--grey); }
        .btn-secondary:hover { background-color: #929292; }
        .btn-danger { background-color: var(--danger-color); }
        .btn-danger:hover { background-color: #c82333; }

        /* Custom Modal for Refund/Cancel Confirmation */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: var(--white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 90%;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        .modal-content h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.8rem;
            font-family: 'Playfair Display', serif;
        }
        .modal-content p {
            font-size: 1.1rem;
            color: var(--text-color);
            margin-bottom: 25px;
        }
        .modal-content .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
        }
        .modal-content .modal-buttons .btn {
            margin-top: 0;
            padding: 12px 25px;
            font-size: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .modal-content .modal-buttons .btn-cancel {
            background-color: var(--grey);
            color: var(--white);
        }
        .modal-content .modal-buttons .btn-cancel:hover {
            background-color: #929292;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .main-content-wrapper {
                margin: 20px auto;
                padding: 20px;
            }
            .head, .navbar-container {
                padding: 15px 20px;
            }
            .menu-items {
                gap: 1rem;
            }
            .menu-right {
                margin-left: 1rem;
            }
        }

        @media (max-width: 768px) {
            .head {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            .sing_in_up {
                margin-top: 10px;
            }
            .navbar-container {
                flex-direction: column;
                height: auto;
                padding: 15px 20px;
            }
            .menu-items {
                flex-direction: column;
                gap: 0.5rem;
                margin-left: 0;
                margin-top: 15px;
                width: 100%;
                align-items: center;
            }
            .menu-right {
                margin-top: 15px;
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }
            .tab-nav {
                flex-direction: column;
                border-bottom: none; /* Remove border on small screens */
            }
            .tab-button {
                width: 100%;
                border-bottom: 1px solid var(--light-grey);
                margin-bottom: 5px;
            }
            .tab-button.active {
                border-bottom: 2px solid var(--primary-color);
            }
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .order-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            .btn {
                width: 100%;
            }
            .order-item {
                flex-direction: column;
                align-items: flex-start;
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

    <div class="main-content-wrapper">
        <h1>My Orders</h1>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-button active" data-tab="ongoing">Ongoing Orders</button>
            <button class="tab-button" data-tab="completed">Completed Orders</button>
            <button class="tab-button" data-tab="canceled">Canceled Orders</button>
        </div>

        <!-- Ongoing Orders Section -->
        <div id="ongoing" class="order-list active">
            <?php if (empty($order_data['ongoing'])): ?>
                <p class="text-center py-4">You have no ongoing orders at the moment.</p>
            <?php else: ?>
                <?php foreach ($order_data['ongoing'] as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Order #<?= htmlspecialchars($order['id']) ?></h3>
                            <span class="status-badge <?= htmlspecialchars($order['badge']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                        </div>
                        <div class="order-details">
                            <p><strong>Order Date:</strong> <?= htmlspecialchars($order['date']) ?></p>
                            <p><strong>Total:</strong> <?= htmlspecialchars($order['total']) ?></p>
                            <p><strong>Estimated Delivery:</strong> <?= htmlspecialchars($order['estimatedDelivery']) ?></p>
                            <?php if ($order['trackingNumber'] !== 'N/A'): ?>
                                <p><strong>Tracking No.:</strong> <?= htmlspecialchars($order['trackingNumber']) ?></p>
                            <?php endif; ?>
                        </div>
                        <button class="order-items-toggle" onclick="toggleOrderItems(this)">
                            View Products <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="order-items-container">
                            <?php if (!empty($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <div class="item-info">
                                            <p class="product-name"><?= htmlspecialchars($item['product_name']) ?></p>
                                            <p>Qty: <?= htmlspecialchars($item['quantity']) ?> | Price: $<?= number_format($item['price'], 2) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center py-2">Items: <?= htmlspecialchars($order['items_summary']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="order-actions">
                            <?php if ($order['trackingNumber'] !== 'N/A'): ?>
                                <a href="https://www.example.com/track?id=<?= htmlspecialchars($order['trackingNumber']) ?>" target="_blank" class="btn btn-info">
                                    <i class="fas fa-map-marker-alt"></i> Track Order
                                </a>
                            <?php endif; ?>
                            <a href="invoice.php?order_id=<?= htmlspecialchars($order['db_id']) ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if (strtolower($order['status']) === 'pending' || strtolower($order['status']) === 'processing'): // Allow cancellation for pending/processing ?>
                                <button class="btn btn-danger" onclick="showConfirmationModal('cancel', '<?= htmlspecialchars($order['id']) ?>', 'cancel_order.php?id=<?= htmlspecialchars($order['db_id']) ?>')">
                                    <i class="fas fa-times-circle"></i> Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Completed Orders Section -->
        <div id="completed" class="order-list">
            <?php if (empty($order_data['completed'])): ?>
                <p class="text-center py-4">You have no completed orders yet.</p>
            <?php else: ?>
                <?php foreach ($order_data['completed'] as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Order #<?= htmlspecialchars($order['id']) ?></h3>
                            <span class="status-badge <?= htmlspecialchars($order['badge']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                        </div>
                        <div class="order-details">
                            <p><strong>Order Date:</strong> <?= htmlspecialchars($order['date']) ?></p>
                            <p><strong>Delivery Date:</strong> <?= htmlspecialchars($order['estimatedDelivery']) ?></p>
                            <p><strong>Total:</strong> <?= htmlspecialchars($order['total']) ?></p>
                        </div>
                        <button class="order-items-toggle" onclick="toggleOrderItems(this)">
                            View Products <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="order-items-container">
                            <?php if (!empty($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <div class="item-info">
                                            <p class="product-name"><?= htmlspecialchars($item['product_name']) ?></p>
                                            <p>Qty: <?= htmlspecialchars($item['quantity']) ?> | Price: $<?= number_format($item['price'], 2) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center py-2">Items: <?= htmlspecialchars($order['items_summary']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="order-actions">
                            <a href="refund.php?order_id=<?= htmlspecialchars($order['db_id']) ?>" class="btn btn-success">
                                <i class="fas fa-undo"></i> Request Refund
                            </a>
                            <a href="invoice.php?order_id=<?= htmlspecialchars($order['db_id']) ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Canceled Orders Section -->
        <div id="canceled" class="order-list">
            <?php if (empty($order_data['canceled'])): ?>
                <p class="text-center py-4">You have no canceled orders.</p>
            <?php else: ?>
                <?php foreach ($order_data['canceled'] as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Order #<?= htmlspecialchars($order['id']) ?></h3>
                            <span class="status-badge <?= htmlspecialchars($order['badge']) ?>"><?= htmlspecialchars($order['status']) ?></span>
                        </div>
                        <div class="order-details">
                            <p><strong>Order Date:</strong> <?= htmlspecialchars($order['date']) ?></p>
                            <?php if (!empty($order['cancellation_reason'])): ?>
                                <p><strong>Reason:</strong> <?= htmlspecialchars($order['cancellation_reason']) ?></p>
                            <?php endif; ?>
                            <p><strong>Total:</strong> <?= htmlspecialchars($order['total']) ?></p>
                        </div>
                        <button class="order-items-toggle" onclick="toggleOrderItems(this)">
                            View Products <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="order-items-container">
                            <?php if (!empty($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                        <div class="item-info">
                                            <p class="product-name"><?= htmlspecialchars($item['product_name']) ?></p>
                                            <p>Qty: <?= htmlspecialchars($item['quantity']) ?> | Price: $<?= number_format($item['price'], 2) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center py-2">Items: <?= htmlspecialchars($order['items_summary']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="order-actions">
                            <a href="invoice.php?order_id=<?= htmlspecialchars($order['db_id']) ?>" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <!-- Potentially add a "Reorder" button here -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Custom Confirmation Modal (for Refund and Cancel) -->
    <div id="confirmationModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle"></h3>
            <p id="modalMessage"></p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-cancel" onclick="hideConfirmationModal()">Cancel</button>
                <a id="confirmActionButton" href="#" class="btn">Confirm</a>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const orderLists = document.querySelectorAll('.order-list');

            // Set initial active tab based on URL hash or default to 'ongoing'
            const initialTab = window.location.hash ? window.location.hash.substring(1) : 'ongoing';
            showTab(initialTab);

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetTab = button.dataset.tab;
                    showTab(targetTab);
                    window.location.hash = targetTab; // Update URL hash
                });
            });
        });

        function showTab(tabId) {
            document.querySelectorAll('.order-list').forEach(list => list.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(button => button.classList.remove('active'));

            document.getElementById(tabId).classList.add('active');
            const activeButton = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }
        }

        // Toggle order items visibility
        function toggleOrderItems(button) {
            const container = button.nextElementSibling; // The .order-items-container
            const icon = button.querySelector('i');

            if (container.classList.contains('expanded')) {
                container.classList.remove('expanded');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                container.classList.add('expanded');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }

        // General Confirmation Modal functionality (for Refund and Cancel)
        function showConfirmationModal(actionType, orderId, actionUrl) {
            const modal = document.getElementById('confirmationModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const confirmActionButton = document.getElementById('confirmActionButton');

            if (actionType === 'refund') {
                modalTitle.textContent = 'Request Refund';
                modalMessage.innerHTML = `Are you sure you want to request a refund for Order <strong>${orderId}</strong>? This action will be processed by our team.`;
                confirmActionButton.textContent = 'Confirm Refund';
                confirmActionButton.className = 'btn btn-success'; // Set specific class for refund
            } else if (actionType === 'cancel') {
                modalTitle.textContent = 'Cancel Order';
                modalMessage.innerHTML = `Are you sure you want to cancel Order <strong>${orderId}</strong>? This action cannot be undone.`;
                confirmActionButton.textContent = 'Confirm Cancellation';
                confirmActionButton.className = 'btn btn-danger'; // Set specific class for cancel
            }

            confirmActionButton.href = actionUrl; // Set the action URL
            modal.classList.add('active');
        }

        function hideConfirmationModal() {
            document.getElementById('confirmationModal').classList.remove('active');
        }

        // Close modal if clicked outside
        document.getElementById('confirmationModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideConfirmationModal();
            }
        });

        // Placeholder for actual refund/cancel processing if not using direct link
        // For actual refund/cancel, you might use fetch() API here instead of direct link
        // Example:
        /*
        document.getElementById('confirmActionButton').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const actionUrl = this.href;
            fetch(actionUrl)
                .then(response => response.text()) // or .json()
                .then(data => {
                    alert('Action successful!'); // Replace with custom message box
                    hideConfirmationModal();
                    window.location.reload(); // Reload to reflect changes
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred.'); // Replace with custom message box
                    hideConfirmationModal();
                });
        });
        */
    </script>
</body>
</html>
