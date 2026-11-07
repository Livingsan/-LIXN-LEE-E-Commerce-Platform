<?php
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin'; 

$message = '';
$message_type = '';

if (isset($_POST['update_status'])) {
    $return_id = intval($_POST['return_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $note = trim($_POST['response_note'] ?? '');
    $now = date('Y-m-d H:i:s');

    $allowed_statuses = ['requested', 'approved', 'rejected', 'refunded'];
    if (in_array($status, $allowed_statuses) && $return_id > 0) {
        $update = execute_query($conn, "UPDATE returns SET status=?, response_note=?, response_date=? WHERE id=?", "sssi", [$status, $note, $now, $return_id]);
        if ($update) {
            $message = "Return request ID " . htmlspecialchars($return_id) . " status updated to " . htmlspecialchars($status) . "!";
            $message_type = 'success';
        } else {
            $message = "Error updating return status: " . mysqli_error($conn);
            $message_type = 'error';
        }
    } else {
        $message = "Invalid return status or request ID.";
        $message_type = 'error';
    }
}


$return_data_query = mysqli_query($conn, "
    SELECT
        r.*,
        o.name AS customer_name,
        p.name AS product_name_from_db
    FROM `returns` r
    LEFT JOIN `order` o ON r.order_id = o.id
    LEFT JOIN `products` p ON r.product_id = p.id
    ORDER BY r.requested_date DESC
");

$total_pending_returns_query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM `returns` WHERE status = 'requested'");
$total_pending_returns = mysqli_fetch_assoc($total_pending_returns_query)['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Manage Returns</title>

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
    --border-light: #ddd;
    --shadow-original: 0 2px 10px rgba(0,0,0,0.1);
    --color-message-success-bg: #d4edda;
    --color-message-success-text: #155724;
    --color-message-success-border: #c3e6cb;
    --color-message-error-bg: #f8d7da;
    --color-message-error-text: #721c24;
    --color-message-error-border: #f5c6cb;
    --color-table-header: #83666e;
    --color-table-stripe: #f9f9f9;
    --color-table-hover: #f0f4ff;
    --status-requested: #f39c12;
    --status-approved: #27ae60;
    --status-rejected: #e74c3c;
    --status-refunded: #3498db;
    --color-form-input-bg: var(--bg-page);
    --color-form-input-border: var(--border-light);
    --color-form-input-focus-border: var(--sidebar-gradient-start);
    --color-form-input-focus-shadow: rgba(102, 126, 234, 0.15);
    --color-button-bg: var(--sidebar-gradient-start);
    --color-button-hover-bg: #5a6cd3;
    --color-button-text: white;
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
    display: flex;
    min-height: 100vh;
}
.dashboard {
    display: flex;
    width: 100%;
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
.message {
    background-color: var(--color-message-success-bg);
    color: var(--color-message-success-text);
    padding: 18px 25px;
    margin-bottom: 30px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
    font-size: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    animation: fadeInDown 0.5s ease-out;
    border: 1px solid var(--color-message-success-border);
}
.message.error {
    background-color: var(--color-message-error-bg);
    color: var(--color-message-error-text);
    border: 1px solid var(--color-message-error-border);
}
.message i {
    cursor: pointer;
    font-size: 20px;
    transition: transform 0.2s ease;
}
.message i:hover {
    transform: scale(1.1);
}
.returns-container {
    background: var(--card-bg);
    padding: 35px;
    border-radius: 12px;
    box-shadow: var(--shadow-original);
    margin-top: 20px;
}
.returns-container h2 {
    font-family: 'Poppins', sans-serif;
    font-size: 28px;
    color: var(--text-dark);
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
}
.returns-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.returns-table th,
.returns-table td {
    padding: 20px 25px;
    text-align: left;
    border-bottom: 1px solid var(--border-light);
    vertical-align: top;
    color: var(--text-dark);
    font-weight: 500;
}
.returns-table th {
    background-color: var(--color-table-header);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 16px;
    letter-spacing: 0.8px;
}
.returns-table tbody tr:nth-child(even) {
    background-color: var(--color-table-stripe);
}
.returns-table tbody tr:hover {
    background-color: var(--color-table-hover);
}
.returns-table td .status-badge {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: white;
    text-transform: capitalize;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    min-width: 90px;
    text-align: center;
}
.status-badge.requested { background-color: var(--status-requested); }
.status-badge.approved { background-color: var(--status-approved); }
.status-badge.rejected { background-color: var(--status-rejected); }
.status-badge.refunded { background-color: var(--status-refunded); }
.returns-actions form {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
}
.returns-actions select {
    padding: 12px 15px;
    border: 1px solid var(--color-form-input-border);
    border-radius: 10px;
    font-size: 15px;
    width: 100%;
    max-width: 180px;
    background-color: var(--color-form-input-bg);
    color: var(--text-dark);
    cursor: pointer;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.returns-actions select:focus {
    outline: none;
    border-color: var(--color-form-input-focus-border);
    box-shadow: 0 0 0 4px var(--color-form-input-focus-shadow);
    background-color: white;
}
.returns-actions textarea {
    width: 100%;
    min-height: 80px;
    padding: 12px 15px;
    border: 1px solid var(--color-form-input-border);
    border-radius: 10px;
    font-size: 15px;
    resize: vertical;
    background-color: var(--color-form-input-bg);
    color: var(--text-dark);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.returns-actions textarea:focus {
    outline: none;
    border-color: var(--color-form-input-focus-border);
    box-shadow: 0 0 0 4px var(--color-form-input-focus-shadow);
    background-color: white;
}
.returns-actions button[type="submit"] {
    padding: 12px 22px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    background-color: var(--color-button-bg);
    color: var(--color-button-text);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.returns-actions button[type="submit"]:hover {
    background-color: var(--color-button-hover-bg);
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.2);
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 1024px) {
    .main-content {
        padding: 30px;
    }
    .header h1 {
        font-size: 32px;
    }
    .user-avatar {
        width: 45px;
        height: 45px;
        font-size: 18px;
    }
    .user-details div:first-child {
        font-size: 16px;
    }
    .user-details div:last-child {
        font-size: 12px;
    }
    .message {
        padding: 15px 20px;
        font-size: 15px;
    }
    .returns-container {
        padding: 30px;
    }
    .returns-container h2 {
        font-size: 26px;
    }
    .returns-table th, .returns-table td {
        padding: 15px 20px;
        font-size: 13px;
    }
    .returns-table td .status-badge {
        padding: 6px 12px;
        font-size: 12px;
        min-width: 80px;
    }
    .returns-actions select,
    .returns-actions textarea {
        padding: 10px 12px;
        font-size: 14px;
    }
    .returns-actions button[type="submit"] {
        padding: 10px 18px;
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
    .message {
        padding: 15px 20px;
        font-size: 14px;
        margin-bottom: 20px;
    }
    .returns-container {
        padding: 25px;
    }
    .returns-container h2 {
        font-size: 24px;
    }
    .returns-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    .returns-table thead {
        display: none;
    }
    .returns-table tbody,
    .returns-table tr,
    .returns-table td {
        display: block;
        width: 100%;
    }
    .returns-table tr {
        margin-bottom: 15px;
        border: 1px solid var(--border-light);
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        background-color: var(--card-bg);
    }
    .returns-table tr:hover {
        background-color: var(--color-table-hover);
    }
    .returns-table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
        border-bottom: 1px solid var(--border-light);
    }
    .returns-table td:last-child {
        border-bottom: none;
    }
    .returns-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: calc(50% - 30px);
        text-align: left;
        font-weight: 600;
        color: var(--text-medium);
    }
    .returns-actions form {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .returns-actions select,
    .returns-actions textarea,
    .returns-actions button[type="submit"] {
        width: 100%;
        max-width: none;
    }
    .returns-actions select,
    .returns-actions textarea {
        padding: 10px 12px;
        font-size: 13px;
    }
    .returns-actions button[type="submit"] {
        padding: 10px 15px;
        font-size: 13px;
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
    .message {
        padding: 10px 15px;
        font-size: 13px;
        margin-bottom: 15px;
    }
    .returns-container {
        padding: 20px;
    }
    .returns-container h2 {
        font-size: 20px;
    }
    .returns-table td {
        padding-left: 45%;
    }
    .returns-table td::before {
        left: 10px;
        width: calc(45% - 20px);
    }
    .returns-table td .status-badge {
        padding: 5px 10px;
        font-size: 11px;
        min-width: 70px;
    }
    .returns-actions select,
    .returns-actions textarea {
        padding: 8px 10px;
        font-size: 12px;
    }
    .returns-actions button[type="submit"] {
        padding: 8px 12px;
        font-size: 12px;
    }
}
body.dark-mode {
    background: #121212;
    color: #e0e0e0;
}
body.dark-mode .header,
body.dark-mode .returns-container {
    background: #1e1e1e;
    box-shadow: none;
}
body.dark-mode .sidebar {
    box-shadow: 3px 0 15px rgba(0,0,0,0.5);
}
body.dark-mode .menu-item {
    color: #ddd;
}
body.dark-mode .menu-item:hover {
    background: rgba(255,255,255,0.1);
}
body.dark-mode .header h1,
body.dark-mode .returns-container h2,
body.dark-mode .returns-table th,
body.dark-mode .returns-table td {
    color: #f0f0f0;
}
body.dark-mode .user-details div:first-child {
    color: #f0f0f0;
}
body.dark-mode .user-details div:last-child {
    color: #b0b0b0;
}
body.dark-mode .message {
    background-color: #1e3a24;
    color: #a7d9b4;
    border-color: #3f6e4a;
}
body.dark-mode .message.error {
    background-color: #4a1c1f;
    color: #d9a7a7;
    border-color: #7e3a3e;
}
body.dark-mode .returns-table {
    background: #1e1e1e;
}
body.dark-mode .returns-table th, body.dark-mode .returns-table td {
    border-color: #3a3a3a;
}
body.dark-mode .returns-table tbody tr:nth-child(even) {
    background-color: #252525;
}
body.dark-mode .returns-table tbody tr:hover {
    background-color: #353535;
}
body.dark-mode .returns-table td::before {
    color: #aaa;
}
body.dark-mode .returns-table td .status-badge.requested { background-color: #c47c0e; }
body.dark-mode .returns-table td .status-badge.approved { background-color: #1a7e44; }
body.dark-mode .returns-table td .status-badge.rejected { background-color: #b33c3c; }
body.dark-mode .returns-table td .status-badge.refunded { background-color: #2a78b0; }
body.dark-mode .returns-actions select,
body.dark-mode .returns-actions textarea {
    background-color: #2b2b2b;
    border-color: #3a3a3a;
    color: #e0e0e0;
}
body.dark-mode .returns-actions select:focus,
body.dark-mode .returns-actions textarea:focus {
    background-color: #2b2b2b;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.25);
}
body.dark-mode .returns-actions button[type="submit"] {
    background: var(--color-button-bg);
    box-shadow: none;
}
body.dark-mode .returns-actions button[type="submit"]:hover {
    background: var(--color-button-hover-bg);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.2);
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
                <a href="revenue.php" class="menu-item" data-page="revenue.php"><i class="fas fa-dollar-sign"></i><span>Total Revenue & Reports</span></a>
                <a href="admin_stock.php" class="menu-item" data-page="admin_stock.php"><i class="fas fa-warehouse"></i><span>Stock Management</span></a>
                <a href="add_coupon.php" class="menu-item" data-page="add_coupon.php"><i class="fas fa-gift"></i><span>Coupon Management</span></a>
                <a href="admin_contact.php" class="menu-item" data-page="admin_contact.php"><i class="fas fa-envelope-open-text"></i><span>Contact Messages</span></a>
                <a href="admin_refund.php" class="menu-item active" data-page="admin_refund.php"><i class="fas fa-undo-alt"></i><span>Return Management</span></a>
                <a href="admin_profile.php" class="menu-item" data-page="admin_profile.php"><i class="fas fa-user-circle"></i><span>Admin Profile</span></a>
                <a href="adminlogout.php" class="menu-item" data-page="adminlogout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Manage Return Requests</h1>
                <div class="user-info">
                    <div class="user-details">
                        <div>LIXN LEE</div>
                        <div style="font-size: 12px; color: var(--text-medium);">Administrator</div>
                    </div>
                    <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message <?= $message_type ?> fade-out">
                    <span><?= htmlspecialchars($message) ?></span>
                    <i class="fas fa-times" onclick="this.parentElement.style.display = 'none';"></i>
                </div>
            <?php endif; ?>

            <div class="returns-container">
                <h2>All Return Requests (<?php echo $total_pending_returns; ?> Pending)</h2>

                <table class="returns-table">
                    <thead>
                        <tr>
                            <th>Return ID</th>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Reason</th>
                            <th>Requested Date</th>
                            <th>Status</th>
                            <th>Admin Response</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($return_data_query) > 0) {
                            while ($row = mysqli_fetch_assoc($return_data_query)) {
                                $status_class = strtolower($row['status']);
                                $product_display_name = $row['product_name_from_db'] ?? 'N/A';
                                $customer_display_name = $row['customer_name'] ?? 'N/A';
                        ?>
                                <tr>
                                    <td data-label="Return ID"><?= htmlspecialchars($row['id']) ?></td>
                                    <td data-label="Order ID"><?= htmlspecialchars($row['order_id']) ?></td>
                                    <td data-label="Product"><?= htmlspecialchars($product_display_name) ?></td>
                                    <td data-label="Customer"><?= htmlspecialchars($customer_display_name) ?></td>
                                    <td data-label="Email"><?= htmlspecialchars($row['user_email']) ?></td>
                                    <td data-label="Reason"><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
                                    <td data-label="Requested Date"><?= date('M d, Y h:i A', strtotime($row['requested_date'])) ?></td>
                                    <td data-label="Status"><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <td data-label="Admin Response"><?= (empty($row['response_note']) ? 'N/A' : nl2br(htmlspecialchars($row['response_note']))) ?></td>
                                    <td class="returns-actions" data-label="Actions">
                                        <form method="post">
                                            <input type="hidden" name="return_id" value="<?= htmlspecialchars($row['id']) ?>">
                                            <select name="status">
                                                <option value="requested" <?= ($row['status'] == 'requested' ? 'selected' : '') ?>>Requested</option>
                                                <option value="approved" <?= ($row['status'] == 'approved' ? 'selected' : '') ?>>Approved</option>
                                                <option value="rejected" <?= ($row['status'] == 'rejected' ? 'selected' : '') ?>>Rejected</option>
                                                <option value="refunded" <?= ($row['status'] == 'refunded' ? 'selected' : '') ?>>Refunded</option>
                                            </select>
                                            <textarea name="response_note" placeholder="Note to user"><?= htmlspecialchars($row['response_note']) ?></textarea>
                                            <button type="submit" name="update_status"><i class="fas fa-sync-alt"></i> Update Status</button>
                                        </form>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="10" style="text-align: center; padding: 20px; color:var(--text-medium);">No return requests found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
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
                if (item.getAttribute('href') === currentPath || dataPage + '.php' === currentPath) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
