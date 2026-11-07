<?php
@include 'config.php';
session_start();

if (!isset($_SESSION['user_email'])) {
    header('Location: userlogin.php');
    exit();
}

$user_email = $_SESSION['user_email'];

$message = [];

if (isset($_POST['save_address'])) {
    $id = $_POST['address_id'] ?? '';
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $street_number = mysqli_real_escape_string($conn, $_POST['street_number']);
    $street = mysqli_real_escape_string($conn, $_POST['street']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $province = mysqli_real_escape_string($conn, $_POST['province']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $pin_code = mysqli_real_escape_string($conn, $_POST['pin_code']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    
    if ($is_default) {
        mysqli_query($conn, "UPDATE addresses SET is_default = 0 WHERE user_email = '$user_email' AND type = '$type'");
    }

    if ($id) {
        $query = "UPDATE addresses SET full_name=?, phone_number=?, street_number=?, street=?, city=?, province=?, country=?, pin_code=?, is_default=? WHERE id=? AND user_email=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssiss", $full_name, $phone, $street_number, $street, $city, $province, $country, $pin_code, $is_default, $id, $user_email);
        if ($stmt->execute()) {
            $message[] = ['text' => 'Address updated successfully!', 'type' => 'success'];
        } else {
            $message[] = ['text' => 'Failed to update address: ' . $stmt->error, 'type' => 'error'];
        }
    } else {
        $query = "INSERT INTO addresses (user_email, type, full_name, phone_number, street_number, street, city, province, country, pin_code, is_default)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssi", $user_email, $type, $full_name, $phone, $street_number, $street, $city, $province, $country, $pin_code, $is_default);
        if ($stmt->execute()) {
            $message[] = ['text' => 'Address added successfully!', 'type' => 'success'];
        } else {
            $message[] = ['text' => 'Failed to add address: ' . $stmt->error, 'type' => 'error'];
        }
    }
    $stmt->close();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM addresses WHERE id = $id AND user_email = '$user_email'")) {
        $message[] = ['text' => 'Address deleted successfully!', 'type' => 'success'];
    } else {
        $message[] = ['text' => 'Failed to delete address: ' . mysqli_error($conn), 'type' => 'error'];
    }
}

if (isset($_GET['set_default'])) {
    $id = intval($_GET['set_default']);
    $type = mysqli_real_escape_string($conn, $_GET['type']);
    mysqli_query($conn, "UPDATE addresses SET is_default = 0 WHERE user_email = '$user_email' AND type = '$type'");
    if (mysqli_query($conn, "UPDATE addresses SET is_default = 1 WHERE id = $id AND user_email = '$user_email'")) {
        $message[] = ['text' => 'Address set as default successfully!', 'type' => 'success'];
    } else {
        $message[] = ['text' => 'Failed to set address as default: ' . mysqli_error($conn), 'type' => 'error'];
    }
}

$addresses = [];
$result = mysqli_query($conn, "SELECT * FROM addresses WHERE user_email = '$user_email' ORDER BY is_default DESC, type ASC, id DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $addresses[] = $row;
}

