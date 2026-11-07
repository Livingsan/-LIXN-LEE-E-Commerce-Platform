<?php
@include 'config.php';
session_start();

$user_email = $_SESSION['user_email'] ?? '';

if (!$user_email) {
    header('location:userlogin.php');
    exit();
}

$delivery_charges = [
    'ampara' => 600, 'anuradhapura' => 500, 'badulla' => 550, 'batticaloa' => 650,
    'colombo' => 300, 'galle' => 500, 'gampaha' => 350, 'hambantota' => 600,
    'jaffna' => 750, 'kalutara' => 400, 'kandy' => 450, 'kegalle' => 400,
    'kilinochchi' => 750, 'kurunegala' => 400, 'mannar' => 700, 'matale' => 450,
    'matara' => 550, 'moneragala' => 600, 'mullaitivu' => 750, 'nuwara eliya' => 500,
    'polonnaruwa' => 550, 'puttalam' => 450, 'ratnapura' => 450, 'trincomalee' => 650,
    'vavuniya' => 700
];

$checkout_fields = ['fullName', 'phone', 'district', 'address', 'address2', 'zip', 'paymentMethod'];

if (!isset($_SESSION['checkout_form']) || !is_array($_SESSION['checkout_form'])) {
    $_SESSION['checkout_form'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($checkout_fields as $field) {
        if (array_key_exists($field, $_POST)) {
            $_SESSION['checkout_form'][$field] = trim((string) $_POST[$field]);
        }
    }
}

$checkout_form = $_SESSION['checkout_form'];
$product_total = 0;
$tax_total = 0;

$cart_query = execute_query($conn, "SELECT c.*, p.category FROM cart c JOIN products p ON c.product_id = p.id WHERE c.email = ?", "s", [$user_email]);
$cart_items = [];
while ($row = mysqli_fetch_assoc($cart_query)) {
    $line_total = $row['price'] * $row['quantity'];
    $product_total += $line_total;
    $tax_rate = get_tax_rate_for_product_category($row['category'] ?? '');
    $tax_total += $line_total * $tax_rate;
    $cart_items[] = $row;
}

$selected_district = normalize_product_category($checkout_form['district'] ?? 'colombo');
$delivery_charge = $delivery_charges[$selected_district] ?? 500;

$discount = 0;
$applied_coupon = $_SESSION['checkout_coupon'] ?? null;
$active_coupon_code = '';

if ($applied_coupon && !empty($applied_coupon['code'])) {
    $today = date('Y-m-d');
    $coupon_query = execute_query(
        $conn,
        "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND valid_from <= ? AND expiry_date >= ? AND (target_email IS NULL OR target_email = '' OR target_email = ?)",
        "ssss",
        [$applied_coupon['code'], $today, $today, $user_email]
    );

    if ($coupon_row = mysqli_fetch_assoc($coupon_query)) {
        $used_query = execute_query(
            $conn,
            "SELECT * FROM coupon_usage WHERE user_email = ? AND coupon_code = ?",
            "ss",
            [$user_email, $coupon_row['code']]
        );

        if (mysqli_num_rows($used_query) === 0) {
            $active_coupon_code = $coupon_row['code'];
            $discount = $product_total * ((float) $coupon_row['discount'] / 100);
            $_SESSION['checkout_coupon'] = [
                'code' => $coupon_row['code'],
                'discount' => (float) $coupon_row['discount']
            ];
        } else {
            unset($_SESSION['checkout_coupon']);
        }
    } else {
        unset($_SESSION['checkout_coupon']);
    }
}

$discount = 0;
if (isset($_POST['apply_coupon'])) {
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $today = date('Y-m-d');

    $coupon_query = execute_query(
        $conn,
        "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND valid_from <= ? AND expiry_date >= ? AND (target_email IS NULL OR target_email = '' OR target_email = ?)",
        "ssss",
        [$coupon_code, $today, $today, $user_email]
    );

    if ($row = mysqli_fetch_assoc($coupon_query)) {
        $used_query = execute_query(
            $conn,
            "SELECT * FROM coupon_usage WHERE user_email = ? AND coupon_code = ?",
            "ss",
            [$user_email, $coupon_code]
        );

        if (mysqli_num_rows($used_query) > 0) {
            unset($_SESSION['checkout_coupon']);
            $message[] = 'This coupon has already been used';
        } else {
            $_SESSION['checkout_coupon'] = [
                'code' => $row['code'],
                'discount' => (float) $row['discount']
            ];
            $active_coupon_code = $row['code'];
            $discount = $product_total * ((float) $row['discount'] / 100);
            $message[] = "Discount ({$row['discount']}%) applied successfully!";
        }
    } else {
        unset($_SESSION['checkout_coupon']);
        $message[] = 'Invalid or expired coupon code';
    }
}

$applied_coupon = $_SESSION['checkout_coupon'] ?? null;
if ($applied_coupon && !empty($applied_coupon['code'])) {
    $active_coupon_code = $applied_coupon['code'];
    $discount = $product_total * ((float) $applied_coupon['discount'] / 100);
}

$grand_total = ($product_total + $tax_total + $delivery_charge) - $discount;

if (isset($_POST['place_order'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $name = trim($_POST['fullName'] ?? '');
    $number = trim($_POST['phone'] ?? '');
    $method = trim($_POST['paymentMethod'] ?? 'Cash on Delivery');
    $district = normalize_product_category($_POST['district'] ?? $selected_district);
    $address = trim($_POST['address'] ?? '');
    $address2 = trim($_POST['address2'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $city = $_POST['city'] ?? $district;

    $placed_on = date('Y-m-d H:i:s');

    $items_summary = [];
    foreach($cart_items as $item) {
        $items_summary[] = $item['name'] . ' (x'.$item['quantity'].')';
        execute_query($conn, "UPDATE products SET stock = stock - ? WHERE id = ?", "ii", [$item['quantity'], $item['product_id']]);
    }
    $total_products = implode(', ', $items_summary);

    $province = 'Western';
    $country = 'Sri Lanka';
    $status = 'Pending';
    
    $insert_order = execute_query($conn, "INSERT INTO `order` 
    (name, number, email, order_date, method, street_number, street, city, province, country, pin_code, total_products, total_price, tax_amount, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
    "ssssssssssssdds", 
    [$name, $number, $user_email, $placed_on, $method, $address2, $address, $city, $province, $country, $zip, $total_products, $grand_total, $tax_total, $status]);

    if($insert_order) {
        if (!empty($active_coupon_code) && $discount > 0) {
            $coupon_used_query = execute_query(
                $conn,
                "SELECT * FROM coupon_usage WHERE user_email = ? AND coupon_code = ?",
                "ss",
                [$user_email, $active_coupon_code]
            );

            if (mysqli_num_rows($coupon_used_query) === 0) {
                execute_query(
                    $conn,
                    "INSERT INTO coupon_usage (user_email, coupon_code) VALUES (?, ?)",
                    "ss",
                    [$user_email, $active_coupon_code]
                );
            }
        }

        execute_query($conn, "DELETE FROM `cart` WHERE email = ?", "s", [$user_email]);
        unset($_SESSION['checkout_coupon'], $_SESSION['checkout_form']);
        header('location:payment_successful.php?order_id=' . mysqli_insert_id($conn));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link href="sections-storefront.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <?php
    if(isset($message)){
        foreach($message as $msg){
            echo '<div class="message">'.$msg.'</div>';
        }
    }
    ?>

    <div class="checkout-page container">
        <form action="" method="post" class="checkout-form-card">
            <?php echo csrf_input(); ?>
            <div class="co-section">
                <h3><i class="fas fa-truck"></i>Shipping Information</h3>
                <input type="text" name="fullName" placeholder="Full Name" value="<?php echo htmlspecialchars($checkout_form['fullName'] ?? ''); ?>" required>
                <div class="co-row">
                    <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($checkout_form['phone'] ?? ''); ?>" required>
                    <input type="email" name="email" value="<?php echo $user_email; ?>" readonly>
                </div>
                
                <select name="district" onchange="this.form.submit()" required>
                    <?php foreach($delivery_charges as $dist => $charge): ?>
                        <option value="<?php echo $dist; ?>" <?php if($selected_district == $dist) echo 'selected'; ?>><?php echo ucfirst($dist); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="address" placeholder="Street Name / Address" value="<?php echo htmlspecialchars($checkout_form['address'] ?? ''); ?>" required>
                <div class="co-row">
                    <input type="text" name="address2" placeholder="Apartment / House No." value="<?php echo htmlspecialchars($checkout_form['address2'] ?? ''); ?>">
                    <input type="text" name="zip" placeholder="Zip / Postal Code" value="<?php echo htmlspecialchars($checkout_form['zip'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="co-section">
                <h3><i class="fas fa-credit-card"></i>Payment Method</h3>
                <select name="paymentMethod" id="paymentMethod" required onchange="toggleCardDetails()">
                    <option value="Cash on Delivery" <?php if(($checkout_form['paymentMethod'] ?? 'Cash on Delivery') == 'Cash on Delivery') echo 'selected'; ?>>Cash on Delivery</option>
                    <option value="Credit Card" <?php if(($checkout_form['paymentMethod'] ?? 'Cash on Delivery') == 'Credit Card') echo 'selected'; ?>>Credit Card</option>
                    <option value="PayPal" <?php if(($checkout_form['paymentMethod'] ?? 'Cash on Delivery') == 'PayPal') echo 'selected'; ?>>PayPal</option>
                </select>

                <div id="cardDetails" style="display: none; margin-top: 4px;">
                    <input type="text" name="cardNumber" placeholder="Card Number (0000 0000 0000 0000)" pattern="\d{16}" maxlength="16">
                    <div class="co-row">
                        <input type="text" name="expiry" placeholder="MM/YY" maxlength="5">
                        <input type="text" name="cvv" placeholder="CVV" maxlength="3" pattern="\d{3}">
                    </div>
                </div>
            </div>

            <button type="submit" name="place_order" class="btn-place-order"><i class="fas fa-lock" style="margin-right:8px;"></i>Place Order</button>
        </form>

        <script>
        function toggleCardDetails() {
            var method = document.getElementById('paymentMethod').value;
            var cardForm = document.getElementById('cardDetails');
            var inputs = cardForm.querySelectorAll('input');
            if(method === 'Credit Card') {
                cardForm.style.display = 'block';
                inputs.forEach(i => i.required = true);
            } else {
                cardForm.style.display = 'none';
                inputs.forEach(i => i.required = false);
            }
        }
        document.addEventListener('DOMContentLoaded', toggleCardDetails);
        </script>

        <aside class="checkout-summary-card">
            <h3 class="co-summary-title"><i class="fas fa-receipt"></i>Your Order</h3>
            <?php foreach($cart_items as $item): ?>
                <div class="co-item">
                    <span><?php echo $item['name']; ?> x <?php echo $item['quantity']; ?></span>
                    <span>RS <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
            <?php endforeach; ?>
            
            <div class="co-item" style="border-top:1px solid #f5f5f5; padding-top:12px; margin-top:12px;">
                <span>Subtotal</span>
                <span>RS <?php echo number_format($product_total, 2); ?></span>
            </div>
            <div class="co-item">
                <span>Tax</span>
                <span>RS <?php echo number_format($tax_total, 2); ?></span>
            </div>
            <div class="co-item">
                <span>Delivery (<?php echo ucfirst($selected_district); ?>)</span>
                <span>RS <?php echo number_format($delivery_charge, 2); ?></span>
            </div>
            <?php if($discount > 0): ?>
            <div class="co-item co-discount">
                <span>Discount</span>
                <span>- RS <?php echo number_format($discount, 2); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="co-total">
                <span>Total</span>
                <span>RS <?php echo number_format($grand_total, 2); ?></span>
            </div>

            <form action="" method="post" class="coupon-input">
                <input type="text" name="coupon_code" placeholder="Coupon Code" value="<?php echo htmlspecialchars($active_coupon_code); ?>">
                <button type="submit" name="apply_coupon" class="btn-coupon">Apply</button>
            </form>
            <p style="font-size:0.75rem; color:#bbb; margin-top:8px; text-align:center;">Try "LIXN10" for 10% off!</p>
        </aside>
    </div>

</body>
</html>
