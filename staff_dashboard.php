<?php
/**
 * staff_dashboard.php
 * WashFlow — Staff Dashboard
 *
 * ✅ Staff-only (auto-redirect admin → dashboard.php, guest → staff_login.php)
 * ✅ My Queue overview (walk-in + online pending)
 * ✅ Payments to verify counter
 * ✅ Today's shift stats (completed today, items processed)
 * ✅ Recent activity (latest bookings needing action)
 * ✅ Quick actions
 *
 * NOTE: Hero card + shift pill removed (per request)
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* ══════════════════════════════════════════
   AUTH — STAFF ONLY
   ══════════════════════════════════════════ */
if (empty($_SESSION['staff_id'])) {
    if (!empty($_SESSION['admin_id'])) {
        header('Location: dashboard.php');
        exit();
    }
    header('Location: staff_login.php');
    exit();
}

$staff_id      = (int)$_SESSION['staff_id'];
$staff_name    = $_SESSION['staff_name'] ?? $_SESSION['staff_username'] ?? 'Staff';
$staff_role    = $_SESSION['staff_role'] ?? 'washer';
$staff_shift   = $_SESSION['staff_shift'] ?? 'morning';
$staff_initial = strtoupper(substr($staff_name, 0, 1));

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

function staff_status_badge($status) {
    $s = strtolower(trim((string)$status));
    switch ($s) {
        case 'pending':     return ['label' => 'Pending',     'color' => '#946200', 'bg' => '#FEF9E7', 'icon' => 'fa-hourglass-half'];
        case 'confirmed':   return ['label' => 'Confirmed',   'color' => '#00537A', 'bg' => '#EBF5FB', 'icon' => 'fa-check'];
        case 'in progress': return ['label' => 'In Progress', 'color' => '#5B2E91', 'bg' => '#F4ECFB', 'icon' => 'fa-spinner'];
        case 'processing':  return ['label' => 'Processing',  'color' => '#5B2E91', 'bg' => '#F4ECFB', 'icon' => 'fa-spinner'];
        case 'ready':       return ['label' => 'Ready',       'color' => '#946200', 'bg' => '#FFF9DB', 'icon' => 'fa-box-open'];
        case 'completed':   return ['label' => 'Completed',   'color' => '#1E7E45', 'bg' => '#EAF7F0', 'icon' => 'fa-check-double'];
        case 'cancelled':   return ['label' => 'Cancelled',   'color' => '#A8322D', 'bg' => '#FDEDEC', 'icon' => 'fa-times-circle'];
        default:            return ['label' => ucfirst($s),   'color' => '#5A7184', 'bg' => '#F4F7F9', 'icon' => 'fa-circle'];
    }
}

/* Role labels */
$role_meta = [
    'washer'     => ['label' => 'Washer',     'icon' => 'fa-soap'],
    'dryer'      => ['label' => 'Dryer',      'icon' => 'fa-wind'],
    'ironing'    => ['label' => 'Ironing',    'icon' => 'fa-tshirt'],
    'packer'     => ['label' => 'Packer',     'icon' => 'fa-box'],
    'cashier'    => ['label' => 'Cashier',    'icon' => 'fa-cash-register'],
    'supervisor' => ['label' => 'Supervisor', 'icon' => 'fa-user-shield'],
];
$my_role = $role_meta[$staff_role] ?? $role_meta['washer'];

/* ══════════════════════════════════════════
   CHECK PAYMENTS TABLE
   ══════════════════════════════════════════ */
$has_payments = false;
if ($res = $conn->query("SHOW TABLES LIKE 'payments_online'")) {
    $has_payments = ($res->num_rows > 0);
    $res->free();
}

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = [
    'walkin_pending'    => 0,
    'walkin_progress'   => 0,
    'walkin_ready'      => 0,
    'walkin_completed'  => 0,
    'online_pending'    => 0,
    'online_processing' => 0,
    'online_ready'      => 0,
    'online_completed'  => 0,
    'pay_pending'       => 0,
    'pay_paid_today'    => 0,
    'completed_today'   => 0,
    'collected_today'   => 0.0,
];

