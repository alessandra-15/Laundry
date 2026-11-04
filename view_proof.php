<?php
/**
 * view_proof.php
 * Serves an uploaded payment proof to its owner, or to staff and admins.
 * Links pass ?id=<payment_id>, plus &_role=staff|admin from the staff and admin pages.
 *
 * @file view_proof.php
 */
require_once __DIR__ . '/session_config.php';
include 'db_connect.php';

function proof_error(int $code, string $message): void {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Payment Proof</title></head>'
       . '<body style="font-family:sans-serif;text-align:center;padding:3rem;color:#063452;">'
       . '<h2>Payment proof unavailable</h2><p>' . htmlspecialchars($message) . '</p></body></html>';
    exit();
}

$payment_id = (int)($_GET['id'] ?? 0);
if ($payment_id <= 0) {
    proof_error(400, 'No payment was selected.');
}

$is_back_office = !empty($_SESSION['admin_id']) || !empty($_SESSION['staff_id']);
$customer_id    = (int)($_SESSION['customer_id'] ?? 0);
if (!$is_back_office && $customer_id <= 0) {
    proof_error(401, 'Please log in to view this file.');
}

$stmt = $conn->prepare("
    SELECT p.payment_proof, b.customer_id
    FROM payments_online p
    LEFT JOIN booking_online b ON b.id = p.booking_id
    WHERE p.payment_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment || (!$is_back_office && (int)$payment['customer_id'] !== $customer_id)) {
    proof_error(404, 'This payment proof was not found.');
}
if (empty($payment['payment_proof'])) {
    proof_error(404, 'No proof was uploaded for this payment.');
}

/* Older rows store a bare filename, newer ones the uploads/ path. */
$filename = basename(str_replace('\\', '/', $payment['payment_proof']));
$filepath = __DIR__ . '/uploads/payment_proofs/' . $filename;
if (!is_file($filepath)) {
    proof_error(404, 'The uploaded file is no longer on the server.');
}

$allowed_types = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf',
];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!isset($allowed_types[$ext])) {
    proof_error(415, 'This file type cannot be displayed.');
}

header('Content-Type: ' . $allowed_types[$ext]);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('X-Content-Type-Options: nosniff');
readfile($filepath);
exit();
