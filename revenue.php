<?php
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$user_query = mysqli_query($conn, "SELECT COUNT(id) AS total_users FROM users");
$user_result = mysqli_fetch_assoc($user_query);
$total_users = $user_result['total_users'] ?? 0;

$order_query = mysqli_query($conn, "SELECT COUNT(id) AS total_orders FROM `order`");
$order_result = mysqli_fetch_assoc($order_query);
$total_orders = $order_result['total_orders'] ?? 0;

$revenue_query = mysqli_query($conn, "SELECT SUM(total_price) AS total_revenue FROM `order`");
$revenue_result = mysqli_fetch_assoc($revenue_query);
$total_revenue = $revenue_result['total_revenue'] ?? 0;

$monthly_revenue = [];
for ($m = 1; $m <= 12; $m++) {
    $month_str = str_pad($m, 2, '0', STR_PAD_LEFT);
    $query = mysqli_query($conn, "SELECT SUM(total_price) as monthly_sum FROM `order` WHERE MONTH(order_date) = '$month_str'");
    $row = mysqli_fetch_assoc($query);
    $monthly_revenue[] = $row['monthly_sum'] ?? 0;
}

$status_query = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM `order` GROUP BY status");

$order_status_data = ['Completed' => 0, 'Pending' => 0, 'Cancelled' => 0];
while ($row = mysqli_fetch_assoc($status_query)) {
    $status = $row['status'];
    $count = $row['count'];
    if (array_key_exists($status, $order_status_data)) {
        $order_status_data[$status] = $count;
    }
}

