<?php
@include 'config.php';
session_start();

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Invalid coupon code.',
    'discount_percentage' => 0,
    'coupon_code' => ''
];

if (!isset($_SESSION['user_email'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['coupon_code'])) {
    echo json_encode($response);
    exit();
}

$user_email = $_SESSION['user_email'];
$coupon_code = mysqli_real_escape_string($conn, trim($_POST['coupon_code']));

$current_date = date('Y-m-d');

$query = "SELECT * FROM `coupons` 
          WHERE `code` = '$coupon_code' 
          AND `is_active` = 1 
          AND `valid_from` <= '$current_date' 
          AND `expiry_date` >= '$current_date' 
          AND (`target_email` IS NULL OR `target_email` = '$user_email')";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $coupon = mysqli_fetch_assoc($result);

    // Check if the coupon has already been used by this user
    $check_usage_query = "SELECT * FROM `coupon_usage` WHERE `user_email` = '$user_email' AND `coupon_code` = '$coupon_code'";
    $usage_result = mysqli_query($conn, $check_usage_query);

    if (mysqli_num_rows($usage_result) > 0) {
        $response['message'] = 'This coupon has already been used.';
    } else {
        $response['success'] = true;
        $response['message'] = 'Coupon applied successfully!';
        $response['discount_percentage'] = floatval($coupon['discount']);
        $response['coupon_code'] = $coupon['code'];
    }
}

echo json_encode($response);
?>