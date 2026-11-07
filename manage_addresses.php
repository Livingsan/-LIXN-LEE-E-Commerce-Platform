<?php
@include 'config.php';
session_start();

if (!isset($_SESSION['user_email'])) {
    header('Location: userlogin.php');
    exit();
}

$user_email = $_SESSION['user_email'];

if (isset($_GET['set_default'])) {
    $addr_id = intval($_GET['set_default']);
    mysqli_query($conn, "UPDATE user_addresses SET is_default=0 WHERE user_email='$user_email'");
    mysqli_query($conn, "UPDATE user_addresses SET is_default=1 WHERE id=$addr_id AND user_email='$user_email'");
    header('Location: manage_addresses.php');
    exit();
}

if (isset($_GET['delete'])) {
    $addr_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM user_addresses WHERE id=$addr_id AND user_email='$user_email'");
    header('Location: manage_addresses.php');
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM user_addresses WHERE user_email='$user_email' ORDER BY is_default DESC, id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Addresses</title>
<style>
  body { font-family: 'Montserrat', sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
  .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
  h1 { color: #83666e; }
  .address-card { border: 1px solid #ccc; border-radius: 8px; padding: 15px; margin-bottom: 15px; position: relative; }
  .default-badge { position: absolute; top: 10px; right: 10px; background: #83666e; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px; }
  .actions a { margin-right: 10px; color: #83666e; text-decoration: none; font-weight: 600; }
  .actions a:hover { text-decoration: underline; }
  .btn-add { display: inline-block; background: #83666e; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; margin-bottom: 20px; }
  .btn-add:hover { background: #a0747c; }
</style>
</head>
<body>

<div class="container">
  <h1>Manage Delivery Addresses</h1>
  <a href="add_address.php" class="btn-add">+ Add New Address</a>

  <?php
    if (mysqli_num_rows($result) == 0) {
        echo "<p>No addresses found. Please add a new address.</p>";
    } else {
        while ($addr = mysqli_fetch_assoc($result)) {
            echo '<div class="address-card">';
            if ($addr['is_default']) {
                echo '<div class="default-badge">Default</div>';
            }
            echo "<p><strong>Street:</strong> " . htmlspecialchars($addr['street']) . "</p>";
            echo "<p><strong>City:</strong> " . htmlspecialchars($addr['city']) . "</p>";
            echo "<p><strong>State:</strong> " . htmlspecialchars($addr['state']) . "</p>";
            echo "<p><strong>Country:</strong> " . htmlspecialchars($addr['country']) . "</p>";
            echo "<p><strong>Postal Code:</strong> " . htmlspecialchars($addr['postal_code']) . "</p>";
            echo "<p><strong>Phone:</strong> " . htmlspecialchars($addr['phone']) . "</p>";

            echo '<div class="actions">';
            if (!$addr['is_default']) {
                echo '<a href="manage_addresses.php?set_default=' . $addr['id'] . '">Set as Default</a>';
            }
            echo '<a href="edit_address.php?id=' . $addr['id'] . '">Edit</a>';
            echo '<a href="manage_addresses.php?delete=' . $addr['id'] . '" onclick="return confirm(\'Are you sure?\')">Delete</a>';
            echo '</div>';

            echo '</div>';
        }
    }
  ?>
</div>

</body>
</html>
