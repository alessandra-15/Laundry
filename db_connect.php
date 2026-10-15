<?php
/**
 * Database Connection - Secured Version
 * 
 * @file db_connect.php
 */

// Prevent direct access
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

// Detect environment: Local vs InfinityFree Production
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || (php_sapi_name() === 'cli' && !getenv('DB_HOST')));

if ($isLocal) {
    // Local development (XAMPP / WAMP / MAMP)
    $servername = getenv('DB_HOST') ?: "localhost";
    $username   = getenv('DB_USER') ?: "root";
    $password   = getenv('DB_PASS') ?: "";
    $database   = getenv('DB_NAME') ?: "laundry_db";
} else {
    // Live InfinityFree credentials
    $servername = getenv('DB_HOST') ?: "sql207.infinityfree.com";
    $username   = getenv('DB_USER') ?: "if0_43180724";
    $password   = getenv('DB_PASS') ?: "0Y3Uae8WUU";
    $database   = getenv('DB_NAME') ?: "if0_43180724_laundry";
}

// Suppress uncaught fatal exceptions on connect
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    if ($isLocal) {
        die("Database connection failed: " . $conn->connect_error);
    } else {
        die("Database connection failed on live server: " . $conn->connect_error . ". Please update your database credentials in db_connect.php.");
    }
}

$conn->set_charset("utf8mb4");
@$conn->query("SET time_zone = '+08:00'");

// Prevent direct file access
if (basename($_SERVER['PHP_SELF'] ?? '') === 'db_connect.php') {
    http_response_code(403);
    die('Direct access not permitted');
}
?>