<?php
@include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $insert = execute_query($conn, "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)", "ssss", [$name, $email, $subject, $message]);

        if ($insert) {
            echo "<script>alert('Message sent successfully!');window.location.href='index.php#contact-section';</script>";
            exit();
        }
    }
    echo "<script>alert('Failed to send message. Please enter valid details!');window.history.back();</script>";
    exit();
}
?>
