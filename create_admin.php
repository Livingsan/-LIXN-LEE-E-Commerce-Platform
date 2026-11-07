<?php
/**
 * CLI-only Admin Account Provisioning Script
 * Usage: php create_admin.php [username] [email] [password]
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access denied: This script can only be run from the command line.\n");
}

@include_once 'config.php';

if (!isset($conn) || !$conn) {
    die("Database connection failed. Please ensure config.php exists and credentials are valid.\n");
}

$username = $argv[1] ?? 'admin';
$email = $argv[2] ?? 'admin@shop.com';
$password_plain = $argv[3] ?? 'admin123';
$name = 'System Administrator';
$role = 'admin';

$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

$created_in_admins = false;
$created_in_users = false;

// 1. Create or update in admins table if table exists
$check_admins = $conn->query("SHOW TABLES LIKE 'admins'");
if ($check_admins && $check_admins->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $res->num_rows > 0) {
        $update = $conn->prepare("UPDATE admins SET name = ?, email = ?, password = ? WHERE username = ?");
        $update->bind_param("ssss", $name, $email, $password_hashed, $username);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO admins (name, email, username, password) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $name, $email, $username, $password_hashed);
        $insert->execute();
        $insert->close();
    }
    $stmt->close();
    $created_in_admins = true;
}

// 2. Create or update in users table with role 'admin'
$check_users = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_users && $check_users->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $res->num_rows > 0) {
        $update = $conn->prepare("UPDATE users SET name = ?, password = ?, role = ? WHERE email = ?");
        $update->bind_param("ssss", $name, $password_hashed, $role, $email);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $name, $email, $password_hashed, $role);
        $insert->execute();
        $insert->close();
    }
    $stmt->close();
    $created_in_users = true;
}

echo "=== Admin Account Setup Complete ===\n";
echo "Username: $username\n";
echo "Email:    $email\n";
echo "Status:   Admin provisioned successfully (Admins table: " . ($created_in_admins ? "Yes" : "No") . ", Users table: " . ($created_in_users ? "Yes" : "No") . ")\n";
?>
