<?php
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$message = [];

if(isset($_POST['add_user'])){
   $name = trim($_POST['name'] ?? '');
   $email = trim($_POST['email'] ?? '');
   $password = $_POST['password'] ?? '';
   $role = trim($_POST['role'] ?? 'customer');

   $hashed_password = password_hash($password, PASSWORD_DEFAULT);

   $insert_query = execute_query($conn, "INSERT INTO users(name, email, password, role) VALUES(?, ?, ?, ?)", "ssss", [$name, $email, $hashed_password, $role]);

   if($insert_query){
      $message[] = 'User added successfully!';
   } else {
      $message[] = 'Failed to add user!';
   }
}

if(isset($_GET['delete'])){
   $delete_id = intval($_GET['delete']);
   $delete_query = execute_query($conn, "DELETE FROM users WHERE id = ?", "i", [$delete_id]);
   if($delete_query){
      $message[] = 'User deleted successfully!'; 
   } else {
      $message[] = 'Failed to delete user!';
   }

}

if(isset($_POST['update_user'])){
   $update_id = intval($_POST['update_id'] ?? 0);
   $update_name = trim($_POST['update_name'] ?? '');
   $update_email = trim($_POST['update_email'] ?? '');
   $update_password = $_POST['update_password'] ?? '';
   $update_role = trim($_POST['update_role'] ?? 'customer');

   if($update_password != ''){
      $update_password = password_hash($update_password, PASSWORD_DEFAULT);
      $update_query = execute_query($conn, "UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE id = ?", "ssssi", [$update_name, $update_email, $update_password, $update_role, $update_id]);
   } else {
      $update_query = execute_query($conn, "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?", "sssi", [$update_name, $update_email, $update_role, $update_id]);
   }

   if($update_query){
      $message[] = 'User updated successfully!'; 
   } else {
      $message[] = 'Failed to update user!';
   }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Panel - User Management</title>

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
--border-light: #ddd;
--shadow-original: 0 2px 10px rgba(0,0,0,0.1);

--color-primary-button: #83666e;
--color-primary-button-hover: #5a6ddf;
--color-success: #27ae60;
--color-danger: #e74c3c;
--color-danger-hover: #c0392b;
--color-info: #3498db;
--color-info-hover: #2980b9;
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

form {
    background: var(--card-bg);
    padding: 25px;
    border-radius: 10px;
    box-shadow: var(--shadow-original);
    margin-bottom: 30px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

form h3 {
    font-family: 'Poppins', sans-serif;
    margin-bottom: 20px;
    color: var(--text-dark);
    font-size: 20px;
    text-align: left;
    font-weight: 600;
}

input[type=text],
input[type=email],
input[type=password],
select {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid var(--border-light);
    font-size: 14px;
    transition: border-color 0.3s ease;
    background-color: white;
    color: var(--text-primary);
}

input[type=text]:focus,
input[type=email]:focus,
input[type=password]:focus,
select:focus {
    border-color: var(--color-primary-button);
    outline: none;
    box-shadow: none;
}

input[type=submit],
.btn {
    background-color: var(--color-primary-button);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: normal;
    transition: background-color 0.3s ease;
    width: auto;
    display: inline-block;
    letter-spacing: normal;
    text-transform: none;
}

input[type=submit]:hover,
.btn:hover {
    background-color: var(--color-primary-button-hover);
    transform: none;
    box-shadow: none;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--card-bg);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow-original);
}

th, td {
    text-align: left;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-light);
    font-size: 14px;
    color: var(--text-dark);
}

th {
    background-color: var(--color-primary-button);
    color: white;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    font-size: 16px;
    text-transform: none;
    letter-spacing: normal;
}

tr:hover {
    background-color: #f0f4ff;
}

.delete-btn, .option-btn {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    text-decoration: none;
    color: white;
    transition: background-color 0.3s ease;
    margin-right: 8px;
}

.delete-btn {
    background-color: var(--color-danger);
}

.delete-btn:hover {
    background-color: var(--color-danger-hover);
    transform: none;
}

.option-btn {
    background-color: var(--color-info);
}

.option-btn:hover {
    background-color: var(--color-info-hover);
    transform: none;
}

.delete-btn i, .option-btn i {
    margin-right: 8px;
}