$offers_query = mysqli_query($conn, "
    SELECT COUNT(*) as total_offers
    FROM coupons
    WHERE is_active = 1
    AND CURDATE() BETWEEN valid_from AND expiry_date
");
$offers_result = mysqli_fetch_assoc($offers_query);
$total_offers = $offers_result['total_offers'] ?? 0;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Revenue Dashboard - Admin Panel</title>

    <!-- Chart.js  -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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

            --color-primary-accent: #83666e;
            --color-success: #27ae60;
            --color-warning: #f39c12;
            --color-danger: #e74c3c;
            --color-info: #3498db;
            --color-purple: #9b59b6;

            --color-table-header: #83666e; 
            --color-table-stripe: #f9f9f9; 
            --color-table-hover: #eef2ff; 
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
            padding: 40px; 
            background: var(--bg-page);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .header {
            background: var(--card-bg);
            padding: 25px 40px; 
            border-radius: 12px; 
            box-shadow: var(--shadow-original); 
            margin-bottom: 40px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-family: 'Poppins', sans-serif; 
            font-size: 36px; 
            color: var(--text-dark); 
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sidebar-gradient-start) 0%, var(--sidebar-gradient-end) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--sidebar-gradient-start);
        }

        .user-details {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .user-details div:first-child {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 17px;
        }

        .user-details div:last-child {
            font-size: 13px;
            color: var(--text-medium);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
            margin-bottom: 40px; 
        }

        .stat-card {
            background: var(--card-bg);
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: var(--shadow-original); 
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15); 
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; 
            margin-bottom: 20px; 
        }

        .stat-title {
            font-size: 15px; 
            color: var(--text-medium); 
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .stat-icon {
            width: 50px; 
            height: 50px; 
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }

        .stat-value {
            font-family: 'Poppins', sans-serif; 
            font-size: 38px; 
            font-weight: 700;
            color: var(--text-dark); 
            margin-bottom: 8px; 
        }

        .stat-change {
            font-size: 13px; 
            font-weight: 500;
            color: var(--text-medium); 
        }

        .stat-change.positive {
            color: var(--color-success);
            font-weight: 600;
        }

        .stat-change.negative {
            color: var(--color-danger);
            font-weight: 600;
        }

        .bg-primary { background: var(--color-primary-accent); }
        .bg-success { background: var(--color-success); }
        .bg-warning { background: var(--color-warning); }
        .bg-danger { background: var(--color-danger); }

        .chart-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px; 
            margin-bottom: 40px; 
        }

        .chart-card {
            background: var(--card-bg);
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: var(--shadow-original); 
            display: flex;
            flex-direction: column;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px; 
        }

        .chart-title {
            font-family: 'Poppins', sans-serif; 
            font-size: 20px; 
            font-weight: 600;
            color: var(--text-dark); 
        }


        .chart-container {
            position: relative;
            height: 350px; 
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chart-container.small {
            height: 280px; 
        }

        .recent-activity { 
            background: var(--card-bg);
            padding: 35px; 
            border-radius: 12px; 
            box-shadow: var(--shadow-original); 
            margin-top: 40px; 
        }

        .recent-activity h2 { 
            font-family: 'Poppins', sans-serif; 
            margin-bottom: 30px; 
            font-weight: 700;
            color: var(--text-dark);
            font-size: 28px; 
            text-align: center;
        }

        .recent-activity table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .recent-activity table th, .recent-activity table td {
            padding: 20px 25px; 
            border-bottom: 1px solid var(--border-light); 
            text-align: left; 
            vertical-align: middle;
            color: var(--text-dark); 
        }
        .recent-activity table th {
            background: var(--color-table-header); 
            color: white;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            text-transform: uppercase; 
            letter-spacing: 0.8px; 
        }
        .recent-activity table tr:nth-child(even) {
            background: var(--color-table-stripe); 
        }
        .recent-activity table tr:hover {
            background: var(--color-table-hover); 
        }
        .recent-activity table .status-text { 
            font-weight: 600; 
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1024px) {
            .chart-section {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
                gap: 20px;
            }
            .stat-card {
                padding: 25px;
            }
            .stat-value {
                font-size: 32px;
            }
            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
            .stat-title {
                font-size: 14px;
            }
            .chart-card {
                padding: 20px;
            }
            .chart-title {
                font-size: 18px;
            }
            .recent-activity {
                padding: 30px;
            }
            .recent-activity h2 {
                font-size: 26px;
            }
            .recent-activity table th, .recent-activity table td {
                padding: 15px 20px;
                font-size: 13px;
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
                padding: 20px 25px;
                gap: 15px;
            }
            .header h1 {
                font-size: 28px;
            }
            .user-info {
                width: 100%;
                justify-content: flex-end;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .stat-card {
                padding: 20px;
            }
            .stat-value {
                font-size: 30px;
            }
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            .stat-title {
                font-size: 13px;
            }
            .chart-card {
                padding: 15px;
            }
            .chart-title {
                font-size: 16px;
            }
            .chart-container {
                height: 300px; 
            }
            .chart-container.small {
                height: 220px;
            }
            .recent-activity {
                padding: 20px;
            }
            .recent-activity h2 {
                font-size: 24px;
            }
            .recent-activity table {
                display: block; 
                overflow-x: auto;
                white-space: nowrap; 
            }
            .recent-activity table th, .recent-activity table td {
                padding: 10px 12px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }
            .header {
                padding: 15px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .user-details div:first-child {
                font-size: 13px;
            }
            .user-details div:last-child {
                font-size: 11px;
            }
            .stat-card {
                padding: 15px;
            }
            .stat-value {
                font-size: 28px;
            }
            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
            .stat-title {
                font-size: 12px;
            }
            .chart-card {
                padding: 10px;
            }
            .chart-title {
                font-size: 14px;
            }
            .chart-container {
                height: 250px;
            }
            .chart-container.small {
                height: 180px;
            }
            .recent-activity {
                padding: 15px;
            }
            .recent-activity h2 {
                font-size: 20px;
            }
            .recent-activity table th, .recent-activity table td {
                padding: 8px 10px;
                font-size: 11px;
            }
        }

        body.dark-mode {
            background: #121212;
            color: #e0e0e0;
        }
        body.dark-mode .header,
        body.dark-mode .stat-card,
        body.dark-mode .chart-card,
        body.dark-mode .recent-activity {
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
        body.dark-mode .stat-value,
        body.dark-mode .chart-title,
        body.dark-mode .recent-activity h2,
        body.dark-mode .recent-activity table th,
        body.dark-mode .recent-activity table td {
            color: #f0f0f0;
        }
        body.dark-mode .user-details div:first-child,
        body.dark-mode .stat-title {
            color: #f0f0f0;
        }
        body.dark-mode .user-details div:last-child,
        body.dark-mode .stat-change {
            color: #b0b0b0;
        }
        body.dark-mode .recent-activity table {
            background: #1e1e1e;
        }
        body.dark-mode .recent-activity table tr:nth-child(even) {
            background-color: #252525;
        }
        body.dark-mode .recent-activity table tr:hover {
            background-color: #353535;
        }
        body.dark-mode .recent-activity table th, body.dark-mode .recent-activity table td {
            border-color: #3a3a3a;
        }
        body.dark-mode .chartjs-render-monitor {
            filter: invert(0.9); 
        }
        body.dark-mode .chart-container canvas {
            background-color: #1e1e1e; 
        }
        body.dark-mode .chartjs-tooltip {
            background-color: rgba(255,255,255,0.9) !important;
            color: #121212 !important;
            border-color: rgba(0,0,0,0.2) !important;
        }
        body.dark-mode .chartjs-tooltip-title,
        body.dark-mode .chartjs-tooltip-body {
            color: #121212 !important;
        }
        body.dark-mode .chartjs-tooltip-label {
            color: #121212 !important;
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
                <a href="adminorder.php" class="menu-item" data-page="adminorder.php"><i class="fas fa-box-open"></i><span>Order Management</span></a>
                <a href="admin.php" class="menu-item" data-page="admin.php"><i class="fas fa-tags"></i><span>Product Management</span></a>
                <a href="revenue.php" class="menu-item active" data-page="revenue.php"><i class="fas fa-dollar-sign"></i><span>Total Revenue & Reports</span></a>
                <a href="admin_stock.php" class="menu-item" data-page="admin_stock.php"><i class="fas fa-warehouse"></i><span>Stock Management</span></a>
                <a href="admin_return.php" class="menu-item" data-page="admin_return.php"><i class="fas fa-warehouse"></i><span>Return&Refund Management</span></a>

                <a href="add_coupon.php" class="menu-item" data-page="add_coupon.php"><i class="fas fa-gift"></i><span>Coupon Management</span></a>
                <a href="admin_contact.php" class="menu-item" data-page="admin_contact.php"><i class="fas fa-envelope-open-text"></i><span>Contact Messages</span></a>
                <a href="admin_profile.php" class="menu-item" data-page="admin_profile.php"><i class="fas fa-user-circle"></i><span>Admin Profile</span></a>
                <a href="adminlogout.php" class="menu-item" data-page="adminlogout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Revenue Dashboard</h1>
                <div class="user-info">
                    <div class="user-details">
                        <div>LIXN LEE</div>
                        <div style="font-size: 12px; color: var(--text-medium);">Administrator</div>
                    </div>
                    <div class="user-avatar">A</div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Users</div>
                        <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_users); ?></div>
                    <div class="stat-change positive">+5% since last month</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Orders</div>
                        <div class="stat-icon bg-success"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                    <div class="stat-change positive">+8% since last month</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-icon bg-warning"><i class="fas fa-dollar-sign"></i></div>
                    </div>
                    <div class="stat-value">RS <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-change positive">+12% since last month</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Active Offers</div>
                        <div class="stat-icon bg-danger"><i class="fas fa-gift"></i></div>
                    </div>
                    <div class="stat-value"><?php echo $total_offers; ?></div>
                    <div class="stat-change positive">Stable</div>
                </div>
            </div>

            <div class="chart-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Monthly Revenue</div>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Order Status</div>
                    </div>
                    <div class="chart-container small">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Recent Transactions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Customer Name</th>
                            <th>Status</th>
                            <th style="text-align: right;">Amount (RS)</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $trans_query = mysqli_query($conn, "SELECT id, name, status, total_price, order_date FROM `order` ORDER BY order_date DESC LIMIT 10");
                        $status_colors = [
                            'completed' => '#27ae60',
                            'pending' => '#f39c12',
                            'cancelled' => '#e74c3c',
                            'canceled' => '#e74c3c', 
                            'shipped' => '#3498db',
                            'out for delivery' => '#9b59b6',
                        ];
                        if (mysqli_num_rows($trans_query) > 0) {
                            while ($trans = mysqli_fetch_assoc($trans_query)):
                                $status = strtolower($trans['status']);
                                $status_color = $status_colors[$status] ?? '#888'; 
                        ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($trans['id']); ?></td>
                            <td><?php echo htmlspecialchars($trans['name']); ?></td>
                            <td>
                                <span class="status-text" style="color: <?php echo $status_color; ?>;">
                                    <?php echo ucfirst(htmlspecialchars($trans['status'])); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">RS <?php echo number_format($trans['total_price'], 2); ?></td>
                            <td>
                                <?php echo date('M d, Y h:i A', strtotime($trans['order_date'])); ?>
                            </td>
                        </tr>
                        <?php endwhile;
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:20px; color:var(--text-medium);'>No recent transactions found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>

        const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        const monthlyRevenueChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue (RS)',
                    data: <?php echo json_encode($monthly_revenue); ?>,
                    borderColor: '#667eea', 
                    backgroundColor: 'rgba(102, 126, 234, 0.1)', 
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4, 
                    pointBackgroundColor: '#667eea', 
                    pointBorderColor: '#fff', 
                    pointBorderWidth: 2, 
                    pointRadius: 6 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false, 
                        labels: {
                            color: 'var(--text-medium)', 
                            font: {
                                size: 14,
                                family: 'Poppins', 
                                weight: 500
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.2)',
                        borderWidth: 1,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += 'RS' + context.parsed.y.toLocaleString('en-US');
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'var(--border-light)', 
                            drawBorder: false
                        },
                        ticks: {
                            color: 'var(--text-medium)',
                            callback: function(value) { return 'RS' + value; },
                            font: {
                                family: 'Poppins' 
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: 'var(--text-medium)',
                            font: {
                                family: 'Poppins' 
                            }
                        }
                    }
                }
            }
        });

        const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($order_status_data)); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode(array_values($order_status_data)); ?>,
                    backgroundColor: [
                        '#27ae60', 
                        '#f39c12', 
                        '#e74c3c', 
                    ],
                    borderColor: 'var(--card-bg)', 
                    borderWidth: 2, 
                    hoverOffset: 10 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'var(--text-medium)', 
                            font: {
                                size: 14,
                                family: 'Poppins', 
                                weight: 500
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(255,255,255,0.2)',
                        borderWidth: 1,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((acc, current) => acc + current, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(2) + '%' : '0%';
                                return `${label}: ${value} (${percentage})`;
                            }
                        }
                    }
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
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