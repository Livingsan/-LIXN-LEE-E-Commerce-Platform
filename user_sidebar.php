<?php
// user_sidebar.php
@include 'config.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch user details if not already available in the including page
if (!isset($user)) {
    $user_email = $_SESSION['user_email'] ?? '';
    $stmt = $conn->prepare("SELECT name, email, role, loyalty_points FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Generate Avatar Initials
$avatar_text = '';
if (!empty($user['name'])) {
    $parts = explode(' ', $user['name']);
    $avatar_text .= substr($parts[0], 0, 1);
    if (count($parts) > 1) {
        $avatar_text .= substr($parts[count($parts) - 1], 0, 1);
    }
    $avatar_text = strtoupper($avatar_text); 
}

// Helper to determine active class
function is_active($page, $current) {
    return ($page == $current) ? 'class="active"' : '';
}

// Loyalty tier
$points = $user['loyalty_points'] ?? 0;
if ($points >= 3000) { $tier = 'Platinum'; $tier_icon = 'fa-gem'; }
else if ($points >= 1500) { $tier = 'Gold'; $tier_icon = 'fa-crown'; }
else { $tier = 'Silver'; $tier_icon = 'fa-medal'; }
?>

<aside class="user-sidebar">
    <div class="user-profile-summary">
        <div class="user-avatar-ring">
            <div class="user-avatar"><?php echo $avatar_text; ?></div>
        </div>
        <h2 class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Guest'); ?></h2>
        <div class="user-tier-badge">
            <i class="fas <?php echo $tier_icon; ?>"></i>
            <span><?php echo $tier; ?> Member</span>
        </div>
    </div>
    <nav class="user-nav">
        <ul>
            <li class="nav-group-label">Account</li>
            <li <?php echo is_active('account.php', $current_page); ?>><a href="account.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li <?php echo is_active('edit account.php', $current_page); ?>><a href="edit account.php"><i class="fas fa-user-circle"></i><span>My Profile</span></a></li>
            <li <?php echo is_active('userorder.php', $current_page); ?>><a href="userorder.php"><i class="fas fa-box-open"></i><span>My Orders</span></a></li>
            <li <?php echo is_active('user_review.php', $current_page); ?>><a href="user_review.php"><i class="fas fa-star"></i><span>My Reviews</span></a></li>
            
            <li class="nav-group-label">Shopping</li>
            <li <?php echo is_active('wishlist.php', $current_page); ?>><a href="wishlist.php"><i class="fas fa-heart"></i><span>Wishlist</span></a></li>
            <li <?php echo is_active('my_coupons.php', $current_page); ?>><a href="my_coupons.php"><i class="fas fa-tag"></i><span>My Coupons</span></a></li>
            
            <li class="nav-group-label">Settings</li>
            <li <?php echo is_active('address.php', $current_page); ?>><a href="address.php"><i class="fas fa-map-marker-alt"></i><span>Addresses</span></a></li>
            <li <?php echo is_active('user_messages.php', $current_page); ?>><a href="user_messages.php"><i class="fas fa-envelope"></i><span>My Messages</span></a></li>
            <li <?php echo is_active('return.php', $current_page); ?>><a href="return.php"><i class="fas fa-undo-alt"></i><span>Returns & Refunds</span></a></li>
            
            <li class="nav-divider"></li>
            <li class="nav-logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </nav>
</aside>
