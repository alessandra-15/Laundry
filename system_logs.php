<?php
/**
 * system_logs.php
 * WashFlow — Admin Activity Logs
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id   = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Admin';

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

function action_icon($action) {
    $a = strtolower((string)$action);
    if (strpos($a, 'login') !== false)     return ['icon' => 'fa-sign-in-alt',      'color' => '#1E7E45', 'bg' => '#EAF7F0'];
    if (strpos($a, 'logout') !== false)    return ['icon' => 'fa-sign-out-alt',     'color' => '#A8322D', 'bg' => '#FDEDEC'];
    if (strpos($a, 'delete') !== false)    return ['icon' => 'fa-trash',            'color' => '#A8322D', 'bg' => '#FDEDEC'];
    if (strpos($a, 'create') !== false || strpos($a, 'insert') !== false || strpos($a, 'add') !== false)
        return ['icon' => 'fa-plus-circle',   'color' => '#1E7E45', 'bg' => '#EAF7F0'];
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false)
        return ['icon' => 'fa-pen',           'color' => '#00537A', 'bg' => '#EBF5FB'];
    if (strpos($a, 'booking') !== false)   return ['icon' => 'fa-clipboard-list','color' => '#5B2E91', 'bg' => '#F4ECFB'];
    if (strpos($a, 'complaint') !== false) return ['icon' => 'fa-headset',        'color' => '#946200', 'bg' => '#FEF9E7'];
    if (strpos($a, 'feedback') !== false)  return ['icon' => 'fa-star',           'color' => '#B88A00', 'bg' => '#FFF9DB'];
    if (strpos($a, 'payment') !== false)   return ['icon' => 'fa-credit-card',    'color' => '#00537A', 'bg' => '#EBF5FB'];
    if (strpos($a, 'inventory') !== false || strpos($a, 'stock') !== false)
        return ['icon' => 'fa-boxes',         'color' => '#5B2E91', 'bg' => '#F4ECFB'];
    if (strpos($a, 'staff') !== false)     return ['icon' => 'fa-user-tie',       'color' => '#00537A', 'bg' => '#EBF5FB'];
    return ['icon' => 'fa-info-circle', 'color' => '#5A7184', 'bg' => '#F4F7F9'];
}

/* ══════════════════════════════════════════
   POST HANDLERS
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    /* ---------- CLEAR ALL LOGS ---------- */
    if ($action === 'clear_all') {
        $ok = false;
        try {
            $ok = $conn->query("DELETE FROM system_logs");
            if ($ok && class_exists('Logger')) {
                Logger::info('All system logs cleared', ['admin' => $admin_id]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Clear logs failed', ['error' => $ex->getMessage()]);
        }
        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok ? 'All activity logs cleared.' : 'Failed to clear logs.'
        ];
    }

    /* ---------- DELETE SINGLE LOG ---------- */
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $ok = false;
        try {
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM system_logs WHERE log_id = ?");
                $stmt->bind_param('i', $id);
                $ok = $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Delete log failed', ['error' => $ex->getMessage()]);
        }
        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok ? "Log #$id deleted." : 'Failed to delete log.'
        ];
    }

    /* ---------- CLEAR OLDER THAN X DAYS ---------- */
    if ($action === 'clear_older') {
        $days = max(1, (int)($_POST['days'] ?? 30));
        $ok = false;
        try {
            $stmt = $conn->prepare("DELETE FROM system_logs WHERE timestamp < (NOW() - INTERVAL ? DAY)");
            $stmt->bind_param('i', $days);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok && class_exists('Logger')) {
                Logger::info("Logs older than $days days cleared", ['admin' => $admin_id]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Clear old logs failed', ['error' => $ex->getMessage()]);
        }
        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok ? "Logs older than $days days cleared." : 'Failed to clear logs.'
        ];
    }

    /* ---------- CSV EXPORT ---------- */
    if ($action === 'export_csv') {
        $search_f  = trim($_POST['q'] ?? '');
        $action_f  = trim($_POST['action_f'] ?? '');
        $date_from = trim($_POST['date_from'] ?? '');
        $date_to   = trim($_POST['date_to'] ?? '');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity_logs_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Log ID', 'Admin ID', 'Action', 'Description', 'Timestamp']);

        $where = ["1=1"];
        $params = [];
        $types = '';

        if ($search_f !== '') {
            $where[] = "(action LIKE ? OR description LIKE ?)";
            $like = '%' . $search_f . '%';
            $params[] = $like; $params[] = $like;
            $types .= 'ss';
        }
        if ($action_f !== '') {
            $where[] = "action = ?";
            $params[] = $action_f;
            $types .= 's';
        }
        if ($date_from !== '') { $where[] = "DATE(timestamp) >= ?"; $params[] = $date_from; $types .= 's'; }
        if ($date_to !== '')   { $where[] = "DATE(timestamp) <= ?"; $params[] = $date_to; $types .= 's'; }

        $where_sql = implode(' AND ', $where);

        $stmt = $conn->prepare("
            SELECT log_id, admin_id, action, description, timestamp
            FROM system_logs
            WHERE $where_sql
            ORDER BY timestamp DESC
        ");
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [
                $r['log_id'],
                $r['admin_id'],
                $r['action'],
                $r['description'],
                $r['timestamp']
            ]);
        }
        $stmt->close();
        fclose($out);
        exit();
    }
}

