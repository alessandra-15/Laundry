<?php
/**
 * admin_tracking.php
 * WashFlow — ADMIN Tracking Monitor (READ-ONLY)
 *
 * - Admin-only (auto-redirect to admin_login if not admin)
 * - View ALL tracking records
 * - Filters, search, sort, pagination
 * - Track status movements with timestamps
 * - NO status updates — staff only
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

if (!function_exists('tracking_badge')) {
    function tracking_badge($status) {
        $s = strtolower(trim((string)$status));
        switch ($s) {
            case 'scheduled':   return ['label' => 'Scheduled',   'color' => '#00537A', 'bg' => '#EBF5FB', 'icon' => 'fa-calendar-check'];
            case 'picked up':
            case 'pickedup':    return ['label' => 'Picked Up',   'color' => '#946200', 'bg' => '#FEF9E7', 'icon' => 'fa-truck-pickup'];
            case 'processing':  return ['label' => 'Processing',  'color' => '#5B2E91', 'bg' => '#F4ECFB', 'icon' => 'fa-spinner'];
            case 'ready':       return ['label' => 'Ready',       'color' => '#946200', 'bg' => '#FFF9DB', 'icon' => 'fa-box-open'];
            case 'completed':   return ['label' => 'Completed',   'color' => '#1E7E45', 'bg' => '#EAF7F0', 'icon' => 'fa-check-double'];
            default:            return ['label' => ucfirst($s) ?: '—', 'color' => '#5A7184', 'bg' => '#F4F7F9', 'icon' => 'fa-circle'];
        }
    }
}

/* ==========================================
   CHECK TABLE
   ========================================== */
$has_tracking_table = false;
if ($res = $conn->query("SHOW TABLES LIKE 'tracking'")) {
    $has_tracking_table = ($res->num_rows > 0);
    $res->free();
}

/* ==========================================
   FLASH
   ========================================== */
$flash = null;
if (!empty($_SESSION['at_flash'])) {
    $flash = $_SESSION['at_flash'];
    unset($_SESSION['at_flash']);
}

/* ==========================================
   STATS
   ========================================== */
$stats = [
    'total'       => 0,
    'scheduled'   => 0,
    'picked_up'   => 0,
    'processing'  => 0,
    'ready'       => 0,
    'completed'   => 0,
];

