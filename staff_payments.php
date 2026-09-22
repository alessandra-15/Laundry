<?php
/**
 * staff_payments.php
 * WashFlow — Staff Payment Verification
 * Compatible sa laundry_db schema
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* AUTH — STAFF ONLY */
if (!is_staff_logged_in()) {
    header('Location: staff_login.php');
    exit();
}

$user          = current_user();
$staff_id      = (int)$user['id'];
$staff_name    = $user['name'];
$staff_role    = $user['role'];
$staff_initial = strtoupper(substr($staff_name, 0, 1));

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$role_meta = [
    'washer'     => ['label' => 'Washer',     'icon' => 'fa-soap'],
    'dryer'      => ['label' => 'Dryer',      'icon' => 'fa-wind'],
    'ironing'    => ['label' => 'Ironing',    'icon' => 'fa-tshirt'],
    'packer'     => ['label' => 'Packer',     'icon' => 'fa-box'],
    'cashier'    => ['label' => 'Cashier',    'icon' => 'fa-cash-register'],
    'supervisor' => ['label' => 'Supervisor', 'icon' => 'fa-user-shield'],
];
$my_role = $role_meta[$staff_role] ?? $role_meta['washer'];

/* ──────────────────────────────────────────
   POST — VERIFY / REFUND PAYMENT
   ────────────────────────────────────────── */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $payment_id = (int)($_POST['payment_id'] ?? 0);

    try {
        if ($payment_id <= 0) {
            throw new Exception('Invalid payment.');
        }

        /* Kunin muna ang payment info */
        $stmt = $conn->prepare("
            SELECT payment_id, booking_id, customer_id, amount, payment_status
            FROM payments
            WHERE payment_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();
        $pay = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$pay) {
            throw new Exception('Payment not found.');
        }

        if ($action === 'verify') {
            if ($pay['payment_status'] === 'Paid') {
                throw new Exception('Payment is already marked as Paid.');
            }

            /* Ang trigger `after_payment_update` at `sync_booking_status_after_payment`
               ay automatic nang:
               - Mag-a-update ng booking status to 'Confirmed'
               - Mag-a-add ng financial record
               - Mag-a-update ng transaction table (kung meron) */
            $stmt = $conn->prepare("
                UPDATE payments
                SET payment_status = 'Paid',
                    remarks = CONCAT(COALESCE(remarks,''), ' | Verified by ', ?, ' on ', NOW())
                WHERE payment_id = ? AND payment_status <> 'Paid'
                LIMIT 1
            ");
            $stmt->bind_param('si', $staff_name, $payment_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected <= 0) {
                throw new Exception('Hindi na-update ang payment. Baka na-verify na ito.');
            }

            if (class_exists('Logger')) {
                Logger::info('Payment verified', [
                    'staff_id'   => $staff_id,
                    'payment_id' => $payment_id,
                    'booking_id' => (int)$pay['booking_id'],
                    'amount'     => (float)$pay['amount'],
                ]);
            }

            $flash = [
                'type'    => 'success',
                'message' => "Payment #{$payment_id} verified! ₱" . number_format((float)$pay['amount'], 2) . " — Booking #{$pay['booking_id']} is now Confirmed."
            ];

        } elseif ($action === 'refund') {
            if ($pay['payment_status'] !== 'Paid') {
                throw new Exception('Only Paid payments can be refunded.');
            }

            $reason = trim($_POST['reason'] ?? 'Refunded by staff');

            /* Ang trigger `sync_booking_status_after_payment` ay automatic
               nang mag-se-set ng booking status to 'Cancelled' kapag refunded. */
            $stmt = $conn->prepare("
                UPDATE payments
                SET payment_status = 'Refunded',
                    remarks = CONCAT(COALESCE(remarks,''), ' | Refunded by ', ?, ': ', ?)
                WHERE payment_id = ? LIMIT 1
            ");
            $stmt->bind_param('ssi', $staff_name, $reason, $payment_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected <= 0) {
                throw new Exception('Hindi na-refund ang payment.');
            }

            if (class_exists('Logger')) {
                Logger::info('Payment refunded', [
                    'staff_id'   => $staff_id,
                    'payment_id' => $payment_id,
                    'reason'     => $reason,
                ]);
            }

            $flash = [
                'type'    => 'success',
                'message' => "Payment #{$payment_id} refunded. Booking #{$pay['booking_id']} is now Cancelled."
            ];
        }

    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Payment action failed', ['error' => $ex->getMessage()]);
        $flash = ['type' => 'error', 'message' => $ex->getMessage()];
    }
}

/* ──────────────────────────────────────────
   FILTER & SEARCH
   ────────────────────────────────────────── */
$filter = $_GET['filter'] ?? 'pending';
$search = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
$types  = '';

/* Sa DB mo, ang payment_status enum ay: Pending, Paid, Refunded */
if ($filter === 'pending') {
    $where[] = "p.payment_status = 'Pending'";
} elseif ($filter === 'paid') {
    $where[] = "p.payment_status = 'Paid'";
} elseif ($filter === 'refunded') {
    $where[] = "p.payment_status = 'Refunded'";
}
/* 'all' = walang filter */

if ($search !== '') {
    $where[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR CAST(b.Booking_ID AS CHAR) LIKE ? OR CAST(p.payment_id AS CHAR) LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

/* ──────────────────────────────────────────
   FETCH PAYMENTS
   ────────────────────────────────────────── */
$payments = [];
try {
    $sql = "
        SELECT
            p.payment_id,
            p.booking_id,
            p.customer_id,
            p.amount,
            p.payment_method,
            p.payment_status,
            p.payment_date,
            p.remarks,
            b.service,
            b.add_ons,
            b.total_amount,
            b.pick_deliver,
            b.status           AS booking_status,
            b.booking_date,
            c.Customer_ID,
            c.first_name,
            c.last_name,
            c.contact_number
        FROM payments p
        LEFT JOIN booking b       ON b.Booking_ID  = p.booking_id
        LEFT JOIN customer_info c ON c.Customer_ID = p.customer_id
        $where_sql
        ORDER BY p.payment_date DESC, p.payment_id DESC
        LIMIT 100
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $payments[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    if (class_exists('Logger')) Logger::error('Fetch payments failed', ['error' => $e->getMessage()]);
}

/* ──────────────────────────────────────────
   STATS — based sa actual statuses mo
   ────────────────────────────────────────── */
$stats = [
    'pending'         => 0,
    'paid_today'      => 0,
    'collected_today' => 0.0,
    'refunded'        => 0,
];

try {
    $q = $conn->query("SELECT COUNT(*) c FROM payments WHERE payment_status = 'Pending'");
    if ($q) $stats['pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM payments WHERE payment_status = 'Paid' AND DATE(payment_date) = CURDATE()");
    if ($q) $stats['paid_today'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE payment_status = 'Paid' AND DATE(payment_date) = CURDATE()");
    if ($q) $stats['collected_today'] = (float)$q->fetch_assoc()['s'];

    $q = $conn->query("SELECT COUNT(*) c FROM payments WHERE payment_status = 'Refunded'");
    if ($q) $stats['refunded'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Payments — WashFlow Staff</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --dark-blue:#063452; --dark-blue-deep:#042640; --primary:#005A85; --primary-mid:#0076A8;
    --light-blue:#A8E8F9; --light-blue-soft:#E8F6FC; --light-blue-pale:#F2FAFD;
    --yellow:#FFD93D; --yellow-soft:#FFF9DB; --yellow-dark:#B88A00;
    --bg:#F5F9FC; --card:#FFFFFF;
    --text-primary:#0A2540; --text-secondary:#5A7184; --text-muted:#94A9B8;
    --border:#E1EEF5; --border-soft:#F0F6FA;
    --green:#27AE60; --red:#E74C3C; --purple:#9B59B6; --gold:#F0B400;
}
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; }
body {
    font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    color:var(--text-primary); background:var(--bg); font-size:14px; line-height:1.6;
    -webkit-font-smoothing:antialiased; overflow:hidden;
}
h1, h2, h3, h4, h5 { font-weight:700; letter-spacing:-0.02em; }
a { text-decoration:none; }

.wf-layout { display:grid; grid-template-columns:300px 1fr; height:100vh; overflow:hidden; }

.wf-sidebar { background:var(--dark-blue-deep); color:white; height:100vh; overflow-y:auto; overflow-x:hidden; display:flex; flex-direction:column; scrollbar-width:none; }
.wf-sidebar::-webkit-scrollbar { display:none; width:0; }
.wf-sidebar-brand { display:flex; align-items:center; gap:14px; padding:2rem 1.75rem; border-bottom:1px solid rgba(168,232,249,0.08); }
.wf-sidebar-brand-logo { width:46px; height:46px; flex-shrink:0; }
.wf-sidebar-brand-logo svg { width:100%; height:100%; }
.wf-sidebar-brand-text { display:flex; flex-direction:column; line-height:1; }
.wf-sidebar-brand-name { font-size:1.45rem; font-weight:800; color:white; letter-spacing:-0.04em; }
.wf-sidebar-brand-name .flow { color:var(--yellow); }
.wf-sidebar-brand-tag { font-size:0.65rem; color:rgba(168,232,249,0.5); letter-spacing:0.2em; text-transform:uppercase; margin-top:5px; font-weight:600; }
.wf-nav { padding:1.5rem 1rem; flex:1; }
.wf-nav-section-label { font-size:0.68rem; font-weight:700; color:rgba(168,232,249,0.35); letter-spacing:0.22em; text-transform:uppercase; padding:1.25rem 1rem 0.65rem; }
.wf-nav-section-label:first-child { padding-top:0.25rem; }
.wf-nav-item { display:flex; align-items:center; gap:1rem; padding:0.85rem 1rem; border-radius:10px; color:rgba(255,255,255,0.65); font-size:0.95rem; font-weight:500; transition:all 0.2s ease; margin-bottom:0.2rem; position:relative; cursor:pointer; }
.wf-nav-item i { width:22px; text-align:center; font-size:1rem; flex-shrink:0; }
.wf-nav-item span { flex:1; }
.wf-nav-item:hover { background:rgba(168,232,249,0.06); color:rgba(255,255,255,0.95); }
.wf-nav-item.active { background:rgba(255,217,61,0.1); color:white; font-weight:600; }
.wf-nav-item.active::before { content:''; position:absolute; left:0; top:50%; transform:translateY(-50%); width:3px; height:22px; background:var(--yellow); border-radius:0 3px 3px 0; }
.wf-nav-item.active i { color:var(--yellow); }
.wf-sidebar-footer { padding:1.25rem 1rem; border-top:1px solid rgba(168,232,249,0.08); }
.wf-user-card { display:flex; align-items:center; gap:0.875rem; padding:0.85rem; border-radius:11px; background:rgba(168,232,249,0.05); margin-bottom:0.6rem; }
.wf-user-avatar { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,var(--yellow) 0%,var(--yellow-soft) 100%); color:var(--dark-blue-deep); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1rem; flex-shrink:0; }
.wf-user-info { flex:1; min-width:0; line-height:1.25; }
.wf-user-name { font-size:0.92rem; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wf-user-role { font-size:0.72rem; color:rgba(168,232,249,0.5); font-weight:500; }
.wf-btn-logout { display:flex; align-items:center; gap:0.875rem; padding:0.75rem 0.85rem; border-radius:11px; color:rgba(255,255,255,0.55); font-size:0.9rem; font-weight:500; transition:all 0.2s; }
.wf-btn-logout:hover { background:rgba(231,76,60,0.12); color:#FF8B7E; }
.wf-btn-logout i { width:22px; text-align:center; font-size:1rem; }

.wf-main { padding:2rem 2.5rem 3rem; overflow-y:auto; overflow-x:hidden; height:100vh; scrollbar-width:none; }
.wf-main::-webkit-scrollbar { display:none; width:0; }

.wf-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; gap:1rem; flex-wrap:wrap; }
.wf-topbar-greeting h1 { color:var(--dark-blue); font-size:1.6rem; font-weight:800; margin:0; letter-spacing:-0.03em; line-height:1.3; display:flex; align-items:center; gap:0.6rem; }
.wf-topbar-greeting h1 .accent { color:var(--gold); }
.wf-topbar-greeting p { color:var(--text-secondary); font-size:0.88rem; margin:0.2rem 0 0; font-weight:500; }
.wf-topbar-right { display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; }
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

.wf-flash { padding:0.9rem 1.15rem; border-radius:12px; margin-bottom:1.25rem; font-weight:600; font-size:0.88rem; display:flex; align-items:center; gap:0.7rem; animation:flashIn 0.35s; }
@keyframes flashIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.wf-flash.success { background:#EAF7F0; color:#1E7E45; border-left:4px solid var(--green); }
.wf-flash.error { background:#FDEDEC; color:#A8322D; border-left:4px solid var(--red); }

.wf-stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; margin-bottom:1.5rem; }
@media (max-width:1100px) { .wf-stats-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width:600px)  { .wf-stats-grid { grid-template-columns:1fr; } }

.wf-stat-card { background:white; border-radius:14px; padding:1.25rem; border:1px solid var(--border); display:flex; flex-direction:column; justify-content:space-between; min-height:110px; }
.wf-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.6rem; }
.wf-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-stat-icon.icon-blue  { background:var(--light-blue-soft); color:var(--primary); }
.wf-stat-icon.icon-green { background:#EAF7F0; color:#1E7E45; }
.wf-stat-icon.icon-gold  { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-stat-icon.icon-red   { background:#FDEDEC; color:#A8322D; }
.wf-stat-value { font-size:1.6rem; font-weight:800; color:var(--dark-blue); line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.2rem; }
.wf-stat-label { font-size:0.78rem; color:var(--text-secondary); font-weight:500; }

.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }
.wf-card-head { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.wf-card-title { display:flex; align-items:center; gap:0.9rem; }
.wf-card-title-icon { width:42px; height:42px; border-radius:11px; background:var(--light-blue-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-card-title h3 { font-size:1rem; font-weight:700; color:var(--dark-blue); margin:0; }
.wf-card-title p { font-size:0.75rem; color:var(--text-muted); margin:0.1rem 0 0; font-weight:500; }
.wf-card-body { padding:1.5rem; }

.wf-toolbar { display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap; margin-bottom:1.25rem; }
.wf-search { flex:1; min-width:220px; position:relative; }
.wf-search input { width:100%; padding:0.7rem 0.95rem 0.7rem 2.5rem; border:1.5px solid var(--border); border-radius:10px; font-size:0.85rem; font-family:inherit; outline:none; transition:all 0.2s; }
.wf-search input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }
.wf-search i { position:absolute; left:0.9rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem; }

.wf-tabs { display:flex; gap:0.35rem; background:var(--light-blue-pale); padding:0.35rem; border-radius:10px; }
.wf-tab { padding:0.55rem 1rem; border-radius:7px; font-size:0.8rem; font-weight:700; color:var(--text-secondary); cursor:pointer; transition:all 0.2s; border:none; background:transparent; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; }
.wf-tab.active { background:white; color:var(--dark-blue); box-shadow:0 2px 8px rgba(6,52,82,0.08); }
.wf-tab:hover:not(.active) { color:var(--dark-blue); }

.wf-table-wrap { overflow-x:auto; border-radius:12px; border:1px solid var(--border-soft); }
.wf-table { width:100%; border-collapse:collapse; font-size:0.83rem; }
.wf-table thead th {
    background:var(--light-blue-pale);
    padding:0.85rem 1rem;
    text-align:left;
    font-size:0.72rem;
    font-weight:800;
    color:var(--dark-blue);
    text-transform:uppercase;
    letter-spacing:0.06em;
    white-space:nowrap;
    border-bottom:1px solid var(--border);
}
.wf-table tbody td { padding:1rem; border-bottom:1px solid var(--border-soft); vertical-align:middle; }
.wf-table tbody tr:last-child td { border-bottom:none; }
.wf-table tbody tr:hover { background:var(--light-blue-pale); }

.wf-cust { display:flex; align-items:center; gap:0.75rem; }
.wf-cust-avatar { width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, var(--light-blue-soft) 0%, var(--light-blue-pale) 100%); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.9rem; flex-shrink:0; }
.wf-cust-info { line-height:1.25; min-width:0; }
.wf-cust-name { font-weight:700; color:var(--dark-blue); font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wf-cust-contact { font-size:0.72rem; color:var(--text-muted); font-weight:500; }

.wf-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.3rem 0.7rem; border-radius:20px; font-size:0.72rem; font-weight:800; letter-spacing:0.02em; }
.wf-badge.paid     { background:#EAF7F0; color:#1E7E45; }
.wf-badge.pending  { background:#FFF9DB; color:#8A6D00; }
.wf-badge.refunded { background:#FDEDEC; color:#A8322D; }
.wf-badge.method   { background:var(--light-blue-soft); color:var(--primary); }

.wf-amount { font-weight:800; color:var(--dark-blue); font-size:0.95rem; letter-spacing:-0.01em; }

.wf-actions { display:flex; gap:0.4rem; flex-wrap:wrap; }
.wf-btn-sm {
    display:inline-flex; align-items:center; gap:0.35rem;
    padding:0.5rem 0.85rem; border-radius:8px;
    font-size:0.75rem; font-weight:800;
    cursor:pointer; transition:all 0.15s;
    border:none; font-family:inherit;
    white-space:nowrap;
}
.wf-btn-sm.verify { background:#EAF7F0; color:#1E7E45; border:1px solid #B8E6C9; }
.wf-btn-sm.verify:hover { background:#1E7E45; color:white; border-color:#1E7E45; }
.wf-btn-sm.refund { background:#FDEDEC; color:#A8322D; border:1px solid #F5C6C2; }
.wf-btn-sm.refund:hover { background:#A8322D; color:white; border-color:#A8322D; }
.wf-btn-sm.view { background:var(--light-blue-soft); color:var(--primary); border:1px solid var(--light-blue); }
.wf-btn-sm.view:hover { background:var(--primary); color:white; border-color:var(--primary); }

.wf-empty { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
.wf-empty i { font-size:2.5rem; color:var(--light-blue); margin-bottom:0.75rem; display:block; }
.wf-empty p { margin:0; font-size:0.88rem; font-weight:600; }
.wf-empty small { font-size:0.78rem; color:var(--text-muted); }

.wf-modal-overlay { display:none; position:fixed; inset:0; background:rgba(6,52,82,0.55); z-index:2000; align-items:center; justify-content:center; padding:1.5rem; backdrop-filter:blur(3px); }
.wf-modal-overlay.open { display:flex; }
.wf-modal { background:white; border-radius:16px; max-width:520px; width:100%; max-height:90vh; overflow-y:auto; padding:1.75rem; animation:modalIn 0.25s; }
@keyframes modalIn { from{opacity:0;transform:scale(0.94);} to{opacity:1;transform:scale(1);} }
.wf-modal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.wf-modal-head h3 { font-size:1.1rem; font-weight:800; color:var(--dark-blue); margin:0; }
.wf-modal-close { width:34px; height:34px; border-radius:9px; background:var(--light-blue-pale); color:var(--text-secondary); display:flex; align-items:center; justify-content:center; cursor:pointer; border:none; font-size:0.9rem; transition:all 0.15s; }
.wf-modal-close:hover { background:var(--red); color:white; }

.wf-receipt {
    background:#FAFCFE; border:1.5px dashed var(--border);
    border-radius:12px; padding:1.25rem 1.4rem;
    font-family:'SF Mono', Monaco, monospace;
    font-size:0.78rem; line-height:1.7;
}
.wf-receipt-head { text-align:center; padding-bottom:0.75rem; border-bottom:1px dashed var(--border); margin-bottom:0.75rem; }
.wf-receipt-head h4 { font-size:1rem; font-weight:800; color:var(--dark-blue); margin:0; font-family:'Plus Jakarta Sans', sans-serif; }
.wf-receipt-head p { font-size:0.7rem; color:var(--text-muted); margin:0.15rem 0 0; }
.wf-receipt-row { display:flex; justify-content:space-between; padding:0.2rem 0; }
.wf-receipt-row .label { color:var(--text-muted); }
.wf-receipt-row .value { color:var(--dark-blue); font-weight:700; text-align:right; }
.wf-receipt-row.total { border-top:1px dashed var(--border); padding-top:0.5rem; margin-top:0.5rem; font-weight:800; color:var(--dark-blue); font-size:0.9rem; }

.wf-modal-actions { display:flex; gap:0.6rem; margin-top:1.25rem; flex-wrap:wrap; }
.wf-btn { display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; padding:0.85rem 1.4rem; border-radius:10px; font-size:0.85rem; font-weight:800; cursor:pointer; transition:all 0.2s; border:none; font-family:inherit; text-decoration:none; }
.wf-btn-primary { background:var(--primary); color:white; }
.wf-btn-primary:hover { background:var(--primary-mid); transform:translateY(-1px); }
.wf-btn-success { background:#1E7E45; color:white; }
.wf-btn-success:hover { background:#166235; transform:translateY(-1px); }
.wf-btn-danger { background:#A8322D; color:white; }
.wf-btn-danger:hover { background:#8A2620; transform:translateY(-1px); }
.wf-btn-outline { background:white; color:var(--primary); border:1.5px solid var(--border); }
.wf-btn-outline:hover { border-color:var(--primary); background:var(--light-blue-pale); }
.wf-btn-block { width:100%; }

.wf-reason-input { width:100%; padding:0.7rem 0.95rem; border:1.5px solid var(--border); border-radius:10px; font-size:0.85rem; font-family:inherit; outline:none; transition:all 0.2s; resize:vertical; min-height:80px; }
.wf-reason-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }

.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
}
</style>
</head>
<body>

<button class="wf-sidebar-toggle" id="wfSidebarToggle"><i class="fas fa-bars"></i></button>
<div class="wf-sidebar-overlay" id="wfSidebarOverlay"></div>

<div class="wf-layout">

    <aside class="wf-sidebar" id="wfSidebar">
        <div class="wf-sidebar-brand">
            <div class="wf-sidebar-brand-logo">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="wfSideLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0076A8"/><stop offset="100%" stop-color="#005A85"/>
                        </linearGradient>
                        <linearGradient id="wfSideWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FFD93D"/><stop offset="100%" stop-color="#A8E8F9"/>
                        </linearGradient>
                    </defs>
                    <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#wfSideLogoGrad)"/>
                    <path d="M14 24 L20 42 L26 30 L32 42 L38 24" stroke="url(#wfSideWaveGrad)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <circle cx="44" cy="24" r="2.5" fill="#FFD93D" opacity="0.9"/>
                    <circle cx="48" cy="32" r="1.8" fill="#FFD93D" opacity="0.7"/>
                    <circle cx="44" cy="40" r="1.2" fill="#FFD93D" opacity="0.5"/>
                    <path d="M14 48 Q22 44 32 48 T50 48" stroke="#A8E8F9" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.6"/>
                </svg>
            </div>
            <div class="wf-sidebar-brand-text">
                <span class="wf-sidebar-brand-name">Wash<span class="flow">Flow</span></span>
                <span class="wf-sidebar-brand-tag">Staff Panel</span>
            </div>
        </div>

        <nav class="wf-nav">
            <div class="wf-nav-section-label">My Work</div>
            <a href="staff_dashboard.php" class="wf-nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="booking_management.php" class="wf-nav-item"><i class="fas fa-clipboard-list"></i><span>My Queue</span></a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="staff_walkin.php" class="wf-nav-item"><i class="fas fa-walking"></i><span>Walk-in Booking</span></a>
            <a href="staff_payments.php" class="wf-nav-item active"><i class="fas fa-money-check-alt"></i><span>Verify Payments</span></a>
            <a href="staff_inventory.php" class="wf-nav-item"><i class="fas fa-flask"></i><span>Log Inventory</span></a>

            <div class="wf-nav-section-label">Support</div>
            <a href="staff_feedback.php" class="wf-nav-item"><i class="fas fa-star"></i><span>Reply Feedback</span></a>
            <a href="staff_complaints.php" class="wf-nav-item"><i class="fas fa-headset"></i><span>Handle Complaints</span></a>

            <div class="wf-nav-section-label">Account</div>
            <a href="staff_shift.php" class="wf-nav-item"><i class="fas fa-clock"></i><span>My Shift</span></a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e($staff_initial) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($staff_name) ?></div>
                    <div class="wf-user-role"><?= e($my_role['label']) ?></div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="wf-main">

        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1><i class="fas fa-money-check-alt" style="color:var(--primary);"></i> Verify <span class="accent">Payments</span></h1>
                <p>Confirm customer payments and manage verification</p>
            </div>
            <div class="wf-topbar-right">
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="wf-flash <?= e($flash['type']) ?>">
            <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span><?= e($flash['message']) ?></span>
        </div>
        <?php endif; ?>

        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-gold"><i class="fas fa-hourglass-half"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="wf-stat-label">Pending Payments</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-check-double"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['paid_today']) ?></div>
                    <div class="wf-stat-label">Paid Today</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-coins"></i></div></div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($stats['collected_today'], 2) ?></div>
                    <div class="wf-stat-label">Collected Today</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-red"><i class="fas fa-rotate-left"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['refunded']) ?></div>
                    <div class="wf-stat-label">Refunded</div>
                </div>
            </div>
        </div>

        <div class="wf-card">
            <div class="wf-card-head">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-list-check"></i></div>
                    <div>
                        <h3>Payment Records</h3>
                        <p>Verify pending payments or issue refunds</p>
                    </div>
                </div>
            </div>
            <div class="wf-card-body">

                <div class="wf-toolbar">
                    <div class="wf-search">
                        <i class="fas fa-search"></i>
                        <form method="GET" style="margin:0;">
                            <input type="hidden" name="filter" value="<?= e($filter) ?>">
                            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by customer name, booking ID, or payment ID...">
                        </form>
                    </div>
                    <div class="wf-tabs">
                        <a href="?filter=pending&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'pending' ? 'active' : '' ?>">
                            <i class="fas fa-hourglass-half"></i> Pending
                        </a>
                        <a href="?filter=paid&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'paid' ? 'active' : '' ?>">
                            <i class="fas fa-check"></i> Paid
                        </a>
                        <a href="?filter=refunded&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'refunded' ? 'active' : '' ?>">
                            <i class="fas fa-rotate-left"></i> Refunded
                        </a>
                        <a href="?filter=all&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'all' ? 'active' : '' ?>">
                            <i class="fas fa-list"></i> All
                        </a>
                    </div>
                </div>

                <?php if (empty($payments)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-inbox"></i>
                        <p>No payments found</p>
                        <small>Try changing the filter or search keyword.</small>
                    </div>
                <?php else: ?>
                    <div class="wf-table-wrap">
                        <table class="wf-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Booking</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($payments as $p):
                                $full = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                                if ($full === '') $full = 'Customer';
                                $initial = strtoupper(substr($full, 0, 1));

                                $status = strtolower($p['payment_status']);
                                if (!in_array($status, ['pending','paid','refunded'], true)) {
                                    $status = 'pending';
                                }

                                $can_verify = ($p['payment_status'] === 'Pending');
                                $can_refund = ($p['payment_status'] === 'Paid');
                            ?>
                                <tr>
                                    <td>
                                        <div class="wf-cust">
                                            <div class="wf-cust-avatar"><?= e($initial) ?></div>
                                            <div class="wf-cust-info">
                                                <div class="wf-cust-name"><?= e($full) ?></div>
                                                <div class="wf-cust-contact"><?= e($p['contact_number'] ?: '—') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:700;color:var(--dark-blue);">#<?= (int)$p['booking_id'] ?></div>
                                        <div style="font-size:0.72rem;color:var(--text-muted);">Pay #<?= (int)$p['payment_id'] ?></div>
                                    </td>
                                    <td><span class="wf-amount">₱<?= number_format((float)$p['amount'], 2) ?></span></td>
                                    <td><span class="wf-badge method"><?= e($p['payment_method'] ?: 'Cash') ?></span></td>
                                    <td>
                                        <span class="wf-badge <?= e($status) ?>">
                                            <?php if ($status === 'paid'): ?><i class="fas fa-check-circle"></i>
                                            <?php elseif ($status === 'refunded'): ?><i class="fas fa-rotate-left"></i>
                                            <?php else: ?><i class="fas fa-clock"></i><?php endif; ?>
                                            <?= e($p['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;color:var(--dark-blue);font-size:0.8rem;">
                                            <?= $p['payment_date'] ? date('M j, Y', strtotime($p['payment_date'])) : '—' ?>
                                        </div>
                                        <div style="font-size:0.72rem;color:var(--text-muted);">
                                            <?= $p['payment_date'] ? date('g:i A', strtotime($p['payment_date'])) : '' ?>
                                        </div>
                                    </td>
                                    <td style="text-align:right;">
                                        <div class="wf-actions" style="justify-content:flex-end;">
                                            <button type="button" class="wf-btn-sm view" onclick='openReceipt(<?= json_encode([
                                                "payment_id"  => (int)$p["payment_id"],
                                                "booking_id"  => (int)$p["booking_id"],
                                                "customer"    => $full,
                                                "contact"     => $p["contact_number"] ?? "",
                                                "amount"      => (float)$p["amount"],
                                                "method"      => $p["payment_method"] ?? "Cash",
                                                "status"      => $p["payment_status"],
                                                "date"        => $p["payment_date"],
                                                "service"     => $p["service"] ?? "",
                                                "addons"      => $p["add_ons"] ?? "",
                                                "remarks"     => $p["remarks"] ?? "",
                                                "booking_status" => $p["booking_status"] ?? "",
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($can_verify): ?>
                                                <button type="button" class="wf-btn-sm verify" onclick="confirmVerify(<?= (int)$p['payment_id'] ?>)">
                                                    <i class="fas fa-check"></i> Verify
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($can_refund): ?>
                                                <button type="button" class="wf-btn-sm refund" onclick="openRefund(<?= (int)$p['payment_id'] ?>)">
                                                    <i class="fas fa-rotate-left"></i> Refund
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </main>
</div>

<!-- RECEIPT MODAL -->
<div class="wf-modal-overlay" id="receiptModal">
    <div class="wf-modal">
        <div class="wf-modal-head">
            <h3><i class="fas fa-receipt" style="color:var(--primary);"></i> Payment Receipt</h3>
            <button type="button" class="wf-modal-close" onclick="closeModal('receiptModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="wf-receipt" id="receiptContent"></div>
        <div class="wf-modal-actions">
            <button type="button" class="wf-btn wf-btn-outline" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="wf-btn wf-btn-primary" onclick="closeModal('receiptModal')">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- VERIFY CONFIRM MODAL -->
<div class="wf-modal-overlay" id="verifyModal">
    <div class="wf-modal" style="max-width:420px;">
        <div class="wf-modal-head">
            <h3><i class="fas fa-check-circle" style="color:#1E7E45;"></i> Confirm Payment</h3>
            <button type="button" class="wf-modal-close" onclick="closeModal('verifyModal')"><i class="fas fa-times"></i></button>
        </div>
        <p style="color:var(--text-secondary);font-size:0.88rem;margin-bottom:1.25rem;">
            Mark this payment as <strong>PAID</strong>?<br>
            <small style="color:var(--text-muted);">Awtomatikong magiging <strong>Confirmed</strong> ang booking at mag-a-add ng financial record.</small>
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="payment_id" id="verifyPaymentId">
            <div class="wf-modal-actions">
                <button type="submit" class="wf-btn wf-btn-success" style="flex:1;">
                    <i class="fas fa-check"></i> Yes, Verify
                </button>
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeModal('verifyModal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- REFUND MODAL -->
<div class="wf-modal-overlay" id="refundModal">
    <div class="wf-modal" style="max-width:460px;">
        <div class="wf-modal-head">
            <h3><i class="fas fa-rotate-left" style="color:#A8322D;"></i> Refund Payment</h3>
            <button type="button" class="wf-modal-close" onclick="closeModal('refundModal')"><i class="fas fa-times"></i></button>
        </div>
        <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;">
            <small style="color:var(--text-muted);">Awtomatikong magiging <strong>Cancelled</strong> ang booking kapag ni-refund.</small>
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="refund">
            <input type="hidden" name="payment_id" id="refundPaymentId">
            <label style="display:block;font-size:0.78rem;font-weight:700;color:var(--dark-blue);margin-bottom:0.4rem;">
                Reason for refund <span style="color:var(--red);">*</span>
            </label>
            <textarea name="reason" class="wf-reason-input" required
                      placeholder="e.g. Customer cancelled, wrong amount, duplicate payment..."></textarea>
            <div class="wf-modal-actions">
                <button type="submit" class="wf-btn wf-btn-danger" style="flex:1;">
                    <i class="fas fa-rotate-left"></i> Confirm Refund
                </button>
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeModal('refundModal')">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openReceipt(data) {
    const fmt = n => '₱' + parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    const dt  = data.date ? new Date(data.date).toLocaleString('en-PH', {month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit'}) : '—';

    document.getElementById('receiptContent').innerHTML = `
        <div class="wf-receipt-head">
            <h4>WashFlow Laundry</h4>
            <p>Payment Receipt</p>
        </div>
        <div class="wf-receipt-row"><span class="label">Payment ID:</span><span class="value">#${data.payment_id}</span></div>
        <div class="wf-receipt-row"><span class="label">Booking ID:</span><span class="value">#${data.booking_id}</span></div>
        <div class="wf-receipt-row"><span class="label">Customer:</span><span class="value">${escapeHtml(data.customer)}</span></div>
        <div class="wf-receipt-row"><span class="label">Contact:</span><span class="value">${escapeHtml(data.contact || '—')}</span></div>
        <div class="wf-receipt-row"><span class="label">Date:</span><span class="value">${dt}</span></div>
        <div class="wf-receipt-row"><span class="label">Service:</span><span class="value">${escapeHtml(data.service || '—')}</span></div>
        <div class="wf-receipt-row"><span class="label">Add-ons:</span><span class="value">${escapeHtml(data.addons || 'None')}</span></div>
        <div class="wf-receipt-row total"><span class="label">AMOUNT:</span><span class="value">${fmt(data.amount)}</span></div>
        <div class="wf-receipt-row" style="margin-top:0.5rem;"><span class="label">Method:</span><span class="value">${escapeHtml(data.method)}</span></div>
        <div class="wf-receipt-row"><span class="label">Status:</span><span class="value">${escapeHtml(data.status)}</span></div>
        ${data.remarks ? `<div class="wf-receipt-row"><span class="label">Remarks:</span><span class="value">${escapeHtml(data.remarks)}</span></div>` : ''}
    `;
    document.getElementById('receiptModal').classList.add('open');
}

function confirmVerify(id) {
    document.getElementById('verifyPaymentId').value = id;
    document.getElementById('verifyModal').classList.add('open');
}

function openRefund(id) {
    document.getElementById('refundPaymentId').value = id;
    document.getElementById('refundModal').classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.querySelectorAll('.wf-modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => {
        if (e.target === ov) ov.classList.remove('open');
    });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.wf-modal-overlay.open').forEach(m => m.classList.remove('open'));
});

(function() {
    const toggle  = document.getElementById('wfSidebarToggle');
    const sidebar = document.getElementById('wfSidebar');
    const overlay = document.getElementById('wfSidebarOverlay');
    if (!toggle) return;
    toggle.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('open'); });
    overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
})();
</script>
</body>
</html>