/* ══════════════════════════════════════════
   FILTERS + PAGINATION
   ══════════════════════════════════════════ */
$search     = trim($_GET['q'] ?? '');
$action_f   = trim($_GET['action_f'] ?? '');
$date_from  = trim($_GET['date_from'] ?? '');
$date_to    = trim($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 25;
$offset     = ($page - 1) * $per_page;

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = ['total' => 0, 'today' => 0, 'week' => 0, 'admins' => 0];

try {
    $q = $conn->query("SELECT COUNT(*) c FROM system_logs");
    if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM system_logs WHERE DATE(timestamp) = CURDATE()");
    if ($q) $stats['today'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM system_logs WHERE timestamp >= (NOW() - INTERVAL 7 DAY)");
    if ($q) $stats['week'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT admin_id) c FROM system_logs");
    if ($q) $stats['admins'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Logs stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   DISTINCT ACTIONS (for filter dropdown)
   ══════════════════════════════════════════ */
$distinct_actions = [];
try {
    $q = $conn->query("SELECT DISTINCT action FROM system_logs WHERE action <> '' ORDER BY action ASC");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $distinct_actions[] = $row['action'];
        }
    }
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Distinct actions failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH LOGS
   ══════════════════════════════════════════ */
$logs        = [];
$total_rows  = 0;
$total_pages = 1;

try {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[] = "(action LIKE ? OR description LIKE ? OR admin_id LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types .= 'sss';
    }
    if ($action_f !== '') {
        $where[] = "action = ?";
        $params[] = $action_f;
        $types .= 's';
    }
    if ($date_from !== '') { $where[] = "DATE(timestamp) >= ?"; $params[] = $date_from; $types .= 's'; }
    if ($date_to !== '')   { $where[] = "DATE(timestamp) <= ?"; $params[] = $date_to; $types .= 's'; }

    $where_sql = implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) c FROM system_logs WHERE $where_sql";
    $stmt = $conn->prepare($count_sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    $list_sql = "SELECT log_id, admin_id, action, description, timestamp
                 FROM system_logs
                 WHERE $where_sql
                 ORDER BY timestamp DESC
                 LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($list_sql);
    $bind_types  = $types . 'ii';
    $bind_params = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch logs failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   URL HELPER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'q'         => $_GET['q']         ?? '',
        'action_f'  => $_GET['action_f']  ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'page'      => $_GET['page']      ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'system_logs.php?' . http_build_query(array_filter($merged, function($v) {
        return $v !== '' && $v !== null;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Logs — WashFlow</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --dark-blue:       #063452;
    --dark-blue-deep:  #042640;
    --primary:         #005A85;
    --primary-mid:     #0076A8;
    --light-blue:      #A8E8F9;
    --light-blue-soft: #E8F6FC;
    --light-blue-pale: #F2FAFD;

    --yellow:          #FFD93D;
    --yellow-soft:     #FFF9DB;
    --yellow-dark:     #B88A00;

    --bg:              #F5F9FC;
    --card:            #FFFFFF;

    --text-primary:    #0A2540;
    --text-secondary:  #5A7184;
    --text-muted:      #94A9B8;

    --border:          #E1EEF5;
    --border-soft:     #F0F6FA;

    --green:           #27AE60;
    --red:             #E74C3C;
    --purple:          #9B59B6;
    --gold:            #F0B400;
}

* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; }

body {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--text-primary);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    background: var(--bg);
    overflow: hidden;
    font-size: 14px;
}

h1, h2, h3, h4, h5 { font-weight: 700; letter-spacing: -0.02em; }
a { text-decoration: none; }

/* LAYOUT */
.wf-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    height: 100vh;
    overflow: hidden;
}

/* SIDEBAR */
.wf-sidebar {
    background: var(--dark-blue-deep);
    color: white;
    height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.wf-sidebar::-webkit-scrollbar { display: none; width: 0; }

.wf-sidebar-brand {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 2rem 1.75rem;
    border-bottom: 1px solid rgba(168, 232, 249, 0.08);
}
.wf-sidebar-brand-logo { width: 46px; height: 46px; flex-shrink: 0; }
.wf-sidebar-brand-logo svg { width: 100%; height: 100%; }
.wf-sidebar-brand-text { display: flex; flex-direction: column; line-height: 1; }
.wf-sidebar-brand-name {
    font-size: 1.45rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.04em;
}
.wf-sidebar-brand-name .flow { color: var(--yellow); }
.wf-sidebar-brand-tag {
    font-size: 0.65rem;
    color: rgba(168, 232, 249, 0.5);
    letter-spacing: 0.2em;
    text-transform: uppercase;
    margin-top: 5px;
    font-weight: 600;
}

.wf-nav { padding: 1.5rem 1rem; flex: 1; }
.wf-nav-section-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: rgba(168, 232, 249, 0.35);
    letter-spacing: 0.22em;
    text-transform: uppercase;
    padding: 1.25rem 1rem 0.65rem;
}
.wf-nav-section-label:first-child { padding-top: 0.25rem; }

.wf-nav-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-bottom: 0.2rem;
    position: relative;
    cursor: pointer;
}
.wf-nav-item i { width: 22px; text-align: center; font-size: 1rem; flex-shrink: 0; }
.wf-nav-item span { flex: 1; }
.wf-nav-item:hover {
    background: rgba(168, 232, 249, 0.06);
    color: rgba(255, 255, 255, 0.95);
}
.wf-nav-item.active {
    background: rgba(255, 217, 61, 0.1);
    color: white;
    font-weight: 600;
}
.wf-nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 22px;
    background: var(--yellow);
    border-radius: 0 3px 3px 0;
}
.wf-nav-item.active i { color: var(--yellow); }

