<?php
/**
 * General Helper Functions
 * 
 * @file functions.php
 */

// Prevent direct access
if (!defined('APP_STARTED')) {
    die('Direct access not permitted');
}

/**
 * Sanitize output for HTML (anti-XSS)
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate Philippine phone number
 * Accepts: 09XXXXXXXXX, +639XXXXXXXXX, 639XXXXXXXXX
 */
function validate_phone($phone) {
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    return preg_match('/^(\+?63|0)9\d{9}$/', $phone);
}

/**
 * Format phone number
 */
function format_phone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (preg_match('/^(\+63|63)(\d{10})$/', $phone, $m)) {
        return '+63 ' . substr($m[2], 0, 3) . ' ' . substr($m[2], 3, 3) . ' ' . substr($m[2], 6);
    }
    if (preg_match('/^0(\d{10})$/', $phone, $m)) {
        return '+63 ' . substr($m[1], 0, 3) . ' ' . substr($m[1], 3, 3) . ' ' . substr($m[1], 6);
    }
    return $phone;
}

/**
 * Validate password strength
 * Returns array of errors (empty kung valid)
 */
function validate_password_strength($password) {
    $errors = [];
    if (strlen($password) < 8) $errors[] = 'at least 8 characters';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'one uppercase letter';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'one lowercase letter';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'one number';
    return $errors;
}

/**
 * Generate secure random filename
 */
function generate_filename($extension) {
    return bin2hex(random_bytes(16)) . '.' . strtolower($extension);
}

/**
 * Redirect with flash message
 */
function redirect_with($url, $type, $message) {
    $_SESSION['flash_' . $type] = $message;
    header('Location: ' . $url);
    exit();
}

/**
 * Get and clear flash message
 */
function get_flash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}
?>