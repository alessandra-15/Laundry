<?php
/**
 * staff_management.php
 * WashFlow — ADMIN Staff Management & Monitoring
 *
 * ✅ Admin-only (session-based auth, same as admin_bookings.php)
 * ✅ List all staff (active/inactive)
 * ✅ Add / Edit / Deactivate / Reactivate staff
 * ✅ Reset staff password
 * ✅ Performance monitoring (bookings handled, revenue, cancellations)
 * ✅ Activity log per staff
 * ✅ Filters, search, sort, pagination
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* ══════════════════════════════════════════
   AUTH — ADMIN ONLY (session-based)
   ══════════════════════════════════════════ */
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
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/* ══════════════════════════════════════════
   CSRF
   ══════════════════════════════════════════ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ══════════════════════════════════════════
   POST ACTIONS
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $_SESSION['sm_flash'] = ['type' => 'error', 'message' => 'Invalid session token. Please refresh and try again.'];
        header('Location: staff_management.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    /* ── ADD STAFF ── */
    if ($action === 'add_staff') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $contact  = trim($_POST['contact_number'] ?? '');
        $role     = trim($_POST['role'] ?? 'staff');
        $status   = trim($_POST['status'] ?? 'active');

        if ($username === '' || $password === '' || $fullName === '') {
            $flash = ['type' => 'error', 'message' => 'Username, password, and full name are required.'];
        } elseif (!in_array($role, ['staff','manager'], true)) {
            $flash = ['type' => 'error', 'message' => 'Invalid role.'];
        } elseif (!in_array($status, ['active','inactive'], true)) {
            $flash = ['type' => 'error', 'message' => 'Invalid status.'];
        } else {
            try {
                $stmt = $conn->prepare("SELECT Staff_ID FROM staff WHERE username = ? LIMIT 1");
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $exists = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($exists) {
                    $flash = ['type' => 'error', 'message' => 'Username already taken.'];
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO staff (username, password, full_name, email, contact_number, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('sssssss', $username, $hash, $fullName, $email, $contact, $role, $status);
                    $ok = $stmt->execute();
                    $newId = $stmt->insert_id;
                    $stmt->close();

                    if ($ok) {
                        if (class_exists('Logger')) {
                            Logger::info('Admin added staff', [
                                'admin_id'   => $admin_id,
                                'admin_name' => $admin_name,
                                'staff_id'   => $newId,
                                'username'   => $username,
                            ]);
                        }
                        $flash = ['type' => 'success', 'message' => "Staff '{$fullName}' added successfully."];
                    } else {
                        $flash = ['type' => 'error', 'message' => 'Failed to add staff.'];
                    }
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Add staff failed', ['error' => $ex->getMessage()]);
                $flash = ['type' => 'error', 'message' => 'Database error: ' . $ex->getMessage()];
            }
        }
    }

    /* ── EDIT STAFF ── */
    elseif ($action === 'edit_staff') {
        $id       = (int)($_POST['staff_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $contact  = trim($_POST['contact_number'] ?? '');
        $role     = trim($_POST['role'] ?? 'staff');
        $status   = trim($_POST['status'] ?? 'active');
        $newPass  = $_POST['new_password'] ?? '';

        if ($id <= 0 || $fullName === '') {
            $flash = ['type' => 'error', 'message' => 'Staff ID and full name are required.'];
        } elseif (!in_array($role, ['staff','manager'], true)) {
            $flash = ['type' => 'error', 'message' => 'Invalid role.'];
        } elseif (!in_array($status, ['active','inactive'], true)) {
            $flash = ['type' => 'error', 'message' => 'Invalid status.'];
        } else {
            try {
                if ($newPass !== '') {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE staff SET full_name=?, email=?, contact_number=?, role=?, status=?, password=? WHERE Staff_ID=?");
                    $stmt->bind_param('ssssssi', $fullName, $email, $contact, $role, $status, $hash, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE staff SET full_name=?, email=?, contact_number=?, role=?, status=? WHERE Staff_ID=?");
                    $stmt->bind_param('sssssi', $fullName, $email, $contact, $role, $status, $id);
                }
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    if (class_exists('Logger')) {
                        Logger::info('Admin edited staff', [
                            'admin_id'   => $admin_id,
                            'staff_id'   => $id,
                            'new_pass'   => ($newPass !== ''),
                        ]);
                    }
                    $flash = ['type' => 'success', 'message' => 'Staff updated successfully.'];
                } else {
                    $flash = ['type' => 'error', 'message' => 'Failed to update staff.'];
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Edit staff failed', ['error' => $ex->getMessage()]);
                $flash = ['type' => 'error', 'message' => 'Database error: ' . $ex->getMessage()];
            }
        }
    }

    /* ── TOGGLE STATUS ── */
    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['staff_id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("SELECT status, full_name FROM staff WHERE Staff_ID=? LIMIT 1");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
                    $stmt = $conn->prepare("UPDATE staff SET status=? WHERE Staff_ID=?");
                    $stmt->bind_param('si', $newStatus, $id);
                    $stmt->execute();
                    $stmt->close();

                    if (class_exists('Logger')) {
                        Logger::info('Admin toggled staff status', [
                            'admin_id'   => $admin_id,
                            'staff_id'   => $id,
                            'new_status' => $newStatus,
                        ]);
                    }
                    $flash = ['type' => 'success', 'message' => "Staff '{$row['full_name']}' set to {$newStatus}."];
                } else {
                    $flash = ['type' => 'error', 'message' => 'Staff not found.'];
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Toggle staff status failed', ['error' => $ex->getMessage()]);
                $flash = ['type' => 'error', 'message' => 'Database error: ' . $ex->getMessage()];
            }
        }
    }

    $_SESSION['sm_flash'] = $flash;
    header('Location: staff_management.php');
    exit();
}

if (!empty($_SESSION['sm_flash'])) {
    $flash = $_SESSION['sm_flash'];
    unset($_SESSION['sm_flash']);
}

/* ══════════════════════════════════════════
   FILTERS + PAGINATION
   ══════════════════════════════════════════ */
$search    = trim($_GET['q'] ?? '');
$status_f  = trim($_GET['status'] ?? '');
$role_f    = trim($_GET['role'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 12;
$offset    = ($page - 1) * $per_page;
$sort      = $_GET['sort'] ?? 'name_asc';

$order_map = [
    'name_asc'   => 'full_name ASC',
    'name_desc'  => 'full_name DESC',
    'id_asc'     => 'Staff_ID ASC',
    'id_desc'    => 'Staff_ID DESC',
    'newest'     => 'created_at DESC',
    'oldest'     => 'created_at ASC',
    'bookings'   => 'bookings_handled DESC',
];
$order_sql = $order_map[$sort] ?? 'full_name ASC';

/* ══════════════════════════════════════════
   FETCH STAFF
   ══════════════════════════════════════════ */
$staff_list  = [];
$total_rows  = 0;
$total_pages = 1;

try {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ? OR contact_number LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 4; $i++) { $params[] = $like; $types .= 's'; }
    }
    if ($status_f !== '' && in_array($status_f, ['active','inactive'], true)) {
        $where[] = "status = ?";
        $params[] = $status_f;
        $types .= 's';
    }
    if ($role_f !== '' && in_array($role_f, ['staff','manager'], true)) {
        $where[] = "role = ?";
        $params[] = $role_f;
        $types .= 's';
    }

    $where_sql = implode(' AND ', $where);

    /* Count */
    $count_sql = "SELECT COUNT(*) c FROM staff WHERE $where_sql";
    $stmt = $conn->prepare($count_sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    /* Fetch with performance subqueries */
    $list_sql = "
        SELECT
            s.*,
            (SELECT COUNT(*) FROM system_logs sl
                WHERE sl.action = 'Booking Status Update'
                AND sl.description LIKE CONCAT('%Staff ', s.full_name, '%')
            ) AS bookings_handled,
            (SELECT COUNT(*) FROM system_logs sl
                WHERE sl.action = 'Booking Status Update'
                AND sl.description LIKE CONCAT('%Staff ', s.full_name, '% to completed%')
            ) AS bookings_completed,
            (SELECT COUNT(*) FROM system_logs sl
                WHERE sl.action = 'Staff emergency cancel'
                AND sl.description LIKE CONCAT('%\"staff_name\":\"', s.full_name, '\"%')
            ) AS cancellations,
            (SELECT MAX(timestamp) FROM system_logs sl
                WHERE sl.action = 'Staff Login'
                AND sl.description LIKE CONCAT('%''', s.username, '''%')
            ) AS last_login
        FROM staff s
        WHERE $where_sql
        ORDER BY $order_sql
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($list_sql);
    $bind_types  = $types . 'ii';
    $bind_params = array_merge($params, [$per_page, $offset]);
    if ($bind_types !== 'ii') {
        $stmt->bind_param($bind_types, ...$bind_params);
    } else {
        $stmt->bind_param('ii', $per_page, $offset);
    }
    $stmt->execute();
    $staff_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch staff failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = [
    'total'      => 0,
    'active'     => 0,
    'inactive'   => 0,
    'managers'   => 0,
    'logged_in_today' => 0,
];

try {
    $q = $conn->query("SELECT COUNT(*) c FROM staff");
    if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM staff WHERE status='active'");
    if ($q) $stats['active'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM staff WHERE status='inactive'");
    if ($q) $stats['inactive'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM staff WHERE role='manager'");
    if ($q) $stats['managers'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT description) c FROM system_logs WHERE action='Staff Login' AND DATE(timestamp)=CURDATE()");
    if ($q) $stats['logged_in_today'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Staff stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   RECENT STAFF ACTIVITY (Global feed)
   ══════════════════════════════════════════ */
$recent_activity = [];
try {
    $q = $conn->query("
        SELECT log_id, action, description, timestamp
        FROM system_logs
        WHERE action LIKE 'Staff %'
        ORDER BY timestamp DESC
        LIMIT 8
    ");
    if ($q) $recent_activity = $q->fetch_all(MYSQLI_ASSOC);
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Recent activity failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   URL HELPER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'q'      => $_GET['q']      ?? '',
        'status' => $_GET['status'] ?? '',
        'role'   => $_GET['role']   ?? '',
        'sort'   => $_GET['sort']   ?? 'name_asc',
        'page'   => $_GET['page']   ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    $merged = array_filter($merged, function($v, $k) {
        if ($v === '' || $v === null) return false;
        if ($k === 'page' && (int)$v === 1) return false;
        if ($k === 'sort' && $v === 'name_asc') return false;
        return true;
    }, ARRAY_FILTER_USE_BOTH);

    return 'staff_management.php?' . http_build_query($merged);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management — WashFlow Admin</title>

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
.wf-topbar-greeting h1 { color:var(--dark-blue); font-size:1.6rem; font-weight:800; margin:0; letter-spacing:-0.03em; line-height:1.3; display:flex; align-items:center; gap:0.6rem; }
.wf-topbar-greeting h1 .accent { color:var(--gold); }
.wf-topbar-greeting p { color:var(--text-secondary); font-size:0.88rem; margin:0.2rem 0 0; font-weight:500; }
.wf-topbar-right { display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; }
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

/* FLASH */
.wf-flash { padding:0.9rem 1.15rem; border-radius:12px; margin-bottom:1.25rem; font-weight:600; font-size:0.88rem; display:flex; align-items:center; gap:0.7rem; }
.wf-flash.success { background:#EAF7F0; color:#1E7E45; border-left:4px solid var(--green); }
.wf-flash.error { background:#FDEDEC; color:#A8322D; border-left:4px solid var(--red); }

/* STATS */
.wf-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
.wf-stat-card { background:white; border-radius:14px; padding:1.25rem; border:1px solid var(--border); transition:all 0.25s; display:flex; flex-direction:column; justify-content:space-between; min-height:120px; }
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

/* FILTER */
.wf-filter-bar { background:white; border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; display:flex; align-items:flex-end; gap:1rem; flex-wrap:wrap; }
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
.wf-btn-danger { background:var(--red); color:white; }
.wf-btn-danger:hover { background:#C0392B; }
.wf-btn-success { background:var(--green); color:white; }
.wf-btn-success:hover { background:#1E8449; }
.wf-btn-sm { padding:0.5rem 0.85rem; font-size:0.78rem; }

/* CARD */
.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }
.wf-card-header { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.wf-card-title { display:flex; align-items:center; gap:0.9rem; }
.wf-card-title-icon { width:42px; height:42px; border-radius:11px; background:var(--light-blue-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-card-title h3 { font-size:1.05rem; color:var(--dark-blue); margin:0; font-weight:700; }
.wf-card-title p { font-size:0.78rem; color:var(--text-muted); margin:0.15rem 0 0; font-weight:500; }

/* STAFF GRID */
.wf-staff-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:1.1rem; padding:1.25rem 1.5rem; }
.wf-staff-card { border:1px solid var(--border); border-radius:14px; padding:1.25rem; transition:all 0.2s; background:white; position:relative; }
.wf-staff-card:hover { border-color:var(--primary); box-shadow:0 6px 20px rgba(0,83,122,0.08); transform:translateY(-2px); }
.wf-staff-head { display:flex; align-items:flex-start; gap:0.9rem; margin-bottom:1rem; }
.wf-staff-avatar { width:54px; height:54px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.25rem; flex-shrink:0; color:white; }
.wf-staff-avatar.role-staff   { background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%); }
.wf-staff-avatar.role-manager { background:linear-gradient(135deg,#F0B400 0%,var(--yellow) 100%); color:var(--dark-blue-deep); }
.wf-staff-info { flex:1; min-width:0; }
.wf-staff-name { font-size:1rem; font-weight:700; color:var(--dark-blue); margin-bottom:0.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wf-staff-username { font-size:0.78rem; color:var(--text-muted); font-weight:500; margin-bottom:0.35rem; }
.wf-staff-tags { display:flex; gap:0.35rem; flex-wrap:wrap; }
.wf-tag { display:inline-flex; align-items:center; gap:0.3rem; font-size:0.68rem; font-weight:700; padding:0.2rem 0.55rem; border-radius:6px; text-transform:uppercase; letter-spacing:0.04em; }
.wf-tag-active   { background:#EAF7F0; color:#1E7E45; }
.wf-tag-inactive { background:#FDEDEC; color:#A8322D; }
.wf-tag-role-staff   { background:var(--light-blue-pale); color:var(--primary); }
.wf-tag-role-manager { background:var(--yellow-soft); color:var(--yellow-dark); }

.wf-staff-details { display:flex; flex-direction:column; gap:0.35rem; margin-bottom:1rem; font-size:0.8rem; color:var(--text-secondary); }
.wf-staff-details i { width:16px; color:var(--text-muted); margin-right:0.4rem; font-size:0.75rem; }

.wf-staff-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:0.5rem; padding:0.75rem 0; border-top:1px solid var(--border-soft); border-bottom:1px solid var(--border-soft); margin-bottom:1rem; }
.wf-staff-stat { text-align:center; }
.wf-staff-stat-value { font-size:1.05rem; font-weight:800; color:var(--dark-blue); line-height:1.1; }
.wf-staff-stat-label { font-size:0.65rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.06em; margin-top:0.15rem; }

.wf-staff-actions { display:flex; gap:0.4rem; }
.wf-staff-actions .wf-btn { flex:1; justify-content:center; }

/* EMPTY */
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

/* ACTIVITY FEED */
.wf-activity-list { padding:0.5rem 0; }
.wf-activity-item { display:flex; align-items:flex-start; gap:0.85rem; padding:0.85rem 1.5rem; border-bottom:1px solid var(--border-soft); }
.wf-activity-item:last-child { border-bottom:none; }
.wf-activity-item:hover { background:var(--light-blue-pale); }
.wf-activity-icon { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.8rem; flex-shrink:0; background:var(--light-blue-soft); color:var(--primary); }
.wf-activity-content { flex:1; min-width:0; }
.wf-activity-title { font-size:0.85rem; font-weight:600; color:var(--dark-blue); margin-bottom:0.15rem; }
.wf-activity-desc { font-size:0.78rem; color:var(--text-secondary); word-break:break-word; }
.wf-activity-time { font-size:0.72rem; color:var(--text-muted); font-weight:500; white-space:nowrap; }

/* MODAL */
.wf-modal-overlay { position:fixed; inset:0; background:rgba(6,52,82,0.5); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; z-index:1060; opacity:0; visibility:hidden; transition:all 0.25s; padding:1rem; }
.wf-modal-overlay.open { opacity:1; visibility:visible; }
.wf-modal { background:white; border-radius:16px; padding:1.75rem; max-width:560px; width:100%; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(6,52,82,0.25); transform:scale(0.95); transition:transform 0.25s; }
.wf-modal-overlay.open .wf-modal { transform:scale(1); }
.wf-modal h3 { font-size:1.15rem; color:var(--dark-blue); margin-bottom:0.4rem; }
.wf-modal p { font-size:0.85rem; color:var(--text-secondary); margin-bottom:1.25rem; }
.wf-modal-actions { display:flex; gap:0.6rem; justify-content:flex-end; margin-top:1.25rem; }

.wf-form-row { display:grid; grid-template-columns:1fr 1fr; gap:0.85rem; margin-bottom:0.85rem; }
.wf-form-row.full { grid-template-columns:1fr; }
.wf-form-group { display:flex; flex-direction:column; gap:0.35rem; }
.wf-form-group label { font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em; }

/* MOBILE */
.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:1200px) { .wf-stats-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:1024px) { .wf-layout { grid-template-columns:260px 1fr; } .wf-main { padding:1.5rem; } }
@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
    .wf-stats-grid { grid-template-columns:1fr; }
    .wf-filter-bar { flex-direction:column; align-items:stretch; }
    .wf-filter-group { min-width:100%; }
    .wf-form-row { grid-template-columns:1fr; }
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
            <a href="admin_tracking.php" class="wf-nav-item">
                <i class="fas fa-route"></i><span>Tracking Monitor</span>
            </a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="customer_management.php" class="wf-nav-item">
                <i class="fas fa-users"></i><span>Customers</span>
            </a>
            <a href="staff_management.php" class="wf-nav-item active">
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
                <h1><i class="fas fa-user-tie" style="color:var(--primary);"></i> Staff <span class="accent">Management</span></h1>
                <p>Manage staff accounts, roles, and monitor their performance</p>
            </div>
            <div class="wf-topbar-right">
                <button type="button" class="wf-btn wf-btn-primary" onclick="openAddModal()">
                    <i class="fas fa-user-plus"></i> Add Staff
                </button>
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

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-users-cog"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Staff</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-user-check"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['active']) ?></div>
                    <div class="wf-stat-label">Active</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-red"><i class="fas fa-user-slash"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['inactive']) ?></div>
                    <div class="wf-stat-label">Inactive</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-yellow"><i class="fas fa-user-tie"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['managers']) ?></div>
                    <div class="wf-stat-label">Managers</div>
                </div>
            </div>
        </div>

        <!-- FILTERS -->
        <form method="get" action="staff_management.php" class="wf-filter-bar">
            <div class="wf-filter-group" style="flex: 1 1 240px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="Name, username, email, contact..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 150px;">
                <label>Status</label>
                <select name="status" class="wf-select">
                    <option value="">All Status</option>
                    <option value="active"   <?= $status_f === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_f === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 150px;">
                <label>Role</label>
                <select name="role" class="wf-select">
                    <option value="">All Roles</option>
                    <option value="staff"   <?= $role_f === 'staff'   ? 'selected' : '' ?>>Staff</option>
                    <option value="manager" <?= $role_f === 'manager' ? 'selected' : '' ?>>Manager</option>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 180px;">
                <label>Sort By</label>
                <select name="sort" class="wf-select">
                    <option value="name_asc"  <?= $sort === 'name_asc'  ? 'selected' : '' ?>>Name (A→Z)</option>
                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z→A)</option>
                    <option value="newest"    <?= $sort === 'newest'    ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest"    <?= $sort === 'oldest'    ? 'selected' : '' ?>>Oldest First</option>
                    <option value="bookings"  <?= $sort === 'bookings'  ? 'selected' : '' ?>>Most Bookings</option>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 auto; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="staff_management.php" class="wf-btn wf-btn-outline"><i class="fas fa-undo"></i> Reset</a>
            </div>
        </form>

        <!-- STAFF LIST -->
        <div class="wf-card">
            <div class="wf-card-header">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-users-cog"></i></div>
                    <div>
                        <h3>Staff Members</h3>
                        <p><?= number_format($total_rows) ?> staff found</p>
                    </div>
                </div>
            </div>

            <?php if (empty($staff_list)): ?>
                <div class="wf-empty">
                    <i class="fas fa-user-slash"></i>
                    <p>No staff found</p>
                </div>
            <?php else: ?>
                <div class="wf-staff-grid">
                    <?php foreach ($staff_list as $s):
                        $isActive = ($s['status'] === 'active');
                        $isManager = ($s['role'] === 'manager');
                        $initial = strtoupper(substr($s['full_name'] ?? $s['username'], 0, 1));
                    ?>
                    <div class="wf-staff-card">
                        <div class="wf-staff-head">
                            <div class="wf-staff-avatar role-<?= $isManager ? 'manager' : 'staff' ?>"><?= e($initial) ?></div>
                            <div class="wf-staff-info">
                                <div class="wf-staff-name"><?= e($s['full_name']) ?></div>
                                <div class="wf-staff-username"><i class="fas fa-at"></i> <?= e($s['username']) ?></div>
                                <div class="wf-staff-tags">
                                    <span class="wf-tag <?= $isActive ? 'wf-tag-active' : 'wf-tag-inactive' ?>">
                                        <i class="fas fa-circle" style="font-size:0.5rem;"></i>
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <span class="wf-tag <?= $isManager ? 'wf-tag-role-manager' : 'wf-tag-role-staff' ?>">
                                        <i class="fas <?= $isManager ? 'fa-crown' : 'fa-user' ?>" style="font-size:0.6rem;"></i>
                                        <?= ucfirst($s['role']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="wf-staff-details">
                            <div><i class="fas fa-envelope"></i><?= e($s['email'] ?: '—') ?></div>
                            <div><i class="fas fa-phone"></i><?= e($s['contact_number'] ?: '—') ?></div>
                            <div><i class="fas fa-clock"></i>
                                <?php if (!empty($s['last_login'])): ?>
                                    Last login: <?= date('M j, Y g:i A', strtotime($s['last_login'])) ?>
                                <?php else: ?>
                                    No login recorded
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="wf-staff-stats">
                            <div class="wf-staff-stat">
                                <div class="wf-staff-stat-value"><?= number_format((int)$s['bookings_handled']) ?></div>
                                <div class="wf-staff-stat-label">Handled</div>
                            </div>
                            <div class="wf-staff-stat">
                                <div class="wf-staff-stat-value" style="color:#1E7E45;"><?= number_format((int)$s['bookings_completed']) ?></div>
                                <div class="wf-staff-stat-label">Completed</div>
                            </div>
                            <div class="wf-staff-stat">
                                <div class="wf-staff-stat-value" style="color:#A8322D;"><?= number_format((int)$s['cancellations']) ?></div>
                                <div class="wf-staff-stat-label">Cancelled</div>
                            </div>
                        </div>

                        <div class="wf-staff-actions">
                            <button type="button" class="wf-btn wf-btn-outline wf-btn-sm"
                                onclick='openEditModal(<?= json_encode([
                                    "id"             => (int)$s["Staff_ID"],
                                    "username"       => $s["username"],
                                    "full_name"      => $s["full_name"],
                                    "email"          => $s["email"],
                                    "contact_number" => $s["contact_number"],
                                    "role"           => $s["role"],
                                    "status"         => $s["status"],
                                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="post" style="flex:1; display:flex;" onsubmit="return confirm('<?= $isActive ? 'Deactivate' : 'Reactivate' ?> this staff?');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="staff_id" value="<?= (int)$s['Staff_ID'] ?>">
                                <button type="submit" class="wf-btn wf-btn-sm <?= $isActive ? 'wf-btn-danger' : 'wf-btn-success' ?>" style="flex:1; justify-content:center;">
                                    <i class="fas <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                    <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="wf-pagination-wrap">
                    <div class="wf-pagination-info">
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> staff
                    </div>
                    <div class="wf-pagination">
                        <a href="<?= e(build_url(['page' => max(1, $page - 1)])) ?>" class="wf-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php
                        $sp = max(1, $page - 2);
                        $ep = min($total_pages, $page + 2);
                        if ($sp > 1): ?>
                            <a href="<?= e(build_url(['page' => 1])) ?>" class="wf-page-btn">1</a>
                            <?php if ($sp > 2): ?><span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($p = $sp; $p <= $ep; $p++): ?>
                            <a href="<?= e(build_url(['page' => $p])) ?>" class="wf-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                        <?php if ($ep < $total_pages): ?>
                            <?php if ($ep < $total_pages - 1): ?><span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span><?php endif; ?>
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

        <!-- RECENT ACTIVITY -->
        <div class="wf-card">
            <div class="wf-card-header">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-stream"></i></div>
                    <div>
                        <h3>Recent Staff Activity</h3>
                        <p>Latest actions across all staff</p>
                    </div>
                </div>
                <a href="system_logs.php" class="wf-btn wf-btn-outline wf-btn-sm">
                    View All Logs <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($recent_activity)): ?>
                <div class="wf-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <div class="wf-activity-list">
                    <?php foreach ($recent_activity as $a):
                        $icon = 'fa-info-circle';
                        $label = $a['action'];
                        if (stripos($a['action'], 'Login') !== false) { $icon = 'fa-sign-in-alt'; }
                        elseif (stripos($a['action'], 'Logout') !== false) { $icon = 'fa-sign-out-alt'; }
                        elseif (stripos($a['action'], 'Status') !== false) { $icon = 'fa-exchange-alt'; }
                        elseif (stripos($a['action'], 'cancel') !== false) { $icon = 'fa-ban'; }
                    ?>
                    <div class="wf-activity-item">
                        <div class="wf-activity-icon"><i class="fas <?= $icon ?>"></i></div>
                        <div class="wf-activity-content">
                            <div class="wf-activity-title"><?= e($label) ?></div>
                            <div class="wf-activity-desc"><?= e($a['description']) ?></div>
                        </div>
                        <div class="wf-activity-time"><?= date('M j, g:i A', strtotime($a['timestamp'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ADD MODAL -->
<div class="wf-modal-overlay" id="addModal">
    <div class="wf-modal">
        <h3><i class="fas fa-user-plus" style="color:var(--primary);"></i> Add New Staff</h3>
        <p>Create a new staff account. They can log in at <code>staff_login.php</code>.</p>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="action" value="add_staff">

            <div class="wf-form-row full">
                <div class="wf-form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" class="wf-input" required maxlength="100">
                </div>
            </div>

            <div class="wf-form-row">
                <div class="wf-form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="wf-input" required maxlength="50">
                </div>
                <div class="wf-form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="wf-input" required minlength="6">
                </div>
            </div>

            <div class="wf-form-row">
                <div class="wf-form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="wf-input" maxlength="150">
                </div>
                <div class="wf-form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" class="wf-input" maxlength="20">
                </div>
            </div>

            <div class="wf-form-row">
                <div class="wf-form-group">
                    <label>Role *</label>
                    <select name="role" class="wf-select">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div class="wf-form-group">
                    <label>Status *</label>
                    <select name="status" class="wf-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="wf-modal-actions">
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="wf-btn wf-btn-primary"><i class="fas fa-save"></i> Add Staff</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="wf-modal-overlay" id="editModal">
    <div class="wf-modal">
        <h3><i class="fas fa-user-edit" style="color:var(--primary);"></i> Edit Staff</h3>
        <p>Update staff details. Leave password blank to keep the current one.</p>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="action" value="edit_staff">
            <input type="hidden" name="staff_id" id="edit_staff_id">

            <div class="wf-form-row full">
                <div class="wf-form-group">
                    <label>Username (read-only)</label>
                    <input type="text" id="edit_username" class="wf-input" readonly style="background:#F5F9FC;">
                </div>
            </div>

            <div class="wf-form-row full">
                <div class="wf-form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" id="edit_full_name" class="wf-input" required maxlength="100">
                </div>
            </div>

            <div class="wf-form-row">
                <div class="wf-form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" class="wf-input" maxlength="150">
                </div>
                <div class="wf-form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" id="edit_contact_number" class="wf-input" maxlength="20">
                </div>
            </div>

            <div class="wf-form-row">
                <div class="wf-form-group">
                    <label>Role *</label>
                    <select name="role" id="edit_role" class="wf-select">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div class="wf-form-group">
                    <label>Status *</label>
                    <select name="status" id="edit_status" class="wf-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="wf-form-row full">
                <div class="wf-form-group">
                    <label>New Password (optional)</label>
                    <input type="password" name="new_password" class="wf-input" minlength="6" placeholder="Leave blank to keep current password">
                </div>
            </div>

            <div class="wf-modal-actions">
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="wf-btn wf-btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const addModal  = document.getElementById('addModal');
const editModal = document.getElementById('editModal');

function openAddModal() { addModal.classList.add('open'); }
function closeAddModal() { addModal.classList.remove('open'); }

function openEditModal(data) {
    document.getElementById('edit_staff_id').value = data.id;
    document.getElementById('edit_username').value = data.username || '';
    document.getElementById('edit_full_name').value = data.full_name || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_contact_number').value = data.contact_number || '';
    document.getElementById('edit_role').value = data.role || 'staff';
    document.getElementById('edit_status').value = data.status || 'active';
    editModal.classList.add('open');
}
function closeEditModal() { editModal.classList.remove('open'); }

addModal.addEventListener('click', e => { if (e.target === addModal) closeAddModal(); });
editModal.addEventListener('click', e => { if (e.target === editModal) closeEditModal(); });

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeAddModal(); closeEditModal(); }
});

/* Sidebar mobile */
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