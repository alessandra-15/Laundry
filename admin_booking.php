<?php
/**
 * admin_bookings.php
 * WashFlow — ADMIN Booking Monitor (READ-ONLY + EMERGENCY CANCEL)
 *
 * ✅ Admin-only (auto-redirect sa admin_login kung hindi admin)
 * ✅ View ALL bookings (walk-in + online)
 * ✅ Filters, search, sort, pagination
 * ✅ Track LAHAT ng movements — sino nag-verify, kailan, ano status
 * ✅ Export CSV
 * ✅ Emergency CANCEL lang (walang verify/status update)
 * ❌ WALANG payment verification — staff lang may ganun
 * ❌ WALANG status workflow — staff lang may ganun
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* ══════════════════════════════════════════
   AUTH — ADMIN ONLY
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

if (!function_exists('admin_status_badge')) {
    function admin_status_badge($status) {
        $s = strtolower(trim((string)$status));
        if ($s === '') $s = 'pending';
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

/**
 * Status filter mapping — pinagsama ang walk-in at online vocabularies.
 */
if (!function_exists('status_filter_map')) {
    function status_filter_map($label) {
        $map = [
            'Pending'     => ['walkin' => ['Pending'],                  'online' => ['Pending', '']],
            'Confirmed'   => ['walkin' => ['Confirmed'],                'online' => ['Confirmed']],
            'In Progress' => ['walkin' => ['In Progress'],              'online' => ['Processing', 'In Progress']],
            'Processing'  => ['walkin' => ['In Progress'],              'online' => ['Processing']],
            'Ready'       => ['walkin' => ['Ready'],                    'online' => ['Ready']],
            'Completed'   => ['walkin' => ['Completed'],                'online' => ['Completed']],
            'Cancelled'   => ['walkin' => ['Cancelled'],                'online' => ['Cancelled']],
        ];
        return $map[$label] ?? ['walkin' => [$label], 'online' => [$label]];
    }
}

/* ══════════════════════════════════════════
   CSRF TOKEN
   ══════════════════════════════════════════ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ══════════════════════════════════════════
   CHECK COLUMNS / TABLES
   ══════════════════════════════════════════ */
$has_payments_table = false;
if ($res = $conn->query("SHOW TABLES LIKE 'payments_online'")) {
    $has_payments_table = ($res->num_rows > 0);
    $res->free();
}

$has_verified_cols = false;
if ($has_payments_table) {
    $res = $conn->query("SHOW COLUMNS FROM payments_online LIKE 'verified_by'");
    if ($res && $res->num_rows > 0) $has_verified_cols = true;
    if ($res) $res->free();
}

