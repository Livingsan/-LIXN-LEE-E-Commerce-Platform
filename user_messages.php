<?php
@include 'config.php';
session_start();

$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if (!$user_email) {
    header('Location: userlogin.php'); 
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM contact_messages WHERE email = '$user_email' ORDER BY created_at DESC");

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Messages - LIXN LEE</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet"/>
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
                    <div class="messages-container">
                        <h1>My Support Messages</h1>
            <?php if (mysqli_num_rows($query) > 0): ?>
                <div class="messages-table-wrapper">
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Admin Reply</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                        <tr>
                            <td data-label="Subject"><?= htmlspecialchars($row['subject']) ?></td>
                            <td data-label="Message"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                            <td data-label="Admin Reply" class="<?= $row['reply'] ? 'reply-status' : 'pending-reply' ?>">
                                <?= $row['reply'] ? nl2br(htmlspecialchars($row['reply'])) : 'Pending reply' ?>
                            </td>
                            <td data-label="Date"><?= $row['created_at'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="no-messages">
                    <i class="fas fa-inbox"></i>
                    <p>You haven't sent any support messages yet.</p>
                </div>
            <?php endif; ?>
        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

</body>
</html>