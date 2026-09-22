<?php
/**
 * inventory_transactions.php
 * Returns JSON list of transactions for a given item_id
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

$item_id = (int)($_GET['item_id'] ?? 0);
if ($item_id <= 0) {
    echo json_encode([]);
    exit();
}

$rows = [];
try {
    $stmt = $conn->prepare("
        SELECT transaction_id, transaction_type, quantity, previous_stock, new_stock,
               reference_type, notes, transaction_date
        FROM inventory_transactions
        WHERE item_id = ?
        ORDER BY transaction_date DESC
        LIMIT 10
    ");
    $stmt->bind_param('i', $item_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {
    $rows = [];
}

echo json_encode($rows);