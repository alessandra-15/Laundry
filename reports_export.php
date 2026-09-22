<?php
/**
 * reports_export.php
 * WashFlow — Export Reports to Excel-compatible format
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'functions.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$type = $_GET['type'] ?? 'summary';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="washflow_' . $type . '_' . date('Ymd') . '.xls"');

// Your export logic here...
?>
<table border="1">
    <tr><th colspan="4">WashFlow Report — <?= $start_date ?> to <?= $end_date ?></th></tr>
    <!-- Populate from database -->
</table>