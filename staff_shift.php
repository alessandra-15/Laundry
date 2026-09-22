<?php
/**
 * staff_shift.php
 * WashFlow — My Shift
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
   KUNIN ANG EMPLOYEE RECORD (match by name)
   ────────────────────────────────────────── */
$employee = null;
try {
    $stmt = $conn->prepare("
        SELECT id, name, role, status, contact
        FROM employees
        WHERE name = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $staff_name);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {}

$employee_id     = $employee ? (int)$employee['id'] : 0;
$employee_status = $employee['status'] ?? 'Active';
$employee_role   = $employee['role']   ?? $my_role['label'];
$employee_contact= $employee['contact'] ?? '—';

/* ──────────────────────────────────────────
   KUNIN ANG RECENT SALARY RECORDS
   ────────────────────────────────────────── */
$salaries      = [];
$latest_salary = null;
$total_salary  = 0.0;
$total_days    = 0;

if ($employee_id > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT salary_id, employee_id, days_worked, total_salary, salary_date
            FROM employee_salaries
            WHERE employee_id = ?
            ORDER BY salary_date DESC, salary_id DESC
            LIMIT 12
        ");
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $salaries[] = $r;
        $stmt->close();

        if (!empty($salaries)) $latest_salary = $salaries[0];

        $q = $conn->prepare("SELECT COALESCE(SUM(total_salary),0) s, COALESCE(SUM(days_worked),0) d FROM employee_salaries WHERE employee_id = ?");
        $q->bind_param('i', $employee_id);
        $q->execute();
        $row = $q->get_result()->fetch_assoc();
        $total_salary = (float)$row['s'];
        $total_days   = (int)$row['d'];
        $q->close();
    } catch (Exception $e) {}
}

/* ──────────────────────────────────────────
   STATS — Staff bookings
   ────────────────────────────────────────── */
$shift_stats = [
    'today_bookings'  => 0,
    'today_completed' => 0,
    'today_pending'   => 0,
    'week_bookings'   => 0,
    'month_bookings'  => 0,
    'total_bookings'  => 0,
];

