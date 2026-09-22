<?php
/**
 * admin_payments.php
 * WashFlow — ADMIN Payments Monitor (READ-ONLY)
 *
 * - Admin-only (auto-redirect to admin_login if not admin)
 * - View ALL payments
 * - Filters, search, sort, pagination
 * - Track all movements — who verified, when, what status
 * - Export CSV
 * - NO payment verification — staff only
 * - NO status update — staff only
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* ==========================================
   AUTH — ADMIN ONLY
   ========================================== */
if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id   = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Admin';

/* ==========================================
   HELPERS
   ========================================== */
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('admin_pay_badge')) {
    function admin_pay_badge($status) {
        $s = strtolower(trim((string)$status));
        switch ($s) {
            case 'paid':                return ['label' => 'Paid',                'color' => '#1E7E45', 'bg' => '#EAF7F0', 'icon' => 'fa-check-circle'];
            case 'pending':
            case 'pending_verification': return ['label' => 'Pending Verification','color' => '#946200', 'bg' => '#FEF9E7', 'icon' => 'fa-hourglass-half'];
            case 'unpaid':              return ['label' => 'Unpaid',              'color' => '#A8322D', 'bg' => '#FDEDEC', 'icon' => 'fa-times-circle'];
            case 'partially paid':      return ['label' => 'Partially Paid',      'color' => '#946200', 'bg' => '#FEF9E7', 'icon' => 'fa-adjust'];
            case 'refunded':            return ['label' => 'Refunded',            'color' => '#5B2E91', 'bg' => '#F4ECFB', 'icon' => 'fa-undo'];
            case 'failed':              return ['label' => 'Failed',              'color' => '#A8322D', 'bg' => '#FDEDEC', 'icon' => 'fa-exclamation-circle'];
            case 'no payment':
            case 'none':
            default:                    return ['label' => 'No Payment',          'color' => '#5A7184', 'bg' => '#F4F7F9', 'icon' => 'fa-minus-circle'];
        }
    }
}

if (!function_exists('method_badge')) {
    function method_badge($method) {
        $m = strtolower(trim((string)$method));
        switch ($m) {
            case 'gcash': return ['label' => 'GCash', 'color' => '#00537A', 'bg' => '#EBF5FB', 'icon' => 'fa-mobile-alt'];
            case 'cash':  return ['label' => 'Cash',  'color' => '#1E7E45', 'bg' => '#EAF7F0', 'icon' => 'fa-money-bill-wave'];
            case 'card':  return ['label' => 'Card',  'color' => '#5B2E91', 'bg' => '#F4ECFB', 'icon' => 'fa-credit-card'];
            default:      return ['label' => ucfirst($m) ?: '—', 'color' => '#5A7184', 'bg' => '#F4F7F9', 'icon' => 'fa-question'];
        }
    }
}

/* ==========================================
   CSRF TOKEN
   ========================================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ==========================================
   CHECK TABLES / COLUMNS
   ========================================== */
$has_payments_online = false;
if ($res = $conn->query("SHOW TABLES LIKE 'payments_online'")) {
    $has_payments_online = ($res->num_rows > 0);
    $res->free();
}

$has_verified_cols = false;
if ($has_payments_online) {
    $res = $conn->query("SHOW COLUMNS FROM payments_online LIKE 'verified_by'");
    if ($res && $res->num_rows > 0) $has_verified_cols = true;
    if ($res) $res->free();
}

/* ==========================================
   FLASH
   ========================================== */
$flash = null;
if (!empty($_SESSION['ap_flash'])) {
    $flash = $_SESSION['ap_flash'];
    unset($_SESSION['ap_flash']);
}

/* ==========================================
   STATS
   ========================================== */
$stats = [
    'total'          => 0,
    'paid'           => 0,
    'pending'        => 0,
    'unpaid'         => 0,
    'refunded'       => 0,
    'total_amount'   => 0,
    'paid_amount'    => 0,
    'pending_amount' => 0,
    'gcash'          => 0,
    'cash'           => 0,
];

