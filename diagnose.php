<?php
/**
 * diagnose.php — WashFlow Database & Environment Diagnostic
 * Upload this to your InfinityFree server and visit it in a browser.
 * DELETE THIS FILE after you're done diagnosing!
 */
define('APP_STARTED', true);

echo "<html><head><title>WashFlow Diagnostics</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#e0e0e0}";
echo "h2{color:#00d4ff}h3{color:#ffd93d}.ok{color:#4caf50}.err{color:#ff5555}.warn{color:#ffa500}";
echo "table{border-collapse:collapse;margin:10px 0}td,th{border:1px solid #333;padding:6px 12px;text-align:left}";
echo "th{background:#2a2a4a}</style></head><body>";

// 1. PHP Info
echo "<h2>🔧 Environment</h2>";
echo "<p>PHP Version: <b>" . phpversion() . "</b></p>";
echo "<p>Server: <b>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "</b></p>";
echo "<p>Host: <b>" . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</b></p>";

// 2. Database Connection
echo "<h2>🗄️ Database Connection</h2>";
require_once 'db_connect.php';

if ($conn->connect_error) {
    echo "<p class='err'>❌ Connection FAILED: " . htmlspecialchars($conn->connect_error) . "</p>";
    echo "</body></html>";
    exit;
}

echo "<p class='ok'>✅ Connected to: <b>" . htmlspecialchars($servername) . "</b></p>";
echo "<p class='ok'>✅ Database: <b>" . htmlspecialchars($database) . "</b></p>";
echo "<p>Username: <b>" . htmlspecialchars($username) . "</b></p>";

// 3. Check required tables
echo "<h2>📋 Required Tables Check</h2>";

$required_tables = [
    'customer_info',
    'admin',
    'staff',
    'booking_online',
    'payments_online',
    'user_activity',
    'notifications_user',
    'services',
    'add_ons',
    'complaints',
    'feedback',
];

echo "<table><tr><th>Table</th><th>Status</th><th>Row Count</th></tr>";

foreach ($required_tables as $table) {
    $result = @$conn->query("SELECT COUNT(*) AS c FROM `{$table}`");
    if ($result) {
        $count = $result->fetch_assoc()['c'];
        echo "<tr><td>{$table}</td><td class='ok'>✅ EXISTS</td><td>{$count}</td></tr>";
    } else {
        echo "<tr><td>{$table}</td><td class='err'>❌ MISSING</td><td>-</td></tr>";
    }
}
echo "</table>";

// 4. List ALL tables in the database
echo "<h2>📂 All Tables in Database</h2>";
$tables = $conn->query("SHOW TABLES");
if ($tables && $tables->num_rows > 0) {
    echo "<ul>";
    while ($row = $tables->fetch_array()) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='err'>❌ No tables found in the database! You need to import your SQL schema.</p>";
}

// 4b. Data health — customer IDs and missing primary keys
echo "<h2>🩺 Data Health</h2>";
$r = @$conn->query("SELECT COUNT(*) AS total, SUM(Customer_ID = 0) AS zeros, COUNT(DISTINCT Customer_ID) AS ids FROM customer_info");
if ($r && ($row = $r->fetch_assoc())) {
    $cls = ((int)$row['zeros'] > 0 || $row['total'] != $row['ids']) ? 'err' : 'ok';
    echo "<p class='{$cls}'>customer_info: {$row['total']} rows, {$row['ids']} distinct IDs, " . (int)$row['zeros'] . " rows with Customer_ID = 0</p>";
}
$r = @$conn->query("SELECT t.TABLE_NAME FROM information_schema.TABLES t
    LEFT JOIN information_schema.TABLE_CONSTRAINTS c
      ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME AND c.CONSTRAINT_TYPE = 'PRIMARY KEY'
    WHERE t.TABLE_SCHEMA = DATABASE() AND t.TABLE_TYPE = 'BASE TABLE' AND c.CONSTRAINT_NAME IS NULL");
if ($r) {
    $nopk = array_column($r->fetch_all(MYSQLI_NUM), 0);
    echo $nopk
        ? "<p class='err'>❌ Tables without PRIMARY KEY (run migrations/004_fix_primary_keys.sql): " . htmlspecialchars(implode(', ', $nopk)) . "</p>"
        : "<p class='ok'>✅ All tables have a PRIMARY KEY</p>";
}

// 4c. Run the queries the customer pages use and show any DB error
echo "<h2>🧪 Page Queries</h2>";
$test_queries = [
    'notifications: count'  => "SELECT COUNT(*) FROM notifications_user WHERE user_id = 0 AND type = 'system'",
    'notifications: list'   => "SELECT notif_id, title, message, type, icon, link, booking_id, is_read, created_at FROM notifications_user WHERE user_id = 0 ORDER BY created_at DESC LIMIT 1",
    'notifications: update' => "UPDATE notifications_user SET is_read=1, read_at=NOW(), status='read' WHERE notif_id = -1",
    'dashboard: payments'   => "SELECT COALESCE(SUM(p.amount),0) FROM payments_online p JOIN booking_online b ON p.booking_id = b.id WHERE b.customer_id = 0 AND p.payment_status = 'Paid'",
    'complaints'            => "SELECT * FROM complaints WHERE customer_id = 0 LIMIT 1",
    'feedback'              => "SELECT * FROM feedback WHERE user_id = 0 LIMIT 1",
];
echo "<table><tr><th>Query</th><th>Result</th></tr>";
foreach ($test_queries as $label => $sql) {
    $ok = @$conn->query($sql);
    echo "<tr><td>" . htmlspecialchars($label) . "</td>" . ($ok
        ? "<td class='ok'>✅ OK</td>"
        : "<td class='err'>❌ " . htmlspecialchars($conn->error) . "</td>") . "</tr>";
}
echo "</table>";

// 5. Check session_config.php loads
echo "<h2>🔐 Session Config</h2>";
try {
    // Already loaded via db_connect, just check if session started
    echo "<p class='ok'>✅ Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "</p>";
    echo "<p>Session name: <b>" . session_name() . "</b></p>";
} catch (Throwable $e) {
    echo "<p class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 6. Check file dependencies
echo "<h2>📁 Required Files Check</h2>";
$required_files = [
    'session_config.php',
    'db_connect.php',
    'logger.php',
    'functions.php',
    'notifications_helper.php',
    'csrf_helper.php',
    'rate_limiter.php',
    'constants.php',
    'userdashboard.php',
    'login.php',
    'register.php',
];

echo "<table><tr><th>File</th><th>Status</th></tr>";
foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<tr><td>{$file}</td><td class='ok'>✅ Found</td></tr>";
    } else {
        echo "<tr><td>{$file}</td><td class='err'>❌ Missing</td></tr>";
    }
}
echo "</table>";

// 7. Check logs directory
echo "<h2>📝 Logs Directory</h2>";
$logDir = __DIR__ . '/logs';
if (is_dir($logDir)) {
    echo "<p class='ok'>✅ logs/ directory exists</p>";
    echo "<p>Writable: " . (is_writable($logDir) ? "<span class='ok'>Yes</span>" : "<span class='err'>No</span>") . "</p>";
} else {
    echo "<p class='warn'>⚠️ logs/ directory missing — creating...</p>";
    if (@mkdir($logDir, 0755, true)) {
        echo "<p class='ok'>✅ Created successfully</p>";
    } else {
        echo "<p class='err'>❌ Could not create logs directory</p>";
    }
}

echo "<hr><p class='warn'>⚠️ DELETE this file (diagnose.php) after you're done!</p>";
echo "</body></html>";
?>
