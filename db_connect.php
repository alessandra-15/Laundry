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

// Environment-based configuration
$servername = getenv('DB_HOST') ?: "localhost";
$username   = getenv('DB_USER') ?: "root";
$password   = getenv('DB_PASS') ?: "";
$database   = getenv('DB_NAME') ?: "laundry_db";

// Error reporting mode (development vs production)
$isDev = getenv('APP_ENV') === 'development' || !getenv('APP_ENV');

if ($isDev) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $database);
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    if ($isDev) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("We're experiencing technical difficulties. Please try again later.");
    }
}

// Prevent direct file access
if (basename($_SERVER['PHP_SELF']) === 'db_connect.php') {
    http_response_code(403);
    die('Direct access not permitted');
}
?>