if ($has_payments_online) {
    try {
        $q = $conn->query("SELECT COUNT(*) c FROM payments_online");
        if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM payments_online WHERE LOWER(payment_status) = 'paid'");
        if ($q) { $r = $q->fetch_assoc(); $stats['paid'] = (int)$r['c']; $stats['paid_amount'] = (float)$r['s']; }

        $q = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM payments_online WHERE LOWER(payment_status) IN ('pending','pending_verification')");
        if ($q) { $r = $q->fetch_assoc(); $stats['pending'] = (int)$r['c']; $stats['pending_amount'] = (float)$r['s']; }

        $q = $conn->query("SELECT COUNT(*) c FROM payments_online WHERE LOWER(payment_status) = 'unpaid'");
        if ($q) $stats['unpaid'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM payments_online WHERE LOWER(payment_status) = 'refunded'");
        if ($q) $stats['refunded'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments_online");
        if ($q) $stats['total_amount'] = (float)$q->fetch_assoc()['s'];

        $q = $conn->query("SELECT payment_method, COUNT(*) c FROM payments_online GROUP BY payment_method");
        if ($q) {
            while ($r = $q->fetch_assoc()) {
                $m = strtolower($r['payment_method']);
                if ($m === 'gcash') $stats['gcash'] = (int)$r['c'];
                elseif ($m === 'cash') $stats['cash'] = (int)$r['c'];
            }
        }
    } catch (Exception $ex) {
        Logger::error('Admin payment stats failed', ['error' => $ex->getMessage()]);
    }
}

/* ==========================================
   FILTERS + PAGINATION
   ========================================== */
$search     = trim($_GET['q'] ?? '');
$status_f   = trim($_GET['status'] ?? '');
$method_f   = trim($_GET['method'] ?? '');
$date_from  = trim($_GET['date_from'] ?? '');
$date_to    = trim($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;
$sort       = $_GET['sort'] ?? 'date_desc';

$order_map = [
    'date_desc'   => 'payment_date DESC, payment_id DESC',
    'date_asc'    => 'payment_date ASC, payment_id ASC',
    'amount_desc' => 'amount DESC, payment_id DESC',
    'amount_asc'  => 'amount ASC, payment_id ASC',
    'id_desc'     => 'payment_id DESC',
    'id_asc'      => 'payment_id ASC',
];
$order_sql = $order_map[$sort] ?? 'payment_date DESC, payment_id DESC';

/* ==========================================
   FETCH PAYMENTS
   ========================================== */
$payments    = [];
$total_rows  = 0;
$total_pages = 1;

if ($has_payments_online) {
    try {
        $where  = ["1=1"];
        $params = [];
        $types  = '';

        if ($search !== '') {
            $where[] = "(p.payment_id LIKE ? OR p.booking_id LIKE ? OR p.reference_number LIKE ? OR b.customer_name LIKE ? OR b.contact_number LIKE ?)";
            $like = '%' . $search . '%';
            for ($i = 0; $i < 5; $i++) { $params[] = $like; $types .= 's'; }
        }

        if ($status_f !== '') {
            if ($status_f === 'pending') {
                $where[] = "LOWER(p.payment_status) IN ('pending','pending_verification')";
            } else {
                $where[] = "LOWER(p.payment_status) = ?";
                $params[] = strtolower($status_f);
                $types .= 's';
            }
        }

        if ($method_f !== '') {
            $where[] = "LOWER(p.payment_method) = ?";
            $params[] = strtolower($method_f);
            $types .= 's';
        }

        if ($date_from !== '') { $where[] = "DATE(p.payment_date) >= ?"; $params[] = $date_from; $types .= 's'; }
        if ($date_to   !== '') { $where[] = "DATE(p.payment_date) <= ?"; $params[] = $date_to;   $types .= 's'; }

        $where_sql = implode(' AND ', $where);

        $count_sql = "SELECT COUNT(*) c
                      FROM payments_online p
                      LEFT JOIN booking_online b ON b.id = p.booking_id
                      WHERE $where_sql";
        $stmt = $conn->prepare($count_sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $total_pages = max(1, (int)ceil($total_rows / $per_page));

        $verified_cols = $has_verified_cols
            ? "p.verified_by, p.verified_at"
            : "NULL AS verified_by, NULL AS verified_at";

        $list_sql = "SELECT
                        p.payment_id,
                        p.booking_id,
                        p.amount,
                        p.payment_method,
                        p.payment_status,
                        p.reference_number,
                        p.payment_date,
                        p.payment_proof,
                        p.notes,
                        $verified_cols,
                        b.customer_id,
                        b.customer_name,
                        b.contact_number,
                        b.service,
                        b.status AS booking_status,
                        b.total_amount AS booking_total,
                        b.delivery_option
                     FROM payments_online p
                     LEFT JOIN booking_online b ON b.id = p.booking_id
                     WHERE $where_sql
                     ORDER BY $order_sql
                     LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($list_sql);
        $bind_types  = $types . 'ii';
        $bind_params = array_merge($params, [$per_page, $offset]);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

    } catch (Exception $ex) {
        Logger::error('Admin fetch payments failed', ['error' => $ex->getMessage(), 'msg' => $conn->error]);
    }
}

/* ==========================================
   URL HELPER
   ========================================== */
function build_url($overrides = []) {
    $base = [
        'q'         => $_GET['q']         ?? '',
        'status'    => $_GET['status']    ?? '',
        'method'    => $_GET['method']    ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'sort'      => $_GET['sort']      ?? 'date_desc',
        'page'      => $_GET['page']      ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    $merged = array_filter($merged, function($v, $k) {
        if ($v === '' || $v === null) return false;
        if ($k === 'page' && (int)$v === 1) return false;
        if ($k === 'sort' && $v === 'date_desc') return false;
        return true;
    }, ARRAY_FILTER_USE_BOTH);
    return 'admin_payments.php?' . http_build_query($merged);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments Monitor — WashFlow Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --dark-blue:#063452; --dark-blue-deep:#042640; --primary:#005A85; --primary-mid:#0076A8;
    --light-blue:#A8E8F9; --light-blue-soft:#E8F6FC; --light-blue-pale:#F2FAFD;
    --yellow:#FFD93D; --yellow-soft:#FFF9DB; --yellow-dark:#B88A00;
    --bg:#F5F9FC; --text-primary:#0A2540; --text-secondary:#5A7184; --text-muted:#94A9B8;
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

/* SIDEBAR */
.wf-sidebar {
    background:var(--dark-blue-deep); color:white; height:100vh;
    overflow-y:auto; overflow-x:hidden; display:flex; flex-direction:column; scrollbar-width:none;
}
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

/* MAIN */
.wf-main { padding:2rem 2.5rem 3rem; overflow-y:auto; overflow-x:hidden; height:100vh; scrollbar-width:none; }
.wf-main::-webkit-scrollbar { display:none; width:0; }

/* TOPBAR */
.wf-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; gap:1rem; flex-wrap:wrap; }
.wf-topbar-greeting h1 { color:var(--dark-blue); font-size:1.6rem; font-weight:800; margin:0; letter-spacing:-0.03em; line-height:1.3; display:flex; align-items:center; gap:0.6rem; }
.wf-topbar-greeting h1 .accent { color:var(--gold); }
.wf-topbar-greeting p { color:var(--text-secondary); font-size:0.88rem; margin:0.2rem 0 0; font-weight:500; }
.wf-topbar-right { display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; }
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

.wf-role-pill {
    display:inline-flex; align-items:center; gap:0.5rem;
    padding:0.55rem 0.95rem; border-radius:999px;
    font-size:0.78rem; font-weight:700;
    background:linear-gradient(135deg, #FFF9DB 0%, #FFF3B0 100%);
    color:#7A5A00; border:1px solid #F0DE8A;
}

/* FLASH */
.wf-flash { padding:0.9rem 1.15rem; border-radius:12px; margin-bottom:1.25rem; font-weight:600; font-size:0.88rem; display:flex; align-items:center; gap:0.7rem; animation:flashIn 0.35s; }
@keyframes flashIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.wf-flash.success { background:#EAF7F0; color:#1E7E45; border-left:4px solid var(--green); }
.wf-flash.error { background:#FDEDEC; color:#A8322D; border-left:4px solid var(--red); }

/* STATS */
.wf-stats-grid { display:grid; grid-template-columns:repeat(6, 1fr); gap:1rem; margin-bottom:1.5rem; }
.wf-stat-card {
    background:white; border-radius:14px; padding:1.25rem;
    border:1px solid var(--border); transition:all 0.25s;
    display:flex; flex-direction:column; justify-content:space-between;
    min-height:130px;
}
.wf-stat-card:hover { border-color:var(--light-blue); box-shadow:0 6px 20px rgba(0,83,122,0.08); transform:translateY(-3px); }
.wf-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.85rem; }
.wf-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-stat-icon.icon-blue   { background:var(--light-blue-soft); color:var(--primary); }
.wf-stat-icon.icon-yellow { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-stat-icon.icon-green  { background:#EAF7F0; color:#1E7E45; }
.wf-stat-icon.icon-purple { background:#F4ECFB; color:#5B2E91; }
.wf-stat-icon.icon-dark   { background:#E6EDF3; color:var(--dark-blue); }
.wf-stat-icon.icon-red    { background:#FDEDEC; color:#A8322D; }
.wf-stat-value { font-size:1.5rem; font-weight:800; color:var(--dark-blue); line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.2rem; }
.wf-stat-label { font-size:0.75rem; color:var(--text-secondary); font-weight:500; }

/* TABS */
.wf-tabs { display:flex; gap:0.5rem; background:white; padding:0.4rem; border-radius:12px; border:1px solid var(--border); margin-bottom:1.25rem; width:fit-content; }
.wf-tab { display:inline-flex; align-items:center; gap:0.55rem; padding:0.7rem 1.25rem; border-radius:9px; font-size:0.85rem; font-weight:600; color:var(--text-secondary); transition:all 0.2s; }
.wf-tab:hover { color:var(--primary); background:var(--light-blue-pale); }
.wf-tab.active { background:var(--dark-blue); color:white; box-shadow:0 4px 12px rgba(6,52,82,0.2); }
.wf-tab .tab-count { background:rgba(255,255,255,0.15); color:inherit; padding:0.15rem 0.55rem; border-radius:6px; font-size:0.72rem; font-weight:700; min-width:24px; text-align:center; }
.wf-tab:not(.active) .tab-count { background:var(--light-blue-soft); color:var(--primary); }

/* FILTER */
.wf-filter-bar { background:white; border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; display:flex; align-items:flex-end; gap:1rem; flex-wrap:nowrap; }
.wf-filter-group { display:flex; flex-direction:column; gap:0.4rem; min-width:0; }
.wf-filter-group label { font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em; white-space:nowrap; }
.wf-input, .wf-select { width:100%; padding:0.65rem 0.9rem; border:1px solid var(--border); border-radius:9px; font-size:0.85rem; font-family:inherit; color:var(--text-primary); background:white; transition:all 0.2s; outline:none; }
.wf-input:focus, .wf-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }
.wf-btn { display:inline-flex; align-items:center; gap:0.5rem; padding:0.7rem 1.2rem; border-radius:9px; font-size:0.85rem; font-weight:600; cursor:pointer; transition:all 0.2s; border:1px solid transparent; font-family:inherit; white-space:nowrap; }
.wf-btn-primary { background:var(--primary); color:white; }
.wf-btn-primary:hover { background:var(--primary-mid); }
.wf-btn-outline { background:white; color:var(--primary); border-color:var(--border); }
.wf-btn-outline:hover { border-color:var(--primary); background:var(--light-blue-pale); }
.wf-btn-yellow { background:var(--yellow); color:var(--dark-blue-deep); }
.wf-btn-yellow:hover { background:#FFCE00; }

/* CARD */
.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }

/* TABLE */
.wf-table-wrap { overflow-x:auto; }
.wf-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.wf-table thead th { color:var(--text-muted); font-weight:600; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.1em; padding:0.9rem 1rem; text-align:left; border-bottom:1px solid var(--border); white-space:nowrap; background:#FAFCFE; }
.wf-table thead th a { color:inherit; display:inline-flex; align-items:center; gap:0.35rem; transition:color 0.15s; }
.wf-table thead th a:hover { color:var(--primary); }
.wf-table tbody td { padding:0.95rem 1rem; border-bottom:1px solid var(--border-soft); vertical-align:middle; }
.wf-table tbody tr:last-child td { border-bottom:none; }
.wf-table tbody tr:hover { background:var(--light-blue-pale); }

.wf-pay-id { font-weight:700; color:var(--primary); font-family:'SF Mono',Monaco,monospace; font-size:0.8rem; }
.wf-booking-ref { display:inline-flex; align-items:center; gap:0.3rem; padding:0.15rem 0.5rem; border-radius:5px; font-size:0.68rem; font-weight:700; background:#F0F6FA; color:var(--primary); margin-left:0.35rem; font-family:'SF Mono',Monaco,monospace; }
.wf-customer { font-weight:600; color:var(--dark-blue); }
.wf-amount { font-weight:700; color:var(--dark-blue); }
.wf-date { color:var(--text-muted); font-size:0.8rem; white-space:nowrap; }
.wf-ref { font-family:'SF Mono',Monaco,monospace; font-size:0.75rem; color:var(--text-secondary); }

.wf-pay-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.3rem 0.7rem; border-radius:999px; font-size:0.7rem; font-weight:700; white-space:nowrap; border:1px solid transparent; }
.wf-pay-badge.pending  { background:#FEF9E7; color:#946200; border-color:#F5E6A8; }
.wf-pay-badge.paid     { background:#EAF7F0; color:#1E7E45; border-color:#B8E6CC; }
.wf-pay-badge.unpaid   { background:#FDEDEC; color:#A8322D; border-color:#F5C6C2; }
.wf-pay-badge.refunded { background:#F4ECFB; color:#5B2E91; border-color:#DCC4EF; }
.wf-pay-badge.none     { background:#F4F7F9; color:#5A7184; border-color:#DCE4EA; }

.wf-method-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.3rem 0.65rem; border-radius:7px; font-size:0.72rem; font-weight:700; white-space:nowrap; }

.wf-row-actions { display:flex; gap:0.4rem; justify-content:flex-end; }
.wf-icon-btn { width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:white; color:var(--text-secondary); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s; font-size:0.8rem; }
.wf-icon-btn:hover { border-color:var(--primary); color:var(--primary); background:var(--light-blue-pale); }

.wf-empty { text-align:center; padding:3.5rem 1rem; color:var(--text-muted); }
.wf-empty i { font-size:3rem; color:var(--light-blue); margin-bottom:0.9rem; display:block; }
.wf-empty p { margin:0; font-size:0.9rem; font-weight:500; }

/* PAGINATION */
.wf-pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-top:1px solid var(--border-soft); flex-wrap:wrap; gap:0.75rem; }
.wf-pagination-info { font-size:0.8rem; color:var(--text-muted); font-weight:500; }
.wf-pagination { display:flex; gap:0.3rem; align-items:center; }
.wf-page-btn { min-width:36px; height:36px; padding:0 0.6rem; border-radius:8px; border:1px solid var(--border); background:white; color:var(--text-secondary); font-size:0.8rem; font-weight:600; display:inline-flex; align-items:center; justify-content:center; transition:all 0.15s; }
.wf-page-btn:hover:not(.disabled):not(.active) { border-color:var(--primary); color:var(--primary); background:var(--light-blue-pale); }
.wf-page-btn.active { background:var(--dark-blue); color:white; border-color:var(--dark-blue); }
.wf-page-btn.disabled { opacity:0.4; cursor:not-allowed; pointer-events:none; }

/* PANEL */
.wf-panel-overlay { position:fixed; inset:0; background:rgba(6,52,82,0.4); backdrop-filter:blur(2px); opacity:0; visibility:hidden; transition:all 0.3s; z-index:1040; }
.wf-panel-overlay.open { opacity:1; visibility:visible; }
.wf-panel { position:fixed; top:0; right:0; bottom:0; width:600px; max-width:100vw; background:white; z-index:1050; transform:translateX(100%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); display:flex; flex-direction:column; box-shadow:-20px 0 60px rgba(6,52,82,0.15); }
.wf-panel.open { transform:translateX(0); }
.wf-panel-header { padding:1.5rem 1.75rem; border-bottom:1px solid var(--border); display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; background:linear-gradient(135deg, var(--light-blue-pale) 0%, white 100%); }
.wf-panel-title { display:flex; align-items:center; gap:0.9rem; flex:1; min-width:0; }
.wf-panel-title-icon { width:46px; height:46px; border-radius:12px; background:var(--dark-blue); color:var(--yellow); display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.wf-panel-title h3 { font-size:1.05rem; color:var(--dark-blue); margin:0; font-weight:700; }
.wf-panel-title p { font-size:0.78rem; color:var(--text-muted); margin:0.15rem 0 0; font-weight:500; }
.wf-panel-close { width:36px; height:36px; border-radius:9px; border:1px solid var(--border); background:white; color:var(--text-secondary); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s; flex-shrink:0; }
.wf-panel-close:hover { border-color:var(--red); color:var(--red); background:#FDEDEC; }
.wf-panel-body { flex:1; overflow-y:auto; padding:1.5rem 1.75rem; }
.wf-panel-body::-webkit-scrollbar { width:6px; }
.wf-panel-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }
.wf-panel-footer { padding:1.25rem 1.75rem; border-top:1px solid var(--border); background:#FAFCFE; display:flex; gap:0.6rem; flex-wrap:wrap; }

.wf-detail-section { margin-bottom:1.5rem; }
.wf-detail-section-title { font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.75rem; display:flex; align-items:center; gap:0.5rem; }
.wf-detail-section-title i { color:var(--primary); }
.wf-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1rem; }
.wf-detail-item { display:flex; flex-direction:column; gap:0.2rem; }
.wf-detail-item.full { grid-column:1 / -1; }
.wf-detail-label { font-size:0.72rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }
.wf-detail-value { font-size:0.88rem; color:var(--text-primary); font-weight:600; word-break:break-word; }
.wf-detail-value.mono { font-family:'SF Mono',Monaco,monospace; color:var(--primary); }
.wf-detail-value.muted { color:var(--text-secondary); font-weight:500; }
.wf-proof-img { max-width:100%; border-radius:10px; border:1px solid var(--border); margin-top:0.4rem; }

/* MOBILE */
.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:1400px) { .wf-stats-grid { grid-template-columns:repeat(3, 1fr); } }
@media (max-width:1200px) { .wf-stats-grid { grid-template-columns:repeat(2, 1fr); } .wf-panel { width:500px; } }
@media (max-width:1024px) { .wf-layout { grid-template-columns:260px 1fr; } .wf-main { padding:1.5rem; } }
@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
    .wf-stats-grid { grid-template-columns:1fr; }
    .wf-panel { width:100%; }
    .wf-detail-grid { grid-template-columns:1fr; }
}
@media (max-width:600px) {
    .wf-filter-bar { flex-direction:column; align-items:stretch; }
    .wf-filter-group { min-width:100%; }
    .wf-tabs { width:100%; overflow-x:auto; }
}
</style>
</head>
<body>

<button class="wf-sidebar-toggle" id="wfSidebarToggle"><i class="fas fa-bars"></i></button>
<div class="wf-sidebar-overlay" id="wfSidebarOverlay"></div>

<div class="wf-layout">

    <!-- SIDEBAR -->
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
                <span class="wf-sidebar-brand-tag">Admin Panel</span>
            </div>
        </div>

        <nav class="wf-nav">
            <div class="wf-nav-section-label">Overview</div>
            <a href="dashboard.php" class="wf-nav-item">
                <i class="fas fa-chart-pie"></i><span>Dashboard</span>
            </a>

            <div class="wf-nav-section-label">Monitoring</div>
            <a href="admin_bookings.php" class="wf-nav-item">
                <i class="fas fa-eye"></i><span>Bookings Monitor</span>
            </a>
            <a href="admin_payments.php" class="wf-nav-item active">
                <i class="fas fa-receipt"></i><span>Payments Monitor</span>
            </a>
            <a href="admin_tracking.php" class="wf-nav-item">
                <i class="fas fa-route"></i><span>Tracking Monitor</span>
            </a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="customer_management.php" class="wf-nav-item">
                <i class="fas fa-users"></i><span>Customers</span>
            </a>
            <a href="staff_management.php" class="wf-nav-item">
                <i class="fas fa-user-tie"></i><span>Staff</span>
            </a>
            <a href="inventory.php" class="wf-nav-item">
                <i class="fas fa-boxes"></i><span>Inventory</span>
            </a>

            <div class="wf-nav-section-label">Insights</div>
            <a href="reports.php" class="wf-nav-item">
                <i class="fas fa-chart-line"></i><span>Reports</span>
            </a>
            <a href="complaints.php" class="wf-nav-item">
                <i class="fas fa-headset"></i><span>Complaints</span>
            </a>
            <a href="feedback.php" class="wf-nav-item">
                <i class="fas fa-star"></i><span>Feedback</span>
            </a>
            <a href="system_logs.php" class="wf-nav-item">
                <i class="fas fa-history"></i><span>Activity Logs</span>
            </a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e(strtoupper(substr($admin_name, 0, 1))) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($admin_name) ?></div>
                    <div class="wf-user-role">Administrator</div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout">
                <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="wf-main">

        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1><i class="fas fa-receipt" style="color:var(--primary);"></i> Payments <span class="accent">Monitor</span></h1>
                <p>Overview of all payments — read-only.</p>
            </div>
            <div class="wf-topbar-right">
                <span class="wf-role-pill"><i class="fas fa-crown"></i> Admin Monitor Mode</span>
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>

        <?php if ($flash): ?>
        <div id="serverFlash" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" style="display:none;"></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-money-check-alt"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Payments</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['paid']) ?></div>
                    <div class="wf-stat-label">Paid</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-yellow"><i class="fas fa-hourglass-half"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="wf-stat-label">Pending Verification</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-red"><i class="fas fa-times-circle"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['unpaid']) ?></div>
                    <div class="wf-stat-label">Unpaid</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-dark"><i class="fas fa-coins"></i></div></div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($stats['paid_amount'], 0) ?></div>
                    <div class="wf-stat-label">Collected Revenue</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-yellow"><i class="fas fa-clock"></i></div></div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($stats['pending_amount'], 0) ?></div>
                    <div class="wf-stat-label">Pending Amount</div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="wf-tabs">
            <a href="<?= e(build_url(['status' => '', 'page' => 1])) ?>" class="wf-tab <?= $status_f === '' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> All
                <span class="tab-count"><?= number_format($stats['total']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'pending', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'pending' ? 'active' : '' ?>">
                <i class="fas fa-hourglass-half"></i> Pending
                <span class="tab-count"><?= number_format($stats['pending']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'paid', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'paid' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i> Paid
                <span class="tab-count"><?= number_format($stats['paid']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'unpaid', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'unpaid' ? 'active' : '' ?>">
                <i class="fas fa-times-circle"></i> Unpaid
                <span class="tab-count"><?= number_format($stats['unpaid']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'refunded', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'refunded' ? 'active' : '' ?>">
                <i class="fas fa-undo"></i> Refunded
                <span class="tab-count"><?= number_format($stats['refunded']) ?></span>
            </a>
        </div>

        <!-- FILTERS -->
        <form method="get" action="admin_payments.php" class="wf-filter-bar">
            <input type="hidden" name="status" value="<?= e($status_f) ?>">
            <input type="hidden" name="page" value="1">

            <div class="wf-filter-group" style="flex: 1 1 200px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="Payment ID, Booking ID, reference, customer..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 150px;">
                <label>Method</label>
                <select name="method" class="wf-select">
                    <option value="">All Methods</option>
                    <option value="gcash" <?= $method_f === 'gcash' ? 'selected' : '' ?>>GCash</option>
                    <option value="cash"  <?= $method_f === 'cash'  ? 'selected' : '' ?>>Cash</option>
                    <option value="card"  <?= $method_f === 'card'  ? 'selected' : '' ?>>Card</option>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 135px;">
                <label>From</label>
                <input type="date" name="date_from" class="wf-input" value="<?= e($date_from) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 135px;">
                <label>To</label>
                <input type="date" name="date_to" class="wf-input" value="<?= e($date_to) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 auto; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="admin_payments.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>

        <!-- TABLE -->
        <div class="wf-card">
            <?php if (!$has_payments_online): ?>
                <div class="wf-empty">
                    <i class="fas fa-database"></i>
                    <p>Payments table not available</p>
                </div>
            <?php elseif (empty($payments)): ?>
                <div class="wf-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No payments found</p>
                </div>
            <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'id_asc' ? 'id_desc' : 'id_asc', 'page' => 1])) ?>">
                                        Payment <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Customer</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'amount_desc' ? 'amount_asc' : 'amount_desc', 'page' => 1])) ?>">
                                        Amount <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'date_desc' ? 'date_asc' : 'date_desc', 'page' => 1])) ?>">
                                        Date <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p):
                                $pay_key = strtolower(trim((string)$p['payment_status']));
                                if ($pay_key === '') $pay_key = 'pending';
                                $pay_badge = admin_pay_badge($pay_key);

                                $method_key = strtolower(trim((string)$p['payment_method']));
                                $m_badge = method_badge($method_key);

                                $customer_name = trim((string)($p['customer_name'] ?? ''));
                                if ($customer_name === '') $customer_name = 'Unknown Customer';

                                $verified_by = $p['verified_by'] ?? null;
                                $verified_at = $p['verified_at'] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <span class="wf-pay-id">#<?= e($p['payment_id']) ?></span>
                                    <?php if (!empty($p['booking_id'])): ?>
                                        <span class="wf-booking-ref">BK-<?= e($p['booking_id']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="wf-customer"><?= e($customer_name) ?></div>
                                    <?php if (!empty($p['contact_number'])): ?>
                                        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">
                                            <i class="fas fa-phone" style="font-size:0.65rem;"></i> <?= e($p['contact_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="wf-amount">₱<?= number_format((float)$p['amount'], 2) ?></span></td>

                                <td>
                                    <span class="wf-method-badge" style="color:<?= $m_badge['color'] ?>;background:<?= $m_badge['bg'] ?>;">
                                        <i class="fas <?= $m_badge['icon'] ?>" style="font-size:0.65rem;"></i>
                                        <?= e($m_badge['label']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                        $pay_cls = 'none';
                                        if ($pay_key === 'paid')          $pay_cls = 'paid';
                                        elseif ($pay_key === 'unpaid')    $pay_cls = 'unpaid';
                                        elseif ($pay_key === 'refunded')  $pay_cls = 'refunded';
                                        elseif (in_array($pay_key, ['pending','pending_verification'], true)) $pay_cls = 'pending';
                                    ?>
                                    <span class="wf-pay-badge <?= $pay_cls ?>">
                                        <i class="fas <?= e($pay_badge['icon']) ?>"></i>
                                        <?= e($pay_badge['label']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($p['reference_number'])): ?>
                                        <span class="wf-ref"><?= e($p['reference_number']) ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>

                                <td><span class="wf-date"><?= date('M j, Y', strtotime($p['payment_date'])) ?></span></td>

                                <td>
                                    <div class="wf-row-actions">
                                        <button type="button" class="wf-icon-btn" title="View Details"
                                            onclick='openPanel(<?= json_encode([
                                                "payment_id"     => $p["payment_id"],
                                                "booking_id"     => $p["booking_id"],
                                                "customer"       => $customer_name,
                                                "contact"        => $p["contact_number"] ?? "",
                                                "amount"         => (float)$p["amount"],
                                                "method"         => $method_key,
                                                "status"         => $pay_key,
                                                "reference"      => $p["reference_number"] ?? "",
                                                "payment_date"   => $p["payment_date"],
                                                "payment_proof"  => $p["payment_proof"] ?? "",
                                                "notes"          => $p["notes"] ?? "",
                                                "verified_by"    => $verified_by,
                                                "verified_at"    => $verified_at,
                                                "service"        => $p["service"] ?? "",
                                                "booking_status" => $p["booking_status"] ?? "",
                                                "booking_total"  => (float)($p["booking_total"] ?? 0),
                                                "delivery_option"=> $p["delivery_option"] ?? "",
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="wf-pagination-wrap">
                    <div class="wf-pagination-info">
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> payments
                    </div>
                    <div class="wf-pagination">
                        <a href="<?= e(build_url(['page' => max(1, $page - 1)])) ?>" class="wf-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page   = min($total_pages, $page + 2);
                        if ($start_page > 1): ?>
                            <a href="<?= e(build_url(['page' => 1])) ?>" class="wf-page-btn">1</a>
                            <?php if ($start_page > 2): ?><span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pg = $start_page; $pg <= $end_page; $pg++): ?>
                            <a href="<?= e(build_url(['page' => $pg])) ?>" class="wf-page-btn <?= $pg === $page ? 'active' : '' ?>"><?= $pg ?></a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?><span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span><?php endif; ?>
                            <a href="<?= e(build_url(['page' => $total_pages])) ?>" class="wf-page-btn"><?= $total_pages ?></a>
                        <?php endif; ?>

                        <a href="<?= e(build_url(['page' => min($total_pages, $page + 1)])) ?>" class="wf-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- PANEL -->
<div class="wf-panel-overlay" id="panelOverlay" onclick="closePanel()"></div>
<aside class="wf-panel" id="paymentPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-receipt"></i></div>
            <div>
                <h3 id="panelTitle">Payment Details</h3>
                <p id="panelSubtitle">Loading...</p>
            </div>
        </div>
        <button type="button" class="wf-panel-close" onclick="closePanel()"><i class="fas fa-times"></i></button>
    </div>
    <div class="wf-panel-body" id="panelBody"></div>
    <div class="wf-panel-footer" id="panelFooter"></div>
</aside>

<div class="wf-toast-wrap" id="toastWrap" style="position:fixed;top:1.5rem;right:1.5rem;z-index:1070;display:flex;flex-direction:column;gap:0.6rem;"></div>

<script>
function showToast(type, message) {
    const wrap = document.getElementById('toastWrap');
    const toast = document.createElement('div');
    toast.style.cssText = 'padding:0.9rem 1.15rem;background:white;border-radius:11px;box-shadow:0 10px 30px rgba(6,52,82,0.15);border-left:4px solid ' + (type === 'success' ? '#27AE60' : '#E74C3C') + ';font-size:0.85rem;font-weight:600;color:#063452;min-width:260px;max-width:380px;display:flex;align-items:center;gap:0.75rem;';
    toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '" style="color:' + (type === 'success' ? '#27AE60' : '#E74C3C') + ';"></i><span>' + message + '</span>';
    wrap.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}

(function() {
    const flash = document.getElementById('serverFlash');
    if (flash) showToast(flash.dataset.type, flash.dataset.message);
})();

const panelOverlay = document.getElementById('panelOverlay');
const paymentPanel = document.getElementById('paymentPanel');

function openPanel(data) {
    const payMap = {
        'paid':                 { color:'#1E7E45', bg:'#EAF7F0', label:'Paid' },
        'pending':              { color:'#946200', bg:'#FEF9E7', label:'Pending Verification' },
        'pending_verification': { color:'#946200', bg:'#FEF9E7', label:'Pending Verification' },
        'unpaid':               { color:'#A8322D', bg:'#FDEDEC', label:'Unpaid' },
        'refunded':             { color:'#5B2E91', bg:'#F4ECFB', label:'Refunded' },
        'failed':               { color:'#A8322D', bg:'#FDEDEC', label:'Failed' },
    };
    const pay = payMap[data.status] || { color:'#5A7184', bg:'#F4F7F9', label:data.status };

    const methodMap = {
        'gcash': { color:'#00537A', bg:'#EBF5FB', label:'GCash', icon:'fa-mobile-alt' },
        'cash':  { color:'#1E7E45', bg:'#EAF7F0', label:'Cash',  icon:'fa-money-bill-wave' },
        'card':  { color:'#5B2E91', bg:'#F4ECFB', label:'Card',  icon:'fa-credit-card' },
    };
    const method = methodMap[data.method] || { color:'#5A7184', bg:'#F4F7F9', label:data.method || '—', icon:'fa-question' };

    document.getElementById('panelTitle').textContent = 'Payment #' + data.payment_id;
    document.getElementById('panelSubtitle').textContent = 'Booking #' + (data.booking_id || '—') + ' • ' + formatDate(data.payment_date);

    let html = '';

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-info-circle"></i> Payment Status</div>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            <span class="wf-pay-badge ${data.status === 'paid' ? 'paid' : (data.status === 'unpaid' ? 'unpaid' : (data.status === 'refunded' ? 'refunded' : 'pending'))}" style="font-size:0.8rem;padding:0.5rem 0.9rem;">
                ${pay.label}
            </span>
            <span class="wf-method-badge" style="color:${method.color};background:${method.bg};font-size:0.8rem;padding:0.5rem 0.9rem;">
                <i class="fas ${method.icon}"></i> ${method.label}
            </span>
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-user"></i> Customer</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Name</div>
                <div class="wf-detail-value">${escapeHtml(data.customer)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Contact</div>
                <div class="wf-detail-value">${escapeHtml(data.contact) || '—'}</div>
            </div>
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-money-check-alt"></i> Payment Info</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Amount</div>
                <div class="wf-detail-value" style="color:var(--dark-blue);font-size:1.05rem;">₱${parseFloat(data.amount).toFixed(2)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Date</div>
                <div class="wf-detail-value">${formatDate(data.payment_date)}</div>
            </div>
            ${data.reference ? `
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Reference No.</div>
                <div class="wf-detail-value mono">${escapeHtml(data.reference)}</div>
            </div>` : ''}
            ${data.notes ? `
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Notes</div>
                <div class="wf-detail-value muted">${escapeHtml(data.notes)}</div>
            </div>` : ''}
        </div>
    </div>`;

    if (data.service || data.booking_status) {
        html += `
        <div class="wf-detail-section">
            <div class="wf-detail-section-title"><i class="fas fa-soap"></i> Booking Info</div>
            <div class="wf-detail-grid">
                ${data.service ? `
                <div class="wf-detail-item full">
                    <div class="wf-detail-label">Service</div>
                    <div class="wf-detail-value">${escapeHtml(data.service)}</div>
                </div>` : ''}
                ${data.booking_status ? `
                <div class="wf-detail-item">
                    <div class="wf-detail-label">Booking Status</div>
                    <div class="wf-detail-value">${escapeHtml(data.booking_status)}</div>
                </div>` : ''}
                ${data.booking_total ? `
                <div class="wf-detail-item">
                    <div class="wf-detail-label">Booking Total</div>
                    <div class="wf-detail-value">₱${parseFloat(data.booking_total).toFixed(2)}</div>
                </div>` : ''}
                ${data.delivery_option ? `
                <div class="wf-detail-item full">
                    <div class="wf-detail-label">Delivery Option</div>
                    <div class="wf-detail-value muted">${escapeHtml(data.delivery_option)}</div>
                </div>` : ''}
            </div>
        </div>`;
    }

    if (data.payment_proof) {
        html += `
        <div class="wf-detail-section">
            <div class="wf-detail-section-title"><i class="fas fa-image"></i> Payment Proof</div>
            <img src="${escapeHtml(data.payment_proof)}" alt="Payment Proof" class="wf-proof-img" onerror="this.style.display='none'; this.insertAdjacentHTML('afterend','<p style=\\'font-size:0.82rem;color:var(--text-muted);\\'>Proof file not found.</p>');">
        </div>`;
    }

    if (data.verified_by || data.verified_at) {
        html += `
        <div class="wf-detail-section">
            <div class="wf-detail-section-title"><i class="fas fa-user-check"></i> Verification</div>
            <div class="wf-detail-grid">
                ${data.verified_by ? `
                <div class="wf-detail-item">
                    <div class="wf-detail-label">Verified By</div>
                    <div class="wf-detail-value">${escapeHtml(data.verified_by)}</div>
                </div>` : ''}
                ${data.verified_at ? `
                <div class="wf-detail-item">
                    <div class="wf-detail-label">Verified At</div>
                    <div class="wf-detail-value">${formatDate(data.verified_at)}</div>
                </div>` : ''}
            </div>
        </div>`;
    }

    document.getElementById('panelBody').innerHTML = html;

    panelOverlay.classList.add('open');
    paymentPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    panelOverlay.classList.remove('open');
    paymentPanel.classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePanel();
});

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    let h = d.getHours();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    const min = String(d.getMinutes()).padStart(2, '0');
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + ' • ' + h + ':' + min + ' ' + ampm;
}

const sidebar = document.getElementById('wfSidebar');
const sidebarOverlay = document.getElementById('wfSidebarOverlay');
const sidebarToggle = document.getElementById('wfSidebarToggle');

sidebarToggle.addEventListener('click', function() {
    sidebar.classList.add('open');
    sidebarOverlay.classList.add('open');
});
sidebarOverlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('open');
});
</script>
</body>
</html>