<?php
header('Content-Type: application/json');
require_once __DIR__ . '/session_config.php';

if (empty($_SESSION['admin_id']) || empty($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
include 'db_connect.php';

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id > 0) {
    $result = $conn->query("SELECT * FROM inventory_items WHERE item_id = $item_id");
    if ($result && $result->num_rows > 0) {
        echo json_encode(['success' => true, 'item' => $result->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
}
$conn->close();
?>