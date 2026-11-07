<?php
/**
 * Security and Utility Functions
 */

// CSRF Protection: Generate a token if it doesn't exist
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Protection: Validate the token
function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// CSRF Protection: Get the hidden input field
function csrf_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Database Helper: Execute a prepared statement
 * @param mysqli $conn The database connection
 * @param string $query The SQL query with ? placeholders
 * @param string $types String containing type indicators (e.g., "ssi")
 * @param array $params Array of parameters to bind
 * @return mysqli_result|bool
 */
function execute_query($conn, $query, $types = "", $params = []) {
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        die('Prepare failed: ' . mysqli_error($conn));
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        // If it's not a SELECT query, return true
        if ($result === false) {
            return true;
        }
        return $result;
    } else {
        die('Execute failed: ' . mysqli_stmt_error($stmt));
    }
}

/**
 * Output Sanitization
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function is_admin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

function normalize_product_category($category) {
    $normalized = strtolower(trim((string) $category));
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    if ($normalized === 'homeappliance') {
        return 'home appliance';
    }

    if ($normalized === 'others') {
        return 'other';
    }

    return $normalized;
}

function get_tax_rate_for_product_category($category) {
    $tax_rates = [
        'kids' => 0.05,
        'men' => 0.05,
        'women' => 0.05,
        'tech' => 0.12,
        'home appliance' => 0.12,
        'other' => 0.08
    ];

    $normalized = normalize_product_category($category);
    return $tax_rates[$normalized] ?? 0.00;
}

/**
 * Validate image uploads (extension and MIME check)
 */
function is_valid_image_upload($file_tmp, $file_name) {
    if (empty($file_tmp) || empty($file_name)) {
        return false;
    }
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed_exts, true)) {
        return false;
    }
    $info = @getimagesize($file_tmp);
    if ($info === false) {
        return false;
    }
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    return in_array($info['mime'], $allowed_mimes, true);
}

/**
 * Generate safe sanitized filename for uploads
 */
function sanitize_image_filename($file_name) {
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file_name, PATHINFO_FILENAME));
    return bin2hex(random_bytes(8)) . '_' . substr($base, 0, 30) . '.' . $ext;
}
?>