.wf-sidebar-footer {
    padding: 1.25rem 1rem;
    border-top: 1px solid rgba(168, 232, 249, 0.08);
}
.wf-user-card {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.85rem;
    border-radius: 11px;
    background: rgba(168, 232, 249, 0.05);
    margin-bottom: 0.6rem;
}
.wf-user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--yellow) 0%, var(--yellow-soft) 100%);
    color: var(--dark-blue-deep);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    flex-shrink: 0;
}
.wf-user-info { flex: 1; min-width: 0; line-height: 1.25; }
.wf-user-name {
    font-size: 0.92rem;
    font-weight: 700;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wf-user-role { font-size: 0.72rem; color: rgba(168, 232, 249, 0.5); font-weight: 500; }

.wf-btn-logout {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.75rem 0.85rem;
    border-radius: 11px;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}
.wf-btn-logout:hover { background: rgba(231, 76, 60, 0.12); color: #FF8B7E; }
.wf-btn-logout i { width: 22px; text-align: center; font-size: 1rem; }

/* MAIN */
.wf-main {
    padding: 2rem 2.5rem 3rem;
    overflow-y: auto;
    overflow-x: hidden;
    height: 100vh;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.wf-main::-webkit-scrollbar { display: none; width: 0; }

.wf-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.75rem;
    gap: 1rem;
    flex-wrap: wrap;
}
.wf-topbar-greeting h1 {
    color: var(--dark-blue);
    font-size: 1.6rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.03em;
    line-height: 1.3;
}
.wf-topbar-greeting h1 .accent { color: var(--gold); }
.wf-topbar-greeting p {
    color: var(--text-secondary);
    font-size: 0.88rem;
    margin: 0.2rem 0 0;
    font-weight: 500;
}
.wf-topbar-date {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.1rem;
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark-blue);
}
.wf-topbar-date i { color: var(--gold); font-size: 0.85rem; }

/* STATS */
.wf-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.wf-stat-card {
    background: white;
    border-radius: 14px;
    padding: 1.25rem 1.25rem;
    border: 1px solid var(--border);
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 130px;
}
.wf-stat-card:hover {
    border-color: var(--light-blue);
    box-shadow: 0 6px 20px rgba(0, 83, 122, 0.08);
    transform: translateY(-3px);
}
.wf-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.85rem;
}
.wf-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.wf-stat-icon.icon-blue   { background: var(--light-blue-soft); color: var(--primary); }
.wf-stat-icon.icon-yellow { background: var(--yellow-soft); color: var(--yellow-dark); }
.wf-stat-icon.icon-green  { background: #EAF7F0; color: #1E7E45; }
.wf-stat-icon.icon-purple { background: #F4ECFB; color: #5B2E91; }
.wf-stat-icon.icon-dark   { background: #E6EDF3; color: var(--dark-blue); }
.wf-stat-icon.icon-red    { background: #FDEDEC; color: #A8322D; }
.wf-stat-icon.icon-gold   { background: #FFF4CC; color: #8A6400; }

.wf-stat-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--dark-blue);
    line-height: 1.1;
    letter-spacing: -0.03em;
    margin-bottom: 0.2rem;
}
.wf-stat-label {
    font-size: 0.78rem;
    color: var(--text-secondary);
    font-weight: 500;
}

/* FILTER BAR */
.wf-filter-bar {
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: nowrap;
}
.wf-filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-width: 0;
}
.wf-filter-group label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: block;
    white-space: nowrap;
}
.wf-input, .wf-select {
    width: 100%;
    padding: 0.65rem 0.9rem;
    border: 1px solid var(--border);
    border-radius: 9px;
    font-size: 0.85rem;
    font-family: inherit;
    color: var(--text-primary);
    background: white;
    transition: all 0.2s;
    outline: none;
}
.wf-input:focus, .wf-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 90, 133, 0.1);
}
.wf-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.2rem;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
    font-family: inherit;
    white-space: nowrap;
}
.wf-btn-primary { background: var(--primary); color: white; }
.wf-btn-primary:hover { background: var(--primary-mid); }
.wf-btn-outline {
    background: white;
    color: var(--primary);
    border-color: var(--border);
}
.wf-btn-outline:hover { border-color: var(--primary); background: var(--light-blue-pale); }
.wf-btn-yellow {
    background: var(--yellow);
    color: var(--dark-blue-deep);
}
.wf-btn-yellow:hover { background: #FFCE00; }
.wf-btn-danger {
    background: var(--red);
    color: white;
}
.wf-btn-danger:hover { background: #C0392B; }

/* CARD */
.wf-card {
    background: white;
    border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
}

/* CARD HEADER */
.wf-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.4rem 1.6rem;
    border-bottom: 1px solid var(--border-soft);
    gap: 1rem;
    flex-wrap: wrap;
}
.wf-card-title { display: flex; align-items: center; gap: 0.95rem; }
.wf-card-title-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    background: var(--yellow-soft);
    color: var(--yellow-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.wf-card-title-icon.icon-blue { background: var(--light-blue-soft); color: var(--primary); }
.wf-card-title-icon.icon-warn { background: #FEF3E2; color: #C2410C; }
.wf-card-title-icon.icon-good { background: #EAF7F0; color: #1E7E45; }
.wf-card-title-icon.icon-red { background: #FDEDEC; color: #A8322D; }
.wf-card-title h3 {
    color: var(--dark-blue);
    font-size: 1.02rem;
    font-weight: 700;
    margin: 0;
}
.wf-card-title p {
    color: var(--text-muted);
    font-size: 0.78rem;
    margin: 0.15rem 0 0;
    font-weight: 500;
}

/* TABLE */
.wf-table-wrap { overflow-x: auto; }
.wf-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.wf-table thead th {
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 0.9rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
    background: #FAFCFE;
}
.wf-table tbody td {
    padding: 0.95rem 1rem;
    border-bottom: 1px solid var(--border-soft);
    vertical-align: middle;
}
.wf-table tbody tr:last-child td { border-bottom: none; }
.wf-table tbody tr { transition: background 0.15s; }
.wf-table tbody tr:hover { background: var(--light-blue-pale); }

.wf-log-id {
    font-weight: 700;
    color: var(--primary);
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.8rem;
}
.wf-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--dark-blue);
    font-size: 0.85rem;
}
.wf-action-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    flex-shrink: 0;
}
.wf-description {
    color: var(--text-secondary);
    font-size: 0.84rem;
    max-width: 420px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-height: 1.5;
}
.wf-admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.65rem;
    background: var(--light-blue-pale);
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--primary);
}
.wf-admin-badge i { font-size: 0.7rem; }
.wf-date { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }
.wf-date .time {
    display: block;
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 500;
}