try {
    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE DATE(booking_date) = CURDATE()");
    if ($q) $shift_stats['today_bookings'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE DATE(booking_date) = CURDATE() AND status = 'Completed'");
    if ($q) $shift_stats['today_completed'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE DATE(booking_date) = CURDATE() AND status = 'Pending'");
    if ($q) $shift_stats['today_pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)");
    if ($q) $shift_stats['week_bookings'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())");
    if ($q) $shift_stats['month_bookings'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking");
    if ($q) $shift_stats['total_bookings'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $e) {}

/* ──────────────────────────────────────────
   SHIFT SCHEDULE (template base sa role)
   ────────────────────────────────────────── */
$shift_templates = [
    'washer'     => ['start' => '07:00', 'end' => '16:00', 'label' => 'Morning Shift'],
    'dryer'      => ['start' => '08:00', 'end' => '17:00', 'label' => 'Morning Shift'],
    'ironing'    => ['start' => '09:00', 'end' => '18:00', 'label' => 'Mid Shift'],
    'packer'     => ['start' => '10:00', 'end' => '19:00', 'label' => 'Mid Shift'],
    'cashier'    => ['start' => '08:00', 'end' => '17:00', 'label' => 'Morning Shift'],
    'supervisor' => ['start' => '07:00', 'end' => '16:00', 'label' => 'Morning Shift'],
];
$my_shift = $shift_templates[$staff_role] ?? $shift_templates['washer'];

$now       = new DateTime();
$shift_in  = DateTime::createFromFormat('H:i', $my_shift['start']);
$shift_out = DateTime::createFromFormat('H:i', $my_shift['end']);
$on_duty   = ($now >= $shift_in && $now <= $shift_out);

/* Progress ng shift (0-100%) */
$shift_total   = $shift_out->getTimestamp() - $shift_in->getTimestamp();
$shift_elapsed = max(0, min($shift_total, $now->getTimestamp() - $shift_in->getTimestamp()));
$shift_progress = $shift_total > 0 ? round(($shift_elapsed / $shift_total) * 100) : 0;
if ($now < $shift_in)  $shift_progress = 0;
if ($now > $shift_out) $shift_progress = 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Shift — WashFlow Staff</title>
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
body { font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    color:var(--text-primary); background:var(--bg); font-size:14px; line-height:1.6;
    -webkit-font-smoothing:antialiased; overflow:hidden; }
h1,h2,h3,h4,h5 { font-weight:700; letter-spacing:-0.02em; }
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
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }
.wf-card-head { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.wf-card-title { display:flex; align-items:center; gap:0.9rem; }
.wf-card-title-icon { width:42px; height:42px; border-radius:11px; background:var(--light-blue-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-card-title h3 { font-size:1rem; font-weight:700; color:var(--dark-blue); margin:0; }
.wf-card-title p { font-size:0.75rem; color:var(--text-muted); margin:0.1rem 0 0; font-weight:500; }
.wf-card-body { padding:1.5rem; }

/* SHIFT BANNER */
.wf-shift-banner {
    border-radius:16px; padding:1.75rem 2rem; margin-bottom:1.5rem;
    display:flex; align-items:center; gap:1.5rem;
    background:linear-gradient(135deg, var(--dark-blue) 0%, var(--primary) 100%);
    color:white; position:relative; overflow:hidden;
}
.wf-shift-banner::before {
    content:''; position:absolute; right:-50px; top:-50px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(255,217,61,0.08);
}
.wf-shift-banner.off {
    background:linear-gradient(135deg, #4A5568 0%, #2D3748 100%);
}
.wf-shift-icon {
    width:80px; height:80px; border-radius:20px;
    background:rgba(255,255,255,0.12);
    display:flex; align-items:center; justify-content:center;
    font-size:2.2rem; color:var(--yellow);
    flex-shrink:0; position:relative; z-index:1;
    border:1px solid rgba(255,217,61,0.3);
}
.wf-shift-info { flex:1; position:relative; z-index:1; min-width:0; }
.wf-shift-label { font-size:0.75rem; color:rgba(255,255,255,0.7); text-transform:uppercase; letter-spacing:0.15em; font-weight:700; margin-bottom:0.35rem; }
.wf-shift-title { font-size:1.6rem; font-weight:800; color:white; letter-spacing:-0.03em; margin-bottom:0.35rem; }
.wf-shift-time { font-size:1rem; color:rgba(255,255,255,0.9); font-weight:600; }
.wf-shift-badge {
    display:inline-flex; align-items:center; gap:0.5rem;
    padding:0.5rem 1rem; border-radius:20px;
    font-weight:800; font-size:0.8rem;
    position:relative; z-index:1;
}
.wf-shift-badge.on  { background:rgba(39,174,96,0.25); color:#6EE7A0; border:1px solid rgba(110,231,160,0.3); }
.wf-shift-badge.off { background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.15); }

.wf-shift-progress {
    margin-top:1rem; height:6px; background:rgba(255,255,255,0.15);
    border-radius:3px; overflow:hidden; position:relative; z-index:1;
}
.wf-shift-progress-bar {
    height:100%; background:linear-gradient(90deg, var(--yellow) 0%, #FFB800 100%);
    border-radius:3px; transition:width 0.5s ease;
}

/* STATS */
.wf-stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; margin-bottom:1.5rem; }
@media (max-width:1100px) { .wf-stats-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width:600px)  { .wf-stats-grid { grid-template-columns:1fr; } }
.wf-stat-card { background:white; border-radius:14px; padding:1.25rem; border:1px solid var(--border); min-height:110px; display:flex; flex-direction:column; justify-content:space-between; }
.wf-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.6rem; }
.wf-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; }
.wf-stat-icon.icon-blue  { background:var(--light-blue-soft); color:var(--primary); }
.wf-stat-icon.icon-green { background:#EAF7F0; color:#1E7E45; }
.wf-stat-icon.icon-gold  { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-stat-icon.icon-red   { background:#FDEDEC; color:#A8322D; }
.wf-stat-value { font-size:1.6rem; font-weight:800; color:var(--dark-blue); line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.2rem; }
.wf-stat-label { font-size:0.78rem; color:var(--text-secondary); font-weight:500; }

/* GRID 2 COL */
.wf-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; align-items:flex-start; }
@media (max-width:1100px) { .wf-grid-2 { grid-template-columns:1fr; } }

/* INFO LIST */
.wf-info-list { display:flex; flex-direction:column; gap:0.9rem; }
.wf-info-row {
    display:flex; align-items:flex-start; gap:0.85rem;
    padding:0.85rem 1rem; border-radius:10px;
    background:#FAFCFE; border:1px solid var(--border-soft);
}
.wf-info-icon {
    width:36px; height:36px; border-radius:9px;
    background:var(--light-blue-soft); color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-size:0.85rem; flex-shrink:0;
}
.wf-info-content { flex:1; min-width:0; line-height:1.4; }
.wf-info-label { font-size:0.7rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em; }
.wf-info-value { font-size:0.88rem; font-weight:700; color:var(--dark-blue); margin-top:0.15rem; }

/* SALARY TABLE */
.wf-table-wrap { overflow-x:auto; border-radius:12px; border:1px solid var(--border-soft); }
.wf-table { width:100%; border-collapse:collapse; font-size:0.83rem; }
.wf-table thead th {
    background:var(--light-blue-pale); padding:0.75rem 1rem; text-align:left;
    font-size:0.7rem; font-weight:800; color:var(--dark-blue);
    text-transform:uppercase; letter-spacing:0.06em;
    border-bottom:1px solid var(--border);
}
.wf-table tbody td { padding:0.85rem 1rem; border-bottom:1px solid var(--border-soft); vertical-align:middle; }
.wf-table tbody tr:last-child td { border-bottom:none; }
.wf-table tbody tr:hover { background:var(--light-blue-pale); }

.wf-salary-total {
    background:linear-gradient(135deg, var(--yellow-soft) 0%, #FFFAE0 100%);
    border:1px solid #F5DDA0;
    border-radius:12px; padding:1rem 1.25rem;
    display:flex; justify-content:space-between; align-items:center;
    margin-top:0.75rem;
}
.wf-salary-total-label { font-size:0.78rem; font-weight:700; color:#8A6D00; text-transform:uppercase; letter-spacing:0.06em; }
.wf-salary-total-value { font-size:1.35rem; font-weight:800; color:var(--yellow-dark); letter-spacing:-0.02em; }

.wf-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.6rem; border-radius:20px; font-size:0.68rem; font-weight:800; }
.wf-badge.active   { background:#EAF7F0; color:#1E7E45; }
.wf-badge.inactive { background:#FDEDEC; color:#A8322D; }

.wf-empty { text-align:center; padding:2.5rem 1rem; color:var(--text-muted); }
.wf-empty i { font-size:2.2rem; color:var(--light-blue); margin-bottom:0.6rem; display:block; }
.wf-empty p { margin:0; font-size:0.85rem; font-weight:600; }

.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
    .wf-shift-banner { flex-direction:column; text-align:center; }
    .wf-shift-badge { align-self:center; }
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
            <a href="staff_payments.php" class="wf-nav-item"><i class="fas fa-money-check-alt"></i><span>Verify Payments</span></a>
            <a href="staff_inventory.php" class="wf-nav-item"><i class="fas fa-flask"></i><span>Log Inventory</span></a>
            <div class="wf-nav-section-label">Support</div>
            <a href="staff_feedback.php" class="wf-nav-item"><i class="fas fa-star"></i><span>Reply Feedback</span></a>
            <a href="staff_complaints.php" class="wf-nav-item"><i class="fas fa-headset"></i><span>Handle Complaints</span></a>
            <div class="wf-nav-section-label">Account</div>
            <a href="staff_shift.php" class="wf-nav-item active"><i class="fas fa-clock"></i><span>My Shift</span></a>
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
                <h1><i class="fas fa-clock" style="color:var(--primary);"></i> My <span class="accent">Shift</span></h1>
                <p>Your schedule, attendance, and salary records</p>
            </div>
            <div class="wf-topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, F j, Y') ?>
            </div>
        </div>

        <!-- SHIFT BANNER -->
        <div class="wf-shift-banner <?= $on_duty ? '' : 'off' ?>">
            <div class="wf-shift-icon">
                <i class="fas <?= $on_duty ? 'fa-briefcase' : 'fa-moon' ?>"></i>
            </div>
            <div class="wf-shift-info">
                <div class="wf-shift-label"><?= e($my_shift['label']) ?></div>
                <div class="wf-shift-title"><?= e($on_duty ? 'Currently On Duty' : 'Off Duty') ?></div>
                <div class="wf-shift-time">
                    <i class="fas fa-clock"></i>
                    <?= date('g:i A', strtotime($my_shift['start'])) ?> —
                    <?= date('g:i A', strtotime($my_shift['end'])) ?>
                </div>
                <div class="wf-shift-progress">
                    <div class="wf-shift-progress-bar" style="width:<?= $shift_progress ?>%;"></div>
                </div>
                <div style="font-size:0.72rem;color:rgba(255,255,255,0.7);margin-top:0.4rem;">
                    <?= $shift_progress ?>% of shift elapsed
                </div>
            </div>
            <div class="wf-shift-badge <?= $on_duty ? 'on' : 'off' ?>">
                <i class="fas fa-circle" style="font-size:0.5rem;"></i>
                <?= $on_duty ? 'ON DUTY' : 'OFF DUTY' ?>
            </div>
        </div>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-calendar-day"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($shift_stats['today_bookings']) ?></div>
                    <div class="wf-stat-label">Bookings Today</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-check-double"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($shift_stats['today_completed']) ?></div>
                    <div class="wf-stat-label">Completed Today</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-gold"><i class="fas fa-clock"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($shift_stats['today_pending']) ?></div>
                    <div class="wf-stat-label">Pending Today</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-calendar-week"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($shift_stats['week_bookings']) ?></div>
                    <div class="wf-stat-label">Bookings This Week</div>
                </div>
            </div>
        </div>

        <div class="wf-grid-2">

            <!-- EMPLOYEE INFO -->
            <div>
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon"><i class="fas fa-user-circle"></i></div>
                            <div>
                                <h3>Employee Info</h3>
                                <p>Your staff profile</p>
                            </div>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <?php if (!$employee): ?>
                            <div class="wf-empty">
                                <i class="fas fa-user-slash"></i>
                                <p>Employee record not found</p>
                                <small style="color:var(--text-muted);font-size:0.75rem;">
                                    Hindi ka pa naka-register sa `employees` table.
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="wf-info-list">
                                <div class="wf-info-row">
                                    <div class="wf-info-icon"><i class="fas fa-id-badge"></i></div>
                                    <div class="wf-info-content">
                                        <div class="wf-info-label">Employee ID</div>
                                        <div class="wf-info-value">#<?= (int)$employee['id'] ?></div>
                                    </div>
                                </div>
                                <div class="wf-info-row">
                                    <div class="wf-info-icon"><i class="fas fa-user"></i></div>
                                    <div class="wf-info-content">
                                        <div class="wf-info-label">Full Name</div>
                                        <div class="wf-info-value"><?= e($employee['name']) ?></div>
                                    </div>
                                </div>
                                <div class="wf-info-row">
                                    <div class="wf-info-icon"><i class="fas <?= e($my_role['icon']) ?>"></i></div>
                                    <div class="wf-info-content">
                                        <div class="wf-info-label">Role</div>
                                        <div class="wf-info-value"><?= e($employee_role) ?></div>
                                    </div>
                                </div>
                                <div class="wf-info-row">
                                    <div class="wf-info-icon"><i class="fas fa-phone"></i></div>
                                    <div class="wf-info-content">
                                        <div class="wf-info-label">Contact</div>
                                        <div class="wf-info-value"><?= e($employee_contact) ?></div>
                                    </div>
                                </div>
                                <div class="wf-info-row">
                                    <div class="wf-info-icon"><i class="fas fa-circle-check"></i></div>
                                    <div class="wf-info-content">
                                        <div class="wf-info-label">Status</div>
                                        <div class="wf-info-value">
                                            <span class="wf-badge <?= strtolower($employee_status) === 'active' ? 'active' : 'inactive' ?>">
                                                <i class="fas fa-circle" style="font-size:0.45rem;"></i>
                                                <?= e($employee_status) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SUMMARY STATS -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon"><i class="fas fa-chart-line"></i></div>
                            <div>
                                <h3>Overall Stats</h3>
                                <p>Lifetime totals</p>
                            </div>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <div class="wf-info-list">
                            <div class="wf-info-row">
                                <div class="wf-info-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="wf-info-content">
                                    <div class="wf-info-label">Total Bookings Handled</div>
                                    <div class="wf-info-value"><?= number_format($shift_stats['total_bookings']) ?></div>
                                </div>
                            </div>
                            <div class="wf-info-row">
                                <div class="wf-info-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div class="wf-info-content">
                                    <div class="wf-info-label">Bookings This Month</div>
                                    <div class="wf-info-value"><?= number_format($shift_stats['month_bookings']) ?></div>
                                </div>
                            </div>
                            <div class="wf-info-row">
                                <div class="wf-info-icon"><i class="fas fa-clock"></i></div>
                                <div class="wf-info-content">
                                    <div class="wf-info-label">Total Days Worked</div>
                                    <div class="wf-info-value"><?= number_format($total_days) ?> days</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SALARY RECORDS -->
            <div>
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <h3>Salary Records</h3>
                                <p>Last 12 salary entries</p>
                            </div>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <?php if (empty($salaries)): ?>
                            <div class="wf-empty">
                                <i class="fas fa-receipt"></i>
                                <p>No salary records yet</p>
                            </div>
                        <?php else: ?>

                            <?php if ($latest_salary): ?>
                            <div class="wf-salary-total" style="margin-top:0;margin-bottom:1rem;">
                                <div>
                                    <div class="wf-salary-total-label">Latest Salary</div>
                                    <div style="font-size:0.72rem;color:#8A6D00;font-weight:600;margin-top:0.15rem;">
                                        <?= date('F j, Y', strtotime($latest_salary['salary_date'])) ?>
                                    </div>
                                </div>
                                <div class="wf-salary-total-value">
                                    ₱<?= number_format((float)$latest_salary['total_salary'], 2) ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="wf-table-wrap">
                                <table class="wf-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Days</th>
                                            <th style="text-align:right;">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($salaries as $s): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight:700;color:var(--dark-blue);font-size:0.82rem;">
                                                    <?= $s['salary_date'] ? date('M j, Y', strtotime($s['salary_date'])) : '—' ?>
                                                </div>
                                                <div style="font-size:0.7rem;color:var(--text-muted);">
                                                    <?= $s['salary_date'] ? date('l', strtotime($s['salary_date'])) : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight:700;color:var(--dark-blue);">
                                                    <?= (int)$s['days_worked'] ?>
                                                </span>
                                                <span style="font-size:0.7rem;color:var(--text-muted);">day<?= (int)$s['days_worked'] === 1 ? '' : 's' ?></span>
                                            </td>
                                            <td style="text-align:right;">
                                                <span style="font-weight:800;color:var(--dark-blue);">
                                                    ₱<?= number_format((float)$s['total_salary'], 2) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="wf-salary-total">
                                <div class="wf-salary-total-label">Total (All Records)</div>
                                <div class="wf-salary-total-value">₱<?= number_format($total_salary, 2) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
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