.edit-form-container {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.4);
    justify-content: center;
    align-items: center;
    padding: 20px;
    z-index: 1500;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    animation: none;
}

        .edit-form-container form {
            max-width: 400px; 
            width: 100%;
            background: var(--card-bg);
            padding: 25px;
            border-radius: 10px; 
            box-shadow: var(--shadow-original); 
            animation: none; 
        }

        .edit-form-container form h3 {
            font-family: 'Poppins', sans-serif; 
            margin-bottom: 20px; 
            color: var(--text-dark); 
            font-size: 20px; 
            text-align: left; 
            font-weight: 600; 
        }


        .edit-form-container .btn-group {
            display: flex; 
            gap: 10px;
            margin-top: 10px; 
        }

        .edit-form-container .btn-group input[type="submit"],
        .edit-form-container .btn-group input[type="button"] {
            flex: 1; 
            width: auto; 
            margin-bottom: 0; 
        }

        #closeEditBtn {
            background-color: var(--color-danger); 
            margin-top: 0; 
        }

        #closeEditBtn:hover {
            background-color: var(--color-danger-hover); 
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
            th, td {
                padding: 12px 15px; 
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
            form {
                padding: 20px; 
            }
            th, td {
                padding: 10px 12px; 
                font-size: 12px; 
            }
            .delete-btn, .option-btn {
                padding: 6px 10px; 
                font-size: 12px; 
                margin-right: 5px; 
            }
            .action-buttons {
                display: block; 
            }
            .action-buttons a {
                margin-bottom: 5px;
                width: auto; 
            }
            .edit-form-container form {
                padding: 20px; 
            }
            .edit-form-container form h3 {
                font-size: 22px; 
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
            form {
                padding: 15px;
            }
            form h3 {
                font-size: 18px;
            }
            input[type=text],
            input[type=email],
            input[type=password],
            select {
                padding: 10px 12px;
                font-size: 13px;
            }
            input[type=submit],
            .btn {
                padding: 10px 15px;
                font-size: 14px;
            }
            th, td {
                padding: 8px 10px;
                font-size: 11px;
            }
            .delete-btn, .option-btn {
                padding: 5px 8px;
                font-size: 11px;
            }
            .edit-form-container form {
                padding: 15px;
            }
            .edit-form-container form h3 {
                font-size: 18px;
            }
        }

        body.dark-mode {
            background: #121212;
            color: #e0e0e0;
        }
        body.dark-mode .header,
        body.dark-mode form,
        body.dark-mode table,
        body.dark-mode .edit-form-container form {
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
        body.dark-mode form h3,
        body.dark-mode th,
        body.dark-mode td {
            color: #f0f0f0;
        }
        body.dark-mode .user-details div:first-child,
        body.dark-mode form label {
            color: #f0f0f0;
        }
        body.dark-mode .user-details div:last-child {
            color: #b0b0b0;
        }
        body.dark-mode input[type=text],
        body.dark-mode input[type=email],
        body.dark-mode input[type=password],
        body.dark-mode select {
            background-color: #2b2b2b;
            border-color: #3a3a3a;
            color: #e0e0e0;
        }
        body.dark-mode input[type=text]:focus,
        body.dark-mode input[type=email]:focus,
        body.dark-mode input[type=password]:focus,
        body.dark-mode select:focus {
            background-color: #2b2b2b; 
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.25); 
        }
        body.dark-mode table {
            background: #1e1e1e;
        }
        body.dark-mode tr:nth-child(even) {
            background-color: #252525;
        }
        body.dark-mode tr:hover {
            background-color: #353535;
        }
        body.dark-mode th, body.dark-mode td {
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
                <a href="adminuser.php" class="menu-item active" data-page="adminuser.php"><i class="fas fa-users"></i><span>User Management</span></a>
                <a href="adminorder.php" class="menu-item" data-page="adminorder.php"><i class="fas fa-box-open"></i><span>Order Management</span></a>
                <a href="admin.php" class="menu-item" data-page="admin.php"><i class="fas fa-tags"></i><span>Product Management</span></a>
                <a href="revenue.php" class="menu-item" data-page="revenue.php"><i class="fas fa-dollar-sign"></i><span>Total Revenue & Reports</span></a>
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
                <h1>User Management</h1>
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

            <form action="" method="post">
                <h3>Add a New User</h3>
                <input type="text" name="name" placeholder="Enter user name" required />
                <input type="email" name="email" placeholder="Enter user email" required />
                <input type="password" name="password" placeholder="Enter user password" required />
                <select name="role" required>
                    <option value="user" selected>User</option>
                    <option value="admin">Admin</option>
                </select>
                <input type="submit" name="add_user" value="Add User" />
            </form>

            <table>
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>User Email</th>
                        <th>User Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $select_users = mysqli_query($conn, "SELECT * FROM users");
                    if(mysqli_num_rows($select_users) > 0){
                        while($row = mysqli_fetch_assoc($select_users)){
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($row['role'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="adminuser.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                                <a href="adminuser.php?edit=<?php echo $row['id']; ?>" class="option-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; padding:20px;">No users found!</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div class="edit-form-container" id="editFormContainer">
                <?php
                if(isset($_GET['edit'])){
                    $edit_id = (int)$_GET['edit'];
                    $edit_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $edit_id");
                    if(mysqli_num_rows($edit_query) > 0){
                        $fetch_edit = mysqli_fetch_assoc($edit_query);
                ?>
                <form action="" method="post">
                    <input type="hidden" name="update_id" value="<?php echo $fetch_edit['id']; ?>" />
                    <h3>Edit User Details</h3>
                    <input type="text" name="update_name" value="<?php echo htmlspecialchars($fetch_edit['name']); ?>" required />
                    <input type="email" name="update_email" value="<?php echo htmlspecialchars($fetch_edit['email']); ?>" required />
                    <input type="password" name="update_password" placeholder="New password (leave blank to keep current)" />
                    <select name="update_role" required>
                        <option value="user" <?php if($fetch_edit['role'] == 'user'){ echo 'selected'; } ?>>User</option>
                        <option value="admin" <?php if($fetch_edit['role'] == 'admin'){ echo 'selected'; } ?>>Admin</option>
                    </select>
                    <div class="btn-group">
                        <input type="submit" name="update_user" value="Update User" class="btn" />
                        <input type="button" value="Cancel" id="closeEditBtn" class="option-btn" />
                    </div>
                </form>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        <?php if(isset($_GET['edit'])): ?>
            document.getElementById('editFormContainer').style.display = 'flex';
        <?php endif; ?>

        document.getElementById('closeEditBtn')?.addEventListener('click', () => {
            window.location.href = 'adminuser.php'; 
        });

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
