<?php
@include 'config.php';
session_start();

$user_email = $_SESSION['user_email'] ?? '';

if (!$user_email) {
    header('location:userlogin.php');
    exit();
}

$orders_query = execute_query($conn, "SELECT * FROM `order` WHERE email = ? ORDER BY id DESC", "s", [$user_email]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link href="premium_account.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body>

    <?php include 'header.php'; ?>

    <div class="user-page-wrapper">
        <div class="user-page-container">
            <?php include 'user_sidebar.php'; ?>
            <main class="user-content">
                <section class="user-section active-section">
                    <h1 style="font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 30px; color: var(--primary-color);">My Orders</h1>
        <?php if (mysqli_num_rows($orders_query) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($orders_query)): ?>
                <div class="order-card">
                    <div class="order-header">
                        <h3 style="font-family: 'Playfair Display', serif;">Order #<?php echo $row['id']; ?></h3>
                        <span class="status-badge status-<?php echo strtolower($row['status'] ?? 'pending'); ?>">
                            <?php echo $row['status'] ?? 'Pending'; ?>
                        </span>
                    </div>
                    <div class="order-details">
                        <div>
                            <p style="color:#777; font-size:0.9rem;">Ordered on</p>
                            <p style="font-weight:600;"><?php echo date('M d, Y', strtotime($row['order_date'])); ?></p>
                        </div>
                        <div>
                            <p style="color:#777; font-size:0.9rem;">Total Amount</p>
                            <p style="font-weight:600; color: var(--primary);">RS <?php echo number_format($row['total_price'], 2); ?></p>
                        </div>
                        <div>
                            <p style="color:#777; font-size:0.9rem;">Items</p>
                            <p style="font-weight:600;"><?php echo htmlspecialchars($row['total_products']); ?></p>
                        </div>
                    </div>
                    <a href="track_order.php?id=<?php echo $row['id']; ?>" class="btn-view">Track Order <i class="fas fa-truck"></i></a>
                    <a href="invoice.php?order_id=<?php echo $row['id']; ?>" class="btn-view" style="margin-left: 20px;">Invoice <i class="fas fa-file-invoice"></i></a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #777;">You haven't placed any orders yet.</p>
        <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

</body>
</html>
