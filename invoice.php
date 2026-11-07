<?php
@include 'config.php';
session_start();

$user_email = $_SESSION['user_email'] ?? '';

if (!$user_email) {
    header('location:userlogin.php');
    exit();
}

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header('location:userorder.php');
    exit();
}

$order_id = intval($_GET['order_id']);
$order_res = execute_query($conn, "SELECT * FROM `order` WHERE id = ? AND email = ?", "is", [$order_id, $user_email]);
$order = mysqli_fetch_assoc($order_res);

if (!$order) {
    header('location:userorder.php');
    exit();
}

$invoice_no = "INV-" . date("Y") . "-" . str_pad($order_id, 5, "0", STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order_id; ?> - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .invoice-container { padding: 60px; max-width: 900px; margin: 40px auto; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #f0f0f0; padding-bottom: 30px; margin-bottom: 40px; }
        .invoice-logo h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: var(--primary); margin: 0; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { font-family: 'Playfair Display', serif; color: var(--primary); margin: 0; }
        .billing-info { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .billing-info h4 { text-transform: uppercase; color: #888; font-size: 0.8rem; letter-spacing: 2px; margin-bottom: 10px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .invoice-table th { text-align: left; padding: 15px; border-bottom: 2px solid #f0f0f0; color: #888; text-transform: uppercase; font-size: 0.8rem; }
        .invoice-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; }
        .invoice-total { margin-left: auto; width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .total-row.grand { border-top: 2px solid var(--primary); margin-top: 10px; font-weight: 700; color: var(--primary); font-size: 1.2rem; }
        @media print { .no-print { display: none; } body { background: white; } .invoice-container { box-shadow: none; margin: 0; padding: 20px; } }
    </style>
</head>
<body>

    <div class="no-print">
        <?php include 'header.php'; ?>
        <div style="height: 120px;"></div>
    </div>

    <div class="invoice-container">
        <div class="invoice-header">
            <div class="invoice-logo">
                <h1>LIXN LEE</h1>
                <p>Premium Lankan Essentials</p>
            </div>
            <div class="invoice-meta">
                <h2>INVOICE</h2>
                <p>#<?php echo $invoice_no; ?></p>
                <p>Date: <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
            </div>
        </div>

        <div class="billing-info">
            <div>
                <h4>Billed To:</h4>
                <p><strong><?php echo htmlspecialchars($order['name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order['email']); ?></p>
                <p><?php echo htmlspecialchars($order['number']); ?></p>
                <p><?php echo htmlspecialchars($order['street']); ?></p>
            </div>
            <div>
                <h4>Payment Method:</h4>
                <p><?php echo htmlspecialchars($order['method']); ?></p>
                <p>Status: <span style="color: #28a745; font-weight: 600;">Paid</span></p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($order['total_products']); ?></td>
                    <td style="text-align: right;">RS <?php echo number_format($order['total_price'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="invoice-total">
            <div class="total-row">
                <span>Subtotal</span>
                <span>RS <?php echo number_format($order['total_price'] - $order['tax_amount'], 2); ?></span>
            </div>
            <div class="total-row">
                <span>Tax</span>
                <span>RS <?php echo number_format($order['tax_amount'], 2); ?></span>
            </div>
            <div class="total-row grand">
                <span>TOTAL</span>
                <span>RS <?php echo number_format($order['total_price'], 2); ?></span>
            </div>
        </div>

        <div style="margin-top: 60px; text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #888; font-size: 0.9rem;">
            <p>Thank you for choosing LIXN LEE.</p>
            <p>If you have any questions, contact us at hello@lixnlee.com</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-bottom: 50px;">
        <button onclick="window.print()" class="btn-add" style="cursor: pointer; padding: 15px 40px;">Print Invoice <i class="fas fa-print"></i></button>
        <a href="userorder.php" style="margin-left: 20px; color: var(--primary); text-decoration: none; font-weight: 600;">Return to Orders</a>
    </div>

</body>
</html>