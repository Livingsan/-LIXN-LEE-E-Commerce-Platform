<?php
@include 'config.php';

// Fetch cart count securely
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$row_count = 0;

if ($user_email) {
    $select_rows = execute_query($conn, "SELECT * FROM `cart` WHERE email = ?", "s", [$user_email]);
    $row_count = mysqli_num_rows($select_rows);
}
?>

<header class="main-header glass-card">
    <div class="header-container">
        <div class="brand">
            <a href="index.php" class="logo-text">LIXN<span>LEE</span></a>
        </div>

        <nav class="nav-links">
            <ul class="menu-items">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Shop</a></li>
                <li><a href="index.php#contact">Contact</a></li>
                <li><a href="account.php">Account</a></li>
                <?php if (isset($_SESSION['user_email'])): ?>
                    <?php 
                        // Check if admin
                        $check_admin = execute_query($conn, "SELECT role FROM users WHERE email = ?", "s", [$_SESSION['user_email']]);
                        $admin_row = mysqli_fetch_assoc($check_admin);
                        if ($admin_row && $admin_row['role'] === 'admin'):
                    ?>
                        <li><a href="admindash.php" class="admin-link">Dashboard</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="header-actions">
            <!-- Global Search -->
            <form action="process_search.php" method="get" class="header-search">
                <input type="text" name="query" placeholder="Search..." required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>

            <a href="wishlist.php" class="action-btn" title="Wishlist">
                <i class="fas fa-heart"></i>
            </a>
            <a href="cart.php" class="action-btn cart-btn" title="Shopping Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="count-badge"><?php echo $row_count; ?></span>
            </a>
            <?php if (isset($_SESSION['user_email'])): ?>
                <a href="logout.php" class="action-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="userlogin.php" class="action-btn" title="Login">
                    <i class="fas fa-user"></i>
                </a>
            <?php endif; ?>
            <div id="sidebar-toggle" class="fas fa-bars"></div>
        </div>
    </div>
</header>

<script>
document.getElementById('sidebar-toggle').addEventListener('click', function() {
    document.querySelector('.nav-links').classList.toggle('active');
});
</script>

<style>
@media (max-width: 992px) {
    .nav-links {
        display: none;
        position: absolute;
        top: 100px;
        left: 20px;
        right: 20px;
        background: white;
        padding: 20px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        text-align: center;
        flex-direction: column;
    }
    .nav-links.active {
        display: flex;
    }
    .menu-items {
        flex-direction: column;
        gap: 15px;
    }
}
</style>