$wishlist_count = 0;
$cart_count = 0;
if ($user_email) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Addresses - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="styles.css" rel="stylesheet">
    <link href="premium_account.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="user-page-wrapper">
        <div class="user-page-container">
            <?php include 'user_sidebar.php'; ?>
            <main class="user-content">
                <section class="user-section active-section">
                    <div style="background: var(--white); padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <h1 style="font-size: 32px; text-align: center; margin-bottom: 40px; color: var(--primary-color); font-family: 'Playfair Display', serif;">Manage Your Addresses</h1>

        <?php
        if (!empty($message)) {
            foreach ($message as $msg_item) {
                echo '<div class="message ' . htmlspecialchars($msg_item['type']) . '"><span>' . htmlspecialchars($msg_item['text']) . '</span><i class="fas fa-times" onclick="this.parentElement.style.display = \'none\';"></i></div>';
            }
        }
        ?>

        <?php if (empty($addresses)): ?>
            <p class="text-center py-4 text-muted" style="font-size: 1.1rem;">No addresses found. Add your first address below to get started!</p>
        <?php else: ?>
            <h2 class="mb-4">Your Saved Addresses</h2>
            <div class="row">
                <?php foreach ($addresses as $addr): ?>
                <div class="col-lg-6 col-md-12">
                    <div class="address-card">
                        <h5>
                            <?php if ($addr['type'] === 'shipping'): ?>
                                <i class="fas fa-truck"></i> Shipping Address
                            <?php else: ?>
                                <i class="fas fa-receipt"></i> Billing Address
                            <?php endif; ?>
                            <?= $addr['is_default'] ? "<span class='badge'>Default</span>" : "" ?>
                        </h5>
                        <p><strong><?= htmlspecialchars($addr['full_name']) ?></strong></p>
                        <p><?= htmlspecialchars($addr['street_number']) ?>, <?= htmlspecialchars($addr['street']) ?></p>
                        <p><?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['province']) ?> - <?= htmlspecialchars($addr['pin_code']) ?></p>
                        <p><?= htmlspecialchars($addr['country']) ?> | Phone: <?= htmlspecialchars($addr['phone_number']) ?></p>
                        <div class="actions">
                            <a href="address.php?set_default=<?= $addr['id'] ?>&type=<?= $addr['type'] ?>" class="btn btn-outline-primary <?= $addr['is_default'] ? 'disabled' : '' ?>">Set as Default</a>
                            <a href="address.php?edit=<?= $addr['id'] ?>" class="btn btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                            <button type="button" class="btn btn-danger" onclick="showDeleteModal(<?= $addr['id'] ?>)"><i class="fas fa-trash-alt"></i> Delete</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <hr>
        <h4 class="mt-4"><?= isset($_GET['edit']) ? "Edit Address" : "Add New Address" ?></h4>

        <?php
        $edit_data = ['id'=>'', 'type'=>'shipping', 'full_name'=>'', 'phone_number'=>'', 'street_number'=>'', 'street'=>'', 'city'=>'', 'province'=>'', 'country'=>'', 'pin_code'=>'', 'is_default'=>0];
        if (isset($_GET['edit'])) {
            $id = intval($_GET['edit']);
            $res = mysqli_query($conn, "SELECT * FROM addresses WHERE id = $id AND user_email = '$user_email'");
            if ($row = mysqli_fetch_assoc($res)) {
                $edit_data = $row;
            }
        }
        ?>

        <form method="POST" class="mt-3">
            <input type="hidden" name="address_id" value="<?= htmlspecialchars($edit_data['id']) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="fullName" class="form-label">Full Name</label>
                    <input type="text" id="fullName" name="full_name" value="<?= htmlspecialchars($edit_data['full_name']) ?>" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="phoneNumber" class="form-label">Phone Number</label>
                    <input type="text" id="phoneNumber" name="phone" value="<?= htmlspecialchars($edit_data['phone_number']) ?>" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="streetNumber" class="form-label">Street Number</label>
                    <input type="text" id="streetNumber" name="street_number" value="<?= htmlspecialchars($edit_data['street_number']) ?>" class="form-control">
                </div>
                <div class="col-md-9">
                    <label for="streetName" class="form-label">Street</label>
                    <input type="text" id="streetName" name="street" value="<?= htmlspecialchars($edit_data['street']) ?>" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="city" class="form-label">City</label>
                    <input type="text" id="city" name="city" value="<?= htmlspecialchars($edit_data['city']) ?>" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="province" class="form-label">Province</label>
                    <input type="text" id="province" name="province" value="<?= htmlspecialchars($edit_data['province']) ?>" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="pinCode" class="form-label">Pin Code</label>
                    <input type="text" id="pinCode" name="pin_code" value="<?= htmlspecialchars($edit_data['pin_code']) ?>" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" id="country" name="country" value="<?= htmlspecialchars($edit_data['country']) ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="addressType" class="form-label">Address Type</label>
                    <select id="addressType" name="type" class="form-select" required>
                        <option value="shipping" <?= $edit_data['type'] === 'shipping' ? 'selected' : '' ?>>Shipping</option>
                        <option value="billing" <?= $edit_data['type'] === 'billing' ? 'selected' : '' ?>>Billing</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="isDefault" name="is_default" value="1" <?= $edit_data['is_default'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isDefault">Set as Default</label>
                    </div>
                </div>
            </div>
            <button type="submit" name="save_address" class="btn btn-success mt-3"><i class="fas fa-save"></i> <?= isset($_GET['edit']) ? 'Update Address' : 'Add Address' ?></button>
        </form>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this address? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-cancel" onclick="hideDeleteModal()">Cancel</button>
                <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>

    <script>
        function showDeleteModal(addressId) {
            const deleteModal = document.getElementById('deleteModal');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            confirmDeleteBtn.href = `address.php?delete=${addressId}`; 
            deleteModal.classList.add('active');
        }

        function hideDeleteModal() {
            const deleteModal = document.getElementById('deleteModal');
            deleteModal.classList.remove('active');
        }

        document.getElementById('deleteModal').addEventListener('click', function(event) {
            if (event.target === this) {
                hideDeleteModal();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease-out';
                    setTimeout(() => {
                        message.style.display = 'none';
                    }, 500); 
                }, 5000); 
            });
        });
    </script>
</body>
</html>
