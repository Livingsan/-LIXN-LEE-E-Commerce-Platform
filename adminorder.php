<?php
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$message = [];

if (isset($_POST['update_order'])) {
    $update_order_id = intval($_POST['update_order_id'] ?? 0);
    $new_status = trim($_POST['update_order_status'] ?? '');
    $now = date('Y-m-d H:i:s');

    $date_column = '';
    if (strtolower($new_status) == 'pending') $date_column = 'status_pending_date';
    elseif (strtolower($new_status) == 'shipped') $date_column = 'status_shipped_date';
    elseif (strtolower($new_status) == 'out for delivery') $date_column = 'status_out_for_delivery_date';
    elseif (strtolower($new_status) == 'completed') $date_column = 'status_completed_date';

    if ($date_column) {
        $update_query = execute_query($conn, "UPDATE `order` SET status = ?, $date_column = ? WHERE id = ?", "ssi", [$new_status, $now, $update_order_id]);
    } else {
        $update_query = execute_query($conn, "UPDATE `order` SET status = ? WHERE id = ?", "si", [$new_status, $update_order_id]);
    }

    if ($update_query) {
        $message[] = 'Order status updated successfully!';
    } else {
        $message[] = 'Failed to update order status!';
    }
}


if(isset($_GET['delete'])){
   $delete_id = intval($_GET['delete']);
   $delete_query = execute_query($conn, "DELETE FROM `order` WHERE id = ?", "i", [$delete_id]);
   if ($delete_query) {
       $message[] = 'Order deleted successfully!';
   } else {
       $message[] = 'Failed to delete order!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin - Order Management</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --sidebar-gradient-start: #83666e;
        --sidebar-gradient-end: #6a4f56;
        --sidebar-text-color: white;
        --sidebar-hover-bg: rgba(255, 255, 255, 0.1);
        --sidebar-active-bg: rgba(255, 255, 255, 0.15);
        --sidebar-active-border: #fff;

        --bg-page: #f5f7fa;
        --card-bg: white;
        --text-primary: #333;
        --text-dark: #2c3e50;
        --text-medium: #666;
        --border-light: #eee;
        --shadow-original: 0 2px 10px rgba(0,0,0,0.1);

        --color-primary-button: #83666e;
        --color-primary-button-hover: #6e525a;
        --color-success: #27ae60;
        --color-danger: #e74c3c;
        --color-danger-hover: #c0392b;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: var(--bg-page);
        color: var(--text-primary);
        line-height: 1.6;
    }

    .dashboard {
        display: flex;
        min-height: 100vh;
    }

    .sidebar {
        width: 260px;
        background: linear-gradient(135deg, var(--sidebar-gradient-start) 0%, var(--sidebar-gradient-end) 100%);
        color: var(--sidebar-text-color);
        padding: 30px 0;
        box-shadow: 3px 0 15px rgba(0,0,0,0.2);
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        transition: width 0.3s ease, padding 0.3s ease;
    }

    .sidebar-header {
        padding: 0 25px 40px;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        margin-bottom: 20px;
        text-align: center;
    }

    .sidebar-header h2 {
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: var(--sidebar-text-color);
    }

    .sidebar-header p {
        font-size: 14px;
        opacity: 0.9;
        color: var(--sidebar-text-color);
    }

    .sidebar-menu {
        padding: 10px 0;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 18px 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        border-left: 5px solid transparent;
        color: var(--sidebar-text-color);
        text-decoration: none;
        font-weight: 500;
        font-size: 16px;
        margin-bottom: 8px;
        position: relative;
        overflow: hidden;
    }

    .menu-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--sidebar-hover-bg);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease-out;
        z-index: -1;
    }

    .menu-item:hover::before {
        transform: scaleX(1);
    }

    .menu-item.active::before {
        transform: scaleX(1);
        background: var(--sidebar-active-bg);
    }

    .menu-item:hover {
        border-left-color: var(--sidebar-active-border);
        transform: translateX(5px);
    }

    .menu-item.active {
        border-left-color: var(--sidebar-active-border);
        font-weight: 600;
        transform: translateX(5px);
    }

    .menu-item i {
        margin-right: 15px;
        font-size: 20px;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
    }

    .main-content {
        flex: 1;
        margin-left: 260px;
        padding: 30px;
        background: var(--bg-page);
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }

    .header {
        background: var(--card-bg);
        padding: 20px 30px;
        border-radius: 10px;
        box-shadow: var(--shadow-original);
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header h1 {
        font-family: 'Poppins', sans-serif;
        font-size: 28px;
        color: var(--text-dark);
        font-weight: 600;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--sidebar-gradient-start) 0%, var(--sidebar-gradient-end) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 16px;
    }

    .user-details {
        display: flex;
        flex-direction: column;
        text-align: right;
    }

    .user-details div:first-child {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 14px;
    }

    .user-details div:last-child {
        font-size: 12px;
        color: var(--text-medium);
    }

    .message {
        background-color: var(--color-success);
        color: white;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 400;
        box-shadow: none;
        animation: fadeInDown 0.5s ease-out;
    }

    .message.error {
        background-color: var(--color-danger);
    }

    .message i {
        cursor: pointer;
        font-size: 18px;
        transition: none;
    }

    .message i:hover {
        transform: none;
    }

    .order-list {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--shadow-original);
        overflow: hidden;
    }

    .order-card {
        border-bottom: 1px solid var(--border-light);
        padding: 20px 0;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        align-items: center;
    }

    .order-card:last-child {
        border-bottom: none;
    }

    .order-details {
        flex: 1;
        min-width: 250px;
        margin-right: 20px;
    }

    .order-details p {
        margin-bottom: 8px;
        font-size: 15px;
        color: var(--text-dark);
    }
    .order-details p strong {
        font-weight: 600;
        color: var(--text-dark);
    }
    .order-details p:last-child {
        margin-bottom: 0;
    }

    .order-actions {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .order-actions form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .order-actions select {
        padding: 10px 15px;
        font-size: 14px;
        border-radius: 8px;
        border: 1px solid var(--border-light);
        outline: none;
        background-color: var(--card-bg);
        color: var(--text-primary);
        transition: border-color 0.3s ease;
    }
    .order-actions select:focus {
        border-color: var(--color-primary-button);
    }

    .order-actions input[type="submit"] {
        background-color: var(--color-primary-button);
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 500;
        transition: background-color 0.3s ease;
        flex-shrink: 0;
    }

    .order-actions input[type="submit"]:hover {
        background-color: var(--color-primary-button-hover);
    }

    .delete-btn {
        background: var(--color-danger);
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        font-size: 15px;
        transition: background-color 0.3s ease;
        flex-shrink: 0;
    }

    .delete-btn:hover {
        background: var(--color-danger-hover);
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideInUp {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 992px) {
        .sidebar {
            width: 220px;
        }
        .main-content {
            margin-left: 220px;
            padding: 25px;
        }
        .header h1 {
            font-size: 26px;
        }
        .sidebar-header h2 {
            font-size: 26px;
        }
        .menu-item {
            padding: 15px 20px;
            font-size: 15px;
        }
        .menu-item i {
            font-size: 18px;
        }
        .order-list {
            padding: 20px;
        }
        .order-card {
            padding: 15px 0;
        }
        .order-details p {
            font-size: 14px;
        }
        .order-actions select,
        .order-actions input[type="submit"],
        .delete-btn {
            padding: 8px 15px;
            font-size: 14px;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 80px;
            padding: 20px 0;
        }
        .main-content {
            margin-left: 80px;
            padding: 20px;
        }
        .sidebar-header {
            padding: 0 10px 20px;
        }
        .sidebar-header h2,
        .sidebar-header p {
            display: none;
        }
        .menu-item {
            padding: 15px 0;
            justify-content: center;
            border-left: none;
            margin-bottom: 0;
        }
        .menu-item span {
            display: none;
        }
        .menu-item i {
            margin-right: 0;
            font-size: 24px;
        }
        .menu-item:hover {
            border-left-color: transparent;
            transform: translateX(0);
        }
        .menu-item.active {
            border-left-color: transparent;
            background: rgba(255,255,255,0.25);
            position: relative;
            transform: translateX(0);
        }
        .menu-item.active::after {
            content: '';
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: var(--sidebar-active-border);
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(255,255,255,0.5);
        }

        .header {
            flex-direction: column;
            align-items: flex-start;
            padding: 15px 20px;
            gap: 10px;
        }
        .header h1 {
            font-size: 24px;
        }
        .user-info {
            width: 100%;
            justify-content: flex-end;
        }
        .order-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        .order-details {
            margin-right: 0;
            width: 100%;
        }
        .order-actions {
            width: 100%;
            justify-content: flex-start;
            gap: 10px;
            margin-top: 0;
        }
        .order-actions form {
            width: 100%;
            justify-content: space-between;
        }
        .order-actions select {
            flex: 1;
        }
        .order-actions input[type="submit"] {
            width: auto;
        }
        .delete-btn {
            width: auto;
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 10px;
        }
        .header {
            padding: 10px 15px;
        }
        .header h1 {
            font-size: 20px;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            font-size: 14px;
        }
        .user-details div:first-child {
            font-size: 13px;
        }
        .user-details div:last-child {
            font-size: 11px;
        }
        .message {
            padding: 10px 15px;
            font-size: 13px;
        }
        .order-list {
            padding: 15px;
        }
        .order-card {
            padding: 10px 0;
        }
        .order-details p {
            font-size: 13px;
            margin-bottom: 5px;
        }
        .order-actions {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }
        .order-actions form {
            flex-direction: column;
            gap: 8px;
        }
        .order-actions select,
        .order-actions input[type="submit"],
        .delete-btn {
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
        }
    }

    body.dark-mode {
        background: #121212;
        color: #e0e0e0;
    }
    body.dark-mode .header,
    body.dark-mode .order-list {
        background: #1e1e1e;
        box-shadow: none;
    }
    body.dark-mode .menu-item {
        color: #ddd;
    }
    body.dark-mode .menu-item:hover {
        background: rgba(255,255,255,0.1);
    }
    body.dark-mode .header h1,
    body.dark-mode .order-details p,
    body.dark-mode .order-details p strong
    {
        color: #f0f0f0;
    }
    body.dark-mode .user-details div:first-child {
        color: #f0f0f0;
    }
    body.dark-mode .user-details div:last-child,
    body.dark-mode .order-actions select {
        color: #b0b0b0;
    }
    body.dark-mode .order-actions select {
        background-color: #2b2b2b;
        border-color: #3a3a3a;
    }
    body.dark-mode .order-actions select:focus {
        background-color: #2b2b2b;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.25);
    }
    body.dark-mode .order-card {
        border-color: #3a3a3a;
    }

</style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>LIXN LEE</h2>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="admindash.php" class="menu-item" data-page="admindash.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
            <a href="adminuser.php" class="menu-item" data-page="adminuser.php"><i class="fas fa-users"></i><span>User Management</span></a>
            <a href="adminorder.php" class="menu-item active" data-page="adminorder.php"><i class="fas fa-box-open"></i><span>Order Management</span></a>
            <a href="admin.php" class="menu-item" data-page="admin.php"><i class="fas fa-tags"></i><span>Product Management</span></a>
            <a href="revenue.php" class="menu-item" data-page="revenue.php"><i class="fas fa-dollar-sign"></i><span>Total Revenue & Reports</span></a>
            <a href="admin_stock.php" class="menu-item" data-page="admin_stock.php"><i class="fas fa-warehouse"></i><span>Stock Management</span></a>
            <a href="add_coupon.php" class="menu-item" data-page="add_coupon.php"><i class="fas fa-gift"></i><span>Coupon Management</span></a>
            <a href="admin_contact.php" class="menu-item" data-page="admin_contact.php"><i class="fas fa-envelope-open-text"></i><span>Contact Messages</span></a>
            <a href="admin_profile.php" class="menu-item" data-page="admin_profile.php"><i class="fas fa-user-circle"></i><span>Admin Profile</span></a>
            <a href="adminlogout.php" class="menu-item" data-page="adminlogout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Order Management</h1>
            <div class="user-info">
                <div class="user-details">
                    <div>LIXN LEE</div>
                    <div style="font-size: 12px; color: var(--text-medium);">Administrator</div>
                </div>
                <div class="user-avatar">A</div>
            </div>
        </div>

        <?php
        if(isset($message)){
            foreach($message as $msg){
                $isError = (strpos($msg, 'Failed') !== false);
                echo '<div class="message ' . ($isError ? 'error' : '') . '"><span>'.htmlspecialchars($msg).'</span><i class="fas fa-times" onclick="this.parentElement.style.display = \'none\';"></i></div>';
            }
        }
        ?>

        <div class="order-list">
            <?php
            $select_orders = mysqli_query($conn, "SELECT * FROM `order` ORDER BY id DESC");
            if(mysqli_num_rows($select_orders) > 0){
                while($row = mysqli_fetch_assoc($select_orders)){
            ?>
            <div class="order-card">
                <div class="order-details">
                    <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($row['id']); ?></p>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($row['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['number']); ?></p>
                    <p><strong>Products:</strong> <?php echo htmlspecialchars($row['total_products']); ?></p>
                    <p><strong>Total Price:</strong> Rs.<?php echo number_format($row['total_price'], 2); ?></p>
                    <p><strong>Current Status:</strong> <?php echo htmlspecialchars($row['status']); ?></p>
                    <p><strong>Status Updated On:</strong>
                    <?php
                        $s = strtolower($row['status']);
                        $date_col = null;
                        if ($s == 'pending') $date_col = $row['status_pending_date'];
                        elseif ($s == 'shipped') $date_col = $row['status_shipped_date'];
                        elseif ($s == 'out for delivery') $date_col = $row['status_out_for_delivery_date'];
                        elseif ($s == 'completed') $date_col = $row['status_completed_date'];
                        echo $date_col ? date('M d, Y h:i A', strtotime($date_col)) : '--';
                    ?>
                    </p>
                    <p><strong>Placed on:</strong> <?php echo date('M d, Y h:i A', strtotime($row['order_date'])); ?></p>
                </div>
                <div class="order-actions">
                    <form method="post" onsubmit="return confirm('Update order status?');">
                        <input type="hidden" name="update_order_id" value="<?php echo $row['id']; ?>">
                        <select name="update_order_status" required>
                            <?php
                            $statuses = ['Pending', 'Shipped', 'Out for Delivery', 'Completed', 'Canceled'];
                            foreach ($statuses as $status) {
                                $selected = ($row['status'] == $status) ? 'selected' : '';
                                echo "<option value='$status' $selected>$status</option>";
                            }
                            ?>
                        </select>
                        <input type="submit" name="update_order" value="Update">
                    </form>
                    <a class="delete-btn" onclick="return confirm('Are you sure you want to delete this order?');" href="adminorder.php?delete=<?php echo $row['id']; ?>">
                        <i class="fas fa-trash-alt"></i> Delete
                    </a>
                </div>
            </div>
            <?php
                }
            } else {
                echo "<p style='text-align:center; padding: 40px 0; color:var(--text-medium);'>No orders found.</p>";
            }
            ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const messages = document.querySelectorAll('.message');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.display = 'none';
            }, 5000); 
        });

        const currentPath = window.location.pathname.split('/').pop();
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            const dataPage = item.getAttribute('data-page');
            if (dataPage === currentPath) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    });
</script>
</body>
</html>
