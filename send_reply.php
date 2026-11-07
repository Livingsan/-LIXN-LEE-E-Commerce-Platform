<?php
@include 'config.php';
session_start();

if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');

    if ($id > 0 && !empty($reply)) {
        $update = execute_query($conn, "UPDATE contact_messages SET reply = ? WHERE id = ?", "si", [$reply, $id]);
        if ($update) {
            echo "<script>alert('Reply sent successfully');window.location.href='admin_contact.php';</script>";
            exit();
        }
    }
    echo "<script>alert('Failed to send reply');window.history.back();</script>";
    exit();
}
?>
