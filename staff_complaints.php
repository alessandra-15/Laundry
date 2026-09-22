<?php
/**
 * staff_complaints.php
 * WashFlow — Staff Handle Complaints
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
   POST — UPDATE COMPLAINT
   ────────────────────────────────────────── */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $complaint_id = (int)($_POST['complaint_id'] ?? 0);
    $remarks      = trim($_POST['remarks'] ?? '');

    try {
        if ($complaint_id <= 0) throw new Exception('Invalid complaint.');

        /* Kunin ang current */
        $stmt = $conn->prepare("SELECT complaint_id, status FROM complaints WHERE complaint_id = ? LIMIT 1");
        $stmt->bind_param('i', $complaint_id);
        $stmt->execute();
        $c = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$c) throw new Exception('Complaint not found.');

        $new_status = null;
        if ($action === 'progress')     $new_status = 'In Progress';
        elseif ($action === 'resolve')  $new_status = 'Resolved';
        elseif ($action === 'pending')  $new_status = 'Pending';
        else throw new Exception('Invalid action.');

        if ($remarks === '') {
            throw new Exception('Please add remarks.');
        }

        /* enum: 'Pending','Resolved','In Progress' */
        $sql = "
            UPDATE complaints
            SET status = ?,
                remarks = CONCAT(COALESCE(remarks,''), IF(COALESCE(remarks,'') = '', '', ' | '), '[', ?, '] ', ?),
                handled_by = ?,
                date_resolved = IF(? = 'Resolved', NOW(), date_resolved)
            WHERE complaint_id = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssssi',
            $new_status, $staff_name, $remarks, $staff_name, $new_status, $complaint_id
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected <= 0) throw new Exception('Failed to update complaint.');

        if (class_exists('Logger')) {
            Logger::info('Complaint updated', [
                'staff_id'     => $staff_id,
                'complaint_id' => $complaint_id,
                'new_status'   => $new_status,
            ]);
        }

        $flash = ['type' => 'success', 'message' => "Complaint #$complaint_id marked as '$new_status'."];

    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Complaint action failed', ['error' => $ex->getMessage()]);
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

if ($filter === 'pending')       $where[] = "co.status = 'Pending'";
elseif ($filter === 'progress')  $where[] = "co.status = 'In Progress'";
elseif ($filter === 'resolved')  $where[] = "co.status = 'Resolved'";

