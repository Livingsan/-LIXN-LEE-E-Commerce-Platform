<?php
/**
 * LIXNLEE eCommerce - Safe Configuration Template
 *
 * QUICK SETUP:
 * 1. Copy this file to create your local config.php:
 *      cp config.example.php config.php
 *    (or copy .env.example to .env)
 *
 * 2. LOCAL DEVELOPMENT (XAMPP):
 *    - Defaults match standard local XAMPP settings:
 *        Host:     127.0.0.1
 *        User:     root
 *        Password: "" (empty)
 *        Database: shop_db
 *        Port:     3306
 *    - Works immediately out of the box with default XAMPP MySQL.
 *
 * 3. PRODUCTION DEPLOYMENT:
 *    - NEVER use MySQL 'root' with a blank password in production!
 *    - Create a dedicated database user with restricted privileges:
 *        CREATE USER 'lixnlee_user'@'localhost' IDENTIFIED BY 'YourStrongSecurePasswordHere!';
 *        GRANT SELECT, INSERT, UPDATE, DELETE ON shop_db.* TO 'lixnlee_user'@'localhost';
 *        FLUSH PRIVILEGES;
 *    - Supply the credentials via environment variables (recommended) or a local .env file:
 *        DB_HOST=127.0.0.1
 *        DB_USER=lixnlee_user
 *        DB_PASSWORD=YourStrongSecurePasswordHere!
 *        DB_NAME=shop_db
 *        DB_PORT=3306
 *
 * 4. SECURITY NOTICE:
 *    - Never commit 'config.php' or '.env' to GitHub or any public version control.
 *    - Ensure .gitignore contains config.php and .env.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Always load core helper and security functions first
require_once __DIR__ . '/functions.php';

// 2. Load environment variables from .env file if present
$envFile = __DIR__ . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                $len = strlen($val);
                if ($len >= 2 && (($val[0] === '"' && $val[$len - 1] === '"') || ($val[0] === "'" && $val[$len - 1] === "'"))) {
                    $val = substr($val, 1, -1);
                }
                if ($key !== '' && getenv($key) === false) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
}

// 3. Resolve database credentials from environment with XAMPP defaults fallback
$dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? '127.0.0.1'));
$dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? 'root'));

$dbPassword = getenv('DB_PASSWORD');
if ($dbPassword === false) {
    $dbPassword = getenv('DB_PASS');
}
if ($dbPassword === false) {
    $dbPassword = $_ENV['DB_PASSWORD'] ?? ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASSWORD'] ?? ($_SERVER['DB_PASS'] ?? '')));
}

$dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? 'shop_db'));
$dbPort = (int) (getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? 3306)));

// 4. Establish database connection with graceful error handling
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);

// On Windows local development, fallback from 127.0.0.1 to localhost if needed
if (!$conn && $dbHost === '127.0.0.1') {
    $conn = @mysqli_connect('localhost', $dbUser, $dbPassword, $dbName, $dbPort);
}

if (!$conn) {
    $connErr = mysqli_connect_error();
    error_log("Database connection failed: " . $connErr);
    http_response_code(500);
    die("Database connection failed. Please ensure MySQL is running in XAMPP and configuration settings are correct.");
}

mysqli_set_charset($conn, 'utf8mb4');
