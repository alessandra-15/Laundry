<?php
/**
 * feedback.php
 * WashFlow — Admin Feedback Management
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

if (!function_exists('render_stars')) {
    function render_stars($rating) {
        $rating = max(0, min(5, (int)$rating));
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $out .= '<i class="fas fa-star" style="color:#F0B400;"></i>';
            } else {
                $out .= '<i class="far fa-star" style="color:#D5DDE5;"></i>';
            }
        }
        return $out;
    }
}

function rating_label($rating) {
    $r = (int)$rating;
    switch ($r) {
        case 5: return ['label' => 'Excellent', 'color' => '#1E7E45', 'bg' => '#EAF7F0'];
        case 4: return ['label' => 'Good',      'color' => '#00537A', 'bg' => '#EBF5FB'];
        case 3: return ['label' => 'Average',   'color' => '#946200', 'bg' => '#FEF9E7'];
        case 2: return ['label' => 'Poor',      'color' => '#A8322D', 'bg' => '#FDEDEC'];
        case 1: return ['label' => 'Bad',       'color' => '#A8322D', 'bg' => '#FDEDEC'];
        default: return ['label' => 'No Rating', 'color' => '#5A7184', 'bg' => '#F4F7F9'];
    }
}

/* ══════════════════════════════════════════
   POST HANDLERS
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    /* ---------- ADD / UPDATE ADMIN RESPONSE ---------- */
    if ($action === 'respond') {
        $id       = (int)($_POST['id'] ?? 0);
        $response = trim($_POST['admin_response'] ?? '');
        $ok = false;

        try {
            if ($id > 0 && $response !== '') {
                $stmt = $conn->prepare("
                    UPDATE feedback
                    SET admin_response = ?, responded_at = NOW()
                    WHERE feedback_id = ?
                ");
                $stmt->bind_param('si', $response, $id);
                $ok = $stmt->execute();
                $stmt->close();
            }

            if ($ok && class_exists('Logger')) {
                Logger::info('Feedback response added', [
                    'id' => $id, 'admin' => $admin_id
                ]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Feedback response failed', ['error' => $ex->getMessage()]);
        }

        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok ? "Response sent for feedback #$id." : 'Failed to send response.'
        ];
    }

    /* ---------- DELETE ---------- */
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $ok = false;
        try {
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ?");
                $stmt->bind_param('i', $id);
                $ok = $stmt->execute();
                $stmt->close();
            }
            if ($ok && class_exists('Logger')) {
                Logger::info('Feedback deleted', ['id' => $id, 'admin' => $admin_id]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Feedback delete failed', ['error' => $ex->getMessage()]);
        }

        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok ? "Feedback #$id deleted." : 'Failed to delete feedback.'
        ];
    }

    /* ---------- CLEAR RESPONSE ---------- */
    if ($action === 'clear_response') {
        $id = (int)($_POST['id'] ?? 0);
        $ok = false;
        try {
            if ($id > 0) {
                $stmt = $conn->prepare("
                    UPDATE feedback
                    SET admin_response = NULL, responded_at = NULL
                    WHERE feedback_id = ?
                ");
                $stmt->bind_param('i', $id);
                $ok = $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Clear response failed', ['error' => $ex->getMessage()]);
        }
        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok ? "Response cleared." : 'Failed to clear response.'
        ];
    }

    /* ---------- CSV EXPORT ---------- */
    if ($action === 'export_csv') {
        $rating_f  = trim($_POST['rating'] ?? '');
        $date_from = trim($_POST['date_from'] ?? '');
        $date_to   = trim($_POST['date_to'] ?? '');
        $has_resp  = trim($_POST['has_response'] ?? '');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="feedback_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['ID', 'Customer', 'Booking ID', 'Rating', 'Comment', 'Admin Response', 'Responded At', 'Created At']);

        $where = ["1=1"];
        $params = [];
        $types = '';

        if ($rating_f !== '' && ctype_digit($rating_f) && (int)$rating_f >= 1 && (int)$rating_f <= 5) {
            $where[] = "f.rating = ?";
            $params[] = (int)$rating_f;
            $types .= 'i';
        }
        if ($date_from !== '') { $where[] = "DATE(f.created_at) >= ?"; $params[] = $date_from; $types .= 's'; }
        if ($date_to !== '')   { $where[] = "DATE(f.created_at) <= ?"; $params[] = $date_to; $types .= 's'; }
        if ($has_resp === '1') { $where[] = "(f.admin_response IS NOT NULL AND f.admin_response <> '')"; }
        if ($has_resp === '0') { $where[] = "(f.admin_response IS NULL OR f.admin_response = '')"; }

        $where_sql = implode(' AND ', $where);

        $stmt = $conn->prepare("
            SELECT f.feedback_id, f.booking_id, f.rating, f.comment,
                   f.admin_response, f.responded_at, f.created_at,
                   CONCAT(cu.first_name,' ',cu.last_name) AS customer_name
            FROM feedback f
            LEFT JOIN customer_info cu ON cu.Customer_ID = f.user_id
            WHERE $where_sql
            ORDER BY f.created_at DESC
        ");
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [
                $r['feedback_id'],
                $r['customer_name'] ?: '—',
                $r['booking_id'],
                $r['rating'],
                $r['comment'],
                $r['admin_response'] ?: '',
                $r['responded_at'] ?: '',
                $r['created_at']
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
$rating_f   = trim($_GET['rating'] ?? '');
$date_from  = trim($_GET['date_from'] ?? '');
$date_to    = trim($_GET['date_to'] ?? '');
$has_resp   = trim($_GET['has_response'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 15;
$offset     = ($page - 1) * $per_page;

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = [
    'total'      => 0,
    'avg_rating' => 0,
    'five_star'  => 0,
    'low_rating' => 0,
    'responded'  => 0,
];

try {
    $q = $conn->query("SELECT COUNT(*) c FROM feedback");
    if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COALESCE(AVG(rating),0) a FROM feedback WHERE rating IS NOT NULL");
    if ($q) $stats['avg_rating'] = round((float)$q->fetch_assoc()['a'], 2);

    $q = $conn->query("SELECT COUNT(*) c FROM feedback WHERE rating = 5");
    if ($q) $stats['five_star'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM feedback WHERE rating <= 2");
    if ($q) $stats['low_rating'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM feedback WHERE admin_response IS NOT NULL AND admin_response <> ''");
    if ($q) $stats['responded'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Feedback stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   RATING DISTRIBUTION (for chart)
   ══════════════════════════════════════════ */
$rating_dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
try {
    $q = $conn->query("
        SELECT rating, COUNT(*) AS count
        FROM feedback
        WHERE rating IS NOT NULL
        GROUP BY rating
    ");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $r = (int)$row['rating'];
            if ($r >= 1 && $r <= 5) {
                $rating_dist[$r] = (int)$row['count'];
            }
        }
    }
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Rating distribution failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH FEEDBACK
   ══════════════════════════════════════════ */
$feedbacks   = [];
$total_rows  = 0;
$total_pages = 1;

try {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[] = "(f.comment LIKE ? OR f.admin_response LIKE ?
                     OR cu.first_name LIKE ? OR cu.last_name LIKE ?
                     OR f.feedback_id LIKE ? OR f.booking_id LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 6; $i++) { $params[] = $like; $types .= 's'; }
    }
    if ($rating_f !== '' && ctype_digit($rating_f) && (int)$rating_f >= 1 && (int)$rating_f <= 5) {
        $where[] = "f.rating = ?";
        $params[] = (int)$rating_f;
        $types .= 'i';
    }
    if ($date_from !== '') { $where[] = "DATE(f.created_at) >= ?"; $params[] = $date_from; $types .= 's'; }
    if ($date_to !== '')   { $where[] = "DATE(f.created_at) <= ?"; $params[] = $date_to; $types .= 's'; }
    if ($has_resp === '1') { $where[] = "(f.admin_response IS NOT NULL AND f.admin_response <> '')"; }
    if ($has_resp === '0') { $where[] = "(f.admin_response IS NULL OR f.admin_response = '')"; }

    $where_sql = implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) c
                  FROM feedback f
                  LEFT JOIN customer_info cu ON cu.Customer_ID = f.user_id
                  WHERE $where_sql";
    $stmt = $conn->prepare($count_sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    $list_sql = "SELECT f.feedback_id, f.user_id, f.booking_id, f.rating, f.comment,
                        f.created_at, f.admin_response, f.responded_at,
                        cu.first_name, cu.last_name, cu.contact_number, cu.email
                 FROM feedback f
                 LEFT JOIN customer_info cu ON cu.Customer_ID = f.user_id
                 WHERE $where_sql
                 ORDER BY f.created_at DESC
                 LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($list_sql);
    $bind_types  = $types . 'ii';
    $bind_params = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch feedback failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   URL HELPER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'q'            => $_GET['q']            ?? '',
        'rating'       => $_GET['rating']       ?? '',
        'date_from'    => $_GET['date_from']    ?? '',
        'date_to'      => $_GET['date_to']      ?? '',
        'has_response' => $_GET['has_response'] ?? '',
        'page'         => $_GET['page']         ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'feedback.php?' . http_build_query(array_filter($merged, function($v) {
        return $v !== '' && $v !== null;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback — WashFlow</title>

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
.wf-btn-green {
    background: var(--green);
    color: white;
}
.wf-btn-green:hover { background: #1E7E45; }

/* CARD */
.wf-card {
    background: white;
    border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
}

/* GRID 2 COL */
.wf-grid-2 {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}
@media (max-width: 1200px) {
    .wf-grid-2 { grid-template-columns: 1fr; }
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
.wf-card-title-icon.icon-gold { background: #FFF4CC; color: #8A6400; }
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
.wf-card-body-pad { padding: 1.5rem 1.6rem; }

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

.wf-feedback-id {
    font-weight: 700;
    color: var(--primary);
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.8rem;
}
.wf-customer { font-weight: 600; color: var(--dark-blue); }
.wf-comment {
    color: var(--text-secondary);
    font-size: 0.84rem;
    max-width: 340px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-height: 1.45;
}
.wf-date { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }

.wf-stars {
    display: inline-flex;
    gap: 0.15rem;
    font-size: 0.85rem;
}
.wf-stars i { font-size: 0.85rem; }

.wf-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 7px;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    white-space: nowrap;
}
.wf-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
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
.wf-icon-btn.reply:hover { border-color: var(--green); color: var(--green); background: #EAF7F0; }
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

/* RATING DISTRIBUTION BARS */
.wf-dist-list { display: flex; flex-direction: column; gap: 0.85rem; }
.wf-dist-item {
    display: grid;
    grid-template-columns: 60px 1fr 50px;
    gap: 0.75rem;
    align-items: center;
}
.wf-dist-label {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--dark-blue);
}
.wf-dist-label i { color: #F0B400; font-size: 0.7rem; }
.wf-dist-track {
    height: 10px;
    background: var(--border-soft);
    border-radius: 6px;
    overflow: hidden;
}
.wf-dist-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.5s ease;
}
.wf-dist-count {
    text-align: right;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--dark-blue);
}

/* AVG RATING HERO */
.wf-avg-hero {
    text-align: center;
    padding: 1rem 0 1.25rem;
    border-bottom: 1px solid var(--border-soft);
    margin-bottom: 1.25rem;
}
.wf-avg-number {
    font-size: 3rem;
    font-weight: 800;
    color: var(--dark-blue);
    line-height: 1;
    letter-spacing: -0.04em;
}
.wf-avg-stars {
    display: inline-flex;
    gap: 0.2rem;
    margin: 0.5rem 0 0.35rem;
}
.wf-avg-stars i { font-size: 1.1rem; }
.wf-avg-label {
    font-size: 0.78rem;
    color: var(--text-muted);
    font-weight: 500;
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

.wf-quote {
    background: var(--light-blue-pale);
    border-left: 4px solid var(--light-blue);
    border-radius: 8px;
    padding: 1rem 1.15rem;
    font-size: 0.88rem;
    color: var(--text-primary);
    line-height: 1.65;
    font-style: italic;
}
.wf-quote-response {
    background: #EAF7F0;
    border-left-color: var(--green);
}

.wf-panel-footer {
    padding: 1.25rem 1.75rem;
    border-top: 1px solid var(--border);
    background: #FAFCFE;
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
}

/* FORM */
.wf-form-group { margin-bottom: 1rem; }
.wf-form-group label {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.4rem;
}
.wf-textarea {
    width: 100%;
    padding: 0.7rem 0.9rem;
    border: 1px solid var(--border);
    border-radius: 9px;
    font-size: 0.85rem;
    font-family: inherit;
    color: var(--text-primary);
    background: white;
    transition: all 0.2s;
    outline: none;
    resize: vertical;
    min-height: 90px;
}
.wf-textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 90, 133, 0.1);
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
    .wf-dist-item { grid-template-columns: 50px 1fr 40px; }
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
            <a href="feedback.php" class="wf-nav-item active">
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
                <p>Read, respond, and track customer reviews</p>
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
                    <div class="wf-stat-icon icon-gold"><i class="fas fa-star"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['avg_rating'], 2) ?></div>
                    <div class="wf-stat-label">Average Rating</div>
                </div>
            </div>

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
                    <div class="wf-stat-icon icon-green"><i class="fas fa-thumbs-up"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['five_star']) ?></div>
                    <div class="wf-stat-label">5-Star Reviews</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-red"><i class="fas fa-thumbs-down"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['low_rating']) ?></div>
                    <div class="wf-stat-label">Low Ratings (1–2★)</div>
                </div>
            </div>
        </div>

        <!-- RATING DISTRIBUTION + OVERVIEW -->
        <div class="wf-grid-2">
            <div class="wf-card" style="margin-bottom:0;">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon icon-gold">
                            <i class="fas fa-chart-simple"></i>
                        </div>
                        <div>
                            <h3>Rating Distribution</h3>
                            <p>How customers rate your service</p>
                        </div>
                    </div>
                </div>
                <div class="wf-card-body-pad">
                    <?php
                    $max_dist = max($rating_dist);
                    if ($max_dist <= 0) $max_dist = 1;
                    ?>
                    <div class="wf-dist-list">
                        <?php for ($r = 5; $r >= 1; $r--):
                            $count = $rating_dist[$r];
                            $pct = ($count / $max_dist) * 100;
                            $pct = max($pct, 2);
                            $colors = [5 => '#1E7E45', 4 => '#00537A', 3 => '#B88A00', 2 => '#E67E22', 1 => '#A8322D'];
                        ?>
                        <div class="wf-dist-item">
                            <div class="wf-dist-label">
                                <?= $r ?> <i class="fas fa-star"></i>
                            </div>
                            <div class="wf-dist-track">
                                <div class="wf-dist-fill" style="width:<?= $pct ?>%;background:<?= $colors[$r] ?>;"></div>
                            </div>
                            <div class="wf-dist-count"><?= number_format($count) ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="wf-card" style="margin-bottom:0;">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon icon-good">
                            <i class="fas fa-reply"></i>
                        </div>
                        <div>
                            <h3>Response Rate</h3>
                            <p>Feedback na may sagot</p>
                        </div>
                    </div>
                </div>
                <div class="wf-card-body-pad">
                    <div class="wf-avg-hero">
                        <div class="wf-avg-number"><?= number_format($stats['avg_rating'], 1) ?></div>
                        <div class="wf-avg-stars">
                            <?= render_stars(round($stats['avg_rating'])) ?>
                        </div>
                        <div class="wf-avg-label">Overall Customer Rating</div>
                    </div>

                    <?php
                    $responded_pct = $stats['total'] > 0 ? ($stats['responded'] / $stats['total']) * 100 : 0;
                    ?>
                    <div class="wf-dist-item" style="grid-template-columns:1fr auto;">
                        <div class="wf-dist-label" style="font-weight:700;">
                            Responded
                        </div>
                        <div class="wf-dist-count">
                            <?= number_format($stats['responded']) ?> / <?= number_format($stats['total']) ?>
                        </div>
                    </div>
                    <div class="wf-dist-track" style="margin-top:0.6rem;">
                        <div class="wf-dist-fill" style="width:<?= $responded_pct ?>%;background:var(--green);"></div>
                    </div>
                    <div style="text-align:center;font-size:0.78rem;color:var(--text-muted);font-weight:600;margin-top:0.5rem;">
                        <?= number_format($responded_pct, 1) ?>% response rate
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form method="get" action="feedback.php" class="wf-filter-bar">
            <div class="wf-filter-group" style="flex: 1 1 220px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="Comment, customer, feedback ID..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 130px;">
                <label>Rating</label>
                <select name="rating" class="wf-select">
                    <option value="">All Ratings</option>
                    <?php foreach ([5,4,3,2,1] as $r): ?>
                    <option value="<?= $r ?>" <?= $rating_f == $r ? 'selected' : '' ?>><?= $r ?> Star<?= $r > 1 ? 's' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 150px;">
                <label>Response</label>
                <select name="has_response" class="wf-select">
                    <option value="">All</option>
                    <option value="1" <?= $has_resp === '1' ? 'selected' : '' ?>>Responded</option>
                    <option value="0" <?= $has_resp === '0' ? 'selected' : '' ?>>No Response</option>
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
                <a href="feedback.php" class="wf-btn wf-btn-outline">
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
            <input type="hidden" name="rating" value="<?= e($rating_f) ?>">
            <input type="hidden" name="has_response" value="<?= e($has_resp) ?>">
            <input type="hidden" name="date_from" value="<?= e($date_from) ?>">
            <input type="hidden" name="date_to" value="<?= e($date_to) ?>">
        </form>

        <!-- TABLE -->
        <div class="wf-card">
            <?php if (empty($feedbacks)): ?>
                <div class="wf-empty">
                    <i class="fas fa-star"></i>
                    <p>No feedback found</p>
                </div>
            <?php else: ?>
            <div class="wf-table-wrap">
                <table class="wf-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Response</th>
                            <th>Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $f):
                            $customer_name = trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
                            if ($customer_name === '') $customer_name = 'Customer #' . $f['user_id'];
                            $has_reply = !empty($f['admin_response']);
                        ?>
                        <tr>
                            <td><span class="wf-feedback-id">#<?= e($f['feedback_id']) ?></span></td>
                            <td>
                                <div class="wf-customer"><?= e($customer_name) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">
                                    Booking #<?= e($f['booking_id']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="wf-stars"><?= render_stars($f['rating']) ?></div>
                                <div style="font-size:0.72rem;color:var(--text-muted);font-weight:600;margin-top:0.15rem;">
                                    <?= e(rating_label($f['rating'])['label']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="wf-comment"><?= e($f['comment'] ?: '—') ?></div>
                            </td>
                            <td>
                                <?php if ($has_reply): ?>
                                    <span class="wf-badge" style="color:#1E7E45;background:#EAF7F0;">
                                        <span class="wf-badge-dot"></span> Replied
                                    </span>
                                <?php else: ?>
                                    <span class="wf-badge" style="color:#946200;background:#FEF9E7;">
                                        <span class="wf-badge-dot"></span> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><span class="wf-date"><?= date('M j, Y', strtotime($f['created_at'])) ?></span></td>
                            <td>
                                <div class="wf-row-actions">
                                    <button type="button" class="wf-icon-btn reply" title="<?= $has_reply ? 'View / Edit Response' : 'Reply' ?>"
                                        onclick='openPanel(<?= json_encode([
                                            "id"             => $f["feedback_id"],
                                            "customer"       => $customer_name,
                                            "customer_id"    => $f["user_id"],
                                            "booking_id"     => $f["booking_id"],
                                            "contact"        => $f["contact_number"] ?? "",
                                            "email"          => $f["email"] ?? "",
                                            "rating"         => (int)$f["rating"],
                                            "comment"        => $f["comment"] ?? "",
                                            "admin_response" => $f["admin_response"] ?? "",
                                            "responded_at"   => $f["responded_at"] ?? "",
                                            "created_at"     => $f["created_at"],
                                        ]) ?>)'>
                                        <i class="fas fa-<?= $has_reply ? 'eye' : 'reply' ?>"></i>
                                    </button>
                                    <button type="button" class="wf-icon-btn delete" title="Delete"
                                        onclick='confirmDelete(<?= (int)$f["feedback_id"] ?>)'>
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
                    Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> feedback
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
     SIDE PANEL — Feedback Details
     ══════════════════════════════════════════ -->
<div class="wf-panel-overlay" id="panelOverlay" onclick="closePanel()"></div>
<aside class="wf-panel" id="feedbackPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-star"></i></div>
            <div>
                <h3 id="panelTitle">Feedback Details</h3>
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
        <!-- dynamic action buttons -->
    </div>
</aside>

<!-- Hidden form for response -->
<form method="post" id="respondForm" style="display:none;">
    <input type="hidden" name="action" value="respond">
    <input type="hidden" name="id" id="respondId">
    <input type="hidden" name="admin_response" id="respondText">
</form>

<!-- Hidden form for delete -->
<form method="post" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<!-- Hidden form for clear response -->
<form method="post" id="clearResponseForm" style="display:none;">
    <input type="hidden" name="action" value="clear_response">
    <input type="hidden" name="id" id="clearResponseId">
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
const feedbackPanel = document.getElementById('feedbackPanel');

function openPanel(data) {
    const hasReply = data.admin_response && data.admin_response.trim() !== '';

    document.getElementById('panelTitle').textContent = 'Feedback #' + data.id;
    document.getElementById('panelSubtitle').textContent =
        'From Booking #' + data.booking_id + ' • ' + formatDate(data.created_at);

    let html = '';

    /* Rating hero */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-star"></i> Customer Rating</div>
        <div style="display:flex;align-items:center;gap:1rem;">
            <div style="font-size:2.5rem;font-weight:800;color:var(--dark-blue);line-height:1;letter-spacing:-0.04em;">
                ${data.rating}<span style="font-size:1.2rem;color:var(--text-muted);">/5</span>
            </div>
            <div>
                <div class="wf-stars" style="font-size:1.1rem;">
                    ${renderStars(data.rating)}
                </div>
                <div style="font-size:0.8rem;font-weight:700;color:${ratingColor(data.rating)};margin-top:0.25rem;">
                    ${ratingLabel(data.rating)}
                </div>
            </div>
        </div>
    </div>`;

    /* Customer */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-user"></i> Customer Information</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Name</div>
                <div class="wf-detail-value">${escapeHtml(data.customer)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Customer ID</div>
                <div class="wf-detail-value">#${escapeHtml(data.customer_id)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Contact</div>
                <div class="wf-detail-value muted">${escapeHtml(data.contact) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Email</div>
                <div class="wf-detail-value muted">${escapeHtml(data.email) || '—'}</div>
            </div>
        </div>
    </div>`;

    /* Comment */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-comment-dots"></i> Customer Comment</div>
        <div class="wf-quote">${escapeHtml(data.comment) || '<span style="color:var(--text-muted);">No comment provided.</span>'}</div>
    </div>`;

    /* Existing response */
    if (hasReply) {
        html += `
        <div class="wf-detail-section">
            <div class="wf-detail-section-title"><i class="fas fa-reply-all"></i> Admin Response</div>
            <div class="wf-quote wf-quote-response">${escapeHtml(data.admin_response)}</div>
            <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;margin-top:0.5rem;">
                <i class="fas fa-clock"></i> Responded on ${formatDate(data.responded_at)}
            </div>
        </div>`;
    }

    /* Response form */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-pen"></i> ${hasReply ? 'Edit Response' : 'Write Response'}</div>
        <div class="wf-form-group">
            <label>Your Response</label>
            <textarea class="wf-textarea" id="panelResponse" placeholder="Thank the customer or address their concern...">${escapeHtml(data.admin_response)}</textarea>
        </div>
    </div>`;

    document.getElementById('panelBody').innerHTML = html;

    /* Footer */
    let footerHtml = '';

    footerHtml += `<button type="button" class="wf-btn wf-btn-primary" style="background:var(--green);" onclick='submitResponse(${data.id})'><i class="fas fa-paper-plane"></i> ${hasReply ? 'Update' : 'Send'} Response</button>`;

    if (hasReply) {
        footerHtml += `<button type="button" class="wf-btn wf-btn-outline" style="border-color:var(--red);color:var(--red);" onclick='clearResponse(${data.id})'><i class="fas fa-eraser"></i> Clear</button>`;
    }

    footerHtml += `<button type="button" class="wf-btn wf-btn-outline" onclick="closePanel()"><i class="fas fa-times"></i> Close</button>`;

    document.getElementById('panelFooter').innerHTML = footerHtml;

    panelOverlay.classList.add('open');
    feedbackPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    panelOverlay.classList.remove('open');
    feedbackPanel.classList.remove('open');
    document.body.style.overflow = '';
}

function submitResponse(id) {
    const responseEl = document.getElementById('panelResponse');
    const text = responseEl ? responseEl.value.trim() : '';

    if (!text) {
        showToast('error', 'Please enter a response.');
        return;
    }

    document.getElementById('respondId').value = id;
    document.getElementById('respondText').value = text;
    document.getElementById('respondForm').submit();
}

function clearResponse(id) {
    if (confirm('Clear the admin response for this feedback?')) {
        document.getElementById('clearResponseId').value = id;
        document.getElementById('clearResponseForm').submit();
    }
}

/* ══════════════════════════════════════════
   DELETE
   ══════════════════════════════════════════ */
function confirmDelete(id) {
    if (confirm('Delete feedback #' + id + '?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
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

function renderStars(rating) {
    rating = Math.max(0, Math.min(5, parseInt(rating) || 0));
    let out = '';
    for (let i = 1; i <= 5; i++) {
        out += i <= rating
            ? '<i class="fas fa-star" style="color:#F0B400;"></i>'
            : '<i class="far fa-star" style="color:#D5DDE5;"></i>';
    }
    return out;
}

function ratingLabel(rating) {
    switch (parseInt(rating)) {
        case 5: return 'Excellent';
        case 4: return 'Good';
        case 3: return 'Average';
        case 2: return 'Poor';
        case 1: return 'Bad';
        default: return 'No Rating';
    }
}

function ratingColor(rating) {
    switch (parseInt(rating)) {
        case 5: return '#1E7E45';
        case 4: return '#00537A';
        case 3: return '#B88A00';
        case 2: return '#E67E22';
        case 1: return '#A8322D';
        default: return '#5A7184';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePanel();
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