if ($search !== '') {
    $where[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR co.issue_description LIKE ? OR CAST(co.complaint_id AS CHAR) LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

/* ──────────────────────────────────────────
   FETCH COMPLAINTS
   ────────────────────────────────────────── */
$complaints = [];
try {
    $sql = "
        SELECT
            co.complaint_id, co.customer_id, co.issue_description, co.status,
            co.date_reported, co.date_resolved, co.remarks, co.handled_by,
            c.first_name, c.last_name, c.contact_number
        FROM complaints co
        LEFT JOIN customer_info c ON c.Customer_ID = co.customer_id
        $where_sql
        ORDER BY
            CASE co.status
                WHEN 'Pending'     THEN 0
                WHEN 'In Progress' THEN 1
                ELSE 2
            END,
            co.date_reported DESC
        LIMIT 100
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $complaints[] = $r;
        $stmt->close();
    }
} catch (Exception $e) {
    if (class_exists('Logger')) Logger::error('Fetch complaints failed', ['error' => $e->getMessage()]);
}

/* ──────────────────────────────────────────
   STATS
   ────────────────────────────────────────── */
$stats = ['total' => 0, 'pending' => 0, 'progress' => 0, 'resolved' => 0];
try {
    $q = $conn->query("SELECT COUNT(*) c FROM complaints");
    if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status = 'Pending'");
    if ($q) $stats['pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status = 'In Progress'");
    if ($q) $stats['progress'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status = 'Resolved'");
    if ($q) $stats['resolved'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Handle Complaints — WashFlow Staff</title>
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

.wf-flash { padding:0.9rem 1.15rem; border-radius:12px; margin-bottom:1.25rem; font-weight:600; font-size:0.88rem; display:flex; align-items:center; gap:0.7rem; animation:flashIn 0.35s; }
@keyframes flashIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.wf-flash.success { background:#EAF7F0; color:#1E7E45; border-left:4px solid var(--green); }
.wf-flash.error { background:#FDEDEC; color:#A8322D; border-left:4px solid var(--red); }

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
.wf-tabs { display:flex; gap:0.35rem; background:var(--light-blue-pale); padding:0.35rem; border-radius:10px; flex-wrap:wrap; }
.wf-tab { padding:0.55rem 1rem; border-radius:7px; font-size:0.8rem; font-weight:700; color:var(--text-secondary); cursor:pointer; transition:all 0.2s; border:none; background:transparent; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; white-space:nowrap; }
.wf-tab.active { background:white; color:var(--dark-blue); box-shadow:0 2px 8px rgba(6,52,82,0.08); }
.wf-tab:hover:not(.active) { color:var(--dark-blue); }

.wf-complaint-card { background:#FAFCFE; border:1.5px solid var(--border-soft); border-radius:12px; padding:1.25rem; margin-bottom:1rem; transition:all 0.2s; }
.wf-complaint-card:hover { border-color:var(--light-blue); }
.wf-complaint-card.pending   { border-left:4px solid #F0B400; }
.wf-complaint-card.progress  { border-left:4px solid var(--primary); }
.wf-complaint-card.resolved  { border-left:4px solid var(--green); background:#F7FCF9; }

.wf-cp-head { display:flex; align-items:flex-start; gap:0.9rem; margin-bottom:0.75rem; }
.wf-cp-avatar { width:42px; height:42px; border-radius:11px; background:linear-gradient(135deg, var(--light-blue-soft), var(--light-blue-pale)); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1rem; flex-shrink:0; }
.wf-cp-info { flex:1; min-width:0; line-height:1.3; }
.wf-cp-name { font-size:0.9rem; font-weight:800; color:var(--dark-blue); }
.wf-cp-meta { font-size:0.72rem; color:var(--text-muted); font-weight:500; }

.wf-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.6rem; border-radius:20px; font-size:0.68rem; font-weight:800; }
.wf-badge.pending  { background:#FFF9DB; color:#8A6D00; }
.wf-badge.progress { background:var(--light-blue-soft); color:var(--primary); }
.wf-badge.resolved { background:#EAF7F0; color:#1E7E45; }

.wf-cp-desc { background:white; border:1px solid var(--border-soft); border-radius:10px; padding:0.85rem 1rem; font-size:0.85rem; color:var(--text-primary); line-height:1.6; margin-bottom:0.75rem; }

.wf-cp-remarks { background:#F0F8FF; border:1px solid #D6E9F7; border-radius:10px; padding:0.85rem 1rem; margin-bottom:0.75rem; }
.wf-cp-remarks-label { font-size:0.7rem; font-weight:800; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem; }
.wf-cp-remarks-text { font-size:0.85rem; color:var(--text-primary); line-height:1.6; }

.wf-btn { display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; padding:0.7rem 1.2rem; border-radius:10px; font-size:0.82rem; font-weight:800; cursor:pointer; transition:all 0.2s; border:none; font-family:inherit; text-decoration:none; }
.wf-btn-primary { background:var(--primary); color:white; }
.wf-btn-primary:hover { background:var(--primary-mid); transform:translateY(-1px); }
.wf-btn-success { background:#1E7E45; color:white; }
.wf-btn-success:hover { background:#166235; transform:translateY(-1px); }
.wf-btn-warn { background:#B86A00; color:white; }
.wf-btn-warn:hover { background:#985800; transform:translateY(-1px); }
.wf-btn-outline { background:white; color:var(--primary); border:1.5px solid var(--border); }
.wf-btn-outline:hover { border-color:var(--primary); background:var(--light-blue-pale); }

.wf-action-form { display:none; margin-top:0.75rem; padding-top:0.75rem; border-top:1px dashed var(--border); }
.wf-action-form.open { display:block; }
.wf-input { width:100%; padding:0.75rem 0.95rem; border:1.5px solid var(--border); border-radius:10px; font-size:0.85rem; font-family:inherit; outline:none; transition:all 0.2s; resize:vertical; min-height:70px; }
.wf-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }

.wf-empty { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
.wf-empty i { font-size:2.5rem; color:var(--light-blue); margin-bottom:0.75rem; display:block; }
.wf-empty p { margin:0; font-size:0.88rem; font-weight:600; }

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
            <a href="staff_payments.php" class="wf-nav-item"><i class="fas fa-money-check-alt"></i><span>Verify Payments</span></a>
            <a href="staff_inventory.php" class="wf-nav-item"><i class="fas fa-flask"></i><span>Log Inventory</span></a>
            <div class="wf-nav-section-label">Support</div>
            <a href="staff_feedback.php" class="wf-nav-item"><i class="fas fa-star"></i><span>Reply Feedback</span></a>
            <a href="staff_complaints.php" class="wf-nav-item active"><i class="fas fa-headset"></i><span>Handle Complaints</span></a>
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
                <h1><i class="fas fa-headset" style="color:var(--primary);"></i> Handle <span class="accent">Complaints</span></h1>
                <p>Review and resolve customer complaints</p>
            </div>
            <div class="wf-topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, F j, Y') ?>
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
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-comment-dots"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Complaints</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-gold"><i class="fas fa-clock"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="wf-stat-label">Pending</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-spinner"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['progress']) ?></div>
                    <div class="wf-stat-label">In Progress</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-check-double"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['resolved']) ?></div>
                    <div class="wf-stat-label">Resolved</div>
                </div>
            </div>
        </div>

        <div class="wf-card">
            <div class="wf-card-head">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div>
                        <h3>Complaints</h3>
                        <p>Update status and add remarks</p>
                    </div>
                </div>
            </div>
            <div class="wf-card-body">

                <div class="wf-toolbar">
                    <div class="wf-search">
                        <i class="fas fa-search"></i>
                        <form method="GET" style="margin:0;">
                            <input type="hidden" name="filter" value="<?= e($filter) ?>">
                            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search customer, issue, or ID...">
                        </form>
                    </div>
                    <div class="wf-tabs">
                        <a href="?filter=pending&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'pending' ? 'active' : '' ?>"><i class="fas fa-clock"></i> Pending</a>
                        <a href="?filter=progress&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'progress' ? 'active' : '' ?>"><i class="fas fa-spinner"></i> In Progress</a>
                        <a href="?filter=resolved&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'resolved' ? 'active' : '' ?>"><i class="fas fa-check"></i> Resolved</a>
                        <a href="?filter=all&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'all' ? 'active' : '' ?>"><i class="fas fa-list"></i> All</a>
                    </div>
                </div>

                <?php if (empty($complaints)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-inbox"></i>
                        <p>No complaints found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($complaints as $cp):
                        $full = trim(($cp['first_name'] ?? '') . ' ' . ($cp['last_name'] ?? ''));
                        if ($full === '') $full = 'Customer';
                        $initial = strtoupper(substr($full, 0, 1));

                        $status = $cp['status'];
                        $status_key = 'pending';
                        if ($status === 'In Progress') $status_key = 'progress';
                        elseif ($status === 'Resolved') $status_key = 'resolved';
                    ?>
                        <div class="wf-complaint-card <?= $status_key ?>">
                            <div class="wf-cp-head">
                                <div class="wf-cp-avatar"><?= e($initial) ?></div>
                                <div class="wf-cp-info">
                                    <div class="wf-cp-name"><?= e($full) ?></div>
                                    <div class="wf-cp-meta">
                                        Complaint #<?= (int)$cp['complaint_id'] ?> ·
                                        <?= date('M j, Y g:i A', strtotime($cp['date_reported'])) ?>
                                        <?php if ($cp['contact_number']): ?>
                                            · <?= e($cp['contact_number']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="wf-badge <?= $status_key ?>">
                                        <?php if ($status_key === 'resolved'): ?><i class="fas fa-check"></i>
                                        <?php elseif ($status_key === 'progress'): ?><i class="fas fa-spinner"></i>
                                        <?php else: ?><i class="fas fa-clock"></i><?php endif; ?>
                                        <?= e($status) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="wf-cp-desc">
                                <strong>Issue:</strong> <?= e($cp['issue_description']) ?>
                            </div>

                            <?php if (!empty($cp['remarks'])): ?>
                                <div class="wf-cp-remarks">
                                    <div class="wf-cp-remarks-label"><i class="fas fa-comment"></i> Remarks</div>
                                    <div class="wf-cp-remarks-text"><?= e($cp['remarks']) ?></div>
                                    <?php if ($cp['handled_by']): ?>
                                        <div style="font-size:0.7rem;color:var(--text-muted);margin-top:0.4rem;">
                                            Handled by: <?= e($cp['handled_by']) ?>
                                            <?php if ($cp['date_resolved']): ?>
                                                · Resolved <?= date('M j, Y g:i A', strtotime($cp['date_resolved'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                <?php if ($status !== 'In Progress' && $status !== 'Resolved'): ?>
                                    <button type="button" class="wf-btn wf-btn-warn" onclick="openAction(<?= (int)$cp['complaint_id'] ?>, 'progress')">
                                        <i class="fas fa-spinner"></i> Mark In Progress
                                    </button>
                                <?php endif; ?>
                                <?php if ($status !== 'Resolved'): ?>
                                    <button type="button" class="wf-btn wf-btn-success" onclick="openAction(<?= (int)$cp['complaint_id'] ?>, 'resolve')">
                                        <i class="fas fa-check"></i> Resolve
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="wf-btn wf-btn-outline" onclick="openAction(<?= (int)$cp['complaint_id'] ?>, 'pending')">
                                    <i class="fas fa-undo"></i> Set Pending
                                </button>
                            </div>

                            <div class="wf-action-form" id="action-form-<?= (int)$cp['complaint_id'] ?>">
                                <form method="POST">
                                    <input type="hidden" name="action" id="act-action-<?= (int)$cp['complaint_id'] ?>">
                                    <input type="hidden" name="complaint_id" value="<?= (int)$cp['complaint_id'] ?>">
                                    <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--dark-blue);margin-bottom:0.4rem;">
                                        Remarks <span style="color:var(--red);">*</span>
                                    </label>
                                    <textarea name="remarks" class="wf-input" required placeholder="Add notes about this action..."></textarea>
                                    <div style="display:flex;gap:0.5rem;margin-top:0.75rem;flex-wrap:wrap;">
                                        <button type="submit" class="wf-btn wf-btn-primary">
                                            <i class="fas fa-paper-plane"></i> Confirm
                                        </button>
                                        <button type="button" class="wf-btn wf-btn-outline" onclick="closeAction(<?= (int)$cp['complaint_id'] ?>)">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </main>
</div>

<script>
function openAction(id, action) {
    /* Close all others first */
    document.querySelectorAll('.wf-action-form.open').forEach(el => el.classList.remove('open'));

    const wrapper = document.getElementById('action-form-' + id);
    const actionField = document.getElementById('act-action-' + id);
    if (!wrapper || !actionField) return;

    actionField.value = action;
    wrapper.classList.add('open');
    wrapper.scrollIntoView({behavior:'smooth', block:'nearest'});
}

function closeAction(id) {
    const el = document.getElementById('action-form-' + id);
    if (el) el.classList.remove('open');
}

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