.wf-row-actions {
    display: flex;
    gap: 0.4rem;
    align-items: center;
    justify-content: flex-end;
}
.wf-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 0.8rem;
}
.wf-icon-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--light-blue-pale);
}
.wf-icon-btn.view:hover { border-color: var(--primary); color: var(--primary); }
.wf-icon-btn.delete:hover { border-color: var(--red); color: var(--red); background: #FDEDEC; }

/* EMPTY */
.wf-empty {
    text-align: center;
    padding: 3.5rem 1rem;
    color: var(--text-muted);
}
.wf-empty i {
    font-size: 3rem;
    color: var(--light-blue);
    margin-bottom: 0.9rem;
    display: block;
}
.wf-empty p { margin: 0; font-size: 0.9rem; font-weight: 500; }

/* PAGINATION */
.wf-pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-soft);
    flex-wrap: wrap;
    gap: 0.75rem;
}
.wf-pagination-info {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 500;
}
.wf-pagination {
    display: flex;
    gap: 0.3rem;
    align-items: center;
}
.wf-page-btn {
    min-width: 36px;
    height: 36px;
    padding: 0 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.wf-page-btn:hover:not(.disabled):not(.active) {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--light-blue-pale);
}
.wf-page-btn.active {
    background: var(--dark-blue);
    color: white;
    border-color: var(--dark-blue);
}
.wf-page-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

/* SIDE PANEL */
.wf-panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(6, 52, 82, 0.4);
    backdrop-filter: blur(2px);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1040;
}
.wf-panel-overlay.open { opacity: 1; visibility: visible; }

.wf-panel {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 600px;
    max-width: 100vw;
    background: white;
    z-index: 1050;
    transform: translateX(100%);
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    box-shadow: -20px 0 60px rgba(6, 52, 82, 0.15);
}
.wf-panel.open { transform: translateX(0); }

