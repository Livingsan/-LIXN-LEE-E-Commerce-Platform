<?php
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM contact_messages ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Contact Messages - Admin Panel</title>

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

    --color-primary-button: #83666e;
    --color-primary-button-hover: #5563c1;
    --color-table-header: #83666e;
    --color-table-stripe: #f9f9f9;
    --color-table-hover: #f0f4ff;
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
    vertical-align: top;
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

table textarea {
    width: 100%;
    min-height: 80px;
    padding: 12px 15px;
    margin-bottom: 10px;
    border: 1px solid var(--border-light);
    border-radius: 10px;
    font-size: 15px;
    resize: vertical;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background-color: var(--bg-page);
    color: var(--text-dark);
}
table textarea:focus {
    border-color: var(--color-primary-button);
    outline: none;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
    background-color: white;
}
table button[type="submit"] {
    background-color: var(--color-primary-button);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    width: auto;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
table button[type="submit"]:hover {
    background-color: var(--color-primary-button-hover);
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.2);
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
    th, td {
        padding: 15px 20px;
        font-size: 13px;
    }
    table textarea {
        padding: 10px 12px;
        font-size: 14px;
    }
    table button[type="submit"] {
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
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    th, td {
        padding: 10px 12px;
        font-size: 12px;
    }
    table textarea {
        min-height: 60px;
        padding: 8px 10px;
        font-size: 13px;
    }
    table button[type="submit"] {
        padding: 8px 15px;
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
    th, td {
        padding: 8px 10px;
        font-size: 11px;
    }
    table textarea {
        min-height: 50px;
        padding: 6px 8px;
        font-size: 12px;
    }
    table button[type="submit"] {
        padding: 6px 12px;
        font-size: 12px;
    }
}

body.dark-mode {
    background: #121212;
    color: #e0e0e0;
}
body.dark-mode .header,
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
body.dark-mode th,
body.dark-mode td {
    color: #f0f0f0;
}
body.dark-mode .user-details div:first-child {
    color: #f0f0f0;
}
body.dark-mode .user-details div:last-child {
    color: #b0b0b0;
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
body.dark-mode table textarea {
    background-color: #2b2b2b;
    border-color: #3a3a3a;
    color: #e0e0e0;
}
body.dark-mode table textarea:focus {
    background-color: #2b2b2b;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.25);
}
body.dark-mode table button[type="submit"] {
    background: var(--color-primary-button);
    box-shadow: none;
}
body.dark-mode table button[type="submit"]:hover {
    background: var(--color-primary-button-hover);
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
                <a href="admin_contact.php" class="menu-item active" data-page="admin_contact.php"><i class="fas fa-envelope-open-text"></i><span>Contact Messages</span></a>
                <a href="admin_profile.php" class="menu-item" data-page="admin_profile.php"><i class="fas fa-user-circle"></i><span>Admin Profile</span></a>
                <a href="adminlogout.php" class="menu-item" data-page="adminlogout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Contact Messages</h1>
                <div class="user-info">
                    <div class="user-details">
                        <div>LIXN LEE</div>
                        <div style="font-size: 12px; color: var(--text-medium);">Administrator</div>
                    </div>
                    <div class="user-avatar">A</div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Reply</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= htmlspecialchars($row['message']) ?></td>
                            <td><?= $row['reply'] ? htmlspecialchars($row['reply']) : 'No reply yet' ?></td>
                            <td>
                                <form method="POST" action="send_reply.php">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                    <textarea name="reply" required placeholder="Write reply here"></textarea>
                                    <button type="submit"><i class="fas fa-paper-plane"></i> Send Reply</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:20px; color:var(--text-medium);'>No contact messages found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
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
