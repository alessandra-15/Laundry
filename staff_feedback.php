<?php
/**
 * staff_feedback.php
 * WashFlow — Staff Feedback
 *
 * ✅ Staff-only (auto-redirect admin → dashboard.php, guest → staff_login.php)
 * ✅ Stats: total, pending, replied, avg rating
 * ✅ Filter: all / pending / replied
 * ✅ Reply + delete per feedback item
 * ✅ Same layout as staff_dashboard.php
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
$staff_initial = strtoupper(substr($staff_name, 0, 1));

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('wf_stars')) {
    function wf_stars(int $n): string {
        $n = max(0, min(5, $n));
        return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
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
   HANDLE ACTIONS (POST)
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $feedback_id = (int)($_POST['feedback_id'] ?? 0);
    $reply       = trim($_POST['reply'] ?? '');

    if ($action === 'reply' && $feedback_id > 0 && $reply !== '') {
        $stmt = $conn->prepare(
            "UPDATE feedback SET response = ?, responded_at = NOW() WHERE feedback_id = ?"
        );
        if ($stmt) {
            $stmt->bind_param('si', $reply, $feedback_id);
            $flash = $stmt->execute()
                ? ['type' => 'success', 'msg' => 'Reply sent successfully.']
                : ['type' => 'danger',  'msg' => 'Failed to save reply.'];
            $stmt->close();
        } else {
            $flash = ['type' => 'danger', 'msg' => 'Database error: ' . $conn->error];
        }
    } elseif ($action === 'delete' && $feedback_id > 0) {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $feedback_id);
            $flash = $stmt->execute()
                ? ['type' => 'success', 'msg' => 'Feedback deleted.']
                : ['type' => 'danger',  'msg' => 'Failed to delete feedback.'];
            $stmt->close();
        }
    }
}

/* ══════════════════════════════════════════
   FILTER + FETCH FEEDBACK
   ══════════════════════════════════════════ */
$filter = $_GET['filter'] ?? 'all';
$where  = '';

if ($filter === 'pending') {
    $where = "WHERE (f.response IS NULL OR f.response = '')";
} elseif ($filter === 'replied') {
    $where = "WHERE (f.response IS NOT NULL AND f.response <> '')";
}

$sql = "SELECT f.feedback_id,
               f.user_id AS customer_id,
               f.booking_id,
               f.rating,
               f.comment,
               f.response,
               f.created_at,
               f.responded_at,
               CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) AS customer_name,
               c.email AS customer_email
        FROM feedback f
        LEFT JOIN customer_info c ON c.Customer_ID = f.user_id
        $where
        ORDER BY f.created_at DESC
        LIMIT 200";

