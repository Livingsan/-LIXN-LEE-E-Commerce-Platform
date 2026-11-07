<?php
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if (!isset($_SESSION['user_email'])) {
    echo "<script>alert('Please log in first!'); window.location.href='userlogin.php';</script>";
    exit();
}

$wishlist_count = 0;
$cart_count = 0;
if ($user_email) {
    $select_wishlist = mysqli_query($conn, "SELECT * FROM `wishlist` WHERE email = '$user_email'");
    $wishlist_count = mysqli_num_rows($select_wishlist);

    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE email = '$user_email'");
    $cart_count = mysqli_num_rows($select_cart);
}

$order_id_from_url = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order_details = null;
$order_products = [];
$error_message = '';
$success_message = '';

if ($order_id_from_url > 0) {
    $select_order_query = mysqli_query($conn, "SELECT * FROM `order` WHERE id = '$order_id_from_url' AND email = '$user_email'");
    if (mysqli_num_rows($select_order_query) > 0) {
        $order_details = mysqli_fetch_assoc($select_order_query);
     
        if (!empty($order_details['total_products'])) {
            $product_strings = explode(', ', $order_details['total_products']);
            foreach ($product_strings as $prod_str) {
                if (preg_match('/^(.*?)\s*\((\d+)\)$/', $prod_str, $matches)) {
                    $product_name = trim($matches[1]);
                   
                    $order_products[] = ['product_name' => $product_name, 'product_id' => null]; // product_id will be null
                } else {
                    
                    $order_products[] = ['product_name' => trim($prod_str), 'product_id' => null];
                }
            }
        }
    } else {
        $error_message = 'Order not found or you do not have permission to access this order.';
        $order_id_from_url = 0; 
    }
}



if (isset($_POST['submit_refund'])) {
    $selected_order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $selected_product_name = mysqli_real_escape_string($conn, $_POST['product_name']); 
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);

   
    $product_id_for_db = 0; 
    if ($order_details) {
        
        $fetch_product_id_query = mysqli_query($conn, "SELECT id FROM `products` WHERE name = '$selected_product_name' LIMIT 1");
        if (mysqli_num_rows($fetch_product_id_query) > 0) {
            $prod_row = mysqli_fetch_assoc($fetch_product_id_query);
            $product_id_for_db = $prod_row['id'];
        }
    }

    $image_path_for_db = null;
    $has_image_error = false;

    if (!empty($_FILES['refund_image']['name'])) {
        $image = $_FILES['refund_image']['name'];
        $image_tmp_name = $_FILES['refund_image']['tmp_name'];
        $image_size = $_FILES['refund_image']['size'];

        if ($image_size > 5000000) { 
            $error_message = 'Image size is too large (max 5MB)!';
            $has_image_error = true;
        } elseif (!is_valid_image_upload($image_tmp_name, $image)) {
            $error_message = 'Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF.';
            $has_image_error = true;
        } else {
            $safe_img_name = sanitize_image_filename($image);
            if (move_uploaded_file($image_tmp_name, 'uploaded_img/' . $safe_img_name)) {
                $image_path_for_db = 'uploaded_img/' . $safe_img_name;
            }
        }
    }

    if (!$has_image_error) {
        $insert = execute_query($conn, "INSERT INTO `returns` (order_id, product_id, user_email, reason, status, requested_date, image_path) VALUES (?, ?, ?, ?, 'requested', NOW(), ?)", "iisss", [$selected_order_id, $product_id_for_db, $user_email, $reason, $image_path_for_db]);

        if ($insert) {
            $success_message = 'Your refund request has been submitted successfully! We will review it shortly.';
            $_POST = array(); 
        } else {
            $error_message = 'Error submitting refund request.';
        }
    }
}

if (!$order_details && $order_id_from_url > 0) {
    $error_message = 'Could not load order details for refund request.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Request Refund - LIXN LEE</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

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
                    <div class="form-container">
                        <h3>Request Refund</h3>

        <?php if ($error_message): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if ($order_details): ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="order_id">Order ID:</label>
                    <input type="text" id="order_id" name="order_id" class="form-control"
                           value="<?= 'ORD-' . date('Y', strtotime($order_details['order_date'])) . '-' . str_pad($order_details['id'], 3, '0', STR_PAD_LEFT); ?>" readonly required>
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_details['id']) ?>">
                </div>

                <div class="form-group">
                    <label for="product_name">Product to Refund:</label>
                    <select id="product_name" name="product_name" class="form-control" required>
                        <option value="">Select a Product</option>
                        <?php foreach ($order_products as $product): ?>
                            <option value="<?= htmlspecialchars($product['product_name']) ?>">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Refund:</label>
                    <textarea id="reason" name="reason" class="form-control" rows="5" placeholder="Please describe your reason for requesting a refund..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="refund_image">Upload Image (Optional, Max 5MB):</label>
                    <input type="file" id="refund_image" name="refund_image" class="form-control" accept="image/*">
                </div>

                <button type="submit" name="submit_refund" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Refund Request
                </button>
            </form>
            <?php
$return_query = mysqli_query($conn, "SELECT r.*, p.name AS product_name 
                                     FROM returns r 
                                     LEFT JOIN products p ON r.product_id = p.id 
                                     WHERE r.order_id = '$order_id_from_url' AND r.user_email = '$user_email'");

if (mysqli_num_rows($return_query) > 0): ?>
    <hr class="my-4">
    <h4 style="text-align:center; margin-bottom: 20px; font-weight:600; color:var(--primary-color)">Return Status & Admin Response</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Admin Response</th>
                    <th>Refund Amount</th>
                    <th>Refund Method</th>
                    <th>Refund Date</th>
                </tr>
            </thead>
            <tbody>
<?php while($ret = mysqli_fetch_assoc($return_query)): ?>
    <tr>
        <td><?= htmlspecialchars($ret['product_name']) ?></td>
        <td><?= htmlspecialchars($ret['reason']) ?></td>
        <td>
            <?php
            $status_color = 'secondary';
            if ($ret['status'] == 'approved') $status_color = 'success';
            elseif ($ret['status'] == 'rejected') $status_color = 'danger';
            elseif ($ret['status'] == 'refunded') $status_color = 'info';
            ?>
            <span class="badge bg-<?= $status_color ?> text-uppercase">
                <?= htmlspecialchars($ret['status']) ?>
            </span>
        </td>
        <td><?= $ret['response_note'] ? htmlspecialchars($ret['response_note']) : '—' ?></td>
        <td>—</td>
        <td>—</td> 
        <td><?= $ret['response_date'] ? date("d M Y", strtotime($ret['response_date'])) : '—' ?></td>
    </tr>
<?php endwhile; ?>
</tbody>

        </table>
    </div>
<?php endif; ?>


        <?php else: ?>
            <p class="text-center">Please navigate from your "My Orders" page to request a refund for a specific order.</p>
        <?php endif; ?>

        <a href="userorder.php" class="go-back-link">
            <i class="fas fa-arrow-left"></i> Back to My Orders
        </a>
                    </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>