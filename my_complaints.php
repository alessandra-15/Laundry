<?php
/**
 * my_complaints.php
 * Customer — File, View, Edit, Cancel Complaints
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

if (empty($_SESSION['customer_id'])) {
    header('Location: customer_login.php');
    exit();
}

$customer_id   = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? $_SESSION['username'] ?? 'Customer';

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Detect kung may extended columns na ang complaints table.
 * Para safe kahit wala pang ALTER.
 */
function complaints_has_column($conn, $col) {
    static $cache = [];
    if (isset($cache[$col])) return $cache[$col];
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
    $q = $conn->query("SHOW COLUMNS FROM complaints LIKE '$safe'");
    $cache[$col] = $q && $q->num_rows > 0;
    return $cache[$col];
}

function complaint_status_meta($status) {
    switch ($status) {
        case 'Pending':     return ['label' => 'Pending',     'color' => '#946200', 'bg' => '#FEF9E7', 'icon' => 'fa-hourglass-half'];
        case 'In Progress': return ['label' => 'In Progress', 'color' => '#00537A', 'bg' => '#EBF5FB', 'icon' => 'fa-spinner'];
        case 'Resolved':    return ['label' => 'Resolved',    'color' => '#1E7E45', 'bg' => '#EAF7F0', 'icon' => 'fa-check-circle'];
        case 'Closed':      return ['label' => 'Closed',      'color' => '#5A7184', 'bg' => '#F4F7F9', 'icon' => 'fa-lock'];
        case 'Cancelled':   return ['label' => 'Cancelled',   'color' => '#A8322D', 'bg' => '#FDEDEC', 'icon' => 'fa-times-circle'];
        default:            return ['label' => $status,       'color' => '#5A7184', 'bg' => '#F4F7F9', 'icon' => 'fa-circle'];
    }
}

function complaint_priority_meta($priority) {
    switch ($priority) {
        case 'Low':    return ['label' => 'Low',    'color' => '#5A7184', 'bg' => '#F4F7F9'];
        case 'Normal': return ['label' => 'Normal', 'color' => '#00537A', 'bg' => '#EBF5FB'];
        case 'High':   return ['label' => 'High',   'color' => '#C2410C', 'bg' => '#FEF3E2'];
        case 'Urgent': return ['label' => 'Urgent', 'color' => '#A8322D', 'bg' => '#FDEDEC'];
        default:       return ['label' => $priority, 'color' => '#5A7184', 'bg' => '#F4F7F9'];
    }
}

function complaint_category_list() {
    return [
        'Service Quality'    => 'Service Quality',
        'Late Delivery'      => 'Late Delivery',
        'Damaged Item'       => 'Damaged Item',
        'Missing Item'       => 'Missing Item',
        'Billing Issue'      => 'Billing Issue',
        'Staff Behavior'     => 'Staff Behavior',
        'App / Booking'      => 'App / Booking Issue',
        'Other'              => 'Other',
    ];
}

/* ══════════════════════════════════════════
   EXTENDED COLUMN FLAGS
   ══════════════════════════════════════════ */
$has_subject   = complaints_has_column($conn, 'subject');
$has_category  = complaints_has_column($conn, 'category');
$has_booking   = complaints_has_column($conn, 'booking_id');
$has_priority  = complaints_has_column($conn, 'priority');
$has_custresp  = complaints_has_column($conn, 'customer_response');
$has_updatedat = complaints_has_column($conn, 'updated_at');

