<?php
@include 'config.php';
session_start();

if (isset($_GET['order_id'])) {
    header("Location: payment_successful.php?order_id=" . intval($_GET['order_id']));
    exit();
}

header("Location: products.php");
exit();
?>