if ($has_tracking_table) {
    try {
        $q = $conn->query("SELECT COUNT(*) c FROM tracking");
        if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM tracking WHERE LOWER(laundry_status) = 'scheduled'");
        if ($q) $stats['scheduled'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM tracking WHERE LOWER(laundry_status) IN ('picked up','pickedup')");
        if ($q) $stats['picked_up'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM tracking WHERE LOWER(laundry_status) = 'processing'");
        if ($q) $stats['processing'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM tracking WHERE LOWER(laundry_status) = 'ready'");
        if ($q) $stats['ready'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM tracking WHERE LOWER(laundry_status) = 'completed'");
        if ($q) $stats['completed'] = (int)$q->fetch_assoc()['c'];
    } catch (Exception $ex) {
        Logger::error('Admin tracking stats failed', ['error' => $ex->getMessage()]);
    }
}

/* ==========================================
   FILTERS + PAGINATION
   ========================================== */
$search     = trim($_GET['q'] ?? '');
$status_f   = trim($_GET['status'] ?? '');
$date_from  = trim($_GET['date_from'] ?? '');
$date_to    = trim($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;
$sort       = $_GET['sort'] ?? 'date_desc';

$order_map = [
    'date_desc' => 'tracking_date DESC, Tracking_ID DESC',
    'date_asc'  => 'tracking_date ASC, Tracking_ID ASC',
    'id_desc'   => 'Tracking_ID DESC',
    'id_asc'    => 'Tracking_ID ASC',
];
$order_sql = $order_map[$sort] ?? 'tracking_date DESC, Tracking_ID DESC';

/* ==========================================
   FETCH TRACKING
   ========================================== */
$records     = [];
$total_rows  = 0;
$total_pages = 1;

if ($has_tracking_table) {
    try {
        $where  = ["1=1"];
        $params = [];
        $types  = '';

        if ($search !== '') {
            $where[] = "(t.Tracking_ID LIKE ? OR t.Customer_ID LIKE ? OR t.Schedule_ID LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)";
            $like = '%' . $search . '%';
            for ($i = 0; $i < 5; $i++) { $params[] = $like; $types .= 's'; }
        }

        if ($status_f !== '') {
            if ($status_f === 'Picked Up') {
                $where[] = "LOWER(t.laundry_status) IN ('picked up','pickedup')";
            } else {
                $where[] = "LOWER(t.laundry_status) = ?";
                $params[] = strtolower($status_f);
                $types .= 's';
            }
        }

        if ($date_from !== '') { $where[] = "t.tracking_date >= ?"; $params[] = $date_from; $types .= 's'; }
        if ($date_to   !== '') { $where[] = "t.tracking_date <= ?"; $params[] = $date_to;   $types .= 's'; }

        $where_sql = implode(' AND ', $where);

        // Count
        $count_sql = "SELECT COUNT(*) c
                      FROM tracking t
                      LEFT JOIN customer_info c ON c.Customer_ID = t.Customer_ID
                      WHERE $where_sql";
        $stmt = $conn->prepare($count_sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $total_pages = max(1, (int)ceil($total_rows / $per_page));

        // List
        $list_sql = "SELECT
                        t.Tracking_ID,
                        t.Customer_ID,
                        t.Schedule_ID,
                        t.laundry_status,
                        t.Scheduled_time,
                        t.Pickup_time,
                        t.Processing_time,
                        t.Ready_time,
                        t.Completed_time,
                        t.tracking_time,
                        t.tracking_date,
                        CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) AS customer_name,
                        c.contact_number,
                        b.service,
                        b.pick_deliver,
                        b.status AS booking_status
                     FROM tracking t
                     LEFT JOIN customer_info c ON c.Customer_ID = t.Customer_ID
                     LEFT JOIN booking b ON b.Schedule_ID = t.Schedule_ID
                     WHERE $where_sql
                     ORDER BY $order_sql
                     LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($list_sql);
        $bind_types  = $types . 'ii';
        $bind_params = array_merge($params, [$per_page, $offset]);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

    } catch (Exception $ex) {
        Logger::error('Admin fetch tracking failed', ['error' => $ex->getMessage(), 'msg' => $conn->error]);
    }
}

/* ==========================================
   URL HELPER
   ========================================== */
function build_url($overrides = []) {
    $base = [
        'q'         => $_GET['q']         ?? '',
        'status'    => $_GET['status']    ?? '',
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
    return 'admin_tracking.php?' . http_build_query($merged);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tracking Monitor — WashFlow Admin</title>

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
.wf-tabs { display:flex; gap:0.5rem; background:white; padding:0.4rem; border-radius:12px; border:1px solid var(--border); margin-bottom:1.25rem; width:fit-content; flex-wrap:wrap; }
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

.wf-track-id { font-weight:700; color:var(--primary); font-family:'SF Mono',Monaco,monospace; font-size:0.8rem; }
.wf-booking-ref { display:inline-flex; align-items:center; gap:0.3rem; padding:0.15rem 0.5rem; border-radius:5px; font-size:0.68rem; font-weight:700; background:#F0F6FA; color:var(--primary); margin-left:0.35rem; font-family:'SF Mono',Monaco,monospace; }
.wf-customer { font-weight:600; color:var(--dark-blue); }
.wf-service { color:var(--text-secondary); font-size:0.84rem; }
.wf-date { color:var(--text-muted); font-size:0.8rem; white-space:nowrap; }

.wf-track-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.35rem 0.75rem; border-radius:999px; font-size:0.72rem; font-weight:700; white-space:nowrap; border:1px solid transparent; }

.wf-timeline { display:flex; align-items:center; gap:0.25rem; }
.wf-timeline-dot { width:8px; height:8px; border-radius:50%; background:var(--border); transition:all 0.2s; }
.wf-timeline-dot.active { background:var(--green); box-shadow:0 0 0 3px rgba(39,174,96,0.15); }
.wf-timeline-dot.current { background:var(--yellow-dark); box-shadow:0 0 0 3px rgba(184,138,0,0.15); }

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

/* TIMELINE IN PANEL */
.wf-track-timeline { position:relative; padding-left:2rem; }
.wf-track-timeline::before { content:''; position:absolute; left:11px; top:8px; bottom:8px; width:2px; background:var(--border); }
.wf-track-step { position:relative; padding-bottom:1.25rem; }
.wf-track-step:last-child { padding-bottom:0; }
.wf-track-step-icon { position:absolute; left:-2rem; top:0; width:24px; height:24px; border-radius:50%; background:white; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:0.6rem; color:var(--text-muted); z-index:1; }
.wf-track-step.done .wf-track-step-icon { background:var(--green); border-color:var(--green); color:white; }
.wf-track-step.current .wf-track-step-icon { background:var(--yellow-dark); border-color:var(--yellow-dark); color:white; animation:pulse 1.8s infinite; }
@keyframes pulse { 0%{box-shadow:0 0 0 0 rgba(184,138,0,0.4);} 70%{box-shadow:0 0 0 8px rgba(184,138,0,0);} 100%{box-shadow:0 0 0 0 rgba(184,138,0,0);} }
.wf-track-step-label { font-size:0.85rem; font-weight:700; color:var(--text-primary); }
.wf-track-step-time { font-size:0.75rem; color:var(--text-muted); font-weight:500; margin-top:0.1rem; }

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
            <a href="admin_payments.php" class="wf-nav-item">
                <i class="fas fa-receipt"></i><span>Payments Monitor</span>
            </a>
            <a href="admin_tracking.php" class="wf-nav-item active">
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
                <h1><i class="fas fa-route" style="color:var(--primary);"></i> Tracking <span class="accent">Monitor</span></h1>
                <p>Overview of all laundry tracking records — read-only.</p>
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
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-clipboard-list"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Records</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['scheduled']) ?></div>
                    <div class="wf-stat-label">Scheduled</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-yellow"><i class="fas fa-truck-pickup"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['picked_up']) ?></div>
                    <div class="wf-stat-label">Picked Up</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-purple"><i class="fas fa-spinner"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['processing']) ?></div>
                    <div class="wf-stat-label">Processing</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-yellow"><i class="fas fa-box-open"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['ready']) ?></div>
                    <div class="wf-stat-label">Ready</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-check-double"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['completed']) ?></div>
                    <div class="wf-stat-label">Completed</div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="wf-tabs">
            <a href="<?= e(build_url(['status' => '', 'page' => 1])) ?>" class="wf-tab <?= $status_f === '' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> All
                <span class="tab-count"><?= number_format($stats['total']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'Scheduled', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'Scheduled' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i> Scheduled
                <span class="tab-count"><?= number_format($stats['scheduled']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'Picked Up', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'Picked Up' ? 'active' : '' ?>">
                <i class="fas fa-truck-pickup"></i> Picked Up
                <span class="tab-count"><?= number_format($stats['picked_up']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'Processing', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'Processing' ? 'active' : '' ?>">
                <i class="fas fa-spinner"></i> Processing
                <span class="tab-count"><?= number_format($stats['processing']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'Ready', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'Ready' ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i> Ready
                <span class="tab-count"><?= number_format($stats['ready']) ?></span>
            </a>
            <a href="<?= e(build_url(['status' => 'Completed', 'page' => 1])) ?>" class="wf-tab <?= $status_f === 'Completed' ? 'active' : '' ?>">
                <i class="fas fa-check-double"></i> Completed
                <span class="tab-count"><?= number_format($stats['completed']) ?></span>
            </a>
        </div>

        <!-- FILTERS -->
        <form method="get" action="admin_tracking.php" class="wf-filter-bar">
            <input type="hidden" name="status" value="<?= e($status_f) ?>">
            <input type="hidden" name="page" value="1">

            <div class="wf-filter-group" style="flex: 1 1 200px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="Tracking ID, Schedule ID, customer..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 175px;">
                <label>Status</label>
                <select name="status" class="wf-select">
                    <option value="">All Status</option>
                    <option value="Scheduled"  <?= $status_f === 'Scheduled'  ? 'selected' : '' ?>>Scheduled</option>
                    <option value="Picked Up"  <?= $status_f === 'Picked Up'  ? 'selected' : '' ?>>Picked Up</option>
                    <option value="Processing" <?= $status_f === 'Processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="Ready"      <?= $status_f === 'Ready'      ? 'selected' : '' ?>>Ready</option>
                    <option value="Completed"  <?= $status_f === 'Completed'  ? 'selected' : '' ?>>Completed</option>
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
                <a href="admin_tracking.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>

        <!-- TABLE -->
        <div class="wf-card">
            <?php if (!$has_tracking_table): ?>
                <div class="wf-empty">
                    <i class="fas fa-database"></i>
                    <p>Tracking table not available</p>
                </div>
            <?php elseif (empty($records)): ?>
                <div class="wf-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No tracking records found</p>
                </div>
            <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'id_asc' ? 'id_desc' : 'id_asc', 'page' => 1])) ?>">
                                        Tracking ID <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'date_desc' ? 'date_asc' : 'date_desc', 'page' => 1])) ?>">
                                        Date <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r):
                                $st = tracking_badge($r['laundry_status']);

                                $customer_name = trim((string)($r['customer_name'] ?? ''));
                                if ($customer_name === '') $customer_name = 'Unknown Customer';

                                // Build timeline steps
                                $steps = [
                                    'Scheduled'  => $r['Scheduled_time']  ?? null,
                                    'Picked Up'  => $r['Pickup_time']     ?? null,
                                    'Processing' => $r['Processing_time'] ?? null,
                                    'Ready'      => $r['Ready_time']      ?? null,
                                    'Completed'  => $r['Completed_time']  ?? null,
                                ];
                                $current_status = strtolower(trim((string)$r['laundry_status']));
                            ?>
                            <tr>
                                <td>
                                    <span class="wf-track-id">#<?= e($r['Tracking_ID']) ?></span>
                                    <?php if (!empty($r['Schedule_ID'])): ?>
                                        <span class="wf-booking-ref">SC-<?= e($r['Schedule_ID']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="wf-customer"><?= e($customer_name) ?></div>
                                    <?php if (!empty($r['contact_number'])): ?>
                                        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">
                                            <i class="fas fa-phone" style="font-size:0.65rem;"></i> <?= e($r['contact_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="wf-service"><?= e($r['service'] ?? '—') ?></span></td>

                                <td>
                                    <div class="wf-timeline">
                                        <?php
                                        $step_keys = ['scheduled','picked up','processing','ready','completed'];
                                        $current_idx = array_search($current_status, $step_keys);
                                        if ($current_idx === false) $current_idx = -1;
                                        foreach ($step_keys as $i => $key):
                                            $cls = '';
                                            if ($current_idx >= 0) {
                                                if ($i < $current_idx) $cls = 'active';
                                                elseif ($i === $current_idx) $cls = 'current';
                                            }
                                        ?>
                                            <div class="wf-timeline-dot <?= $cls ?>" title="<?= e(ucfirst($key)) ?>"></div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="wf-track-badge" style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;">
                                        <i class="fas <?= $st['icon'] ?>" style="font-size:0.65rem;"></i>
                                        <?= e($st['label']) ?>
                                    </span>
                                </td>

                                <td><span class="wf-date"><?= !empty($r['tracking_date']) ? date('M j, Y', strtotime($r['tracking_date'])) : '—' ?></span></td>

                                <td>
                                    <div class="wf-row-actions">
                                        <button type="button" class="wf-icon-btn" title="View Details"
                                            onclick='openPanel(<?= json_encode([
                                                "tracking_id"      => $r["Tracking_ID"],
                                                "customer_id"      => $r["Customer_ID"],
                                                "schedule_id"      => $r["Schedule_ID"],
                                                "customer"         => $customer_name,
                                                "contact"          => $r["contact_number"] ?? "",
                                                "service"          => $r["service"] ?? "",
                                                "pick_deliver"     => $r["pick_deliver"] ?? "",
                                                "laundry_status"   => $r["laundry_status"] ?? "",
                                                "scheduled_time"   => $r["Scheduled_time"]  ?? "",
                                                "pickup_time"      => $r["Pickup_time"]     ?? "",
                                                "processing_time"  => $r["Processing_time"] ?? "",
                                                "ready_time"       => $r["Ready_time"]      ?? "",
                                                "completed_time"   => $r["Completed_time"]  ?? "",
                                                "tracking_time"    => $r["tracking_time"]   ?? "",
                                                "tracking_date"    => $r["tracking_date"]   ?? "",
                                                "booking_status"   => $r["booking_status"]  ?? "",
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
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> records
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
<aside class="wf-panel" id="trackingPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-route"></i></div>
            <div>
                <h3 id="panelTitle">Tracking Details</h3>
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
const trackingPanel = document.getElementById('trackingPanel');

function openPanel(data) {
    const statusMap = {
        'scheduled':   { color:'#00537A', bg:'#EBF5FB', label:'Scheduled',  icon:'fa-calendar-check' },
        'picked up':   { color:'#946200', bg:'#FEF9E7', label:'Picked Up',  icon:'fa-truck-pickup' },
        'pickedup':    { color:'#946200', bg:'#FEF9E7', label:'Picked Up',  icon:'fa-truck-pickup' },
        'processing':  { color:'#5B2E91', bg:'#F4ECFB', label:'Processing', icon:'fa-spinner' },
        'ready':       { color:'#946200', bg:'#FFF9DB', label:'Ready',      icon:'fa-box-open' },
        'completed':   { color:'#1E7E45', bg:'#EAF7F0', label:'Completed',  icon:'fa-check-double' },
    };
    const stKey = String(data.laundry_status).toLowerCase();
    const st = statusMap[stKey] || { color:'#5A7184', bg:'#F4F7F9', label:data.laundry_status, icon:'fa-circle' };

    document.getElementById('panelTitle').textContent = 'Tracking #' + data.tracking_id;
    document.getElementById('panelSubtitle').textContent = 'Schedule #' + (data.schedule_id || '—') + ' • ' + (data.tracking_date ? formatDate(data.tracking_date) : '—');

    let html = '';

    // Current status
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-info-circle"></i> Current Status</div>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            <span class="wf-track-badge" style="color:${st.color};background:${st.bg};font-size:0.8rem;padding:0.5rem 0.9rem;">
                <i class="fas ${st.icon}"></i> ${st.label}
            </span>
        </div>
    </div>`;

    // Customer
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
            <div class="wf-detail-item">
                <div class="wf-detail-label">Customer ID</div>
                <div class="wf-detail-value mono">${escapeHtml(data.customer_id)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Schedule ID</div>
                <div class="wf-detail-value mono">${escapeHtml(data.schedule_id)}</div>
            </div>
        </div>
    </div>`;

    // Service info
    if (data.service || data.pick_deliver) {
        html += `
        <div class="wf-detail-section">
            <div class="wf-detail-section-title"><i class="fas fa-soap"></i> Service Info</div>
            <div class="wf-detail-grid">
                ${data.service ? `
                <div class="wf-detail-item full">
                    <div class="wf-detail-label">Service</div>
                    <div class="wf-detail-value">${escapeHtml(data.service)}</div>
                </div>` : ''}
                ${data.pick_deliver ? `
                <div class="wf-detail-item full">
                    <div class="wf-detail-label">Pickup / Delivery</div>
                    <div class="wf-detail-value muted">${escapeHtml(data.pick_deliver)}</div>
                </div>` : ''}
                ${data.booking_status ? `
                <div class="wf-detail-item">
                    <div class="wf-detail-label">Booking Status</div>
                    <div class="wf-detail-value">${escapeHtml(data.booking_status)}</div>
                </div>` : ''}
            </div>
        </div>`;
    }

    // Timeline
    const steps = [
        { key: 'scheduled',  label: 'Scheduled',  time: data.scheduled_time },
        { key: 'picked up',  label: 'Picked Up',  time: data.pickup_time },
        { key: 'processing', label: 'Processing', time: data.processing_time },
        { key: 'ready',      label: 'Ready',      time: data.ready_time },
        { key: 'completed',  label: 'Completed',  time: data.completed_time },
    ];

    const stepKeys = ['scheduled', 'picked up', 'processing', 'ready', 'completed'];
    let currentIdx = stepKeys.indexOf(stKey);
    if (currentIdx === -1) currentIdx = -1;

    let timelineHtml = '<div class="wf-track-timeline">';
    steps.forEach((step, i) => {
        let cls = '';
        if (currentIdx >= 0) {
            if (i < currentIdx) cls = 'done';
            else if (i === currentIdx) cls = 'current';
        }
        const icon = cls === 'done' ? 'fa-check' : (cls === 'current' ? 'fa-circle' : 'fa-circle');
        timelineHtml += `
            <div class="wf-track-step ${cls}">
                <div class="wf-track-step-icon"><i class="fas ${icon}"></i></div>
                <div class="wf-track-step-label">${step.label}</div>
                <div class="wf-track-step-time">${step.time ? escapeHtml(step.time) : '—'}</div>
            </div>
        `;
    });
    timelineHtml += '</div>';

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-stream"></i> Progress Timeline</div>
        ${timelineHtml}
    </div>`;

    document.getElementById('panelBody').innerHTML = html;

    document.getElementById('panelFooter').innerHTML = '';

    panelOverlay.classList.add('open');
    trackingPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    panelOverlay.classList.remove('open');
    trackingPanel.classList.remove('open');
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