<?php
@include 'config.php';
session_start();

$step = 1;
$error = '';
$success = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); 
}

if (isset($_POST['check_email'])) {
    $email = trim($_POST['email'] ?? '');
    $check = execute_query($conn, "SELECT * FROM users WHERE email = ?", "s", [$email]);
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['reset_email'] = $email;
        $step = 2;
    } else {
        $error = "Email not found in our records. Please check the email address.";
    }
}

if (isset($_POST['reset_password'])) {

    if (!isset($_SESSION['reset_email'])) {
        $error = "Session expired or invalid request. Please start over.";
        $step = 1;
    } else {
        $newpass = $_POST['new_password'] ?? '';
        $cpass = $_POST['confirm_password'] ?? '';

        if ($newpass !== $cpass) {
            $error = "Passwords do not match! Please try again.";
            $step = 2; 
        } else {
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $email = $_SESSION['reset_email'];
            $update = execute_query($conn, "UPDATE users SET password = ? WHERE email = ?", "ss", [$hashed, $email]);
            if ($update) {
                unset($_SESSION['reset_email']); 
                $_SESSION['success'] = "Password reset successful! You can now log in with your new password.";
                header("Location: userlogin.php");
                exit();
            } else {
                $error = "Failed to update password. Please try again later.";
                $step = 2; 
            }
        }
    }
} else if (isset($_SESSION['reset_email'])) {
    $step = 2;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIXN LEE - Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --success-bg: #e0ffe0;
            --success-border: #00cc00;
            --success-text: #008000;
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

        .forgot-password-container {
            max-width: 500px;
            width: 100%;
            background: var(--card-background);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .forgot-password-header {
            margin-bottom: 30px;
        }

        .forgot-password-header h1 {
            color: var(--primary-color);
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .forgot-password-header p {
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

        .message-box {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .message-box.error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }

        .message-box.success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        .message-box i {
            font-size: 1.1em;
        }

        .back-to-login {
            margin-top: 25px;
            font-size: 0.95em;
            color: var(--text-medium);
        }

        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-to-login a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @media (max-width: 500px) {
            .forgot-password-container {
                padding: 25px;
                margin: 20px;
            }
            .forgot-password-header h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-header">
            <h1>LIXN LEE</h1>
            <p>
                <?php if ($step == 1): ?>
                    Enter your registered email address to reset your password.
                <?php elseif ($step == 2): ?>
                    Set your new password below.
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="message-box error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="message-box success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
                </div>
                <button type="submit" name="check_email" class="btn-primary">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        <?php elseif ($step == 2): ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="••••••••" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password">
                </div>
                <button type="submit" name="reset_password" class="btn-primary">
                    Reset Password <i class="fas fa-key"></i>
                </button>
            </form>
        <?php endif; ?>

        <div class="back-to-login">
            <p>Remember your password? <a href="userlogin.php">Back to Login</a></p>
        </div>
    </div>
</body>
</html>
