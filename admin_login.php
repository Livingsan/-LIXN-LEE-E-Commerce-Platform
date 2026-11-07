<?php
@include 'config.php';
session_start();

$error = '';

if (isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = execute_query($conn, "SELECT * FROM admins WHERE username = ?", "s", [$username]);

    if (mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);

        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            $_SESSION['user_email'] = $admin['email'] ?? 'admin@shop.com'; // Set this for compatibility
            $_SESSION['user_role'] = 'admin';
            header('Location: admindash.php');
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Admin user not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: var(--bg-main); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { width: 100%; max-width: 450px; padding: 50px; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border-top: 5px solid var(--primary); }
        .login-box h2 { font-family: 'Playfair Display', serif; color: var(--primary); text-align: center; font-size: 2.2rem; margin-bottom: 10px; }
        .login-box p { text-align: center; color: #888; margin-bottom: 40px; font-size: 0.9rem; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #444; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .form-group input { width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 8px; background: #fafafa; transition: var(--transition-smooth); }
        .form-group input:focus { border-color: var(--primary); background: white; outline: none; box-shadow: 0 0 15px rgba(131, 102, 110, 0.1); }
        .btn-login { width: 100%; background: var(--primary); color: white; border: none; padding: 18px; border-radius: 50px; font-weight: 700; cursor: pointer; text-transform: uppercase; letter-spacing: 2px; transition: var(--transition-smooth); margin-top: 10px; }
        .btn-login:hover { background: var(--primary-dark); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(131, 102, 110, 0.2); }
        .error-msg { background: #fff3f3; color: #d63031; padding: 15px; border-radius: 8px; margin-bottom: 25px; text-align: center; font-size: 0.9rem; border-left: 4px solid #d63031; }
    </style>
</head>
<body>

    <div class="login-box glass-card">
        <h2>Admin Portal</h2>
        <p>Enter your credentials to manage the store</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <?php echo csrf_input(); ?>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Admin username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="btn-login">Secure Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" style="color: #999; text-decoration: none; font-size: 0.85rem;"><i class="fas fa-arrow-left"></i> Back to Storefront</a>
        </div>
    </div>

</body>
</html>