.wf-panel-header {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    background: linear-gradient(135deg, var(--light-blue-pale) 0%, white 100%);
}
.wf-panel-title {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    flex: 1;
    min-width: 0;
}
.wf-panel-title-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: var(--dark-blue);
    color: var(--yellow);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.wf-panel-title h3 {
    font-size: 1.05rem;
    color: var(--dark-blue);
    margin: 0;
    font-weight: 700;
}
.wf-panel-title p {
    font-size: 0.78rem;
    color: var(--text-muted);
    margin: 0.15rem 0 0;
    font-weight: 500;
}
.wf-panel-close {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    flex-shrink: 0;
}
.wf-panel-close:hover { border-color: var(--red); color: var(--red); background: #FDEDEC; }

.wf-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1.75rem;
}
.wf-panel-body::-webkit-scrollbar { width: 6px; }
.wf-panel-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

.wf-detail-section { margin-bottom: 1.5rem; }
.wf-detail-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.wf-detail-section-title i { color: var(--primary); }

.wf-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem 1rem;
}
.wf-detail-item { display: flex; flex-direction: column; gap: 0.2rem; }
.wf-detail-item.full { grid-column: 1 / -1; }
.wf-detail-label {
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.wf-detail-value {
    font-size: 0.88rem;
    color: var(--text-primary);
    font-weight: 600;
    word-break: break-word;
}
.wf-detail-value.muted { color: var(--text-secondary); font-weight: 500; }

.wf-panel-footer {
    padding: 1.25rem 1.75rem;
    border-top: 1px solid var(--border);
    background: #FAFCFE;
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
}

/* TOAST */
.wf-toast-wrap {
    position: fixed;
    top: 1.5rem;
    right: 1.5rem;
    z-index: 1070;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    pointer-events: none;
}
.wf-toast {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.9rem 1.15rem;
    background: white;
    border-radius: 11px;
    box-shadow: 0 10px 30px rgba(6, 52, 82, 0.15);
    border-left: 4px solid var(--primary);
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark-blue);
    min-width: 260px;
    max-width: 380px;
    pointer-events: auto;
    animation: toastIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.wf-toast.success { border-left-color: var(--green); }
.wf-toast.error   { border-left-color: var(--red); }
.wf-toast i { font-size: 1rem; }
.wf-toast.success i { color: var(--green); }
.wf-toast.error i { color: var(--red); }
@keyframes toastIn {
    from { transform: translateX(120%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}
@keyframes toastOut {
    from { transform: translateX(0);    opacity: 1; }
    to   { transform: translateX(120%); opacity: 0; }
}
.wf-toast.hide { animation: toastOut 0.3s forwards; }

/* MOBILE */
.wf-sidebar-toggle {
    display: none;
    position: fixed;
    top: 1rem; left: 1rem;
    z-index: 1001;
    width: 44px; height: 44px;
    background: var(--dark-blue-deep);
    color: white;
    border: none;
    border-radius: 10px;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(4, 38, 64, 0.3);
    cursor: pointer;
}
.wf-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}
.wf-sidebar-overlay.open { display: block; }

@media (max-width: 1400px) {
    .wf-filter-bar { flex-wrap: wrap; }
    .wf-filter-bar > .wf-filter-group:last-child {
        margin-left: 0 !important;
        flex: 1 0 100% !important;
        justify-content: flex-end;
        margin-top: 0.5rem;
    }
}
@media (max-width: 1200px) {
    .wf-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .wf-panel { width: 500px; }
}
@media (max-width: 1024px) {
    .wf-layout { grid-template-columns: 260px 1fr; }
    .wf-main { padding: 1.5rem; }
}
@media (max-width: 900px) {
    .wf-layout { grid-template-columns: 1fr; }
    .wf-sidebar {
        position: fixed;
        top: 0; left: -300px;
        width: 300px;
        z-index: 1000;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 20px 0 60px rgba(0, 0, 0, 0.3);
    }
    .wf-sidebar.open { left: 0; }
    .wf-sidebar-toggle { display: flex; }
    .wf-main { padding: 1.25rem; padding-top: 4rem; }
    .wf-topbar { padding-left: 0; }
    .wf-topbar-greeting h1 { font-size: 1.35rem; }
    .wf-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .wf-panel { width: 100%; }
    .wf-detail-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .wf-stats-grid { grid-template-columns: 1fr; }
    .wf-filter-bar { flex-direction: column; align-items: stretch; }
    .wf-filter-group { min-width: 100%; }
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
                            <stop offset="0%" stop-color="#0076A8"/>
                            <stop offset="100%" stop-color="#005A85"/>
                        </linearGradient>
                        <linearGradient id="wfSideWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FFD93D"/>
                            <stop offset="100%" stop-color="#A8E8F9"/>
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
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="booking_management.php" class="wf-nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Bookings</span>
            </a>
            <a href="customer_management.php" class="wf-nav-item">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
            <a href="staff_management.php" class="wf-nav-item">
                <i class="fas fa-user-tie"></i>
                <span>Staff</span>
            </a>
            <a href="inventory.php" class="wf-nav-item">
                <i class="fas fa-boxes"></i>
                <span>Inventory</span>
            </a>

            <div class="wf-nav-section-label">Insights</div>
            <a href="reports.php" class="wf-nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
            <a href="complaints.php" class="wf-nav-item">
                <i class="fas fa-headset"></i>
                <span>Complaints</span>
            </a>
            <a href="feedback.php" class="wf-nav-item">
                <i class="fas fa-star"></i>
                <span>Feedback</span>
            </a>
            <a href="system_logs.php" class="wf-nav-item active">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
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
                <h1>Activity <span class="accent">Logs</span></h1>
                <p>Track all system actions and admin activity</p>
            </div>
            <div class="wf-topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, F j, Y') ?>
            </div>
        </div>

        <!-- FLASH -->
        <?php if ($flash): ?>
        <div id="serverFlash" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" style="display:none;"></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-history"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Logs</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-calendar-day"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['today']) ?></div>
                    <div class="wf-stat-label">Today's Activity</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-purple"><i class="fas fa-calendar-week"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['week']) ?></div>
                    <div class="wf-stat-label">Last 7 Days</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-user-shield"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['admins']) ?></div>
                    <div class="wf-stat-label">Unique Admins</div>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form method="get" action="system_logs.php" class="wf-filter-bar">
            <div class="wf-filter-group" style="flex: 1 1 220px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="Action, description, admin ID..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 200px;">
                <label>Action Type</label>
                <select name="action_f" class="wf-select">
                    <option value="">All Actions</option>
                    <?php foreach ($distinct_actions as $a): ?>
                    <option value="<?= e($a) ?>" <?= $action_f === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 145px;">
                <label>From</label>
                <input type="date" name="date_from" class="wf-input" value="<?= e($date_from) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 145px;">
                <label>To</label>
                <input type="date" name="date_to" class="wf-input" value="<?= e($date_to) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 auto; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="system_logs.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
                <button type="button" class="wf-btn wf-btn-yellow"
                        onclick="document.getElementById('exportForm').submit();">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </form>

        <!-- Export form -->
        <form method="post" id="exportForm" style="display:none;">
            <input type="hidden" name="action" value="export_csv">
            <input type="hidden" name="q" value="<?= e($search) ?>">
            <input type="hidden" name="action_f" value="<?= e($action_f) ?>">
            <input type="hidden" name="date_from" value="<?= e($date_from) ?>">
            <input type="hidden" name="date_to" value="<?= e($date_to) ?>">
        </form>

        <!-- LOGS TABLE -->
        <div class="wf-card">
            <div class="wf-card-header">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon icon-blue">
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <div>
                        <h3>System Activity</h3>
                        <p>Chronological list of all recorded actions</p>
                    </div>
                </div>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <button type="button" class="wf-btn wf-btn-outline" style="border-color:#FBE9DC;color:#8B4A1F;"
                            onclick="openClearOldModal()">
                        <i class="fas fa-broom"></i> Clear Old
                    </button>
                    <button type="button" class="wf-btn wf-btn-danger"
                            onclick="confirmClearAll()">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>
            </div>

            <?php if (empty($logs)): ?>
                <div class="wf-empty">
                    <i class="fas fa-history"></i>
                    <p>No activity logs found</p>
                </div>
            <?php else: ?>
            <div class="wf-table-wrap">
                <table class="wf-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Admin</th>
                            <th>Timestamp</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log):
                            $meta = action_icon($log['action']);
                        ?>
                        <tr>
                            <td><span class="wf-log-id">#<?= e($log['log_id']) ?></span></td>
                            <td>
                                <div class="wf-action">
                                    <span class="wf-action-icon" style="color:<?= $meta['color'] ?>;background:<?= $meta['bg'] ?>;">
                                        <i class="fas <?= $meta['icon'] ?>"></i>
                                    </span>
                                    <?= e($log['action']) ?>
                                </div>
                            </td>
                            <td><div class="wf-description"><?= e($log['description'] ?: '—') ?></div></td>
                            <td>
                                <span class="wf-admin-badge">
                                    <i class="fas fa-user-shield"></i>
                                    Admin #<?= e($log['admin_id']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="wf-date">
                                    <?= date('M j, Y', strtotime($log['timestamp'])) ?>
                                    <span class="time"><?= date('h:i A', strtotime($log['timestamp'])) ?></span>
                                </span>
                            </td>
                            <td>
                                <div class="wf-row-actions">
                                    <button type="button" class="wf-icon-btn view" title="View Details"
                                        onclick='openPanel(<?= json_encode([
                                            "id"          => $log["log_id"],
                                            "admin_id"    => $log["admin_id"],
                                            "action"      => $log["action"],
                                            "description" => $log["description"] ?? "",
                                            "timestamp"   => $log["timestamp"],
                                        ]) ?>)'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="wf-icon-btn delete" title="Delete"
                                        onclick='confirmDelete(<?= (int)$log["log_id"] ?>)'>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="wf-pagination-wrap">
                <div class="wf-pagination-info">
                    Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> logs
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
                        <?php if ($start_page > 2): ?>
                            <span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                        <a href="<?= e(build_url(['page' => $p])) ?>" class="wf-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span>
                        <?php endif; ?>
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

