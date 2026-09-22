<?php
define('APP_STARTED', true);

// Detect role from URL ?role=admin|staff|customer
$role = $_GET['role'] ?? 'customer';
$session_names = [
    'admin'    => 'LAUNDRY_ADMIN',
    'staff'    => 'LAUNDRY_STAFF',
    'customer' => 'LAUNDRY_CUSTOMER',
];

session_name($session_names[$role]);
session_start();

echo "<h2>TEST SESSION — Role: {$role}</h2>";
echo "<table border='1' cellpadding='8' style='font-family:monospace;font-size:14px;'>";
echo "<tr><td><b>Current PHP file</b></td><td>" . basename($_SERVER['PHP_SELF']) . "</td></tr>";
echo "<tr><td><b>Session Name</b></td><td>" . session_name() . "</td></tr>";
echo "<tr><td><b>Session ID</b></td><td>" . session_id() . "</td></tr>";
echo "<tr><td><b>user_type</b></td><td>" . ($_SESSION['user_type'] ?? '<i>NONE</i>') . "</td></tr>";
echo "<tr><td><b>user_id</b></td><td>" . ($_SESSION['user_id'] ?? '<i>NONE</i>') . "</td></tr>";
echo "<tr><td><b>user_name</b></td><td>" . ($_SESSION['user_name'] ?? '<i>NONE</i>') . "</td></tr>";
echo "<tr><td><b>admin_id</b></td><td>" . ($_SESSION['admin_id'] ?? '<i>NONE</i>') . "</td></tr>";
echo "<tr><td><b>staff_id</b></td><td>" . ($_SESSION['staff_id'] ?? '<i>NONE</i>') . "</td></tr>";
echo "<tr><td><b>customer_id</b></td><td>" . ($_SESSION['customer_id'] ?? '<i>NONE</i>') . "</td></tr>";
echo "</table>";

echo "<h3>All Session Keys:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>All Cookies Received by PHP:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<hr>";
echo "<h3>Test Links (buksan bawat isa sa magkaibang tab):</h3>";
echo "<a href='?role=admin' target='_blank'>Test as ADMIN</a><br>";
echo "<a href='?role=staff' target='_blank'>Test as STAFF</a><br>";
echo "<a href='?role=customer' target='_blank'>Test as CUSTOMER</a><br>";