/* ══════════════════════════════════════════
   POST HANDLERS
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---------- FILE NEW COMPLAINT ---------- */
    if ($action === 'file') {
        $subject     = mb_substr(trim($_POST['subject'] ?? ''), 0, 150);
        $category    = trim($_POST['category'] ?? 'Other');
        $booking_id  = (int)($_POST['booking_id'] ?? 0) ?: null;
        $priority    = trim($_POST['priority'] ?? 'Normal');
        $description = mb_substr(trim($_POST['issue_description'] ?? ''), 0, 1000);

        $ok = false;
        $new_id = 0;

        try {
            if ($description === '') {
                throw new Exception('Please describe your issue.');
            }
            if (!array_key_exists($category, complaint_category_list())) {
                $category = 'Other';
            }
            if (!in_array($priority, ['Low','Normal','High','Urgent'], true)) {
                $priority = 'Normal';
            }
            if ($subject === '') {
                $subject = mb_substr($description, 0, 80);
            }

            // Optional: validate booking belongs to customer if provided
            if ($booking_id && $has_booking) {
                $chk = $conn->prepare("SELECT Booking_ID FROM booking WHERE Booking_ID = ? AND Customer_ID = ?");
                $chk->bind_param('ii', $booking_id, $customer_id);
                $chk->execute();
                if (!$chk->get_result()->fetch_assoc()) {
                    $chk->close();
                    throw new Exception('Invalid booking reference.');
                }
                $chk->close();
            }

            // Build dynamic INSERT based on which columns exist
            $cols = ['customer_id', 'issue_description', 'status', 'date_reported'];
            $vals = ['?', '?', "'Pending'", 'NOW()'];
            $types = 'is';
            $params = [$customer_id, $description];

            if ($has_subject)  { $cols[] = 'subject';  $vals[] = '?'; $types .= 's'; $params[] = $subject; }
            if ($has_category) { $cols[] = 'category'; $vals[] = '?'; $types .= 's'; $params[] = $category; }
            if ($has_booking)  { $cols[] = 'booking_id'; $vals[] = '?'; $types .= 'i'; $params[] = $booking_id; }
            if ($has_priority) { $cols[] = 'priority'; $vals[] = '?'; $types .= 's'; $params[] = $priority; }

            $sql = "INSERT INTO complaints (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $ok = $stmt->execute();
            $new_id = $stmt->insert_id;
            $stmt->close();

            if ($ok && class_exists('Logger')) {
                Logger::info('Customer filed complaint', [
                    'customer_id' => $customer_id,
                    'complaint_id' => $new_id
                ]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('File complaint failed', ['error' => $ex->getMessage()]);
            $flash = ['type' => 'error', 'message' => $ex->getMessage()];
        }

        if (!$flash) {
            $flash = ['type' => 'success', 'message' => "Complaint submitted. Reference #$new_id."];
        }

        $_SESSION['comp_flash'] = $flash;
        header('Location: my_complaints.php');
        exit();
    }

    /* ---------- UPDATE COMPLAINT (only if Pending) ---------- */
    if ($action === 'update') {
        $complaint_id = (int)($_POST['complaint_id'] ?? 0);
        $description  = mb_substr(trim($_POST['issue_description'] ?? ''), 0, 1000);
        $ok = false;

        try {
            if ($description === '') {
                throw new Exception('Description cannot be empty.');
            }

            // Only own + Pending can be edited
            $check = $conn->prepare("
                SELECT complaint_id FROM complaints
                WHERE complaint_id = ? AND customer_id = ? AND status = 'Pending'
            ");
            $check->bind_param('ii', $complaint_id, $customer_id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                $check->close();
                throw new Exception('Only pending complaints can be edited.');
            }
            $check->close();

            $stmt = $conn->prepare("
                UPDATE complaints SET issue_description = ?
                WHERE complaint_id = ? AND customer_id = ?
            ");
            $stmt->bind_param('sii', $description, $complaint_id, $customer_id);
            $ok = $stmt->execute();
            $stmt->close();
        } catch (Exception $ex) {
            $flash = ['type' => 'error', 'message' => $ex->getMessage()];
        }

        if (!$flash) {
            $flash = ['type' => 'success', 'message' => 'Complaint updated.'];
        }
        $_SESSION['comp_flash'] = $flash;
        header('Location: my_complaints.php');
        exit();
    }

    /* ---------- CANCEL COMPLAINT (only if Pending) ---------- */
    if ($action === 'cancel') {
        $complaint_id = (int)($_POST['complaint_id'] ?? 0);
        $ok = false;

        try {
            // We'll try to set status='Cancelled' if the enum supports it,
            // otherwise fall back to 'Resolved' with a cancel remark.
            $check = $conn->prepare("
                SELECT complaint_id FROM complaints
                WHERE complaint_id = ? AND customer_id = ? AND status = 'Pending'
            ");
            $check->bind_param('ii', $complaint_id, $customer_id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                $check->close();
                throw new Exception('Only pending complaints can be cancelled.');
            }
            $check->close();

            // Try Cancelled status
            $ok = false;
            try {
                $stmt = $conn->prepare("
                    UPDATE complaints SET status = 'Cancelled', date_resolved = NOW()
                    WHERE complaint_id = ? AND customer_id = ?
                ");
                $stmt->bind_param('ii', $complaint_id, $customer_id);
                $ok = $stmt->execute();
                $stmt->close();
            } catch (Exception $e) {
                $ok = false;
            }

            // Fallback kung hindi supported ng enum
            if (!$ok) {
                $note = 'Cancelled by customer.';
                $stmt = $conn->prepare("
                    UPDATE complaints SET status = 'Resolved', date_resolved = NOW(), remarks = ?
                    WHERE complaint_id = ? AND customer_id = ?
                ");
                $stmt->bind_param('sii', $note, $complaint_id, $customer_id);
                $ok = $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $ex) {
            $flash = ['type' => 'error', 'message' => $ex->getMessage()];
        }

        if (!$flash) {
            $flash = ['type' => 'success', 'message' => 'Complaint cancelled.'];
        }
        $_SESSION['comp_flash'] = $flash;
        header('Location: my_complaints.php');
        exit();
    }
}

// Pull flash
if (!empty($_SESSION['comp_flash'])) {
    $flash = $_SESSION['comp_flash'];
    unset($_SESSION['comp_flash']);
}

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = ['total' => 0, 'pending' => 0, 'progress' => 0, 'resolved' => 0];
try {
    $q = $conn->prepare("SELECT COUNT(*) c FROM complaints WHERE customer_id = ?");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['total'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) c FROM complaints WHERE customer_id = ? AND status = 'Pending'");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['pending'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) c FROM complaints WHERE customer_id = ? AND status = 'In Progress'");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['progress'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) c FROM complaints WHERE customer_id = ? AND status IN ('Resolved','Closed')");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['resolved'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Complaints stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH COMPLAINTS
   ══════════════════════════════════════════ */
$filter_status   = trim($_GET['status'] ?? '');
$filter_category = trim($_GET['category'] ?? '');
$search          = trim($_GET['q'] ?? '');
$page            = max(1, (int)($_GET['page'] ?? 1));
$per_page        = 8;
$offset          = ($page - 1) * $per_page;

$complaints  = [];
$total_rows  = 0;
$total_pages = 1;

try {
    $where  = ["c.customer_id = ?"];
    $params = [$customer_id];
    $types  = 'i';

    if ($filter_status !== '') {
        $where[] = "c.status = ?";
        $params[] = $filter_status;
        $types .= 's';
    }
    if ($filter_category !== '' && $has_category) {
        $where[] = "c.category = ?";
        $params[] = $filter_category;
        $types .= 's';
    }
    if ($search !== '') {
        if ($has_subject) {
            $where[] = "(c.issue_description LIKE ? OR c.subject LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like; $params[] = $like;
            $types .= 'ss';
        } else {
            $where[] = "c.issue_description LIKE ?";
            $params[] = '%' . $search . '%';
            $types .= 's';
        }
    }
    $where_sql = implode(' AND ', $where);

    // Build SELECT based on available columns
    $select_cols = "c.complaint_id, c.issue_description, c.status, c.date_reported, c.date_resolved, c.remarks, c.handled_by";
    if ($has_subject)   $select_cols .= ", c.subject";
    if ($has_category)  $select_cols .= ", c.category";
    if ($has_booking)   $select_cols .= ", c.booking_id";
    if ($has_priority)  $select_cols .= ", c.priority";
    if ($has_custresp)  $select_cols .= ", c.customer_response";

    // Count
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM complaints c WHERE $where_sql");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    $stmt = $conn->prepare("
        SELECT $select_cols
        FROM complaints c
        WHERE $where_sql
        ORDER BY c.date_reported DESC
        LIMIT ? OFFSET ?
    ");
    $bind_types  = $types . 'ii';
    $bind_params = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $complaints = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch complaints failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   CUSTOMER'S COMPLETED BOOKINGS (for dropdown)
   ══════════════════════════════════════════ */
$my_bookings = [];
try {
    $stmt = $conn->prepare("
        SELECT Booking_ID, service, booking_date
        FROM booking
        WHERE Customer_ID = ?
        ORDER BY booking_date DESC
        LIMIT 30
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $my_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {}

/* ══════════════════════════════════════════
   URL HELPER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'status'   => $_GET['status']   ?? '',
        'category' => $_GET['category'] ?? '',
        'q'        => $_GET['q']        ?? '',
        'page'     => $_GET['page']     ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'my_complaints.php?' . http_build_query(array_filter($merged, function($v) {
        return $v !== '' && $v !== null;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Complaints — WashFlow</title>

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
    --text-primary:    #0A2540;
    --text-secondary:  #5A7184;
    --text-muted:      #94A9B8;
    --border:          #E1EEF5;
    --border-soft:     #F0F6FA;
    --green:           #27AE60;
    --red:             #E74C3C;
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
    font-size: 14px;
}

h1, h2, h3, h4, h5 { font-weight: 700; letter-spacing: -0.02em; }
a { text-decoration: none; }

/* LAYOUT */
.wf-layout { display: grid; grid-template-columns: 300px 1fr; min-height: 100vh; }

/* SIDEBAR */
.wf-sidebar {
    background: var(--dark-blue-deep);
    color: white;
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    scrollbar-width: none;
}
.wf-sidebar::-webkit-scrollbar { display: none; }

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
    font-size: 1.45rem; font-weight: 800; color: white; letter-spacing: -0.04em;
}
.wf-sidebar-brand-name .flow { color: var(--yellow); }
.wf-sidebar-brand-tag {
    font-size: 0.65rem; color: rgba(168, 232, 249, 0.5);
    letter-spacing: 0.2em; text-transform: uppercase;
    margin-top: 5px; font-weight: 600;
}

.wf-nav { padding: 1.5rem 1rem; flex: 1; }
.wf-nav-section-label {
    font-size: 0.68rem; font-weight: 700;
    color: rgba(168, 232, 249, 0.35);
    letter-spacing: 0.22em; text-transform: uppercase;
    padding: 1.25rem 1rem 0.65rem;
}
.wf-nav-section-label:first-child { padding-top: 0.25rem; }

.wf-nav-item {
    display: flex; align-items: center; gap: 1rem;
    padding: 0.85rem 1rem; border-radius: 10px;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.95rem; font-weight: 500;
    transition: all 0.2s ease;
    margin-bottom: 0.2rem;
    position: relative; cursor: pointer;
}
.wf-nav-item i { width: 22px; text-align: center; font-size: 1rem; flex-shrink: 0; }
.wf-nav-item span { flex: 1; }
.wf-nav-item:hover { background: rgba(168, 232, 249, 0.06); color: rgba(255, 255, 255, 0.95); }
.wf-nav-item.active { background: rgba(255, 217, 61, 0.1); color: white; font-weight: 600; }
.wf-nav-item.active::before {
    content: ''; position: absolute;
    left: 0; top: 50%; transform: translateY(-50%);
    width: 3px; height: 22px;
    background: var(--yellow); border-radius: 0 3px 3px 0;
}
.wf-nav-item.active i { color: var(--yellow); }

.wf-sidebar-footer {
    padding: 1.25rem 1rem;
    border-top: 1px solid rgba(168, 232, 249, 0.08);
}
.wf-user-card {
    display: flex; align-items: center; gap: 0.875rem;
    padding: 0.85rem; border-radius: 11px;
    background: rgba(168, 232, 249, 0.05);
    margin-bottom: 0.6rem;
}
.wf-user-avatar {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--yellow) 0%, var(--yellow-soft) 100%);
    color: var(--dark-blue-deep);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem; flex-shrink: 0;
}
.wf-user-info { flex: 1; min-width: 0; line-height: 1.25; }
.wf-user-name {
    font-size: 0.92rem; font-weight: 700; color: white;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.wf-user-role { font-size: 0.72rem; color: rgba(168, 232, 249, 0.5); font-weight: 500; }

.wf-btn-logout {
    display: flex; align-items: center; gap: 0.875rem;
    padding: 0.75rem 0.85rem; border-radius: 11px;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.9rem; font-weight: 500; transition: all 0.2s;
}
.wf-btn-logout:hover { background: rgba(231, 76, 60, 0.12); color: #FF8B7E; }
.wf-btn-logout i { width: 22px; text-align: center; font-size: 1rem; }

/* MAIN */
.wf-main { padding: 2rem 2.5rem 3rem; overflow-y: auto; height: 100vh; scrollbar-width: none; }
.wf-main::-webkit-scrollbar { display: none; }

/* TOPBAR */
.wf-topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.75rem; gap: 1rem; flex-wrap: wrap;
}
.wf-topbar-greeting h1 {
    color: var(--dark-blue); font-size: 1.6rem; font-weight: 800;
    margin: 0; letter-spacing: -0.03em; line-height: 1.3;
}
.wf-topbar-greeting h1 .accent { color: var(--gold); }
.wf-topbar-greeting p { color: var(--text-secondary); font-size: 0.88rem; margin: 0.2rem 0 0; font-weight: 500; }

.wf-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.7rem 1.2rem; border-radius: 9px;
    font-size: 0.85rem; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
    border: 1px solid transparent;
    font-family: inherit; white-space: nowrap;
}
.wf-btn-primary { background: var(--primary); color: white; }
.wf-btn-primary:hover { background: var(--primary-mid); color: white; }
.wf-btn-outline { background: white; color: var(--primary); border-color: var(--border); }
.wf-btn-outline:hover { border-color: var(--primary); background: var(--light-blue-pale); color: var(--primary); }
.wf-btn-ghost { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
.wf-btn-ghost:hover { background: var(--light-blue-pale); color: var(--primary); }
.wf-btn-danger { background: white; color: var(--red); border: 1px solid var(--border); }
.wf-btn-danger:hover { background: #FDEDEC; border-color: var(--red); color: var(--red); }
.wf-btn-sm { padding: 0.5rem 0.85rem; font-size: 0.78rem; }

/* STATS */
.wf-stats-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 1rem; margin-bottom: 1.5rem;
}
.wf-stat-card {
    background: white; border-radius: 14px;
    padding: 1.25rem; border: 1px solid var(--border);
    transition: all 0.25s ease;
    display: flex; flex-direction: column; justify-content: space-between;
    min-height: 130px;
}
.wf-stat-card:hover {
    border-color: var(--light-blue);
    box-shadow: 0 6px 20px rgba(0, 83, 122, 0.08);
    transform: translateY(-3px);
}
.wf-stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
.wf-stat-icon {
    width: 42px; height: 42px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.wf-stat-icon.icon-gold   { background: #FFF4CC; color: #8A6400; }
.wf-stat-icon.icon-blue   { background: var(--light-blue-soft); color: var(--primary); }
.wf-stat-icon.icon-green  { background: #EAF7F0; color: #1E7E45; }
.wf-stat-icon.icon-warn   { background: #FEF3E2; color: #C2410C; }
.wf-stat-value {
    font-size: 1.6rem; font-weight: 800; color: var(--dark-blue);
    line-height: 1.1; letter-spacing: -0.03em; margin-bottom: 0.2rem;
}
.wf-stat-label { font-size: 0.78rem; color: var(--text-secondary); font-weight: 500; }

/* FLASH */
.wf-flash {
    padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem;
    font-weight: 600; font-size: 0.9rem;
    display: flex; align-items: center; gap: 0.7rem;
    animation: flashIn 0.35s ease;
}
@keyframes flashIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.wf-flash.success { background: #EAF7F0; color: #1E7E45; border-left: 4px solid var(--green); }
.wf-flash.error   { background: #FDEDEC; color: #A8322D; border-left: 4px solid var(--red); }
.wf-flash i { font-size: 1.1rem; }

/* FILTER BAR */
.wf-filter-bar {
    background: white; border: 1px solid var(--border);
    border-radius: 14px; padding: 1.1rem 1.4rem;
    margin-bottom: 1.25rem;
    display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap;
}
.wf-filter-group { display: flex; flex-direction: column; gap: 0.4rem; }
.wf-filter-group label {
    font-size: 0.72rem; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.08em;
    white-space: nowrap;
}
.wf-input, .wf-select {
    padding: 0.65rem 0.9rem;
    border: 1px solid var(--border); border-radius: 9px;
    font-size: 0.85rem; font-family: inherit; color: var(--text-primary);
    background: white; transition: all 0.2s; outline: none; min-width: 160px;
}
.wf-input:focus, .wf-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 90, 133, 0.1);
}

/* COMPLAINT CARD */
.wf-c-card {
    background: white; border: 1px solid var(--border);
    border-radius: 14px; padding: 1.4rem 1.6rem;
    margin-bottom: 1rem; transition: all 0.2s;
}
.wf-c-card:hover {
    box-shadow: 0 6px 20px rgba(0, 83, 122, 0.06);
    border-color: var(--light-blue);
}
.wf-c-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;
}
.wf-c-head-left { display: flex; gap: 1rem; align-items: flex-start; }
.wf-c-avatar {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, var(--light-blue-soft) 0%, var(--light-blue-pale) 100%);
    color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem; flex-shrink: 0;
}
.wf-c-meta { line-height: 1.35; }
.wf-c-id {
    font-weight: 700; color: var(--primary);
    font-family: 'SF Mono', Monaco, monospace; font-size: 0.85rem;
}
.wf-c-subject {
    font-weight: 700; color: var(--dark-blue);
    font-size: 0.95rem; margin-top: 0.15rem;
}
.wf-c-date {
    font-size: 0.74rem; color: var(--text-muted);
    font-weight: 500; margin-top: 0.15rem;
}

.wf-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.35rem 0.7rem; border-radius: 7px;
    font-size: 0.72rem; font-weight: 700; white-space: nowrap;
}
.wf-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.wf-c-body { margin-bottom: 0.5rem; }
.wf-c-label {
    font-size: 0.7rem; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.08em;
    margin-bottom: 0.4rem;
    display: flex; align-items: center; gap: 0.4rem;
}
.wf-c-label i { color: var(--primary); font-size: 0.7rem; }

.wf-c-desc {
    background: var(--light-blue-pale);
    border-left: 3px solid var(--light-blue);
    border-radius: 8px; padding: 0.9rem 1.1rem;
    font-size: 0.88rem; color: var(--text-primary); line-height: 1.65;
}
.wf-c-reply {
    background: #EAF7F0; border-left: 3px solid var(--green);
    border-radius: 8px; padding: 0.9rem 1.1rem;
    font-size: 0.88rem; color: var(--text-primary); line-height: 1.65;
    margin-top: 0.4rem;
}
.wf-c-reply .wf-c-label i { color: var(--green); }

.wf-c-actions {
    display: flex; gap: 0.4rem; margin-top: 0.85rem;
    padding-top: 0.85rem; border-top: 1px dashed var(--border);
    flex-wrap: wrap; align-items: center;
}

/* EDIT PANEL */
.wf-edit-panel {
    display: none; background: #FAFCFE;
    border: 1px solid var(--border); border-radius: 10px;
    padding: 1rem 1.1rem; margin-top: 0.85rem;
}
.wf-edit-panel.open { display: block; }

.wf-textarea {
    width: 100%; padding: 0.75rem 0.95rem;
    border: 1px solid var(--border); border-radius: 9px;
    font-family: inherit; font-size: 0.88rem; color: var(--text-primary);
    resize: vertical; min-height: 90px; outline: none;
    transition: all 0.2s;
}
.wf-textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 90, 133, 0.1);
}

/* EMPTY */
.wf-empty {
    text-align: center; padding: 4rem 1rem; color: var(--text-muted);
    background: white; border: 1px solid var(--border); border-radius: 14px;
}
.wf-empty i { font-size: 3rem; color: var(--light-blue); margin-bottom: 0.9rem; display: block; }
.wf-empty h3 { color: var(--dark-blue); font-size: 1rem; font-weight: 700; margin-bottom: 0.35rem; }
.wf-empty p { margin: 0; font-size: 0.88rem; font-weight: 500; color: var(--text-secondary); }

/* PAGINATION */
.wf-pagination-wrap {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.25rem; background: white;
    border: 1px solid var(--border); border-radius: 14px;
    flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem;
}
.wf-pagination-info { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; }
.wf-pagination { display: flex; gap: 0.3rem; align-items: center; }
.wf-page-btn {
    min-width: 36px; height: 36px; padding: 0 0.6rem;
    border-radius: 8px; border: 1px solid var(--border);
    background: white; color: var(--text-secondary);
    font-size: 0.8rem; font-weight: 600;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.wf-page-btn:hover:not(.disabled):not(.active) {
    border-color: var(--primary); color: var(--primary);
    background: var(--light-blue-pale);
}
.wf-page-btn.active { background: var(--dark-blue); color: white; border-color: var(--dark-blue); }
.wf-page-btn.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

/* MODAL */
.wf-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(6, 52, 82, 0.5);
    backdrop-filter: blur(3px);
    z-index: 1050;
    display: none;
    align-items: flex-start; justify-content: center;
    padding: 2rem 1rem;
    overflow-y: auto;
}
.wf-modal-overlay.open { display: flex; }
.wf-modal {
    background: white; border-radius: 16px;
    width: 100%; max-width: 640px;
    box-shadow: 0 20px 60px rgba(6, 52, 82, 0.2);
    animation: modalIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 1rem;
}
@keyframes modalIn {
    from { opacity: 0; transform: translateY(-20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.wf-modal-head {
    padding: 1.4rem 1.6rem;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem;
    background: linear-gradient(135deg, var(--light-blue-pale) 0%, white 100%);
    border-radius: 16px 16px 0 0;
}
.wf-modal-title {
    display: flex; align-items: center; gap: 0.9rem;
}
.wf-modal-title-icon {
    width: 46px; height: 46px; border-radius: 12px;
    background: var(--dark-blue); color: var(--yellow);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.wf-modal-title h3 { font-size: 1.05rem; color: var(--dark-blue); margin: 0; font-weight: 700; }
.wf-modal-title p { font-size: 0.78rem; color: var(--text-muted); margin: 0.15rem 0 0; font-weight: 500; }
.wf-modal-close {
    width: 36px; height: 36px; border-radius: 9px;
    border: 1px solid var(--border); background: white;
    color: var(--text-secondary);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.15s; flex-shrink: 0;
}
.wf-modal-close:hover { border-color: var(--red); color: var(--red); background: #FDEDEC; }
.wf-modal-body { padding: 1.5rem 1.6rem; }
.wf-modal-footer {
    padding: 1.25rem 1.6rem;
    border-top: 1px solid var(--border);
    background: #FAFCFE;
    display: flex; gap: 0.6rem; justify-content: flex-end;
    flex-wrap: wrap;
    border-radius: 0 0 16px 16px;
}

.wf-form-row {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 1rem; margin-bottom: 1rem;
}
.wf-form-group { margin-bottom: 1rem; }
.wf-form-group label {
    display: block; font-size: 0.72rem; font-weight: 700;
    color: var(--text-muted); text-transform: uppercase;
    letter-spacing: 0.08em; margin-bottom: 0.4rem;
}
.wf-form-group .req { color: var(--red); }
.wf-hint { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem; font-weight: 500; }

/* MOBILE */
.wf-sidebar-toggle {
    display: none; position: fixed; top: 1rem; left: 1rem;
    z-index: 1001; width: 44px; height: 44px;
    background: var(--dark-blue-deep); color: white;
    border: none; border-radius: 10px;
    align-items: center; justify-content: center;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(4, 38, 64, 0.3);
    cursor: pointer;
}
.wf-sidebar-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0, 0, 0, 0.5); z-index: 999;
}
.wf-sidebar-overlay.open { display: block; }

@media (max-width: 1200px) { .wf-stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 900px) {
    .wf-layout { grid-template-columns: 1fr; }
    .wf-sidebar {
        position: fixed; top: 0; left: -300px;
        width: 300px; z-index: 1000;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 20px 0 60px rgba(0, 0, 0, 0.3);
    }
    .wf-sidebar.open { left: 0; }
    .wf-sidebar-toggle { display: flex; }
    .wf-main { padding: 1.25rem; padding-top: 4rem; }
    .wf-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .wf-stats-grid { grid-template-columns: 1fr; }
    .wf-filter-bar { flex-direction: column; align-items: stretch; }
    .wf-input, .wf-select { min-width: 100%; }
    .wf-form-row { grid-template-columns: 1fr; }
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
                        <linearGradient id="wfLogoGradC" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0076A8"/>
                            <stop offset="100%" stop-color="#005A85"/>
                        </linearGradient>
                        <linearGradient id="wfWaveGradC" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FFD93D"/>
                            <stop offset="100%" stop-color="#A8E8F9"/>
                        </linearGradient>
                    </defs>
                    <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#wfLogoGradC)"/>
                    <path d="M14 24 L20 42 L26 30 L32 42 L38 24" stroke="url(#wfWaveGradC)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <circle cx="44" cy="24" r="2.5" fill="#FFD93D" opacity="0.9"/>
                    <circle cx="48" cy="32" r="1.8" fill="#FFD93D" opacity="0.7"/>
                    <circle cx="44" cy="40" r="1.2" fill="#FFD93D" opacity="0.5"/>
                    <path d="M14 48 Q22 44 32 48 T50 48" stroke="#A8E8F9" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.6"/>
                </svg>
            </div>
            <div class="wf-sidebar-brand-text">
                <span class="wf-sidebar-brand-name">Wash<span class="flow">Flow</span></span>
                <span class="wf-sidebar-brand-tag">Customer</span>
            </div>
        </div>

        <nav class="wf-nav">
            <div class="wf-nav-section-label">Overview</div>
            <a href="userdashboard.php" class="wf-nav-item">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>

            <div class="wf-nav-section-label">Laundry</div>
            <a href="book_service.php" class="wf-nav-item">
                <i class="fas fa-plus-circle"></i><span>New Booking</span>
            </a>
            <a href="my_bookings.php" class="wf-nav-item">
                <i class="fas fa-clipboard-list"></i><span>My Bookings</span>
            </a>
            <a href="payments.php" class="wf-nav-item">
                <i class="fas fa-credit-card"></i><span>Payments</span>
            </a>

            <div class="wf-nav-section-label">Support</div>
            <a href="notifications.php" class="wf-nav-item">
                <i class="fas fa-bell"></i><span>Notifications</span>
            </a>
            <a href="my_feedback.php" class="wf-nav-item">
                <i class="fas fa-star"></i><span>Feedback</span>
            </a>
            <a href="my_complaints.php" class="wf-nav-item active">
                <i class="fas fa-headset"></i><span>Complaints</span>
            </a>

            <div class="wf-nav-section-label">Account</div>
            <a href="profile.php" class="wf-nav-item">
                <i class="fas fa-user"></i><span>Profile</span>
            </a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e(strtoupper(substr($customer_name, 0, 1))) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($customer_name) ?></div>
                    <div class="wf-user-role">Customer</div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout">
                <i class="fas fa-sign-out-alt"></i><span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="wf-main">

        <!-- TOPBAR -->
        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1>My <span class="accent">Complaints</span></h1>
                <p>Report an issue and track its resolution status</p>
            </div>
            <button type="button" class="wf-btn wf-btn-primary" onclick="openFileModal()">
                <i class="fas fa-plus"></i> File a Complaint
            </button>
        </div>

        <!-- FLASH -->
        <?php if ($flash): ?>
        <div class="wf-flash <?= e($flash['type']) ?>">
            <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span><?= e($flash['message']) ?></span>
        </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-ticket-alt"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Complaints</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-warn"><i class="fas fa-hourglass-half"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="wf-stat-label">Pending</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-spinner"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['progress']) ?></div>
                    <div class="wf-stat-label">In Progress</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['resolved']) ?></div>
                    <div class="wf-stat-label">Resolved</div>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form method="get" action="my_complaints.php" class="wf-filter-bar">
            <div class="wf-filter-group" style="flex: 1 1 220px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input"
                       placeholder="Search description or subject..."
                       value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group">
                <label>Status</label>
                <select name="status" class="wf-select">
                    <option value="">All Status</option>
                    <?php foreach (['Pending','In Progress','Resolved','Closed','Cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($has_category): ?>
            <div class="wf-filter-group">
                <label>Category</label>
                <select name="category" class="wf-select">
                    <option value="">All Categories</option>
                    <?php foreach (complaint_category_list() as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $filter_category === $key ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="wf-filter-group" style="flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="my_complaints.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>

        <!-- COMPLAINTS LIST -->
        <?php if (empty($complaints)): ?>
            <div class="wf-empty">
                <i class="fas fa-inbox"></i>
                <h3>Walang complaints</h3>
                <p>
                    <?php if ($search || $filter_status || $filter_category): ?>
                        Walang tumutugma sa filter.
                        <a href="my_complaints.php" style="color:var(--primary);font-weight:700;">Reset →</a>
                    <?php else: ?>
                        Wala ka pang nai-file na complaint. Sana tuloy-tuloy ang magandang service! 🎉
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($complaints as $c):
                $cm       = complaint_status_meta($c['status']);
                $can_edit = ($c['status'] === 'Pending');
                $cid      = (int)$c['complaint_id'];
                $subject  = $has_subject && !empty($c['subject'])
                            ? $c['subject']
                            : 'Complaint #' . $cid;
            ?>
            <div class="wf-c-card">
                <div class="wf-c-head">
                    <div class="wf-c-head-left">
                        <div class="wf-c-avatar">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="wf-c-meta">
                            <div class="wf-c-id">#<?= $cid ?></div>
                            <div class="wf-c-subject"><?= e($subject) ?></div>
                            <div class="wf-c-date">
                                <i class="far fa-clock"></i>
                                Filed <?= date('M j, Y g:i A', strtotime($c['date_reported'])) ?>
                                <?php if ($has_category && !empty($c['category'])): ?>
                                    • <?= e($c['category']) ?>
                                <?php endif; ?>
                                <?php if ($has_booking && !empty($c['booking_id'])): ?>
                                    • Booking #<?= (int)$c['booking_id'] ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.4rem;">
                        <span class="wf-badge" style="color:<?= $cm['color'] ?>;background:<?= $cm['bg'] ?>;">
                            <i class="fas <?= $cm['icon'] ?>"></i> <?= e($cm['label']) ?>
                        </span>
                        <?php if ($has_priority && !empty($c['priority'])):
                            $pm = complaint_priority_meta($c['priority']); ?>
                            <span class="wf-badge" style="color:<?= $pm['color'] ?>;background:<?= $pm['bg'] ?>;">
                                <span class="wf-badge-dot"></span> <?= e($pm['label']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="wf-c-body">
                    <div class="wf-c-label"><i class="fas fa-file-alt"></i> Description</div>
                    <div class="wf-c-desc"><?= nl2br(e($c['issue_description'])) ?></div>
                </div>

                <?php if (!empty($c['remarks']) && $c['status'] !== 'Pending'): ?>
                    <div class="wf-c-body" style="margin-top:0.75rem;">
                        <div class="wf-c-label"><i class="fas fa-reply-all"></i> Admin Remarks</div>
                        <div class="wf-c-reply">
                            <?= nl2br(e($c['remarks'])) ?>
                            <?php if (!empty($c['handled_by'])): ?>
                                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.4rem;font-weight:600;">
                                    <i class="fas fa-user-shield"></i> Handled by <?= e($c['handled_by']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($c['date_resolved'])): ?>
                                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.2rem;font-weight:600;">
                                    <i class="fas fa-clock"></i> <?= date('M j, Y g:i A', strtotime($c['date_resolved'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($can_edit): ?>
                    <div class="wf-c-actions">
                        <button type="button" class="wf-btn wf-btn-outline wf-btn-sm"
                                onclick="toggleEdit(<?= $cid ?>)">
                            <i class="fas fa-pen"></i> Edit
                        </button>

                        <form method="post" action="my_complaints.php" style="display:inline;"
                              onsubmit="return confirm('Cancel complaint #<?= $cid ?>? This cannot be undone.');">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="complaint_id" value="<?= $cid ?>">
                            <button type="submit" class="wf-btn wf-btn-danger wf-btn-sm">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </form>

                        <span style="font-size:0.75rem;color:var(--text-muted);font-weight:600;margin-left:auto;">
                            <i class="fas fa-info-circle"></i> Editable while Pending
                        </span>
                    </div>

                    <!-- Inline edit panel -->
                    <div class="wf-edit-panel" id="edit-panel-<?= $cid ?>">
                        <form method="post" action="my_complaints.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="complaint_id" value="<?= $cid ?>">

                            <div class="wf-c-label"><i class="fas fa-pen"></i> Update Description</div>
                            <textarea name="issue_description"
                                      class="wf-textarea"
                                      maxlength="1000"
                                      required><?= e($c['issue_description']) ?></textarea>

                            <div style="display:flex;gap:0.5rem;margin-top:0.75rem;flex-wrap:wrap;">
                                <button type="submit" class="wf-btn wf-btn-primary wf-btn-sm">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="wf-btn wf-btn-ghost wf-btn-sm"
                                        onclick="toggleEdit(<?= $cid ?>)">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="wf-pagination-wrap">
                <div class="wf-pagination-info">
                    Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> complaints
                </div>
                <div class="wf-pagination">
                    <a href="<?= e(build_url(['page' => max(1, $page - 1)])) ?>"
                       class="wf-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
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
                        <a href="<?= e(build_url(['page' => $p])) ?>"
                           class="wf-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($ep < $total_pages): ?>
                        <?php if ($ep < $total_pages - 1): ?><span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span><?php endif; ?>
                        <a href="<?= e(build_url(['page' => $total_pages])) ?>" class="wf-page-btn"><?= $total_pages ?></a>
                    <?php endif; ?>
                    <a href="<?= e(build_url(['page' => min($total_pages, $page + 1)])) ?>"
                       class="wf-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<!-- ══════════════════════════════════════════
     FILE COMPLAINT MODAL
     ══════════════════════════════════════════ -->
<div class="wf-modal-overlay" id="fileModal">
    <div class="wf-modal">
        <div class="wf-modal-head">
            <div class="wf-modal-title">
                <div class="wf-modal-title-icon"><i class="fas fa-headset"></i></div>
                <div>
                    <h3>File a Complaint</h3>
                    <p>Ikwento mo sa amin ang problema</p>
                </div>
            </div>
            <button type="button" class="wf-modal-close" onclick="closeFileModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="post" action="my_complaints.php" id="fileForm">
            <input type="hidden" name="action" value="file">

            <div class="wf-modal-body">
                <div class="wf-form-row">
                    <?php if ($has_category): ?>
                    <div class="wf-form-group">
                        <label>Category</label>
                        <select name="category" class="wf-select" style="width:100%;">
                            <?php foreach (complaint_category_list() as $key => $label): ?>
                                <option value="<?= e($key) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_priority): ?>
                    <div class="wf-form-group">
                        <label>Priority</label>
                        <select name="priority" class="wf-select" style="width:100%;">
                            <option value="Low">Low</option>
                            <option value="Normal" selected>Normal</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($has_subject): ?>
                <div class="wf-form-group">
                    <label>Subject <span class="req">*</span></label>
                    <input type="text" name="subject" class="wf-input" style="width:100%;"
                           maxlength="150" required
                           placeholder="Short title, e.g. 'Damaged shirt after wash'">
                </div>
                <?php endif; ?>

                <?php if ($has_booking && !empty($my_bookings)): ?>
                <div class="wf-form-group">
                    <label>Related Booking (optional)</label>
                    <select name="booking_id" class="wf-select" style="width:100%;">
                        <option value="">— None —</option>
                        <?php foreach ($my_bookings as $bk): ?>
                            <option value="<?= (int)$bk['Booking_ID'] ?>">
                                #<?= (int)$bk['Booking_ID'] ?> —
                                <?= e($bk['service'] ?: 'Laundry') ?>
                                (<?= date('M j, Y', strtotime($bk['booking_date'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="wf-hint">Piliin kung anong booking ang may problema.</div>
                </div>
                <?php endif; ?>

                <div class="wf-form-group">
                    <label>Describe the Issue <span class="req">*</span></label>
                    <textarea name="issue_description"
                              class="wf-textarea"
                              maxlength="1000"
                              required
                              placeholder="Ikwento mo nang detalyado — ano ang nangyari, kailan, at ano ang inaasahan mong resolution."></textarea>
                    <div class="wf-hint">Max 1000 characters.</div>
                </div>
            </div>

            <div class="wf-modal-footer">
                <button type="button" class="wf-btn wf-btn-ghost" onclick="closeFileModal()">
                    Cancel
                </button>
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Complaint
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ══════════════════════════════════════════
   MODAL
   ══════════════════════════════════════════ */
const fileModal = document.getElementById('fileModal');
function openFileModal()  { fileModal.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeFileModal() { fileModal.classList.remove('open'); document.body.style.overflow = ''; }
fileModal.addEventListener('click', e => { if (e.target === fileModal) closeFileModal(); });
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeFileModal();
});

/* ══════════════════════════════════════════
   TOGGLE INLINE EDIT
   ══════════════════════════════════════════ */
function toggleEdit(id) {
    const panel = document.getElementById('edit-panel-' + id);
    if (!panel) return;
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/* ══════════════════════════════════════════
   MOBILE SIDEBAR
   ══════════════════════════════════════════ */
const sidebar        = document.getElementById('wfSidebar');
const sidebarOverlay = document.getElementById('wfSidebarOverlay');
const sidebarToggle  = document.getElementById('wfSidebarToggle');

sidebarToggle.addEventListener('click', () => {
    sidebar.classList.add('open');
    sidebarOverlay.classList.add('open');
});
sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('open');
});
</script>

</body>
</html>