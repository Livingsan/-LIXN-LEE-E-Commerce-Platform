<?php 
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit;
}

$admin_id = intval($_SESSION['admin_id'] ?? 0);

$admin_query = execute_query($conn, "SELECT * FROM admins WHERE id = ?", "i", [$admin_id]);
$admin = mysqli_fetch_assoc($admin_query);

if (!$admin) {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>❌ Admin not found in database.</h2>";
    exit;
}

$success_msg = '';
$error_msg = '';

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email format.";
    } else {
        $update = execute_query($conn, "UPDATE admins SET name = ?, email = ?, username = ? WHERE id = ?", "sssi", [$name, $email, $username, $admin_id]);
        if ($update) {
            $success_msg = "Profile updated successfully.";
            $admin_refresh = execute_query($conn, "SELECT * FROM admins WHERE id = ?", "i", [$admin_id]);
            $admin = mysqli_fetch_assoc($admin_refresh);
        } else {
            $error_msg = "Error updating profile.";
        }
    }
}

if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    $admin_pass = $admin['password'];

    if (!password_verify($current_pass, $admin_pass)) {
        $error_msg = "Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = "New password and confirm password do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error_msg = "New password must be at least 6 characters.";
    } else {
        $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $update_pass = execute_query($conn, "UPDATE admins SET password = ? WHERE id = ?", "si", [$new_hashed, $admin_id]);
        if ($update_pass) {
            $success_msg = "Password changed successfully.";
        } else {
            $error_msg = "Error changing password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * {margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif;}
    body {background: #f5f7fa; color: #333;}
    .dashboard {display: flex; min-height: 100vh;}
    .sidebar {
      width: 260px;
      background: linear-gradient(135deg, #83666e 0%, #6a4f56 100%);
      color: white;
      padding: 20px 0;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
    }
    .sidebar-header {padding: 0 20px 30px; border-bottom: 1px solid rgba(255,255,255,0.1);}
    .sidebar-header h2 {font-size: 24px; font-weight: 600;}
    .sidebar-header p {opacity: 0.8; font-size: 14px;}
    .sidebar-menu {padding: 20px 0;}
    .menu-item {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }
    .menu-item:hover, .menu-item.active {
      background: rgba(255,255,255,0.15);
      border-left-color: #fff;
    }
    .menu-item i {margin-right: 12px;}
    .main-content {
      flex: 1;
      margin-left: 260px;
      padding: 30px;
      background: #f5f7fa;
      min-height: 100vh;
    }
    .header {
      background: white;
      padding: 20px 30px;
      border-radius: 10px;
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .header h1 {font-size: 28px; color: #2c3e50; font-weight: 600;}
    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #83666e 0%, #6a4f56 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 18px;
    }
    .profile-card {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.1);
      max-width: 700px;
      margin: auto;
      margin-bottom: 40px;
    }
    h2 {font-weight: 600; margin-bottom: 20px; color: #2c3e50;}
    form {display: grid; gap: 20px;}
    label {font-weight: 600; color: #555;}
    input[type="text"], input[type="email"], input[type="password"] {
      padding: 12px 15px;
      font-size: 16px;
      border-radius: 8px;
      border: 1px solid #ddd;
    }
    input:focus {border-color: #83666e; outline: none;}
    button {
      background: #83666e;
      color: white;
      border: none;
      padding: 14px 0;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      font-weight: 600;
    }
    button:hover {background: #5561c1;}
    .message {
      margin-bottom: 25px;
      padding: 15px 20px;
      border-radius: 10px;
    }
    .success {background: #d4edda; color: #155724; border-left: 6px solid #28a745;}
    .error {background: #f8d7da; color: #721c24; border-left: 6px solid #dc3545;}
  </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <h2>Admin Panel</h2>
        <p>Dashboard v2.0</p>
      </div>
      <div class="sidebar-menu">
        <a href="admindash.php" class="menu-item"><i>📊</i><span>Dashboard</span></a>
        <a href="adminuser.php" class="menu-item"><i>👥</i><span>User Management</span></a>
        <a href="adminorder.php" class="menu-item"><i>📦</i><span>Order Management</span></a>
        <a href="admin.php" class="menu-item"><i>🛍️</i><span>Product Management</span></a>
        <a href="revenue.php" class="menu-item"><i>📋</i><span>Total Revenue</span></a>
        <a href="admin_stock.php" class="menu-item"><i>📦</i><span>Stock</span></a>
        <a href="admin_return.php" class="menu-item" data-page="admin_return.php"><i class="fas fa-warehouse"></i><span>Return&Refund Management</span></a>

        <a href="add_coupon.php" class="menu-item"><i>🎁</i><span>Coupons</span></a>
        <a href="#" class="menu-item active"><i>👤</i><span>Admin Profile</span></a>
        <a href="admin_logout.php" class="menu-item"><i>🚪</i><span>Logout</span></a>
      </div>
    </div>

    <div class="main-content">
      <div class="header">
        <h1>Admin Profile</h1>
        <div class="user-info">
          <div class="user-avatar"><?php echo strtoupper($admin['name'][0]); ?></div>
          <div>
            <div style="font-weight: 600;"><?php echo htmlspecialchars($admin['name']); ?></div>
            <div style="font-size: 12px; color: #666;">Administrator</div>
          </div>
        </div>
      </div>

      <?php if ($success_msg): ?>
        <div class="message success"><?php echo $success_msg; ?></div>
      <?php endif; ?>
      <?php if ($error_msg): ?>
        <div class="message error"><?php echo $error_msg; ?></div>
      <?php endif; ?>

      <div class="profile-card">
        <h2>Update Profile Information</h2>
        <form method="POST">
          <label for="name">Name</label>
          <input type="text" name="name" required value="<?php echo htmlspecialchars($admin['name']); ?>">

          <label for="email">Email</label>
          <input type="email" name="email" required value="<?php echo htmlspecialchars($admin['email']); ?>">

          <label for="username">Username</label>
          <input type="text" name="username" required value="<?php echo htmlspecialchars($admin['username']); ?>">

          <button type="submit" name="update_profile">Save Changes</button>
        </form>
      </div>

      <div class="profile-card">
        <h2>Change Password</h2>
        <form method="POST">
          <label for="current_password">Current Password</label>
          <input type="password" name="current_password" required>

          <label for="new_password">New Password</label>
          <input type="password" name="new_password" required>

          <label for="confirm_password">Confirm New Password</label>
          <input type="password" name="confirm_password" required>

          <button type="submit" name="change_password">Change Password</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
