<?php
@include 'config.php'; 
session_start();

$error = ''; 

if (isset($_POST['login'])) {

    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = execute_query($conn, "SELECT * FROM users WHERE email=?", "s", [$email]);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $row['role'];

            if ($row['role'] === 'admin') {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_name'] = $row['name'] ?? 'Admin';
                header('Location: admindash.php');
            } else {
                header('Location: account.php');
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Invalid email or user not found!"; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LIXN LEE - User Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #83666e; 
            --primary-dark: #6a4f56;
            --primary-light: #9a7e86;
            --background-light: #f7f7f7;
            --card-background: #ffffff;
            --text-dark: #333333;
            --text-medium: #555555;
            --text-light: #777777;
            --border-color: #e0e0e0;
            --error-bg: #ffe0e0;
            --error-border: #cc0000;
            --error-text: #cc0000;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            background: var(--card-background);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            font-size: 0.9em;
            color: var(--text-medium);
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-medium);
            font-weight: 600;
            font-size: 0.95em;
        }

        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            color: var(--text-dark);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            -webkit-appearance: none; 
            -moz-appearance: none;
            appearance: none;
        }

        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(131, 102, 110, 0.2);
            outline: none;
        }

        .form-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9em;
        }

        .form-action a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-action a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .btn-primary {
            display: block;
            width: 100%;
            padding: 14px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(131, 102, 110, 0.3);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 6px 20px rgba(131, 102, 110, 0.4);
        }

        .separator {
            margin: 30px 0;
            font-size: 0.85em;
            color: var(--text-light);
            position: relative;
        }

        .separator::before,
        .separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: var(--border-color);
        }

        .separator::before {
            left: 0;
        }

        .separator::after {
            right: 0;
        }

        .social-login-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .social-btn {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: transparent;
            color: var(--text-dark);
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        .social-btn i {
            margin-right: 8px;
            font-size: 1.2em;
        }

        .social-btn.google:hover {
            background-color: #db4437; 
            color: white;
            border-color: #db4437;
        }

        .social-btn.facebook:hover {
            background-color: #1877f2; 
            color: white;
            border-color: #1877f2;
        }

        .register-link {
            font-size: 0.95em;
            color: var(--text-medium);
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .error-message {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .error-message i {
            font-size: 1.1em;
        }

        @media (max-width: 500px) {
            .login-container {
                padding: 25px;
                margin: 20px;
            }
            .login-header h1 {
                font-size: 1.8em;
            }
            .social-login-buttons {
                flex-direction: column;
            }
            .social-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>LIXN LEE</h1>
            <p>Welcome back! Please login to your account to continue.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php echo csrf_input(); ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Lixn@email.com" required autocomplete="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="form-action">
                <div></div> <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" name="login" class="btn-primary">
                Login <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="separator">Or</div>

        <div class="social-login-buttons">
            <button type="button" class="social-btn google">
                <i class="fab fa-google"></i> Continue with Google
            </button>
            <button type="button" class="social-btn facebook">
                <i class="fab fa-facebook-f"></i> Continue with Facebook
            </button>
        </div>

        <div class="register-link">
            <p>Don't have an account? <a href="userreg.php">Signup here</a></p>
        </div>
    </div>
</body>
</html>