try {
    /* Walk-in */
    $q = $conn->query("SELECT COUNT(DISTINCT Booking_ID) c FROM booking WHERE status='Pending'");
    if ($q) $stats['walkin_pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT Booking_ID) c FROM booking WHERE status IN ('Confirmed','In Progress')");
    if ($q) $stats['walkin_progress'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT Booking_ID) c FROM booking WHERE status='Completed' AND DATE(booking_date)=CURDATE()");
    if ($q) $stats['walkin_completed'] = (int)$q->fetch_assoc()['c'];

    /* Online */
    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Pending' OR status=''");
    if ($q) $stats['online_pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Processing'");
    if ($q) $stats['online_processing'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Ready'");
    if ($q) $stats['online_ready'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Completed' AND DATE(timestamp)=CURDATE()");
    if ($q) $stats['online_completed'] = (int)$q->fetch_assoc()['c'];

    /* Payments */
    if ($has_payments) {
        $q = $conn->query("SELECT COUNT(*) c FROM payments_online WHERE LOWER(payment_status) IN ('pending','pending_verification')");
        if ($q) $stats['pay_pending'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("
            SELECT COUNT(*) c, COALESCE(SUM(amount),0) s
            FROM payments_online
            WHERE LOWER(payment_status)='paid' AND DATE(payment_date)=CURDATE()
        ");
        if ($q) {
            $row = $q->fetch_assoc();
            $stats['pay_paid_today'] = (int)$row['c'];
            $stats['collected_today'] = (float)$row['s'];
        }
    }

    $stats['completed_today'] = $stats['walkin_completed'] + $stats['online_completed'];

} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Staff dashboard stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   RECENT BOOKINGS NEEDING ACTION
   ══════════════════════════════════════════ */
$recent = [];

try {
    $q = $conn->query("
        SELECT
            b.Booking_ID AS id,
            'walkin' AS source,
            b.service,
            b.status,
            b.total_amount,
            b.booking_date AS date_col,
            CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) AS customer_name,
            c.contact_number
        FROM booking b
        LEFT JOIN customer_info c ON c.Customer_ID = b.Customer_ID
        WHERE b.status IN ('Pending','Confirmed','In Progress')
        GROUP BY b.Booking_ID
        ORDER BY b.booking_date DESC
        LIMIT 5
    ");
    if ($q) {
        while ($r = $q->fetch_assoc()) $recent[] = $r;
    }

    $q = $conn->query("
        SELECT
            b.id AS id,
            'online' AS source,
            b.service,
            b.status,
            b.total_amount,
            b.timestamp AS date_col,
            b.customer_name,
            b.contact_number
        FROM booking_online b
        WHERE b.status IN ('Pending','Processing','Ready')
           OR b.status = '' OR b.status IS NULL
        ORDER BY b.timestamp DESC
        LIMIT 5
    ");
    if ($q) {
        while ($r = $q->fetch_assoc()) $recent[] = $r;
    }

    usort($recent, function($a, $b) {
        return strtotime($b['date_col']) - strtotime($a['date_col']);
    });
    $recent = array_slice($recent, 0, 8);

} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Recent failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   PAYMENTS TO VERIFY (Top 5)
   ══════════════════════════════════════════ */
$pending_payments = [];
if ($has_payments) {
    try {
        $q = $conn->query("
            SELECT
                p.payment_id,
                p.booking_id,
                p.amount,
                p.payment_method,
                p.reference_number,
                p.payment_date,
                b.customer_name,
                b.service
            FROM payments_online p
            LEFT JOIN booking_online b ON b.id = p.booking_id
            WHERE LOWER(p.payment_status) IN ('pending','pending_verification')
            ORDER BY p.payment_date DESC
            LIMIT 5
        ");
        if ($q) $pending_payments = $q->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Pending payments failed', ['error' => $ex->getMessage()]);
    }
}

/* ══════════════════════════════════════════
   GREETING
   ══════════════════════════════════════════ */
$hour = (int)date('H');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 18)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard — WashFlow</title>

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

/* SIDEBAR */
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

/* MAIN */
.wf-main { padding:2rem 2.5rem 3rem; overflow-y:auto; overflow-x:hidden; height:100vh; scrollbar-width:none; }
.wf-main::-webkit-scrollbar { display:none; width:0; }

/* TOPBAR */
.wf-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; gap:1rem; flex-wrap:wrap; }
.wf-topbar-greeting h1 { color:var(--dark-blue); font-size:1.6rem; font-weight:800; margin:0; letter-spacing:-0.03em; line-height:1.3; }
.wf-topbar-greeting h1 .accent { color:var(--gold); }
.wf-topbar-greeting p { color:var(--text-secondary); font-size:0.88rem; margin:0.2rem 0 0; font-weight:500; }
.wf-topbar-right { display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; }
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

/* STATS */
.wf-stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; margin-bottom:1.5rem; }
.wf-stat-card {
    background:white; border-radius:14px; padding:1.25rem;
    border:1px solid var(--border); transition:all 0.25s ease;
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
.wf-stat-icon.icon-gold   { background:#FFF4CC; color:#8A6400; }
.wf-stat-value { font-size:1.6rem; font-weight:800; color:var(--dark-blue); line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.2rem; }
.wf-stat-label { font-size:0.78rem; color:var(--text-secondary); font-weight:500; }

/* GRID 2 COL */
.wf-grid-2 { display:grid; grid-template-columns:1.5fr 1fr; gap:1.25rem; margin-bottom:1.25rem; }
@media (max-width:1100px) { .wf-grid-2 { grid-template-columns:1fr; } }

/* CARD */
.wf-card {
    background:white; border-radius:14px;
    border:1px solid var(--border); overflow:hidden;
    margin-bottom:1.25rem;
}
.wf-card:last-child { margin-bottom:0; }
.wf-card-head {
    padding:1.1rem 1.5rem; border-bottom:1px solid var(--border-soft);
    display:flex; align-items:center; justify-content:space-between;
    gap:1rem; flex-wrap:wrap;
}
.wf-card-title { display:flex; align-items:center; gap:0.9rem; }
.wf-card-title-icon {
    width:42px; height:42px; border-radius:11px;
    background:var(--light-blue-soft); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; flex-shrink:0;
}
.wf-card-title-icon.gold  { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-card-title-icon.green { background:#EAF7F0; color:#1E7E45; }
.wf-card-title-icon.warn  { background:#FEF3E2; color:#C2410C; }
.wf-card-title h3 { font-size:1rem; font-weight:700; color:var(--dark-blue); margin:0; }
.wf-card-title p { font-size:0.75rem; color:var(--text-muted); margin:0.1rem 0 0; font-weight:500; }
.wf-card-link {
    display:inline-flex; align-items:center; gap:0.4rem;
    color:var(--primary); font-weight:600; font-size:0.8rem;
    padding:0.4rem 0.75rem; border-radius:7px; transition:all 0.2s;
}
.wf-card-link:hover { background:var(--light-blue-pale); color:var(--primary-mid); }
.wf-card-body { padding:1.25rem 1.5rem; }

/* TABLE */
.wf-table-wrap { overflow-x:auto; }
.wf-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.wf-table thead th {
    color:var(--text-muted); font-weight:600; font-size:0.68rem;
    text-transform:uppercase; letter-spacing:0.08em;
    padding:0.75rem 0.9rem; text-align:left;
    border-bottom:1px solid var(--border); white-space:nowrap;
    background:#FAFCFE;
}
.wf-table tbody td { padding:0.85rem 0.9rem; border-bottom:1px solid var(--border-soft); vertical-align:middle; }
.wf-table tbody tr:last-child td { border-bottom:none; }
.wf-table tbody tr:hover { background:var(--light-blue-pale); }

.wf-booking-id { font-weight:700; color:var(--primary); font-family:'SF Mono',Monaco,monospace; font-size:0.78rem; }
.wf-source-tag { display:inline-flex; align-items:center; gap:0.3rem; padding:0.15rem 0.5rem; border-radius:5px; font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-left:0.3rem; }
.wf-source-tag.walkin { background:#EBF5FB; color:#00537A; }
.wf-source-tag.online { background:#F4ECFB; color:#5B2E91; }
.wf-customer { font-weight:600; color:var(--dark-blue); font-size:0.85rem; }
.wf-service { color:var(--text-secondary); font-size:0.82rem; }

.wf-badge {
    display:inline-flex; align-items:center; gap:0.35rem;
    padding:0.3rem 0.65rem; border-radius:7px;
    font-size:0.7rem; font-weight:600; white-space:nowrap;
}
.wf-badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

.wf-icon-btn {
    width:30px; height:30px; border-radius:8px;
    border:1px solid var(--border); background:white;
    color:var(--text-secondary);
    display:inline-flex; align-items:center; justify-content:center;
    cursor:pointer; transition:all 0.15s; font-size:0.75rem;
}
.wf-icon-btn:hover { border-color:var(--primary); color:var(--primary); background:var(--light-blue-pale); }

/* QUICK ACTIONS */
.wf-actions-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; }
@media (max-width:1100px) { .wf-actions-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width:600px) { .wf-actions-grid { grid-template-columns:1fr; } }

.wf-action-tile {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:0.7rem; padding:1.35rem 0.875rem;
    border:1px solid var(--border); border-radius:12px;
    background:white; color:var(--dark-blue);
    font-weight:600; font-size:0.82rem;
    transition:all 0.2s ease; text-align:center;
    min-height:130px;
}
.wf-action-tile:hover {
    border-color:var(--primary); background:var(--light-blue-pale);
    color:var(--primary); transform:translateY(-3px);
    box-shadow:0 6px 20px rgba(0,83,122,0.08);
}
.wf-action-tile-icon {
    width:48px; height:48px; border-radius:12px;
    background:var(--light-blue-soft); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; transition:all 0.25s;
}
.wf-action-tile:hover .wf-action-tile-icon {
    background:var(--yellow); color:var(--dark-blue-deep);
    transform:scale(1.05);
}
.wf-action-tile-icon.warn { background:#FEF3E2; color:#C2410C; }
.wf-action-tile:hover .wf-action-tile-icon.warn {
    background:#FFD9A0; color:#7A3400;
}

/* EMPTY STATE */
.wf-empty-mini { text-align:center; padding:2.25rem 1rem; color:var(--text-muted); }
.wf-empty-mini i { font-size:2.25rem; color:var(--light-blue); margin-bottom:0.65rem; display:block; }
.wf-empty-mini p { margin:0; font-size:0.85rem; font-weight:500; }

/* MOBILE */
.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:1200px) { .wf-stats-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width:1024px) { .wf-layout { grid-template-columns:260px 1fr; } .wf-main { padding:1.5rem; } }
@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
    .wf-stats-grid { grid-template-columns:1fr; }
}
</style>
</head>
<body>

<button class="wf-sidebar-toggle" id="wfSidebarToggle"><i class="fas fa-bars"></i></button>
<div class="wf-sidebar-overlay" id="wfSidebarOverlay"></div>

<div class="wf-layout">

    <!-- SIDEBAR (STAFF) -->
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
            <a href="staff_dashboard.php" class="wf-nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="booking_management.php" class="wf-nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>My Queue</span>
            </a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="staff_walkin.php" class="wf-nav-item">
                <i class="fas fa-walking"></i>
                <span>Walk-in Booking</span>
            </a>
            <a href="staff_payments.php" class="wf-nav-item">
                <i class="fas fa-money-check-alt"></i>
                <span>Verify Payments</span>
            </a>
            <a href="staff_inventory.php" class="wf-nav-item">
                <i class="fas fa-flask"></i>
                <span>Log Inventory</span>
            </a>

            <div class="wf-nav-section-label">Support</div>
            <a href="staff_feedback.php" class="wf-nav-item">
                <i class="fas fa-star"></i>
                <span>Reply Feedback</span>
            </a>
            <a href="staff_complaints.php" class="wf-nav-item">
                <i class="fas fa-headset"></i>
                <span>Handle Complaints</span>
            </a>

            <div class="wf-nav-section-label">Account</div>
            <a href="staff_shift.php" class="wf-nav-item">
                <i class="fas fa-clock"></i>
                <span>My Shift</span>
            </a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e($staff_initial) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($staff_name) ?></div>
                    <div class="wf-user-role"><?= e($my_role['label']) ?></div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="wf-main">

        <!-- TOPBAR (Hero + Shift Pill removed) -->
        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1><?= e($greeting) ?>, <span class="accent"><?= e(explode(' ', $staff_name)[0]) ?></span></h1>
                <p>Here's your work summary for today</p>
            </div>
            <div class="wf-topbar-right">
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-hourglass-half"></i></div>
                    <?php if ($stats['walkin_pending'] + $stats['online_pending'] > 0): ?>
                    <span class="wf-badge" style="color:#946200;background:#FEF9E7;">
                        <span class="wf-badge-dot"></span> Action
                    </span>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['walkin_pending'] + $stats['online_pending']) ?></div>
                    <div class="wf-stat-label">Pending in Queue</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-purple"><i class="fas fa-spinner"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['walkin_progress'] + $stats['online_processing']) ?></div>
                    <div class="wf-stat-label">In Progress</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['completed_today']) ?></div>
                    <div class="wf-stat-label">Completed Today</div>
                </div>
            </div>

            <div class="wf-stat-card" style="border-color:#F5E6A8;">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-gold"><i class="fas fa-money-check-alt"></i></div>
                    <?php if ($stats['pay_pending'] > 0): ?>
                    <span class="wf-badge" style="color:#946200;background:#FEF9E7;">
                        <span class="wf-badge-dot"></span> Needs action
                    </span>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pay_pending']) ?></div>
                    <div class="wf-stat-label">Payments to Verify</div>
                </div>
            </div>
        </div>

        <!-- GRID: QUEUE + PAYMENTS -->
        <div class="wf-grid-2">

            <!-- MY QUEUE -->
            <div class="wf-card">
                <div class="wf-card-head">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div>
                            <h3>My Queue</h3>
                            <p>Bookings needing your attention</p>
                        </div>
                    </div>
                    <a href="booking_management.php" class="wf-card-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($recent)): ?>
                    <div class="wf-empty-mini">
                        <i class="fas fa-check-circle"></i>
                        <p>No pending items in your queue. Great job! 🎉</p>
                    </div>
                <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $r):
                                $b = staff_status_badge($r['status'] ?: 'Pending');
                                $is_walkin = ($r['source'] === 'walkin');
                                $customer_name = trim($r['customer_name'] ?: '');
                                if ($customer_name === '') $customer_name = $is_walkin ? 'Walk-in' : 'Online Customer';
                            ?>
                            <tr>
                                <td>
                                    <span class="wf-booking-id">#<?= e($r['id']) ?></span>
                                    <span class="wf-source-tag <?= $is_walkin ? 'walkin' : 'online' ?>">
                                        <?= $is_walkin ? 'Walk-in' : 'Online' ?>
                                    </span>
                                </td>
                                <td><span class="wf-customer"><?= e($customer_name) ?></span></td>
                                <td><span class="wf-service"><?= e($r['service'] ?: '-') ?></span></td>
                                <td>
                                    <span class="wf-badge" style="color:<?= $b['color'] ?>;background:<?= $b['bg'] ?>;">
                                        <i class="fas <?= $b['icon'] ?>" style="font-size:0.62rem;"></i>
                                        <?= e($b['label']) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <a href="booking_management.php?tab=<?= $is_walkin ? 'walkin' : 'online' ?>&q=<?= urlencode($r['id']) ?>"
                                       class="wf-icon-btn" title="Open in Queue">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- PAYMENTS TO VERIFY -->
            <div class="wf-card">
                <div class="wf-card-head">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon gold"><i class="fas fa-money-check-alt"></i></div>
                        <div>
                            <h3>Payments to Verify</h3>
                            <p>Verify customer payments</p>
                        </div>
                    </div>
                    <a href="booking_management.php?tab=online&payment=pending" class="wf-card-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($pending_payments)): ?>
                    <div class="wf-empty-mini">
                        <i class="fas fa-check-circle"></i>
                        <p>No pending payments. All clear! 🎉</p>
                    </div>
                <?php else: ?>
                <div class="wf-card-body">
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <?php foreach ($pending_payments as $p): ?>
                        <a href="booking_management.php?tab=online&payment=verify&id=<?= (int)$p['payment_id'] ?>"
                           style="display:flex;align-items:center;gap:0.85rem;padding:0.85rem 1rem;background:#FEF9E7;border:1px solid #F5E6A8;border-radius:11px;text-decoration:none;transition:all 0.15s;"
                           onmouseover="this.style.background='#FCEFC4';this.style.transform='translateY(-1px)';"
                           onmouseout="this.style.background='#FEF9E7';this.style.transform='translateY(0)';">
                            <div style="width:38px;height:38px;border-radius:10px;background:#FFF3B0;color:#8A6400;display:flex;align-items:center;justify-content:center;font-size:0.95rem;flex-shrink:0;">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div style="flex:1;min-width:0;line-height:1.3;">
                                <div style="font-weight:700;color:var(--dark-blue);font-size:0.85rem;">
                                    Booking #<?= e($p['booking_id']) ?>
                                </div>
                                <div style="font-size:0.72rem;color:var(--text-muted);font-weight:500;">
                                    <?= e($p['payment_method'] ?: 'Payment') ?>
                                    <?php if (!empty($p['reference_number'])): ?>
                                        • <?= e($p['reference_number']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="text-align:right;flex-shrink:0;">
                                <div style="font-weight:800;color:var(--dark-blue);font-size:0.9rem;">
                                    ₱<?= number_format((float)$p['amount'], 2) ?>
                                </div>
                                <div style="font-size:0.65rem;color:#8A6400;font-weight:700;">
                                    VERIFY →
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- QUICK ACTIONS -->
        <div class="wf-card">
            <div class="wf-card-head">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <h3>Quick Actions</h3>
                        <p>Common daily tasks</p>
                    </div>
                </div>
            </div>

            <div class="wf-card-body">
                <div class="wf-actions-grid">
                    <a href="booking_management.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-clipboard-list"></i></div>
                        <span>My Queue</span>
                    </a>
                    <a href="staff_walkin.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-walking"></i></div>
                        <span>New Walk-in</span>
                    </a>
                    <a href="booking_management.php?tab=online&payment=pending" class="wf-action-tile">
                        <div class="wf-action-tile-icon warn"><i class="fas fa-money-check-alt"></i></div>
                        <span>Verify Payments</span>
                    </a>
                    <a href="staff_inventory.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-flask"></i></div>
                        <span>Log Inventory</span>
                    </a>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
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