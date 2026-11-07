
<?php
session_start();
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin'){
    header('location: userlogin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Panel</title>
</head>
<body>
    <h2>Welcome Admin, <?php echo $_SESSION['user_name']; ?>!</h2>
    <a href="userlogin.php">Logout</a>
</body>
</html>
