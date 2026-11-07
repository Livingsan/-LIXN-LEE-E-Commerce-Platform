<?php
@include 'config.php';
session_start();

if (!isset($_SESSION['checkout_data']) || !isset($_SESSION['user_email'])) {
    exit();
}

$user_email = $_SESSION['user_email'];
$checkout = $_SESSION['checkout_data'];

if (isset($_POST['pay_now'])) {
    $raw_card = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $_SESSION['card_info'] = [
        'card_holder' => htmlspecialchars($_POST['card_holder'] ?? ''),
        'card_last4' => substr($raw_card, -4)
    ];

    $name = $checkout['name'] ?? '';
    $number = $checkout['number'] ?? '';
    $email = $checkout['email'] ?? '';
    $method = 'Card';
    $street_number = $checkout['street_number'] ?? '';
    $street = $checkout['street'] ?? '';
    $city = $checkout['city'] ?? '';
    $province = $checkout['province'] ?? '';
    $country = $checkout['country'] ?? '';
    $pin_code = $checkout['pin_code'] ?? '';
    $placed_on = date('Y-m-d H:i:s');

    $coupon_code = trim($checkout['coupon_code'] ?? '');
    $discount = floatval($checkout['discount_value'] ?? 0);
    $delivery_charge = floatval($checkout['delivery_charge'] ?? 0);

    $cart_products = [];
    $cart_total = 0;
    $tax_total = 0;

    $cart_query = execute_query($conn, "SELECT c.*, p.category FROM cart c JOIN products p ON c.name = p.name WHERE c.email = ?", "s", [$user_email]);
    while ($cart_item = mysqli_fetch_assoc($cart_query)) {
        $line_total = $cart_item['price'] * $cart_item['quantity'];
        $cart_products[] = $cart_item['name'] . ' (x' . $cart_item['quantity'] . ')';
        $cart_total += $line_total;

        $tax_rate = get_tax_rate_for_product_category($cart_item['category'] ?? '');
        $tax_total += $line_total * $tax_rate;
    }

    $grand_total = ($cart_total - $discount) + $delivery_charge + $tax_total;
    $total_products = implode(', ', $cart_products);

    $delivery_days = (strtolower(trim($province)) === 'western') ? 5 : 10;
    $delivery_date = date('Y-m-d', strtotime("+$delivery_days days"));

    $insert_order = execute_query($conn, "INSERT INTO `order` 
        (name, number, email, order_date, delivery_date, method, street_number, street, city, province, country, pin_code, total_products, total_price, tax_amount)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
        "sssssssssssssdd", 
        [$name, $number, $email, $placed_on, $delivery_date, $method, $street_number, $street, $city, $province, $country, $pin_code, $total_products, $grand_total, $tax_total]);

    if (!$insert_order) {
        die('❌ Order insert failed.');
    }

    $stock_items = execute_query($conn, "SELECT name, quantity FROM cart WHERE email = ?", "s", [$user_email]);
    while ($item = mysqli_fetch_assoc($stock_items)) {
        $product_name = $item['name'];
        $qty = intval($item['quantity']);
        execute_query($conn, "UPDATE products SET stock = stock - ? WHERE name = ?", "is", [$qty, $product_name]);
    }

    $points_to_add = floor($grand_total / 10);
    $stmt = $conn->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("is", $points_to_add, $email);
        $stmt->execute();
        $stmt->close();
    }

    if (!empty($coupon_code) && $discount > 0) {
        $check_coupon = execute_query($conn, "SELECT * FROM coupon_usage WHERE user_email = ? AND coupon_code = ?", "ss", [$user_email, $coupon_code]);
        if (mysqli_num_rows($check_coupon) == 0) {
            execute_query($conn, "INSERT INTO coupon_usage (user_email, coupon_code) VALUES (?, ?)", "ss", [$user_email, $coupon_code]);
        }
    }

    $new_order_id = mysqli_insert_id($conn);
    execute_query($conn, "DELETE FROM cart WHERE email = ?", "s", [$user_email]);

    unset($_SESSION['checkout_data']);

    header('Location: payment_successful.php?order_id=' . $new_order_id);
    exit();
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Card Payment</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f0f0f0;
        }
        .card-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 400px;
            margin: 60px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }
        .card-form input {
            width: 100%;
            margin-top: 10px;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        .card-form button {
            margin-top: 20px;
            padding: 14px;
            width: 100%;
            background: #83666e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s ease;
        }
        .card-form button:hover {
            background: #6d535a;
        }
    </style>
</head>
<body>

<div class="card-form">
    <h2>Enter Card Details</h2>
    <form method="post">
        <input type="text" name="card_number" placeholder="Card Number" required>
        <input type="text" name="card_holder" placeholder="Cardholder Name" required>
        <input type="text" name="expiry" placeholder="MM/YY" required>
        <input type="text" name="cvv" placeholder="CVV" required>
        <button type="submit" name="pay_now">Pay Now</button>
    </form>
</div>

</body>
</html>
