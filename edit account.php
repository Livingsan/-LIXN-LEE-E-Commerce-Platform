<?php
@include 'config.php'; 
session_start();

if (!isset($_SESSION['user_email'])) {
    header('Location: userlogin.php');
    exit();
}

$user_email = $_SESSION['user_email'];

$user = null; 
$stmt_fetch = $conn->prepare("SELECT name, email, phone, gender, dob, street, city, country FROM users WHERE email = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("s", $user_email);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows > 0) {
        $user = $result_fetch->fetch_assoc();
    } else {
        session_unset();
        session_destroy();
        header('Location: userlogin.php');
        exit();
    }
    $stmt_fetch->close();
} else {
    error_log("Failed to prepare statement for fetching user data: " . $conn->error);
    echo "<script>alert('Error fetching user data. Please try again later.');</script>";
    $user = ['name' => '', 'email' => $user_email, 'phone' => '', 'gender' => '', 'dob' => '', 'street' => '', 'city' => '', 'country' => '']; // Provide empty values to avoid errors
}



if (isset($_POST['update_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
   
    $new_email = mysqli_real_escape_string($conn, $_POST['email']); 
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $street = mysqli_real_escape_string($conn, $_POST['street']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);

    $stmt_update = $conn->prepare("UPDATE users SET
        name=?, email=?, phone=?, gender=?, dob=?, street=?, city=?, country=?
        WHERE email=?"); 
    if ($stmt_update) {
        $stmt_update->bind_param("sssssssss", $name, $new_email, $phone, $gender, $dob, $street, $city, $country, $user_email);
        $update_success = $stmt_update->execute();

        if ($update_success) {
            if ($new_email !== $user_email) {
                $_SESSION['user_email'] = $new_email;
            }
            echo "<script>alert('Profile updated successfully!'); window.location.href='account.php';</script>";
        } else {
            echo "<script>alert('Update failed! " . $stmt_update->error . "');</script>";
            error_log("Profile update failed for email: $user_email - Error: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        echo "<script>alert('Update failed! Database error.');</script>";
        error_log("Failed to prepare update statement: " . $conn->error);
    }
}

$wishlist_count = 0;
$cart_count = 0;

$stmt_wishlist = $conn->prepare("SELECT COUNT(*) FROM `wishlist` WHERE email = ?");
if ($stmt_wishlist) {
    $stmt_wishlist->bind_param("s", $user_email);
    $stmt_wishlist->execute();
    $stmt_wishlist->bind_result($wishlist_count);
    $stmt_wishlist->fetch();
    $stmt_wishlist->close();
} else {
    error_log("Failed to prepare statement for wishlist count: " . $conn->error);
}

$stmt_cart = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE email = ?");
if ($stmt_cart) {
    $stmt_cart->bind_param("s", $user_email);
    $stmt_cart->execute();
    $stmt_cart->bind_result($cart_count);
    $stmt_cart->fetch();
    $stmt_cart->close();
} else {
    error_log("Failed to prepare statement for cart count: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Edit Account - LIXN LEE</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>

    <link href="styles.css" rel="stylesheet">
    <link href="premium_account.css" rel="stylesheet">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="user-page-wrapper">
        <div class="user-page-container">
            <?php include 'user_sidebar.php'; ?>
            
            <main class="user-content">
                <section class="user-section active-section">
                    <div class="edit-account-container">
                        <h1>Edit My Profile</h1>
                        <form method="post" class="edit-account-form">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($user['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($user['dob'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="street">Street</label>
                                <input type="text" id="street" name="street" value="<?= htmlspecialchars($user['street'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" value="<?= htmlspecialchars($user['country'] ?? '') ?>">
                            </div>
                            <div class="form-actions" style="justify-content: flex-start;">
                                <button type="submit" name="update_btn" class="btn btn-primary">Update Profile</button>
                                <a href="account.php" class="btn btn-outline">Cancel</a>
                            </div>
                        </form>
                    </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>