/* ══════════════════════════════════════════
   POST — EMERGENCY CANCEL ONLY
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'emergency_cancel') {

    // CSRF check
    $posted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_token)) {
        $_SESSION['ab_flash'] = ['type' => 'error', 'message' => 'Invalid session token. Please refresh and try again.'];
        header('Location: admin_bookings.php');
        exit();
    }

    $type   = $_POST['type'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($id <= 0 || !in_array($type, ['walkin', 'online'], true)) {
        $flash = ['type' => 'error', 'message' => 'Invalid request.'];
    } elseif ($reason === '') {
        $flash = ['type' => 'error', 'message' => 'Cancellation reason is required.'];
    } else {
        $ok = false;
        try {
            // Server-side guard: bawal i-cancel ang Completed o Cancelled
            if ($type === 'walkin') {
                $stmt = $conn->prepare("UPDATE booking SET status='Cancelled' WHERE Booking_ID = ? AND status NOT IN ('Completed','Cancelled')");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $ok = ($stmt->affected_rows > 0);
                $stmt->close();
            } else {
                $stmt = $conn->prepare("UPDATE booking_online SET status='Cancelled' WHERE id = ? AND status NOT IN ('Completed','Cancelled')");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $ok = ($stmt->affected_rows > 0);
                $stmt->close();
            }

            if ($ok && class_exists('Logger')) {
                Logger::info('Admin emergency cancel', [
                    'admin_id' => $admin_id,
                    'type'     => $type,
                    'id'       => $id,
                    'reason'   => $reason,
                ]);
            }
        } catch (Exception $ex) {
            Logger::error('Emergency cancel failed', ['error' => $ex->getMessage()]);
            $ok = false;
        }

        if ($ok) {
            $flash = ['type' => 'success', 'message' => "Booking #$id cancelled. Reason logged."];
        } else {
            $flash = ['type' => 'error', 'message' => 'Failed to cancel. Booking may already be completed or cancelled.'];
        }
    }

    $_SESSION['ab_flash'] = $flash;
    header('Location: admin_bookings.php');
    exit();
}

if (!empty($_SESSION['ab_flash'])) {
    $flash = $_SESSION['ab_flash'];
    unset($_SESSION['ab_flash']);
}

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = [
    'total'         => 0,
    'walkin'        => 0,
    'online'        => 0,
    'pending'       => 0,
    'in_progress'   => 0,
    'completed'     => 0,
    'cancelled'     => 0,
    'unverified'    => 0,
    'revenue'       => 0,
];

try {
    // Walk-in count
    $q = $conn->query("SELECT COUNT(*) c FROM booking"); 
    if ($q) $stats['walkin'] = (int)$q->fetch_assoc()['c'];

    // Online count (excluding walk-in duplicates)
    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE delivery_option <> 'walkin'"); 
    if ($q) $stats['online'] = (int)$q->fetch_assoc()['c'];

    // Total
    $stats['total'] = $stats['walkin'] + $stats['online'];

    // Pending
    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='Pending'"); 
    $w_p = $q ? (int)$q->fetch_assoc()['c'] : 0;
    
    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE (status='Pending' OR status='') AND delivery_option <> 'walkin'"); 
    $o_p = $q ? (int)$q->fetch_assoc()['c'] : 0;
    $stats['pending'] = $w_p + $o_p;

    // In Progress
    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE status IN ('Confirmed','In Progress')"); 
    $w_ip = $q ? (int)$q->fetch_assoc()['c'] : 0;
    
    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status IN ('Processing','Ready') AND delivery_option <> 'walkin'"); 
    $o_ip = $q ? (int)$q->fetch_assoc()['c'] : 0;
    $stats['in_progress'] = $w_ip + $o_ip;

    // Completed
    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='Completed'"); 
    $w_c = $q ? (int)$q->fetch_assoc()['c'] : 0;
    
    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Completed' AND delivery_option <> 'walkin'"); 
    $o_c = $q ? (int)$q->fetch_assoc()['c'] : 0;
    $stats['completed'] = $w_c + $o_c;

    // Cancelled
    $q = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='Cancelled'"); 
    $w_x = $q ? (int)$q->fetch_assoc()['c'] : 0;
    
    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Cancelled' AND delivery_option <> 'walkin'"); 
    $o_x = $q ? (int)$q->fetch_assoc()['c'] : 0;
    $stats['cancelled'] = $w_x + $o_x;

    // Payments + Revenue
    if ($has_payments_table) {
        $q = $conn->query("SELECT COUNT(*) c FROM payments_online WHERE LOWER(payment_status) IN ('pending','pending_verification')");
        if ($q) $stats['unverified'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM payments_online WHERE LOWER(payment_status)='paid'");
        if ($q) $stats['revenue'] = (float)$q->fetch_assoc()['s'];
    }
} catch (Exception $ex) {
    Logger::error('Admin stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FILTERS + PAGINATION
   ══════════════════════════════════════════ */
