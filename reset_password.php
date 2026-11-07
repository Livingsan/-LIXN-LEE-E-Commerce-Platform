<?php
@include 'config.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    echo "<script>alert('No token provided'); window.location.href='userlogin.php';</script>";
    exit;
}

$check = execute_query($conn, "SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW()", "s", [$token]);
if (mysqli_num_rows($check) == 0) {
    echo "<script>alert('Invalid or expired token'); window.location.href='userlogin.php';</script>";
    exit;
}

if (isset($_POST['update_password'])) {
    $newpass = $_POST['new_password'] ?? '';
    $cpass = $_POST['confirm_password'] ?? '';

    if ($newpass !== $cpass) {
        echo "<script>alert('Passwords do not match');</script>";
    } else {
        $hashed = password_hash($newpass, PASSWORD_DEFAULT);
        execute_query($conn, "UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?", "ss", [$hashed, $token]);
        echo "<script>alert('Password updated successfully!'); window.location.href='userlogin.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
   <title>Reset Password</title>
   <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
   <style>
      body {
         font-family: 'Montserrat', sans-serif;
         background-color: #f4f4f4;
      }
      .container {
         max-width: 500px;
         margin: 60px auto;
         background: white;
         padding: 30px;
         border-radius: 10px;
         box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      }
      h1 {
         text-align: center;
         color: #83666e;
         margin-bottom: 30px;
      }
      .inputBox {
         margin-bottom: 20px;
      }
      .inputBox span {
         display: block;
         margin-bottom: 5px;
         color: #555;
      }
      .inputBox input {
         width: 100%;
         padding: 10px;
         border: 1px solid #ccc;
         border-radius: 5px;
         font-size: 16px;
      }
      .btn {
         width: 100%;
         background-color: #83666e;
         color: white;
         padding: 12px;
         border: none;
         border-radius: 6px;
         font-size: 16px;
         cursor: pointer;
      }
      .btn:hover {
         background-color: #9a7e86;
      }
   </style>
</head>
<body>

   <div class="container">
      <h1>Reset Password</h1>
      <form method="post">
         <div class="inputBox">
            <span>New Password</span>
            <input type="password" name="new_password" required>
         </div>
         <div class="inputBox">
            <span>Confirm Password</span>
            <input type="password" name="confirm_password" required>
         </div>
         <input type="submit" name="update_password" value="Update Password" class="btn">
      </form>
   </div>

</body>
</html>