$feedbacks = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) $feedbacks[] = $row;
    $res->free();
} else {
    $flash = ['type' => 'danger', 'msg' => 'Query failed: ' . $conn->error];
}

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = ['total' => 0, 'pending' => 0, 'replied' => 0, 'avg_rating' => 0];
$sr = $conn->query(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN response IS NULL OR response='' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN response IS NOT NULL AND response<>'' THEN 1 ELSE 0 END) AS replied,
            AVG(rating) AS avg_rating
     FROM feedback"
);
if ($sr && $r = $sr->fetch_assoc()) {
    $stats['total']      = (int)$r['total'];
    $stats['pending']    = (int)$r['pending'];
    $stats['replied']    = (int)$r['replied'];
    $stats['avg_rating'] = round((float)$r['avg_rating'], 1);
    $sr->free();
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
<title>Staff Feedback — WashFlow</title>

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
.wf-stat-card { background:white; border-radius:14px; padding:1.25rem; border:1px solid var(--border); transition:all 0.25s ease; display:flex; flex-direction:column; justify-content:space-between; min-height:130px; }
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

/* CARD */
.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }
.wf-card:last-child { margin-bottom:0; }
.wf-card-head { padding:1.1rem 1.5rem; border-bottom:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.wf-card-title { display:flex; align-items:center; gap:0.9rem; }
.wf-card-title-icon { width:42px; height:42px; border-radius:11px; background:var(--light-blue-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-card-title-icon.gold  { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-card-title-icon.green { background:#EAF7F0; color:#1E7E45; }
.wf-card-title-icon.warn  { background:#FEF3E2; color:#C2410C; }
.wf-card-title h3 { font-size:1rem; font-weight:700; color:var(--dark-blue); margin:0; }
.wf-card-title p { font-size:0.75rem; color:var(--text-muted); margin:0.1rem 0 0; font-weight:500; }
.wf-card-link { display:inline-flex; align-items:center; gap:0.4rem; color:var(--primary); font-weight:600; font-size:0.8rem; padding:0.4rem 0.75rem; border-radius:7px; transition:all 0.2s; }
.wf-card-link:hover { background:var(--light-blue-pale); color:var(--primary-mid); }
.wf-card-body { padding:1.25rem 1.5rem; }

/* FILTER PILLS */
.wf-filter-pills { display:inline-flex; gap:0.4rem; background:#F5F9FC; padding:0.35rem; border-radius:10px; border:1px solid var(--border); }
.wf-filter-pill { padding:0.45rem 0.95rem; border-radius:7px; font-size:0.78rem; font-weight:600; color:var(--text-secondary); transition:all 0.2s; }
.wf-filter-pill:hover { color:var(--primary); background:var(--light-blue-pale); }
.wf-filter-pill.active { background:var(--primary); color:white; box-shadow:0 2px 8px rgba(0,90,133,0.25); }

/* FEEDBACK CARD */
.wf-fb-list { display:flex; flex-direction:column; gap:1rem; }
.wf-fb { border:1px solid var(--border); border-radius:12px; padding:1.25rem 1.35rem; background:white; transition:all 0.2s; }
.wf-fb:hover { border-color:var(--light-blue); box-shadow:0 4px 14px rgba(0,83,122,0.06); }
.wf-fb-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:0.85rem; }
.wf-fb-cust { display:flex; align-items:center; gap:0.85rem; }
.wf-fb-avatar { width:42px; height:42px; border-radius:11px; background:linear-gradient(135deg,var(--light-blue-soft) 0%,var(--light-blue-pale) 100%); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.95rem; flex-shrink:0; }
.wf-fb-name { font-weight:700; color:var(--dark-blue); font-size:0.92rem; line-height:1.25; }
.wf-fb-meta { font-size:0.72rem; color:var(--text-muted); font-weight:500; margin-top:0.1rem; }
.wf-fb-right { text-align:right; }
.wf-fb-stars { color:var(--gold); letter-spacing:2px; font-size:1.05rem; line-height:1; }
.wf-fb-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.6rem; border-radius:6px; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-top:0.4rem; }
.wf-fb-badge.pending { background:#FEF9E7; color:#946200; }
.wf-fb-badge.replied { background:#EAF7F0; color:#1E7E45; }
.wf-fb-msg { color:var(--text-primary); font-size:0.88rem; line-height:1.6; margin:0.5rem 0 0.85rem; }
.wf-fb-reply { background:var(--light-blue-pale); border-left:3px solid var(--primary); padding:0.85rem 1rem; border-radius:0 8px 8px 0; margin-bottom:0.85rem; }
.wf-fb-reply-label { font-size:0.68rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:0.35rem; }
.wf-fb-reply-text { color:var(--text-primary); font-size:0.85rem; line-height:1.55; }
.wf-fb-form { display:flex; gap:0.6rem; align-items:stretch; margin-bottom:0.6rem; }
.wf-fb-form textarea { flex:1; padding:0.65rem 0.85rem; border:1px solid var(--border); border-radius:9px; font-family:inherit; font-size:0.85rem; resize:vertical; min-height:44px; color:var(--text-primary); }
.wf-fb-form textarea:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.08); }
.wf-fb-actions { display:flex; justify-content:flex-end; gap:0.5rem; }
.wf-btn-primary { display:inline-flex; align-items:center; gap:0.45rem; padding:0.65rem 1.15rem; border-radius:9px; border:none; background:var(--primary); color:white; font-weight:600; font-size:0.82rem; cursor:pointer; transition:all 0.2s; }
.wf-btn-primary:hover { background:var(--primary-mid); transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,90,133,0.25); }
.wf-btn-ghost { display:inline-flex; align-items:center; gap:0.4rem; padding:0.45rem 0.85rem; border-radius:8px; border:1px solid var(--border); background:white; color:var(--text-secondary); font-weight:600; font-size:0.75rem; cursor:pointer; transition:all 0.2s; }
.wf-btn-ghost:hover { border-color:var(--red); color:var(--red); background:#FDEDEC; }

/* ALERT */
.wf-alert { display:flex; align-items:center; gap:0.65rem; padding:0.85rem 1.1rem; border-radius:11px; font-size:0.85rem; font-weight:600; margin-bottom:1.25rem; }
.wf-alert.success { background:#EAF7F0; color:#1E7E45; border:1px solid #C7EAD4; }
.wf-alert.danger  { background:#FDEDEC; color:#A8322D; border:1px solid #F5C6C2; }

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
            <a href="staff_dashboard.php" class="wf-nav-item">
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
            <a href="staff_feedback.php" class="wf-nav-item active">
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

        <!-- TOPBAR -->
        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1>Customer <span class="accent">Feedback</span></h1>
                <p>Reply to customer reviews and improve our service</p>
            </div>
            <div class="wf-topbar-right">
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>

        <!-- FLASH -->
        <?php if ($flash): ?>
            <div class="wf-alert <?= e($flash['type']) ?>">
                <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= e($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-comments"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Feedback</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-hourglass-half"></i></div>
                    <?php if ($stats['pending'] > 0): ?>
                    <span class="wf-fb-badge pending"><span class="wf-badge-dot" style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span> Action</span>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="wf-stat-label">Pending Reply</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['replied']) ?></div>
                    <div class="wf-stat-label">Replied</div>
                </div>
            </div>

            <div class="wf-stat-card" style="border-color:#F5E6A8;">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-gold"><i class="fas fa-star"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['avg_rating'], 1) ?></div>
                    <div class="wf-stat-label">Average Rating</div>
                </div>
            </div>
        </div>

        <!-- FEEDBACK LIST -->
        <div class="wf-card">
            <div class="wf-card-head">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-comments"></i></div>
                    <div>
                        <h3>All Feedback</h3>
                        <p>Reply to customer reviews</p>
                    </div>
                </div>
                <div class="wf-filter-pills">
                    <a href="?filter=all"     class="wf-filter-pill <?= $filter==='all'?'active':'' ?>">All</a>
                    <a href="?filter=pending" class="wf-filter-pill <?= $filter==='pending'?'active':'' ?>">Pending</a>
                    <a href="?filter=replied" class="wf-filter-pill <?= $filter==='replied'?'active':'' ?>">Replied</a>
                </div>
            </div>

            <div class="wf-card-body">
                <?php if (empty($feedbacks)): ?>
                    <div class="wf-empty-mini">
                        <i class="fas fa-comment-slash"></i>
                        <p>No feedback found for this filter.</p>
                    </div>
                <?php else: ?>
                    <div class="wf-fb-list">
                        <?php foreach ($feedbacks as $fb):
                            $hasReply = !empty($fb['response']);
                            $created  = $fb['created_at'] ? date('M d, Y h:i A', strtotime($fb['created_at'])) : '';
                            $name     = trim($fb['customer_name'] ?: '') ?: ('Customer #' . $fb['customer_id']);
                            $initial  = strtoupper(substr($name, 0, 1));
                        ?>
                        <div class="wf-fb">
                            <div class="wf-fb-head">
                                <div class="wf-fb-cust">
                                    <div class="wf-fb-avatar"><?= e($initial) ?></div>
                                    <div>
                                        <div class="wf-fb-name"><?= e($name) ?></div>
                                        <div class="wf-fb-meta">
                                            <?php if (!empty($fb['customer_email'])): ?>
                                                <?= e($fb['customer_email']) ?> •
                                            <?php endif; ?>
                                            Booking #<?= (int)$fb['booking_id'] ?>
                                            • <?= e($created) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="wf-fb-right">
                                    <div class="wf-fb-stars"><?= wf_stars((int)$fb['rating']) ?></div>
                                    <div>
                                        <?php if ($hasReply): ?>
                                            <span class="wf-fb-badge replied"><i class="fas fa-check"></i> Replied</span>
                                        <?php else: ?>
                                            <span class="wf-fb-badge pending"><i class="fas fa-hourglass-half"></i> Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="wf-fb-msg"><?= nl2br(e($fb['comment'])) ?></div>

                            <?php if ($hasReply): ?>
                                <div class="wf-fb-reply">
                                    <div class="wf-fb-reply-label">
                                        <i class="fas fa-reply"></i> Staff Reply
                                        <?php if (!empty($fb['responded_at'])): ?>
                                            • <?= e(date('M d, Y h:i A', strtotime($fb['responded_at']))) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="wf-fb-reply-text"><?= nl2br(e($fb['response'])) ?></div>
                                </div>
                            <?php else: ?>
                                <form method="post" class="wf-fb-form">
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="feedback_id" value="<?= (int)$fb['feedback_id'] ?>">
                                    <textarea name="reply" placeholder="Write your reply to this customer..." required></textarea>
                                    <button type="submit" class="wf-btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                </form>
                            <?php endif; ?>

                            <div class="wf-fb-actions">
                                <form method="post" onsubmit="return confirm('Delete this feedback? This cannot be undone.');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="feedback_id" value="<?= (int)$fb['feedback_id'] ?>">
                                    <button type="submit" class="wf-btn-ghost">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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