$search      = trim($_GET['q'] ?? '');
$status_f    = trim($_GET['status'] ?? '');
$payment_f   = trim($_GET['payment'] ?? '');
$source_f    = trim($_GET['source'] ?? '');   // walkin | online | ''
$date_from   = trim($_GET['date_from'] ?? '');
$date_to     = trim($_GET['date_to'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 20;
$offset      = ($page - 1) * $per_page;
$sort        = $_GET['sort'] ?? 'date_desc';

/* Payment filter → laging online-only (walk-in walang payment record) */
if ($payment_f !== '' && $source_f === '') {
    $source_f = 'online';
}

$order_map = [
    'date_desc'   => 'date_col DESC, source DESC',
    'date_asc'    => 'date_col ASC, source ASC',
    'id_desc'     => 'booking_id DESC, source DESC',
    'id_asc'      => 'booking_id ASC, source ASC',
    'amount_desc' => 'amount_col DESC, booking_id DESC',
    'amount_asc'  => 'amount_col ASC, booking_id ASC',
];
$order_sql = $order_map[$sort] ?? 'date_col DESC, source DESC';

/* ══════════════════════════════════════════
   FETCH — Unified view (walk-in + online)
   ══════════════════════════════════════════ */
$bookings    = [];
$total_rows  = 0;
$total_pages = 1;

try {
    $include_walkin = ($source_f === '' || $source_f === 'walkin');
    $include_online = ($source_f === '' || $source_f === 'online');

    /* ── Walk-in WHERE + params ── */
    $w_where  = ["1=1"];
    $w_params = [];
    $w_types  = '';

    if ($search !== '') {
        $w_where[] = "(b.Booking_ID LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR b.service LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 4; $i++) { $w_params[] = $like; $w_types .= 's'; }
    }
    if ($status_f !== '') {
        $map = status_filter_map($status_f);
        $st_list = $map['walkin'];
        if (!empty($st_list)) {
            $placeholders = implode(',', array_fill(0, count($st_list), '?'));
            $w_where[] = "b.status IN ($placeholders)";
            foreach ($st_list as $s) { $w_params[] = $s; $w_types .= 's'; }
        }
    }
    if ($date_from !== '') { $w_where[] = "DATE(b.booking_date) >= ?"; $w_params[] = $date_from; $w_types .= 's'; }
    if ($date_to   !== '') { $w_where[] = "DATE(b.booking_date) <= ?"; $w_params[] = $date_to;   $w_types .= 's'; }

    /* ── Online WHERE + params ── */
    $o_where  = ["b.delivery_option <> 'walkin'"];  // IMPORTANT: exclude walkin entries
    $o_params = [];
    $o_types  = '';

    if ($search !== '') {
        $o_where[] = "(b.id LIKE ? OR b.customer_name LIKE ? OR b.contact_number LIKE ? OR b.service LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 4; $i++) { $o_params[] = $like; $o_types .= 's'; }
    }
    if ($status_f !== '') {
        $map = status_filter_map($status_f);
        $st_list = $map['online'];
        if (!empty($st_list)) {
            $placeholders = implode(',', array_fill(0, count($st_list), '?'));
            $o_where[] = "b.status IN ($placeholders)";
            foreach ($st_list as $s) { $o_params[] = $s; $o_types .= 's'; }
        }
    }
    if ($date_from !== '') { $o_where[] = "DATE(b.timestamp) >= ?"; $o_params[] = $date_from; $o_types .= 's'; }
    if ($date_to   !== '') { $o_where[] = "DATE(b.timestamp) <= ?"; $o_params[] = $date_to;   $o_types .= 's'; }

    /* Payment filter (online only) */
    if ($has_payments_table && $payment_f !== '') {
        if ($payment_f === 'pending') {
            $o_where[] = "LOWER(p.payment_status) IN ('pending','pending_verification')";
        } elseif ($payment_f === 'paid') {
            $o_where[] = "LOWER(p.payment_status) = 'paid'";
        } elseif ($payment_f === 'unpaid') {
            $o_where[] = "(p.payment_status IS NULL OR LOWER(p.payment_status) = 'unpaid')";
        }
    }

    $w_where_sql = implode(' AND ', $w_where);
    $o_where_sql = implode(' AND ', $o_where);

    /* ── Build UNION parts ── */
    $parts        = [];
    $final_params = [];
    $final_types  = '';

    if ($include_walkin) {
        $parts[] = "
            SELECT
                b.Booking_ID AS booking_id,
                'walkin' AS source,
                b.service,
                b.status,
                b.total_amount AS amount_col,
                b.booking_date AS date_col,
                CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) AS customer_name,
                c.contact_number,
                NULL AS payment_id,
                NULL AS payment_status,
                NULL AS payment_method,
                NULL AS payment_reference,
                NULL AS payment_proof,
                NULL AS verified_by,
                NULL AS verified_at
            FROM booking b
            LEFT JOIN customer_info c ON c.Customer_ID = b.Customer_ID
            WHERE $w_where_sql
        ";
        foreach ($w_params as $p) $final_params[] = $p;
        $final_types .= $w_types;
    }

    if ($include_online) {
        $pay_join = $has_payments_table ? "LEFT JOIN payments_online p ON p.booking_id = b.id" : "";

        if ($has_payments_table) {
            $pay_cols = "p.payment_id, p.payment_status, p.payment_method,
                         p.reference_number AS payment_reference, p.payment_proof";
            $pay_cols .= $has_verified_cols
                ? ", p.verified_by, p.verified_at"
                : ", NULL AS verified_by, NULL AS verified_at";
        } else {
            $pay_cols = "NULL AS payment_id, NULL AS payment_status, NULL AS payment_method,
                         NULL AS payment_reference, NULL AS payment_proof,
                         NULL AS verified_by, NULL AS verified_at";
        }

        $parts[] = "
            SELECT
                b.id AS booking_id,
                'online' AS source,
                b.service,
                b.status,
                b.total_amount AS amount_col,
                b.timestamp AS date_col,
                b.customer_name,
                b.contact_number,
                $pay_cols
            FROM booking_online b
            $pay_join
            WHERE $o_where_sql
        ";
        foreach ($o_params as $p) $final_params[] = $p;
        $final_types .= $o_types;
    }

    if (empty($parts)) {
        $bookings = [];
    } else {
        $union_sql = implode(' UNION ALL ', $parts);

        /* Count total */
        $count_sql = "SELECT COUNT(*) c FROM ($union_sql) AS u";
        $stmt = $conn->prepare($count_sql);
        if ($final_types !== '') $stmt->bind_param($final_types, ...$final_params);
        $stmt->execute();
        $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $total_pages = max(1, (int)ceil($total_rows / $per_page));

        /* Main query with sort + pagination */
        $list_sql = "SELECT * FROM ($union_sql) AS u
                     ORDER BY $order_sql
                     LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($list_sql);
        $bind_types  = $final_types . 'ii';
        $bind_params = array_merge($final_params, [$per_page, $offset]);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

} catch (Exception $ex) {
    Logger::error('Admin fetch bookings failed', ['error' => $ex->getMessage(), 'msg' => $conn->error]);
}

/* ══════════════════════════════════════════
   URL HELPER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'q'         => $_GET['q']         ?? '',
        'status'    => $_GET['status']    ?? '',
        'payment'   => $_GET['payment']   ?? '',
        'source'    => $_GET['source']    ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'sort'      => $_GET['sort']      ?? 'date_desc',
        'page'      => $_GET['page']      ?? 1,
    ];
    $merged = array_merge($base, $overrides);

    // Alisin ang default/empty values
    $merged = array_filter($merged, function($v, $k) {
        if ($v === '' || $v === null) return false;
        if ($k === 'page' && (int)$v === 1) return false;
        if ($k === 'sort' && $v === 'date_desc') return false;
        return true;
    }, ARRAY_FILTER_USE_BOTH);

    return 'admin_bookings.php?' . http_build_query($merged);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bookings Monitor — WashFlow Admin</title>

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
.wf-btn-danger { background:var(--red); color:white; }
.wf-btn-danger:hover { background:#C0392B; }

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

.wf-booking-id { font-weight:700; color:var(--primary); font-family:'SF Mono',Monaco,monospace; font-size:0.8rem; }
.wf-source-tag { display:inline-flex; align-items:center; gap:0.3rem; padding:0.15rem 0.5rem; border-radius:5px; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-left:0.35rem; }
.wf-source-tag.walkin { background:#EBF5FB; color:#00537A; }
.wf-source-tag.online { background:#F4ECFB; color:#5B2E91; }
.wf-customer { font-weight:600; color:var(--dark-blue); }
.wf-service { color:var(--text-secondary); font-size:0.84rem; }
.wf-amount { font-weight:700; color:var(--dark-blue); }
.wf-date { color:var(--text-muted); font-size:0.8rem; white-space:nowrap; }

.wf-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.35rem 0.7rem; border-radius:7px; font-size:0.72rem; font-weight:600; white-space:nowrap; }
.wf-badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

.wf-pay-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.3rem 0.7rem; border-radius:999px; font-size:0.7rem; font-weight:700; white-space:nowrap; border:1px solid transparent; }
.wf-pay-badge.pending  { background:#FEF9E7; color:#946200; border-color:#F5E6A8; }
.wf-pay-badge.paid     { background:#EAF7F0; color:#1E7E45; border-color:#B8E6CC; }
.wf-pay-badge.unpaid   { background:#FDEDEC; color:#A8322D; border-color:#F5C6C2; }
.wf-pay-badge.refunded { background:#F4ECFB; color:#5B2E91; border-color:#DCC4EF; }
.wf-pay-badge.none     { background:#F4F7F9; color:#5A7184; border-color:#DCE4EA; }

.wf-row-actions { display:flex; gap:0.4rem; justify-content:flex-end; }
.wf-icon-btn { width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:white; color:var(--text-secondary); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s; font-size:0.8rem; }
.wf-icon-btn:hover { border-color:var(--primary); color:var(--primary); background:var(--light-blue-pale); }
.wf-icon-btn.cancel:hover { border-color:var(--red); color:var(--red); background:#FDEDEC; }

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

/* MODAL */
.wf-modal-overlay { position:fixed; inset:0; background:rgba(6,52,82,0.5); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; z-index:1060; opacity:0; visibility:hidden; transition:all 0.25s; padding:1rem; }
.wf-modal-overlay.open { opacity:1; visibility:visible; }
.wf-modal { background:white; border-radius:16px; padding:1.75rem; max-width:480px; width:100%; box-shadow:0 20px 60px rgba(6,52,82,0.25); transform:scale(0.95); transition:transform 0.25s; }
.wf-modal-overlay.open .wf-modal { transform:scale(1); }
.wf-modal-icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin:0 auto 1rem; background:#FDEDEC; color:var(--red); }
.wf-modal h3 { font-size:1.15rem; color:var(--dark-blue); text-align:center; margin-bottom:0.5rem; }
.wf-modal p { font-size:0.88rem; color:var(--text-secondary); text-align:center; margin-bottom:1.5rem; line-height:1.6; }
.wf-modal-actions { display:flex; gap:0.6rem; justify-content:center; }
.wf-modal textarea { width:100%; padding:0.7rem 0.9rem; border:1px solid var(--border); border-radius:9px; font-family:inherit; font-size:0.85rem; resize:vertical; min-height:80px; outline:none; margin-bottom:1.25rem; }
.wf-modal textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }

/* MONITOR BANNER */
.wf-monitor-banner {
    background:linear-gradient(135deg, #FFF9DB 0%, #FFFCF0 100%);
    border:1px solid #F0DE8A;
    border-radius:12px;
    padding:0.9rem 1.15rem;
    margin-bottom:1.25rem;
    display:flex;
    align-items:center;
    gap:0.75rem;
    font-size:0.85rem;
    color:#7A5A00;
    font-weight:600;
}
.wf-monitor-banner i { font-size:1.1rem; color:var(--gold); }

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
            <a href="admin_bookings.php" class="wf-nav-item active">
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
                <h1><i class="fas fa-eye" style="color:var(--primary);"></i> Bookings <span class="accent">Monitor</span></h1>
                <p>Overview ng lahat ng bookings — read-only. Ang Staff ang nagve-verify at nag-a-update ng status.</p>
            </div>
            <div class="wf-topbar-right">
                <span class="wf-role-pill"><i class="fas fa-crown"></i> Admin Monitor Mode</span>
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>

        <div class="wf-monitor-banner">
            <i class="fas fa-info-circle"></i>
            <span>Admin view lang ito. Para sa verification at status updates, ipagawa sa Staff sa <strong>Staff Panel → My Queue</strong>.</span>
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
                    <div class="wf-stat-label">Total Bookings</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-yellow"><i class="fas fa-hourglass-half"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="wf-stat-label">Pending</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-purple"><i class="fas fa-spinner"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['in_progress']) ?></div>
                    <div class="wf-stat-label">In Progress</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['completed']) ?></div>
                    <div class="wf-stat-label">Completed</div>
                </div>
            </div>

            <div class="wf-stat-card" style="border-color:#F5E6A8;">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-yellow"><i class="fas fa-money-check-alt"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['unverified']) ?></div>
                    <div class="wf-stat-label">Unverified Payments</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-dark"><i class="fas fa-coins"></i></div></div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($stats['revenue'], 0) ?></div>
                    <div class="wf-stat-label">Total Revenue</div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="wf-tabs">
            <a href="<?= e(build_url(['source' => '', 'page' => 1])) ?>" class="wf-tab <?= $source_f === '' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> All
                <span class="tab-count"><?= number_format($stats['total']) ?></span>
            </a>
            <a href="<?= e(build_url(['source' => 'walkin', 'page' => 1])) ?>" class="wf-tab <?= $source_f === 'walkin' ? 'active' : '' ?>">
                <i class="fas fa-store"></i> Walk-in
                <span class="tab-count"><?= number_format($stats['walkin']) ?></span>
            </a>
            <a href="<?= e(build_url(['source' => 'online', 'page' => 1])) ?>" class="wf-tab <?= $source_f === 'online' ? 'active' : '' ?>">
                <i class="fas fa-globe"></i> Online
                <span class="tab-count"><?= number_format($stats['online']) ?></span>
            </a>
        </div>

        <!-- FILTERS -->
        <form method="get" action="admin_bookings.php" class="wf-filter-bar">
            <input type="hidden" name="source" value="<?= e($source_f) ?>">
            <input type="hidden" name="page" value="1">

            <div class="wf-filter-group" style="flex: 1 1 200px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="Booking ID, customer, service..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 150px;">
                <label>Status</label>
                <select name="status" class="wf-select">
                    <option value="">All Status</option>
                    <?php foreach (['Pending','Confirmed','In Progress','Processing','Ready','Completed','Cancelled'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $status_f === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 175px;">
                <label>Payment</label>
                <select name="payment" class="wf-select">
                    <option value="">All Payments</option>
                    <option value="pending" <?= $payment_f === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="paid"    <?= $payment_f === 'paid'    ? 'selected' : '' ?>>Paid</option>
                    <option value="unpaid"  <?= $payment_f === 'unpaid'  ? 'selected' : '' ?>>Unpaid</option>
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
                <a href="admin_bookings.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>

        <!-- TABLE -->
        <div class="wf-card">
            <?php if (empty($bookings)): ?>
                <div class="wf-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No bookings found</p>
                </div>
            <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'id_asc' ? 'id_desc' : 'id_asc', 'page' => 1])) ?>">
                                        Booking <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'amount_desc' ? 'amount_asc' : 'amount_desc', 'page' => 1])) ?>">
                                        Amount <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Payment</th>
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
                            <?php foreach ($bookings as $b):
                                $st_badge = admin_status_badge($b['status']);
                                $is_walkin = ($b['source'] === 'walkin');
                                $status_raw = trim((string)$b['status']);
                                if ($status_raw === '') $status_raw = 'Pending';
                                $customer_name = trim((string)$b['customer_name']);
                                if ($customer_name === '') $customer_name = $is_walkin ? 'Walk-in' : 'Online Customer';

                                $has_pay = !empty($b['payment_id']);
                                $pay_key = $has_pay ? strtolower(trim((string)$b['payment_status'])) : 'no payment';
                                if ($pay_key === '') $pay_key = $has_pay ? 'pending' : 'no payment';
                                $pay = admin_pay_badge($pay_key);

                                $verified_by = $b['verified_by'] ?? null;
                                $verified_at = $b['verified_at'] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <span class="wf-booking-id">#<?= e($b['booking_id']) ?></span>
                                    <span class="wf-source-tag <?= $is_walkin ? 'walkin' : 'online' ?>">
                                        <i class="fas <?= $is_walkin ? 'fa-store' : 'fa-globe' ?>"></i>
                                        <?= $is_walkin ? 'Walk-in' : 'Online' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="wf-customer"><?= e($customer_name) ?></div>
                                    <?php if (!empty($b['contact_number'])): ?>
                                        <div style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">
                                            <i class="fas fa-phone" style="font-size:0.65rem;"></i> <?= e($b['contact_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="wf-service"><?= e($b['service'] ?? '-') ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format((float)$b['amount_col'], 2) ?></span></td>

                                <td>
                                    <?php
                                        $pay_cls = 'none';
                                        if ($pay_key === 'paid')          $pay_cls = 'paid';
                                        elseif ($pay_key === 'unpaid')    $pay_cls = 'unpaid';
                                        elseif ($pay_key === 'refunded')  $pay_cls = 'refunded';
                                        elseif ($has_pay)                 $pay_cls = 'pending';
                                    ?>
                                    <span class="wf-pay-badge <?= $pay_cls ?>">
                                        <i class="fas <?= e($pay['icon']) ?>"></i>
                                        <?= e($pay['label']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="wf-badge" style="color:<?= $st_badge['color'] ?>;background:<?= $st_badge['bg'] ?>;">
                                        <i class="fas <?= $st_badge['icon'] ?>" style="font-size:0.65rem;"></i>
                                        <?= e($st_badge['label']) ?>
                                    </span>
                                </td>

                                <td><span class="wf-date"><?= date('M j, Y', strtotime($b['date_col'])) ?></span></td>

                                <td>
                                    <div class="wf-row-actions">
                                        <button type="button" class="wf-icon-btn" title="View Details"
                                            onclick='openPanel(<?= json_encode([
                                                "id"          => $b["booking_id"],
                                                "source"      => $b["source"],
                                                "customer"    => $customer_name,
                                                "contact"     => $b["contact_number"] ?? "",
                                                "service"     => $b["service"] ?? "",
                                                "status"      => $status_raw,
                                                "amount"      => (float)$b["amount_col"],
                                                "date"        => $b["date_col"],
                                                "payment"     => $pay_key,
                                                "pay_method"  => $b["payment_method"] ?? "",
                                                "pay_ref"     => $b["payment_reference"] ?? "",
                                                "pay_proof"   => $b["payment_proof"] ?? "",
                                                "pay_id"      => $b["payment_id"] ?? null,
                                                "verified_by" => $verified_by,
                                                "verified_at" => $verified_at,
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if (!in_array(strtolower($status_raw), ['completed','cancelled'], true)): ?>
                                            <button type="button" class="wf-icon-btn cancel" title="Emergency Cancel"
                                                onclick='openCancelModal(<?= json_encode([
                                                    "id"     => $b["booking_id"],
                                                    "source" => $b["source"],
                                                    "label"  => "Booking #" . $b["booking_id"] . " (" . ($is_walkin ? "Walk-in" : "Online") . ")",
                                                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
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
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> bookings
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

                        <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                            <a href="<?= e(build_url(['page' => $p])) ?>" class="wf-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
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
<aside class="wf-panel" id="bookingPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-receipt"></i></div>
            <div>
                <h3 id="panelTitle">Booking Details</h3>
                <p id="panelSubtitle">Loading...</p>
            </div>
        </div>
        <button type="button" class="wf-panel-close" onclick="closePanel()"><i class="fas fa-times"></i></button>
    </div>
    <div class="wf-panel-body" id="panelBody"></div>
    <div class="wf-panel-footer" id="panelFooter"></div>
</aside>

<!-- CANCEL MODAL -->
<div class="wf-modal-overlay" id="cancelModal">
    <div class="wf-modal">
        <div class="wf-modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Emergency Cancel</h3>
        <p id="cancelSubtitle">Cancel this booking? Mangyaring magbigay ng dahilan.</p>
        <textarea id="cancelReason" placeholder="Reason for cancellation (required, ilalagay sa logs)..." required></textarea>
        <div class="wf-modal-actions">
            <button type="button" class="wf-btn wf-btn-outline" onclick="closeCancelModal()">Cancel</button>
            <button type="button" class="wf-btn wf-btn-danger" onclick="executeCancel()">
                <i class="fas fa-ban"></i> Yes, Cancel Booking
            </button>
        </div>
    </div>
</div>

<form method="post" id="cancelForm" style="display:none;">
    <input type="hidden" name="action" value="emergency_cancel">
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
    <input type="hidden" name="id" id="cancelId">
    <input type="hidden" name="type" id="cancelType">
    <input type="hidden" name="reason" id="cancelReasonField">
</form>

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
const bookingPanel = document.getElementById('bookingPanel');

function openPanel(data) {
    const statusMap = {
        'pending':     { color:'#946200', bg:'#FEF9E7' },
        'confirmed':   { color:'#00537A', bg:'#EBF5FB' },
        'in progress': { color:'#5B2E91', bg:'#F4ECFB' },
        'processing':  { color:'#5B2E91', bg:'#F4ECFB' },
        'ready':       { color:'#946200', bg:'#FFF9DB' },
        'completed':   { color:'#1E7E45', bg:'#EAF7F0' },
        'cancelled':   { color:'#A8322D', bg:'#FDEDEC' },
    };
    const st = statusMap[String(data.status).toLowerCase()] || { color:'#5A7184', bg:'#F4F7F9' };

    const payMap = {
        'paid':                 { color:'#1E7E45', bg:'#EAF7F0', label:'Paid' },
        'pending':              { color:'#946200', bg:'#FEF9E7', label:'Pending Verification' },
        'pending_verification': { color:'#946200', bg:'#FEF9E7', label:'Pending Verification' },
        'unpaid':               { color:'#A8322D', bg:'#FDEDEC', label:'Unpaid' },
        'refunded':             { color:'#5B2E91', bg:'#F4ECFB', label:'Refunded' },
        'no payment':           { color:'#5A7184', bg:'#F4F7F9', label:'No Payment' },
    };
    const pay = payMap[data.payment] || payMap['no payment'];

    const isWalkin = data.source === 'walkin';

    document.getElementById('panelTitle').textContent = 'Booking #' + data.id;
    document.getElementById('panelSubtitle').textContent = (isWalkin ? 'Walk-in' : 'Online') + ' • ' + formatDate(data.date);

    let html = '';

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-info-circle"></i> Current Status</div>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            <span class="wf-badge" style="color:${st.color};background:${st.bg};font-size:0.8rem;padding:0.5rem 0.9rem;">
                <span class="wf-badge-dot"></span> ${escapeHtml(data.status)}
            </span>
            <span class="wf-pay-badge ${data.payment === 'paid' ? 'paid' : (data.payment === 'unpaid' ? 'unpaid' : (data.payment === 'refunded' ? 'refunded' : (data.payment === 'no payment' ? 'none' : 'pending')))}" style="font-size:0.8rem;padding:0.5rem 0.9rem;">
                ${pay.label}
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
        <div class="wf-detail-section-title"><i class="fas fa-soap"></i> Booking Info</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Service</div>
                <div class="wf-detail-value">${escapeHtml(data.service) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Amount</div>
                <div class="wf-detail-value" style="color:var(--dark-blue);font-size:1.05rem;">₱${parseFloat(data.amount).toFixed(2)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Date</div>
                <div class="wf-detail-value">${formatDate(data.date)}</div>
            </div>
            ${data.pay_method ? `
            <div class="wf-detail-item">
                <div class="wf-detail-label">Payment Method</div>
                <div class="wf-detail-value muted">${escapeHtml(data.pay_method)}</div>
            </div>` : ''}
            ${data.pay_ref ? `
            <div class="wf-detail-item">
                <div class="wf-detail-label">Reference No.</div>
                <div class="wf-detail-value mono">${escapeHtml(data.pay_ref)}</div>
            </div>` : ''}
        </div>
    </div>`;

    if (data.verified_by || data.verified_at) {
        html += `
        <div class="wf-detail-section">
            <div class="wf-detail-section-title"><i class="fas fa-user-check"></i> Payment Verification</div>
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

    let footer = '';
    if (!['completed','cancelled'].includes(String(data.status).toLowerCase())) {
        footer = `<button type="button" class="wf-btn wf-btn-danger" onclick='closePanel(); openCancelModal(${JSON.stringify({id:data.id, source:data.source, label:"Booking #" + data.id})})'>
            <i class="fas fa-ban"></i> Emergency Cancel
        </button>`;
    } else {
        footer = '<p style="font-size:0.82rem;color:var(--text-muted);margin:0;">No actions available for this status.</p>';
    }
    document.getElementById('panelFooter').innerHTML = footer;

    panelOverlay.classList.add('open');
    bookingPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    panelOverlay.classList.remove('open');
    bookingPanel.classList.remove('open');
    document.body.style.overflow = '';
}

const cancelModal = document.getElementById('cancelModal');
let cancelTarget = null;

function openCancelModal(data) {
    cancelTarget = data;
    document.getElementById('cancelSubtitle').textContent = 'Cancel ' + (data.label || ('Booking #' + data.id)) + '? Mangyaring magbigay ng dahilan.';
    document.getElementById('cancelReason').value = '';
    cancelModal.classList.add('open');
}

function closeCancelModal() {
    cancelModal.classList.remove('open');
    cancelTarget = null;
}

function executeCancel() {
    if (!cancelTarget) return;
    const reason = document.getElementById('cancelReason').value.trim();
    if (reason === '') {
        alert('Please provide a reason.');
        return;
    }
    document.getElementById('cancelId').value = cancelTarget.id;
    document.getElementById('cancelType').value = cancelTarget.source;
    document.getElementById('cancelReasonField').value = reason;
    document.getElementById('cancelForm').submit();
}

cancelModal.addEventListener('click', function(e) {
    if (e.target === cancelModal) closeCancelModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeCancelModal(); closePanel(); }
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