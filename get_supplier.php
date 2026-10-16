<?php
header('Content-Type: application/json');
require_once __DIR__ . '/session_config.php';

if (empty($_SESSION['admin_id']) || empty($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
include 'db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $result = $conn->query("SELECT * FROM suppliers WHERE supplier_id = $id");
    if ($result && $result->num_rows > 0) {
        echo json_encode(['success' => true, 'supplier' => $result->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
$conn->close();
?>