<!-- ══════════════════════════════════════════
     SIDE PANEL — Log Details
     ══════════════════════════════════════════ -->
<div class="wf-panel-overlay" id="panelOverlay" onclick="closePanel()"></div>
<aside class="wf-panel" id="logPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-history"></i></div>
            <div>
                <h3 id="panelTitle">Log Details</h3>
                <p id="panelSubtitle">Loading...</p>
            </div>
        </div>
        <button type="button" class="wf-panel-close" onclick="closePanel()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="wf-panel-body" id="panelBody">
        <!-- dynamic -->
    </div>
    <div class="wf-panel-footer" id="panelFooter">
        <!-- dynamic -->
    </div>
</aside>

<!-- MODAL — Clear Old Logs -->
<div class="wf-panel-overlay" id="clearOldOverlay" onclick="closeClearOldModal()"></div>
<div id="clearOldModal" style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%) scale(0.95); width:min(440px, 92vw); background:white; border-radius:16px; padding:1.75rem; z-index:1060; opacity:0; visibility:hidden; transition:all 0.25s; box-shadow:0 20px 60px rgba(6,52,82,0.25);">
    <div style="width:56px;height:56px;border-radius:14px;background:#FEF3E2;color:#C2410C;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin:0 auto 1rem;">
        <i class="fas fa-broom"></i>
    </div>
    <h3 style="font-size:1.15rem;color:var(--dark-blue);text-align:center;margin-bottom:0.5rem;">Clear Old Logs</h3>
    <p style="font-size:0.88rem;color:var(--text-secondary);text-align:center;margin-bottom:1.5rem;line-height:1.6;">
        Delete all logs older than the specified number of days.
    </p>
    <div style="margin-bottom:1.25rem;">
        <label style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.4rem;">
            Keep logs for the last (days)
        </label>
        <input type="number" id="clearOldDays" value="30" min="1" max="3650" class="wf-input">
    </div>
    <div style="display:flex; gap:0.6rem; justify-content:center;">
        <button type="button" class="wf-btn wf-btn-outline" onclick="closeClearOldModal()">Cancel</button>
        <button type="button" class="wf-btn wf-btn-danger" onclick="submitClearOld()">
            <i class="fas fa-trash"></i> Clear Logs
        </button>
    </div>
