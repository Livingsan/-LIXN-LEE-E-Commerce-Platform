<?php
@include 'config.php';
session_start();

$error = ""; 

if (isset($_POST['signup_btn'])) {

    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $street = trim($_POST['street']);
    $city = trim($_POST['city']);
    $country = trim($_POST['country']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if ($password !== $cpassword) {
        $error = "Passwords do not match!";
    } else {
        $check_email = execute_query($conn, "SELECT * FROM users WHERE email=?", "s", [$email]);
        if (mysqli_num_rows($check_email) > 0) {
            $error = "Email already exists! Please try a different one.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = execute_query($conn, "INSERT INTO users (name, email, password, phone, gender, dob, street, city, country) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", "sssssssss", [$name, $email, $password_hash, $phone, $gender, $dob, $street, $city, $country]);

            if ($insert) {
                $_SESSION['success_message'] = "Signup successful! Please login with your new account."; 
                header('Location: userlogin.php');
                exit();
            } else {
                $error = "Signup failed, please try again!"; 
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LIXN LEE - User Registration</title>
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

        .signup-container {
            max-width: 600px;
            width: 100%;
            background: var(--card-background);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .signup-header {
            margin-bottom: 30px;
        }

        .signup-header h1 {
            color: var(--primary-color);
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .signup-header p {
            font-size: 0.9em;
            color: var(--text-medium);
            line-height: 1.5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        @media (min-width: 550px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .form-group {
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-medium);
            font-weight: 600;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="password"],
        .form-group input[type="date"],
        .form-group select {
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

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(131, 102, 110, 0.2);
            outline: none;
        }

        .btn-primary {
            display: block;
            width: 100%;
            padding: 14px 20px;
            margin-top: 30px;
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

        .login-link {
            margin-top: 25px;
            font-size: 0.95em;
            color: var(--text-medium);
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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

        .form-group select {
            padding-right: 30px; 
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23777%22%20d%3D%22M287%2C197.3L159.2%2C69.5c-3.2-3.2-8.3-3.2-11.5%2C0L5.4%2C197.3c-3.2%2C3.2-3.2%2C8.3%2C0%2C11.5l11.5%2C11.5c3.2%2C3.2%2C8.3%2C3.2%2C11.5%2C0l124.7-124.7l124.7%2C124.7c3.2%2C3.2%2C8.3%2C3.2%2C11.5%2C0l11.5-11.5C290.2%2C205.6%2C290.2%2C200.5%2C287%2C197.3z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <h1>LIXN LEE</h1>
            <p>Create your account to start shopping with us!</p>
        </div>

        <?php 
        if (!empty($error)) {
            echo "<div class='message-box error'><i class='fas fa-exclamation-circle'></i><span>" . htmlspecialchars($error) . "</span></div>";
        }
        if (isset($_SESSION['success_message'])) {
            echo "<div class='message-box success'><i class='fas fa-check-circle'></i><span>" . htmlspecialchars($_SESSION['success_message']) . "</span></div>";
            unset($_SESSION['success_message']); 
        }
        ?>

        <form method="post" action="">
            <?php echo csrf_input(); ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Lixn lee" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Lixn@email.com" required autocomplete="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+94 7x xxx xxxx" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="" disabled <?php echo !isset($_POST['gender']) ? 'selected' : ''; ?>>Select gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="street">Street Address</label>
                    <input type="text" id="street" name="street" placeholder="123 Main St" required value="<?php echo isset($_POST['street']) ? htmlspecialchars($_POST['street']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" placeholder="Colombo" required value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" placeholder="Sri Lanka" value="Sri Lanka" required value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="cpassword">Confirm Password</label>
                    <input type="password" id="cpassword" name="cpassword" placeholder="••••••••" required autocomplete="new-password">
                </div>
            </div>
            
            <button type="submit" name="signup_btn" class="btn-primary">
                Create Account <i class="fas fa-user-plus"></i>
            </button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="userlogin.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
