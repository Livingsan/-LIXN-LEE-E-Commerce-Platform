<?php 
@include 'config.php';
session_start();

$user_email = $_SESSION['user_email'] ?? '';

if (!$user_email) {
    header('location:userlogin.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location:userorder.php');
    exit();
}

$order_id = intval($_GET['id']);
$order_res = execute_query($conn, "SELECT * FROM `order` WHERE id = ? AND email = ?", "is", [$order_id, $user_email]);
$order = mysqli_fetch_assoc($order_res);

if (!$order) {
    header('location:userorder.php');
    exit();
}

$status = strtolower($order['status'] ?? 'pending');
$statuses = ['pending', 'shipped', 'out for delivery', 'completed'];
$current_index = array_search($status, $statuses);
if ($current_index === false) $current_index = 0;

$date_map = [
  'pending' => $order['order_date'] ?? date('Y-m-d'),
  'shipped' => $order['shipped_date'] ?? null,
  'out for delivery' => $order['out_delivery_date'] ?? null,
  'completed' => $order['completed_date'] ?? null,
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order_id; ?> - LIXN LEE</title>
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
                    <div class="tracking-container">
        <div class="tracking-header">
            <p style="text-transform: uppercase; letter-spacing: 2px; color: #888;">Order ID: #ORD-<?php echo $order_id; ?></p>
            <h1>Track Your Delivery</h1>
        </div>

        <div class="timeline">
            <div class="timeline-progress" style="width: <?php echo ($current_index / (count($statuses) - 1)) * 100; ?>%"></div>
            <?php foreach($statuses as $index => $stage): 
                $is_completed = $index < $current_index;
                $is_active = $index == $current_index;
                $icon = 'fa-clock';
                if($stage == 'shipped') $icon = 'fa-box';
                if($stage == 'out for delivery') $icon = 'fa-truck';
                if($stage == 'completed') $icon = 'fa-check-circle';
            ?>
                <div class="step <?php echo $is_completed ? 'completed' : ($is_active ? 'active' : ''); ?>">
                    <div class="step-icon">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="step-label"><?php echo ucwords($stage); ?></div>
                    <div style="font-size: 0.75rem; color: #999; margin-top: 5px;">
                        <?php echo $date_map[$stage] ? date('M d, Y', strtotime($date_map[$stage])) : '--'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="order-info">
            <div class="info-card">
                <h3><i class="fas fa-shipping-fast"></i> Shipping Details</h3>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['street']); ?></p>
                <p><strong>Method:</strong> <?php echo htmlspecialchars($order['method']); ?></p>
            </div>
            <div class="info-card">
                <h3><i class="fas fa-box-open"></i> Package Info</h3>
                <p><strong>Items:</strong> <?php echo htmlspecialchars($order['total_products']); ?></p>
                <p><strong>Total Weight:</strong> Estimated 1.5kg</p>
                <p><strong>Vendor:</strong> LIXN LEE Studio</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 50px;">
            <a href="userorder.php" class="btn-add" style="text-decoration: none; padding: 15px 40px;">Back to Orders</a>
        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>