</div>

<!-- Hidden forms -->
<form method="post" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<form method="post" id="clearAllForm" style="display:none;">
    <input type="hidden" name="action" value="clear_all">
</form>

<form method="post" id="clearOldForm" style="display:none;">
    <input type="hidden" name="action" value="clear_older">
    <input type="hidden" name="days" id="clearOldDaysInput">
</form>

<!-- TOAST WRAP -->
<div class="wf-toast-wrap" id="toastWrap"></div>

<script>
/* ══════════════════════════════════════════
   TOASTS
   ══════════════════════════════════════════ */
function showToast(type, message) {
    const wrap = document.getElementById('toastWrap');
    const toast = document.createElement('div');
    toast.className = 'wf-toast ' + (type === 'success' ? 'success' : 'error');
    toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i><span>' + message + '</span>';
    wrap.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

(function() {
    const flash = document.getElementById('serverFlash');
    if (flash) showToast(flash.dataset.type, flash.dataset.message);
})();

/* ══════════════════════════════════════════
   SIDE PANEL
   ══════════════════════════════════════════ */
const panelOverlay = document.getElementById('panelOverlay');
const logPanel = document.getElementById('logPanel');

function openPanel(data) {
    document.getElementById('panelTitle').textContent = 'Log #' + data.id;
    document.getElementById('panelSubtitle').textContent = formatDate(data.timestamp);

    const meta = getActionIcon(data.action);

    let html = '';

    /* Action */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-bolt"></i> Action</div>
        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.9rem 1.1rem;background:${meta.bg};border-radius:10px;">
            <span style="width:38px;height:38px;border-radius:10px;background:white;color:${meta.color};display:flex;align-items:center;justify-content:center;font-size:1rem;">
                <i class="fas ${meta.icon}"></i>
            </span>
            <div>
                <div style="font-size:0.95rem;font-weight:700;color:${meta.color};">${escapeHtml(data.action)}</div>
                <div style="font-size:0.72rem;color:var(--text-muted);font-weight:600;">Action Type</div>
            </div>
        </div>
    </div>`;

    /* Description */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-align-left"></i> Description</div>
        <div style="background:var(--light-blue-pale);border:1px solid var(--border);border-radius:10px;padding:1rem;font-size:0.88rem;color:var(--text-primary);line-height:1.6;">
            ${escapeHtml(data.description) || '<span style="color:var(--text-muted);">No description</span>'}
        </div>
    </div>`;

    /* Meta */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-info-circle"></i> Details</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Log ID</div>
                <div class="wf-detail-value">#${escapeHtml(data.id)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Admin ID</div>
                <div class="wf-detail-value">#${escapeHtml(data.admin_id)}</div>
            </div>
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Timestamp</div>
                <div class="wf-detail-value">${formatDate(data.timestamp)}</div>
            </div>
        </div>
    </div>`;

    document.getElementById('panelBody').innerHTML = html;

    /* Footer */
    let footerHtml = '';
    footerHtml += `<button type="button" class="wf-btn wf-btn-danger" onclick='closePanel(); confirmDelete(${data.id})'><i class="fas fa-trash"></i> Delete Log</button>`;
    footerHtml += `<button type="button" class="wf-btn wf-btn-outline" onclick="closePanel()"><i class="fas fa-times"></i> Close</button>`;

    document.getElementById('panelFooter').innerHTML = footerHtml;

    panelOverlay.classList.add('open');
    logPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    panelOverlay.classList.remove('open');
    logPanel.classList.remove('open');
    document.body.style.overflow = '';
}

/* ══════════════════════════════════════════
   DELETE / CLEAR
   ══════════════════════════════════════════ */
function confirmDelete(id) {
    if (confirm('Delete log #' + id + '?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function confirmClearAll() {
    if (confirm('⚠️ Delete ALL activity logs?\n\nThis action cannot be undone.\n\nType OK to confirm.')) {
        document.getElementById('clearAllForm').submit();
    }
}

function openClearOldModal() {
    const modal = document.getElementById('clearOldModal');
    const overlay = document.getElementById('clearOldOverlay');
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    modal.style.transform = 'translate(-50%,-50%) scale(1)';
    overlay.classList.add('open');
}

function closeClearOldModal() {
    const modal = document.getElementById('clearOldModal');
    const overlay = document.getElementById('clearOldOverlay');
    modal.style.opacity = '0';
    modal.style.visibility = 'hidden';
    modal.style.transform = 'translate(-50%,-50%) scale(0.95)';
    overlay.classList.remove('open');
}

function submitClearOld() {
    const days = parseInt(document.getElementById('clearOldDays').value) || 30;
    if (days < 1) { showToast('error', 'Please enter at least 1 day.'); return; }
    document.getElementById('clearOldDaysInput').value = days;
    document.getElementById('clearOldForm').submit();
}

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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

function getActionIcon(action) {
    const a = String(action || '').toLowerCase();
    if (a.includes('login'))     return { icon: 'fa-sign-in-alt', color: '#1E7E45', bg: '#EAF7F0' };
    if (a.includes('logout'))    return { icon: 'fa-sign-out-alt', color: '#A8322D', bg: '#FDEDEC' };
    if (a.includes('delete'))    return { icon: 'fa-trash', color: '#A8322D', bg: '#FDEDEC' };
    if (a.includes('create') || a.includes('insert') || a.includes('add'))
        return { icon: 'fa-plus-circle', color: '#1E7E45', bg: '#EAF7F0' };
    if (a.includes('update') || a.includes('edit'))
        return { icon: 'fa-pen', color: '#00537A', bg: '#EBF5FB' };
    if (a.includes('booking'))   return { icon: 'fa-clipboard-list', color: '#5B2E91', bg: '#F4ECFB' };
    if (a.includes('complaint')) return { icon: 'fa-headset', color: '#946200', bg: '#FEF9E7' };
    if (a.includes('feedback'))  return { icon: 'fa-star', color: '#B88A00', bg: '#FFF9DB' };
    if (a.includes('payment'))   return { icon: 'fa-credit-card', color: '#00537A', bg: '#EBF5FB' };
    if (a.includes('inventory') || a.includes('stock'))
        return { icon: 'fa-boxes', color: '#5B2E91', bg: '#F4ECFB' };
    if (a.includes('staff'))     return { icon: 'fa-user-tie', color: '#00537A', bg: '#EBF5FB' };
    return { icon: 'fa-info-circle', color: '#5A7184', bg: '#F4F7F9' };
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePanel();
        closeClearOldModal();
    }
});

/* ══════════════════════════════════════════
   MOBILE SIDEBAR
   ══════════════════════════════════════════ */
const sidebar        = document.getElementById('wfSidebar');
const sidebarOverlay = document.getElementById('wfSidebarOverlay');
const sidebarToggle  = document.getElementById('wfSidebarToggle');

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