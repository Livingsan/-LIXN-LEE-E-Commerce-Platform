<?php
include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$edit_mode = false;
$id = null;
$msg = "";

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (execute_query($conn, "DELETE FROM coupons WHERE id = ?", "i", [$id])) {
        $msg = "<p class='msg success'>Coupon deleted successfully!</p>";
    } else {
        $msg = "<p class='msg error'>Failed to delete coupon.</p>";
    }
}

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = intval($_GET['edit']);
    $edit_result = execute_query($conn, "SELECT * FROM coupons WHERE id = ?", "i", [$id]);
    $coupon = mysqli_fetch_assoc($edit_result);
}

if (isset($_POST['submit'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount = floatval($_POST['discount'] ?? 0);
    $valid_from = !empty($_POST['valid_from']) ? $_POST['valid_from'] : date('Y-m-d');
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+30 days'));
    $target_email = trim($_POST['target_email'] ?? '');
    $target_email = $target_email !== '' ? $target_email : NULL;
    $label = trim($_POST['label'] ?? '');

    if ($edit_mode && $id) {
        if ($target_email !== null) {
            $update = execute_query($conn, "UPDATE coupons SET code=?, label=?, discount=?, valid_from=?, expiry_date=?, target_email=? WHERE id=?", "ssdsssi", [$code, $label, $discount, $valid_from, $expiry_date, $target_email, $id]);
        } else {
            $update = execute_query($conn, "UPDATE coupons SET code=?, label=?, discount=?, valid_from=?, expiry_date=?, target_email=NULL WHERE id=?", "ssdssi", [$code, $label, $discount, $valid_from, $expiry_date, $id]);
        }
        if ($update) {
            $msg = "<p class='msg success'>Coupon updated successfully!</p>";
            $edit_mode = false; 
        } else {
            $msg = "<p class='msg error'>Failed to update coupon.</p>";
        }
    } else {
        $check_result = execute_query($conn, "SELECT * FROM coupons WHERE code = ?", "s", [$code]);
        if (mysqli_num_rows($check_result) > 0) {
            $msg = "<p class='msg error'>Coupon code already exists.</p>";
        } else {
            if ($target_email !== null) {
                $insert = execute_query($conn, "INSERT INTO coupons (code, label, discount, valid_from, expiry_date, target_email, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)", "ssdsss", [$code, $label, $discount, $valid_from, $expiry_date, $target_email]);
            } else {
                $insert = execute_query($conn, "INSERT INTO coupons (code, label, discount, valid_from, expiry_date, target_email, is_active) VALUES (?, ?, ?, ?, ?, NULL, 1)", "ssdss", [$code, $label, $discount, $valid_from, $expiry_date]);
            }
            if ($insert) {
                $msg = "<p class='msg success'>Coupon added successfully!</p>";
            } else {
                $msg = "<p class='msg error'>Failed to add coupon.</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Coupon Management - Admin Panel</title>

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
    --border-light: #ccc;
    --shadow-original: 0 2px 10px rgba(0,0,0,0.1);
    --color-primary-button: #83666e;
    --color-primary-button-hover: #6a4f56;
    --color-message-success-bg: #d4edda;
    --color-message-success-text: #155724;
    --color-message-success-border: #c3e6cb;
    --color-message-error-bg: #f8d7da;
    --color-message-error-text: #721c24;
    --color-message-error-border: #f5c6cb;
    --color-table-header: #83666e;
    --color-table-stripe: #f9f9f9;
    --color-table-hover: #eef2ff;
    --color-edit-link: #83666e;
    --color-delete-link: #cc4c4c;
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

.card {
    background: var(--card-bg);
    padding: 35px;
    border-radius: 12px;
    box-shadow: var(--shadow-original);
    margin-bottom: 40px;
}
.card h2 {
    font-family: 'Poppins', sans-serif;
    margin-bottom: 30px;
    font-weight: 700;
    color: var(--text-dark);
    font-size: 28px;
    text-align: center;
}

form label {
    font-weight: 600;
    display: block;
    margin-bottom: 10px;
    color: var(--text-dark);
    font-size: 15px;
}
form input[type="text"],
form input[type="email"],
form input[type="number"],
form input[type="date"] {
    width: 100%;
    padding: 15px 20px;
    margin-bottom: 25px;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background-color: var(--bg-page);
    color: var(--text-dark);
}
form input[type="text"]:focus,
form input[type="email"]:focus,
form input[type="number"]:focus,
form input[type="date"]:focus {
    border-color: var(--color-primary-button);
    outline: none;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
    background-color: white;
}
form button[type="submit"] {
    background-color: var(--color-primary-button);
    color: white;
    border: none;
    padding: 16px 35px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 17px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    width: 100%;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}
form button[type="submit"]:hover {
    background-color: var(--color-primary-button-hover);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.table-container {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-original);
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
table th, table td {
    padding: 20px 25px;
    text-align: left;
    vertical-align: middle;
    font-weight: 500;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
}
table th {
    background: var(--color-table-header);
    color: white;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    font-size: 16px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}
table tr:nth-child(even) {
    background: var(--color-table-stripe);
}
table tr:hover {
    background: var(--color-table-hover);
}
table a {
    display: inline-flex;
    align-items: center;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 14px;
    text-decoration: none;
    color: white;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-weight: 500;
    margin: 0 5px;
}
table a:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    text-decoration: none;
}
table a.edit-btn {
    background-color: var(--color-edit-link);
}
table a.edit-btn:hover {
    background-color: #5a67d8;
}
table a.delete-btn {
    background-color: var(--color-delete-link);
}
table a.delete-btn:hover {
    background-color: #c0392b;
}
table a i {
    margin-right: 8px;
}

.filter-form {
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    padding: 15px 0;
}
.filter-form label {
    font-weight: 600;
    margin-right: 0;
    color: var(--text-dark);
    font-size: 15px;
}
.filter-form input[type="email"] {
    width: 280px;
    max-width: 100%;
    padding: 12px 15px;
    border-radius: 10px;
    border: 1px solid var(--border-light);
    margin-right: 0;
    font-size: 15px;
    background-color: var(--bg-page);
    color: var(--text-dark);
}
.filter-form input[type="email"]:focus {
    border-color: var(--color-primary-button);
    outline: none;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
    background-color: white;
}
.filter-form button {
    background-color: var(--color-primary-button);
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: background-color 0.3s ease, transform 0.2s ease;
}
.filter-form button:hover {
    background-color: var(--color-primary-button-hover);
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.2);
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px);}
    to { opacity: 1; transform: translateY(0);}
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
    .card {
        padding: 30px;
    }
    .card h2 {
        font-size: 26px;
    }
    form label {
        font-size: 14px;
    }
    form input[type="text"],
    form input[type="email"],
    form input[type="number"],
    form input[type="date"] {
        padding: 12px 15px;
        font-size: 15px;
        margin-bottom: 20px;
    }
    form button[type="submit"] {
        padding: 14px 25px;
        font-size: 16px;
    }
    .filter-form input[type="email"] {
        width: 220px;
        padding: 10px 12px;
        font-size: 14px;
    }
    .filter-form button {
        padding: 10px 18px;
        font-size: 14px;
    }
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    th, td {
        padding: 15px 20px;
        font-size: 13px;
    }
    table a {
        padding: 8px 12px;
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
    .message {
        padding: 15px 20px;
        font-size: 14px;
        margin-bottom: 20px;
    }
    .card {
        padding: 25px;
    }
    .card h2 {
        font-size: 24px;
    }
    form input[type="text"],
    form input[type="email"],
    form input[type="number"],
    form input[type="date"] {
        padding: 10px 12px;
        font-size: 14px;
        margin-bottom: 15px;
    }
    form button[type="submit"] {
        padding: 12px 20px;
        font-size: 15px;
    }
    .filter-form {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .filter-form input[type="email"] {
        width: 100%;
    }
    .filter-form button {
        width: 100%;
    }
    th, td {
        padding: 10px 12px;
        font-size: 12px;
    }
    table a {
        padding: 6px 10px;
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
    .message {
        padding: 10px 15px;
        font-size: 13px;
        margin-bottom: 15px;
    }
    .card {
        padding: 20px;
    }
    .card h2 {
        font-size: 20px;
    }
    form label {
        font-size: 13px;
    }
    form input[type="text"],
    form input[type="email"],
    form input[type="number"],
    form input[type="date"] {
        padding: 8px 10px;
        font-size: 13px;
        margin-bottom: 10px;
    }
    form button[type="submit"] {
        padding: 10px 15px;
        font-size: 14px;
    }
    .filter-form {
        gap: 8px;
    }
    th, td {
        padding: 8px 10px;
        font-size: 11px;
    }
    table a {
        padding: 4px 6px;
        font-size: 10px;
    }
}

body.dark-mode {
    background: #121212;
    color: #e0e0e0;
}
body.dark-mode .header,
body.dark-mode .card,
body.dark-mode .table-container {
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
body.dark-mode .card h2,
body.dark-mode th,
body.dark-mode td {
    color: #f0f0f0;
}
body.dark-mode form label {
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
body.dark-mode input[type="text"],
body.dark-mode input[type="email"],
body.dark-mode input[type="number"],
body.dark-mode input[type="date"] {
    background-color: #2b2b2b;
    border-color: #3a3a3a;
    color: #e0e0e0;
}
body.dark-mode input[type="text"]:focus,
body.dark-mode input[type="email"]:focus,
body.dark-mode input[type="number"]:focus,
body.dark-mode input[type="date"]:focus {
    background-color: #2b2b2b;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.25);
}
body.dark-mode form button[type="submit"] {
    background: var(--color-primary-button);
    box-shadow: none;
}
body.dark-mode form button[type="submit"]:hover {
    background: var(--color-primary-button-hover);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.2);
}
body.dark-mode table {
    background: #1e1e1e;
}
body.dark-mode th, body.dark-mode td {
    border-color: #3a3a3a;
}
body.dark-mode tr:nth-child(even) {
    background-color: #252525;
}
body.dark-mode tr:hover {
    background-color: #353535;
}
body.dark-mode table a {
    color: white;
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
                <a href="admin_return.php" class="menu-item" data-page="admin_return.php"><i class="fas fa-warehouse"></i><span>Return&Refund Management</span></a>

                <a href="add_coupon.php" class="menu-item active" data-page="add_coupon.php"><i class="fas fa-gift"></i><span>Coupon Management</span></a>
                <a href="admin_contact.php" class="menu-item" data-page="admin_contact.php"><i class="fas fa-envelope-open-text"></i><span>Contact Messages</span></a>
                <a href="admin_profile.php" class="menu-item" data-page="admin_profile.php"><i class="fas fa-user-circle"></i><span>Admin Profile</span></a>
                <a href="adminlogout.php" class="menu-item" data-page="adminlogout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Coupon Management</h1>
                <div class="user-info">
                    <div class="user-details">
                        <div>LIXN LEE</div>
                        <div style="font-size: 12px; color: var(--text-medium);">Administrator</div>
                    </div>
                    <div class="user-avatar">A</div>
                </div>
            </div>

            <?php
            if (!empty($msg)) {
                $isError = (strpos($msg, 'Failed') !== false || strpos($msg, 'already exists') !== false);
                echo '<div class="message ' . ($isError ? 'error' : '') . '"><span>' . strip_tags($msg) . '</span><i class="fas fa-times" onclick="this.parentElement.style.display = \'none\';"></i></div>';
            }
            ?>

            <div class="card">
                <h2><?php echo $edit_mode ? "Edit Coupon" : "Add New Coupon"; ?></h2>
                <form method="POST">
                    <label for="coupon_code">Coupon Code:</label>
                    <input type="text" id="coupon_code" name="code" value="<?php echo $edit_mode ? htmlspecialchars($coupon['code']) : ''; ?>" placeholder="e.g., SUMMER20" required>

                    <label for="discount_percent">Discount (%):</label>
                    <input type="number" id="discount_percent" name="discount" step="0.01" value="<?php echo $edit_mode ? htmlspecialchars($coupon['discount']) : ''; ?>" placeholder="e.g., 10.50" required>

                    <label for="valid_from_date">Valid From:</label>
                    <input type="date" id="valid_from_date" name="valid_from" value="<?php echo $edit_mode ? htmlspecialchars($coupon['valid_from']) : ''; ?>" required>

                    <label for="expiry_date">Expiry Date:</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?php echo $edit_mode ? htmlspecialchars($coupon['expiry_date']) : ''; ?>" required>

                    <label for="target_email">Target Email (leave blank for public):</label>
                    <input type="email" id="target_email" name="target_email" value="<?php echo $edit_mode ? htmlspecialchars($coupon['target_email']) : ''; ?>" placeholder="e.g., user@example.com">

                    <label for="coupon_label">Coupon Label:</label>
                    <input type="text" id="coupon_label" name="label" value="<?php echo $edit_mode ? htmlspecialchars($coupon['label']) : ''; ?>" placeholder="e.g., Summer Sale Discount" required>

                    <button type="submit" name="submit"><?php echo $edit_mode ? "Update Coupon" : "Add Coupon"; ?></button>
                </form>
            </div>

            <div class="card">
                <h2>All Coupons</h2>
                <form method="GET" class="filter-form">
                    <label for="filter_email">Filter by Email:</label>
                    <input type="email" id="filter_email" name="filter_email" placeholder="Enter email to filter" value="<?php echo isset($_GET['filter_email']) ? htmlspecialchars($_GET['filter_email']) : ''; ?>">
                    <button type="submit"><i class="fas fa-filter"></i> Filter</button>
                    <?php if (isset($_GET['filter_email']) && $_GET['filter_email'] !== ''): ?>
                        <a href="add_coupon.php" class="filter-form button" style="background-color: #95a5a6; color: white; text-decoration: none; padding: 12px 22px; border-radius: 10px; font-weight: 600; font-size: 15px; transition: background-color 0.3s ease, transform 0.2s ease;"><i class="fas fa-times-circle"></i> Clear Filter</a>
                    <?php endif; ?>
                </form>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Label</th>
                                <th>Discount</th>
                                <th>Valid From</th>
                                <th>Expiry Date</th>
                                <th>Target Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $filter = "";
                        if (isset($_GET['filter_email']) && $_GET['filter_email'] !== '') {
                            $email = mysqli_real_escape_string($conn, $_GET['filter_email']);
                            $filter = "WHERE target_email = '$email'";
                        }

                        $result = mysqli_query($conn, "SELECT * FROM coupons $filter ORDER BY id DESC");
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                    <td>".htmlspecialchars($row['id'])."</td>
                                    <td>".htmlspecialchars($row['code'])."</td>
                                    <td>".htmlspecialchars($row['label'])."</td>
                                    <td>".htmlspecialchars($row['discount'])."%</td>
                                    <td>".htmlspecialchars($row['valid_from'])."</td>
                                    <td>".htmlspecialchars($row['expiry_date'])."</td>
                                    <td>" . ($row['target_email'] ? htmlspecialchars($row['target_email']) : 'Public') . "</td>
                                    <td>
                                        <a href='?edit=".htmlspecialchars($row['id'])."' class='edit-btn'><i class='fas fa-edit'></i> Edit</a>
                                        <a href='?delete=".htmlspecialchars($row['id'])."' onclick=\"return confirm('Delete this coupon?')\" class='delete-btn'><i class='fas fa-trash-alt'></i> Delete</a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align:center; padding:20px; color:var(--text-medium);'>No coupons found.</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
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