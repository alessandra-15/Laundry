<?php
/**
 * my_feedback.php
 * Customer — Feedback Hub (Rate + Submit + View History)
 * All-in-one page. No separate submit page needed.
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

function render_stars($rating) {
    $rating = max(0, min(5, (int)$rating));
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating
            ? '<i class="fas fa-star" style="color:#F0B400;"></i>'
            : '<i class="far fa-star" style="color:#D5DDE5;"></i>';
    }
    return $out;
}

function rating_label($rating) {
    switch ((int)$rating) {
        case 5: return ['label' => 'Excellent', 'color' => '#1E7E45', 'bg' => '#EAF7F0'];
        case 4: return ['label' => 'Good',      'color' => '#00537A', 'bg' => '#EBF5FB'];
        case 3: return ['label' => 'Average',   'color' => '#946200', 'bg' => '#FEF9E7'];
        case 2: return ['label' => 'Poor',      'color' => '#A8322D', 'bg' => '#FDEDEC'];
        case 1: return ['label' => 'Bad',       'color' => '#A8322D', 'bg' => '#FDEDEC'];
        default: return ['label' => 'No Rating', 'color' => '#5A7184', 'bg' => '#F4F7F9'];
    }
}

/* ══════════════════════════════════════════
   POST HANDLER — SUBMIT / UPDATE / DELETE
   ══════════════════════════════════════════ */
$flash = null;
$active_tab = $_GET['tab'] ?? 'rate'; // rate | history

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ---------- SUBMIT FEEDBACK ---------- */
    if ($action === 'submit') {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $rating     = (int)($_POST['rating'] ?? 0);
        $comment    = mb_substr(trim($_POST['comment'] ?? ''), 0, 1000);
        $ok = false;

        try {
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Please select a rating from 1 to 5 stars.');
            }

            $check = $conn->prepare("
                SELECT Booking_ID FROM booking
                WHERE Booking_ID = ? AND Customer_ID = ? AND status = 'Completed'
            ");
            $check->bind_param('ii', $booking_id, $customer_id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                $check->close();
                throw new Exception('Invalid booking or booking is not yet completed.');
            }
            $check->close();

            $exists = $conn->prepare("SELECT feedback_id FROM feedback WHERE booking_id = ? AND user_id = ?");
            $exists->bind_param('ii', $booking_id, $customer_id);
            $exists->execute();
            if ($exists->get_result()->fetch_assoc()) {
                $exists->close();
                throw new Exception('You already submitted feedback for this booking.');
            }
            $exists->close();

            $stmt = $conn->prepare("
                INSERT INTO feedback (user_id, booking_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param('iiis', $customer_id, $booking_id, $rating, $comment);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok && class_exists('Logger')) {
                Logger::info('Customer submitted feedback', [
                    'customer_id' => $customer_id,
                    'booking_id'  => $booking_id,
                    'rating'      => $rating
                ]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Submit feedback failed', ['error' => $ex->getMessage()]);
            $flash = ['type' => 'error', 'message' => $ex->getMessage()];
        }

        if (!$flash) {
            $flash = ['type' => 'success', 'message' => 'Salamat! Your feedback has been submitted.'];
        }

        $_SESSION['fb_flash'] = $flash;
        $_SESSION['fb_tab']   = 'history';
        header('Location: my_feedback.php?tab=history');
        exit();
    }

    /* ---------- UPDATE FEEDBACK (within 24 hours) ---------- */
    if ($action === 'update') {
        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        $rating      = (int)($_POST['rating'] ?? 0);
        $comment     = mb_substr(trim($_POST['comment'] ?? ''), 0, 1000);
        $ok = false;

        try {
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Please select a rating from 1 to 5 stars.');
            }

            // Can only update own feedback, within 24 hours, and no admin reply yet
            $check = $conn->prepare("
                SELECT feedback_id FROM feedback
                WHERE feedback_id = ? AND user_id = ?
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND (admin_response IS NULL OR admin_response = '')
            ");
            $check->bind_param('ii', $feedback_id, $customer_id);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                $check->close();
                throw new Exception('Cannot edit: feedback is over 24 hours old or already replied to.');
            }
            $check->close();

            $stmt = $conn->prepare("
                UPDATE feedback SET rating = ?, comment = ?
                WHERE feedback_id = ? AND user_id = ?
            ");
            $stmt->bind_param('isii', $rating, $comment, $feedback_id, $customer_id);
            $ok = $stmt->execute();
            $stmt->close();
        } catch (Exception $ex) {
            $flash = ['type' => 'error', 'message' => $ex->getMessage()];
        }

        if (!$flash) {
            $flash = ['type' => 'success', 'message' => 'Feedback updated successfully.'];
        }

        $_SESSION['fb_flash'] = $flash;
        header('Location: my_feedback.php?tab=history');
        exit();
    }

    /* ---------- DELETE FEEDBACK (within 24 hours) ---------- */
    if ($action === 'delete') {
        $feedback_id = (int)($_POST['feedback_id'] ?? 0);
        $ok = false;

        try {
            $stmt = $conn->prepare("
                DELETE FROM feedback
                WHERE feedback_id = ? AND user_id = ?
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND (admin_response IS NULL OR admin_response = '')
            ");
            $stmt->bind_param('ii', $feedback_id, $customer_id);
            $ok = $stmt->execute() && $stmt->affected_rows > 0;
            $stmt->close();

            if (!$ok) {
                throw new Exception('Cannot delete: feedback is over 24 hours old or already replied to.');
            }
        } catch (Exception $ex) {
            $flash = ['type' => 'error', 'message' => $ex->getMessage()];
        }

        if (!$flash) {
            $flash = ['type' => 'success', 'message' => 'Feedback deleted.'];
        }

        $_SESSION['fb_flash'] = $flash;
        header('Location: my_feedback.php?tab=history');
        exit();
    }
}

// Pull flash from session
if (!empty($_SESSION['fb_flash'])) {
    $flash = $_SESSION['fb_flash'];
    unset($_SESSION['fb_flash']);
}

// If redirecting back to a specific tab
if (isset($_SESSION['fb_tab'])) {
    $active_tab = $_SESSION['fb_tab'];
    unset($_SESSION['fb_tab']);
}

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = ['total' => 0, 'avg' => 0, 'five_star' => 0, 'responded' => 0, 'completed' => 0, 'pending' => 0];

try {
    $q = $conn->prepare("SELECT COUNT(*) c FROM feedback WHERE user_id = ?");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['total'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();

    $q = $conn->prepare("SELECT COALESCE(AVG(rating),0) a FROM feedback WHERE user_id = ?");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['avg'] = round((float)$q->get_result()->fetch_assoc()['a'], 1); $q->close();

    $q = $conn->prepare("SELECT COUNT(*) c FROM feedback WHERE user_id = ? AND rating = 5");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['five_star'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) c FROM feedback WHERE user_id = ? AND admin_response IS NOT NULL AND admin_response <> ''");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['responded'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();

    $q = $conn->prepare("SELECT COUNT(*) c FROM booking WHERE Customer_ID = ? AND status = 'Completed'");
    $q->bind_param('i', $customer_id); $q->execute();
    $stats['completed'] = (int)$q->get_result()->fetch_assoc()['c']; $q->close();

    $stats['pending'] = max(0, $stats['completed'] - $stats['total']);
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   TAB: RATE — Completed bookings without feedback
   ══════════════════════════════════════════ */
$pending_bookings = [];
if ($active_tab === 'rate') {
    try {
        $stmt = $conn->prepare("
            SELECT b.Booking_ID, b.service, b.add_ons, b.total_amount, b.booking_date, b.pick_deliver
            FROM booking b
            LEFT JOIN feedback f
                ON f.booking_id = b.Booking_ID AND f.user_id = b.Customer_ID
            WHERE b.Customer_ID = ?
              AND b.status = 'Completed'
              AND f.feedback_id IS NULL
            ORDER BY b.booking_date DESC
        ");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $pending_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Fetch pending failed', ['error' => $ex->getMessage()]);
    }
}

/* ══════════════════════════════════════════
   TAB: HISTORY — Submitted feedback
   ══════════════════════════════════════════ */
$feedbacks   = [];
$total_rows  = 0;
$total_pages = 1;
$rating_f    = trim($_GET['rating'] ?? '');
$has_reply   = trim($_GET['has_reply'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 8;
$offset      = ($page - 1) * $per_page;

if ($active_tab === 'history') {
    try {
        $where  = ["f.user_id = ?"];
        $params = [$customer_id];
        $types  = 'i';

        if ($rating_f !== '' && ctype_digit($rating_f) && (int)$rating_f >= 1 && (int)$rating_f <= 5) {
            $where[] = "f.rating = ?";
            $params[] = (int)$rating_f;
            $types .= 'i';
        }
        if ($has_reply === '1') {
            $where[] = "(f.admin_response IS NOT NULL AND f.admin_response <> '')";
        } elseif ($has_reply === '0') {
            $where[] = "(f.admin_response IS NULL OR f.admin_response = '')";
        }

        $where_sql = implode(' AND ', $where);

        $stmt = $conn->prepare("SELECT COUNT(*) c FROM feedback f WHERE $where_sql");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $total_pages = max(1, (int)ceil($total_rows / $per_page));

        $stmt = $conn->prepare("
            SELECT f.feedback_id, f.booking_id, f.rating, f.comment,
                   f.admin_response, f.responded_at, f.created_at,
                   b.service, b.total_amount, b.booking_date,
                   (f.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    AND (f.admin_response IS NULL OR f.admin_response = '')) AS can_edit
            FROM feedback f
            LEFT JOIN booking b ON b.Booking_ID = f.booking_id
            WHERE $where_sql
            ORDER BY f.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $bind_types  = $types . 'ii';
        $bind_params = array_merge($params, [$per_page, $offset]);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Fetch feedback failed', ['error' => $ex->getMessage()]);
    }
}

/* ══════════════════════════════════════════
   URL HELPER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'tab'       => $_GET['tab']       ?? 'rate',
        'rating'    => $_GET['rating']    ?? '',
        'has_reply' => $_GET['has_reply'] ?? '',
        'page'      => $_GET['page']      ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'my_feedback.php?' . http_build_query(array_filter($merged, function($v) {
        return $v !== '' && $v !== null;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Feedback — WashFlow</title>

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
.wf-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    min-height: 100vh;
}

/* SIDEBAR */
.wf-sidebar {
    background: var(--dark-blue-deep);
    color: white;
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
    overflow-x: hidden;
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
    height: 100vh;
    scrollbar-width: none;
}
.wf-main::-webkit-scrollbar { display: none; }

/* TOPBAR */
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
    padding: 1.25rem;
    border: 1px solid var(--border);
    transition: all 0.25s ease;
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
.wf-stat-icon.icon-gold   { background: #FFF4CC; color: #8A6400; }
.wf-stat-icon.icon-blue   { background: var(--light-blue-soft); color: var(--primary); }
.wf-stat-icon.icon-green  { background: #EAF7F0; color: #1E7E45; }
.wf-stat-icon.icon-warn   { background: #FEF3E2; color: #C2410C; }

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

/* FLASH */
.wf-flash {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.25rem;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    animation: flashIn 0.35s ease;
}
@keyframes flashIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.wf-flash.success {
    background: #EAF7F0;
    color: #1E7E45;
    border-left: 4px solid var(--green);
}
.wf-flash.error {
    background: #FDEDEC;
    color: #A8322D;
    border-left: 4px solid var(--red);
}
.wf-flash i { font-size: 1.1rem; }

/* TABS */
.wf-tabs {
    display: flex;
    gap: 0.35rem;
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0.4rem;
    margin-bottom: 1.25rem;
    width: fit-content;
    max-width: 100%;
    overflow-x: auto;
}
.wf-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.15rem;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-secondary);
    transition: all 0.2s;
    white-space: nowrap;
}
.wf-tab:hover:not(.active) {
    background: var(--light-blue-pale);
    color: var(--primary);
}
.wf-tab.active {
    background: var(--dark-blue);
    color: white;
}
.wf-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 0.4rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
}
.wf-tab.active .wf-tab-count {
    background: var(--yellow);
    color: var(--dark-blue-deep);
}
.wf-tab:not(.active) .wf-tab-count {
    background: var(--light-blue-soft);
    color: var(--primary);
}

/* FILTER BAR */
.wf-filter-bar {
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.1rem 1.4rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
}
.wf-filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
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
.wf-select {
    padding: 0.65rem 0.9rem;
    border: 1px solid var(--border);
    border-radius: 9px;
    font-size: 0.85rem;
    font-family: inherit;
    color: var(--text-primary);
    background: white;
    transition: all 0.2s;
    outline: none;
    min-width: 160px;
}
.wf-select:focus {
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
.wf-btn-ghost {
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid var(--border);
}
.wf-btn-ghost:hover { background: var(--light-blue-pale); color: var(--primary); }
.wf-btn-danger {
    background: white;
    color: var(--red);
    border: 1px solid var(--border);
}
.wf-btn-danger:hover { background: #FDEDEC; border-color: var(--red); }
.wf-btn-sm {
    padding: 0.5rem 0.85rem;
    font-size: 0.78rem;
}

/* PENDING BOOKING CARD */
.wf-pending-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.4rem 1.6rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
}
.wf-pending-card:hover {
    box-shadow: 0 6px 20px rgba(0, 83, 122, 0.06);
    border-color: var(--light-blue);
}
.wf-pending-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.wf-pending-info {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.wf-pending-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--yellow-soft) 0%, #FFFCF0 100%);
    color: var(--yellow-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.wf-pending-title {
    font-weight: 700;
    color: var(--dark-blue);
    font-size: 0.98rem;
}
.wf-pending-meta {
    font-size: 0.82rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin-top: 0.15rem;
}
.wf-pending-date {
    font-size: 0.74rem;
    color: var(--text-muted);
    font-weight: 500;
    margin-top: 0.15rem;
}
.wf-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 7px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}
.wf-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

/* STAR PICKER */
.wf-star-picker {
    display: inline-flex;
    gap: 0.25rem;
    cursor: pointer;
    user-select: none;
    margin: 0.35rem 0 0.75rem;
    align-items: center;
}
.wf-star-picker i {
    font-size: 2rem;
    color: #D5DDE5;
    transition: all 0.15s;
}
.wf-star-picker i.active,
.wf-star-picker i.hover {
    color: var(--gold);
    transform: scale(1.05);
}
.wf-rating-text {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-secondary);
    margin-left: 0.6rem;
}

.wf-textarea {
    width: 100%;
    padding: 0.75rem 0.95rem;
    border: 1px solid var(--border);
    border-radius: 9px;
    font-family: inherit;
    font-size: 0.88rem;
    color: var(--text-primary);
    resize: vertical;
    min-height: 85px;
    outline: none;
    transition: all 0.2s;
}
.wf-textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 90, 133, 0.1);
}

.wf-form-actions {
    display: flex;
    gap: 0.6rem;
    margin-top: 0.9rem;
    flex-wrap: wrap;
}

/* FEEDBACK HISTORY CARD */
.wf-fb-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.4rem 1.6rem;
    margin-bottom: 1rem;
    transition: all 0.2s;
}
.wf-fb-card:hover {
    box-shadow: 0 6px 20px rgba(0, 83, 122, 0.06);
    border-color: var(--light-blue);
}
.wf-fb-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.wf-fb-head-left {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.wf-fb-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--light-blue-soft) 0%, var(--light-blue-pale) 100%);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.wf-fb-meta { line-height: 1.35; }
.wf-fb-id {
    font-weight: 700;
    color: var(--primary);
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.85rem;
}
.wf-fb-service {
    font-size: 0.82rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin-top: 0.15rem;
}
.wf-fb-date {
    font-size: 0.74rem;
    color: var(--text-muted);
    font-weight: 500;
    margin-top: 0.15rem;
}
.wf-fb-stars {
    display: inline-flex;
    gap: 0.15rem;
    font-size: 0.95rem;
}

.wf-fb-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.wf-fb-label i { color: var(--primary); font-size: 0.7rem; }

.wf-fb-comment {
    background: var(--light-blue-pale);
    border-left: 3px solid var(--light-blue);
    border-radius: 8px;
    padding: 0.9rem 1.1rem;
    font-size: 0.88rem;
    color: var(--text-primary);
    line-height: 1.65;
    font-style: italic;
}
.wf-fb-reply {
    background: #EAF7F0;
    border-left: 3px solid var(--green);
    border-radius: 8px;
    padding: 0.9rem 1.1rem;
    font-size: 0.88rem;
    color: var(--text-primary);
    line-height: 1.65;
    margin-top: 0.4rem;
}
.wf-fb-reply .wf-fb-label i { color: var(--green); }
.wf-fb-pending-note {
    background: #FFF9DB;
    border-left: 3px solid var(--gold);
    border-radius: 8px;
    padding: 0.7rem 1rem;
    font-size: 0.82rem;
    color: #8A6400;
    font-weight: 600;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.wf-fb-actions {
    display: flex;
    gap: 0.4rem;
    margin-top: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px dashed var(--border);
    flex-wrap: wrap;
    align-items: center;
}
.wf-edit-hint {
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 600;
    margin-left: auto;
}

/* EDIT PANEL */
.wf-edit-panel {
    display: none;
    background: #FAFCFE;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1rem 1.1rem;
    margin-top: 0.85rem;
}
.wf-edit-panel.open { display: block; }

/* EMPTY */
.wf-empty {
    text-align: center;
    padding: 4rem 1rem;
    color: var(--text-muted);
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
}
.wf-empty i {
    font-size: 3rem;
    color: var(--light-blue);
    margin-bottom: 0.9rem;
    display: block;
}
.wf-empty h3 {
    color: var(--dark-blue);
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
}
.wf-empty p {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--text-secondary);
}
.wf-empty a {
    color: var(--primary);
    font-weight: 700;
}

/* PAGINATION */
.wf-pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1rem;
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

@media (max-width: 1200px) {
    .wf-stats-grid { grid-template-columns: repeat(2, 1fr); }
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
    .wf-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .wf-stats-grid { grid-template-columns: 1fr; }
    .wf-filter-bar { flex-direction: column; align-items: stretch; }
    .wf-select { min-width: 100%; }
    .wf-fb-head, .wf-pending-head { flex-direction: column; }
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
                        <linearGradient id="wfLogoGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0076A8"/>
                            <stop offset="100%" stop-color="#005A85"/>
                        </linearGradient>
                        <linearGradient id="wfWaveGrad1" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FFD93D"/>
                            <stop offset="100%" stop-color="#A8E8F9"/>
                        </linearGradient>
                    </defs>
                    <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#wfLogoGrad1)"/>
                    <path d="M14 24 L20 42 L26 30 L32 42 L38 24" stroke="url(#wfWaveGrad1)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
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
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>

            <div class="wf-nav-section-label">Laundry</div>
            <a href="book_service.php" class="wf-nav-item">
                <i class="fas fa-plus-circle"></i>
                <span>New Booking</span>
            </a>
            <a href="my_bookings.php" class="wf-nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>My Bookings</span>
            </a>
            <a href="payments.php" class="wf-nav-item">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>

            <div class="wf-nav-section-label">Support</div>
            <a href="notifications.php" class="wf-nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="my_feedback.php" class="wf-nav-item active">
                <i class="fas fa-star"></i>
                <span>Feedback</span>
            </a>
            <a href="complaints.php" class="wf-nav-item">
                <i class="fas fa-headset"></i>
                <span>Complaints</span>
            </a>

            <div class="wf-nav-section-label">Account</div>
            <a href="profile.php" class="wf-nav-item">
                <i class="fas fa-user"></i>
                <span>Profile</span>
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
                <h1>My <span class="accent">Feedback</span></h1>
                <p>Rate your laundry experience and see replies from us</p>
            </div>
            <div class="wf-topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, F j, Y') ?>
            </div>
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
                    <div class="wf-stat-icon icon-gold"><i class="fas fa-star"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= $stats['avg'] > 0 ? number_format($stats['avg'], 1) : '—' ?></div>
                    <div class="wf-stat-label">My Average Rating</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-comments"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Feedback Submitted</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-warn"><i class="fas fa-clock"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                    <div class="wf-stat-label">Awaiting Your Rating</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-reply"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['responded']) ?></div>
                    <div class="wf-stat-label">Admin Replies</div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="wf-tabs">
            <a href="<?= e(build_url(['tab' => 'rate', 'page' => 1])) ?>"
               class="wf-tab <?= $active_tab === 'rate' ? 'active' : '' ?>">
                <i class="fas fa-star-half-alt"></i>
                <span>Rate a Booking</span>
                <?php if ($stats['pending'] > 0): ?>
                    <span class="wf-tab-count"><?= number_format($stats['pending']) ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= e(build_url(['tab' => 'history', 'page' => 1])) ?>"
               class="wf-tab <?= $active_tab === 'history' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                <span>My Feedback History</span>
                <?php if ($stats['total'] > 0): ?>
                    <span class="wf-tab-count"><?= number_format($stats['total']) ?></span>
                <?php endif; ?>
            </a>
        </div>

        <?php if ($active_tab === 'rate'): ?>
            <!-- ═══════════════════════════════════════════
                 TAB: RATE — Rate a Booking
                 ═══════════════════════════════════════════ -->

            <?php if (empty($pending_bookings)): ?>
                <div class="wf-empty">
                    <i class="fas fa-check-circle"></i>
                    <h3>All caught up!</h3>
                    <p>You've rated all your completed bookings. Salamat po! 🎉</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_bookings as $b):
                    $booking_ref = (int)$b['Booking_ID'];
                ?>
                <div class="wf-pending-card" data-booking="<?= $booking_ref ?>">
                    <div class="wf-pending-head">
                        <div class="wf-pending-info">
                            <div class="wf-pending-icon">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div>
                                <div class="wf-pending-title">Booking #<?= $booking_ref ?></div>
                                <div class="wf-pending-meta">
                                    <?= e($b['service'] ?: 'Laundry Service') ?>
                                    <?php if (!empty($b['add_ons']) && $b['add_ons'] !== 'None'): ?>
                                        • <?= e($b['add_ons']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="wf-pending-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('M j, Y', strtotime($b['booking_date'])) ?>
                                    • ₱<?= number_format((float)$b['total_amount'], 2) ?>
                                </div>
                            </div>
                        </div>
                        <span class="wf-badge" style="color:#8A6400;background:#FFF9DB;">
                            <span class="wf-badge-dot"></span> Awaiting Feedback
                        </span>
                    </div>

                    <form method="post" action="my_feedback.php" class="wf-feedback-form">
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="booking_id" value="<?= $booking_ref ?>">
                        <input type="hidden" name="rating" class="rating-input" value="0">

                        <div class="wf-fb-label">
                            <i class="fas fa-star"></i> How would you rate this service?
                        </div>
                        <div class="wf-star-picker" data-picker>
                            <i class="far fa-star" data-value="1"></i>
                            <i class="far fa-star" data-value="2"></i>
                            <i class="far fa-star" data-value="3"></i>
                            <i class="far fa-star" data-value="4"></i>
                            <i class="far fa-star" data-value="5"></i>
                            <span class="wf-rating-text"></span>
                        </div>

                        <div class="wf-fb-label" style="margin-top:0.4rem;">
                            <i class="fas fa-comment-dots"></i> Comment (optional)
                        </div>
                        <textarea name="comment"
                                  class="wf-textarea"
                                  maxlength="1000"
                                  placeholder="Tell us about your experience — how was the quality, scent, folding, timing?"></textarea>

                        <div class="wf-form-actions">
                            <button type="submit" class="wf-btn wf-btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Feedback
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php else: ?>
            <!-- ═══════════════════════════════════════════
                 TAB: HISTORY — My Feedback History
                 ═══════════════════════════════════════════ -->

            <!-- FILTER BAR -->
            <form method="get" action="my_feedback.php" class="wf-filter-bar">
                <input type="hidden" name="tab" value="history">

                <div class="wf-filter-group">
                    <label>Rating</label>
                    <select name="rating" class="wf-select">
                        <option value="">All Ratings</option>
                        <?php foreach ([5,4,3,2,1] as $r): ?>
                            <option value="<?= $r ?>" <?= $rating_f == $r ? 'selected' : '' ?>>
                                <?= $r ?> Star<?= $r > 1 ? 's' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="wf-filter-group">
                    <label>Admin Reply</label>
                    <select name="has_reply" class="wf-select">
                        <option value="">All</option>
                        <option value="1" <?= $has_reply === '1' ? 'selected' : '' ?>>With Reply</option>
                        <option value="0" <?= $has_reply === '0' ? 'selected' : '' ?>>No Reply Yet</option>
                    </select>
                </div>

                <div class="wf-filter-group" style="flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                    <button type="submit" class="wf-btn wf-btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?= e(build_url(['tab' => 'history', 'rating' => '', 'has_reply' => '', 'page' => 1])) ?>"
                       class="wf-btn wf-btn-outline">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>

            <?php if (empty($feedbacks)): ?>
                <div class="wf-empty">
                    <i class="fas fa-comments"></i>
                    <h3>No feedback yet</h3>
                    <p>
                        <?php if ($rating_f !== '' || $has_reply !== ''): ?>
                            Walang feedback na tumutugma sa filter.
                            <a href="<?= e(build_url(['tab' => 'history', 'rating' => '', 'has_reply' => '', 'page' => 1])) ?>">Reset filters</a>
                        <?php else: ?>
                            Wala ka pang nasusubmit na feedback.
                            <a href="<?= e(build_url(['tab' => 'rate', 'page' => 1])) ?>">Rate a completed booking →</a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>

                <?php foreach ($feedbacks as $f):
                    $rl = rating_label($f['rating']);
                    $has_reply = !empty($f['admin_response']);
                    $can_edit  = !empty($f['can_edit']);
                    $fb_id     = (int)$f['feedback_id'];
                ?>
                <div class="wf-fb-card">
                    <div class="wf-fb-head">
                        <div class="wf-fb-head-left">
                            <div class="wf-fb-avatar">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <div class="wf-fb-meta">
                                <div class="wf-fb-id">Booking #<?= (int)$f['booking_id'] ?></div>
                                <div class="wf-fb-service">
                                    <?= e($f['service'] ?: 'Laundry Service') ?>
                                    <?php if ($f['total_amount'] !== null): ?>
                                        • ₱<?= number_format((float)$f['total_amount'], 2) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="wf-fb-date">
                                    <i class="far fa-clock"></i>
                                    Submitted <?= date('M j, Y g:i A', strtotime($f['created_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.4rem;">
                            <div class="wf-fb-stars"><?= render_stars($f['rating']) ?></div>
                            <span class="wf-badge" style="color:<?= $rl['color'] ?>;background:<?= $rl['bg'] ?>;">
                                <span class="wf-badge-dot"></span> <?= e($rl['label']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="wf-fb-body">
                        <div class="wf-fb-label"><i class="fas fa-comment-dots"></i> Your Comment</div>
                        <div class="wf-fb-comment">
                            <?= $f['comment'] ? e($f['comment']) : '<span style="color:var(--text-muted);font-style:normal;">No comment provided.</span>' ?>
                        </div>
                    </div>

                    <?php if ($has_reply): ?>
                        <div class="wf-fb-body" style="margin-top:0.75rem;">
                            <div class="wf-fb-label"><i class="fas fa-reply-all"></i> Reply from WashFlow</div>
                            <div class="wf-fb-reply">
                                <?= e($f['admin_response']) ?>
                                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.4rem;font-weight:600;">
                                    <i class="fas fa-clock"></i>
                                    <?= date('M j, Y g:i A', strtotime($f['responded_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="wf-fb-pending-note">
                            <i class="fas fa-hourglass-half"></i>
                            Waiting for admin reply...
                        </div>
                    <?php endif; ?>

                    <?php if ($can_edit): ?>
                        <div class="wf-fb-actions">
                            <button type="button"
                                    class="wf-btn wf-btn-outline wf-btn-sm"
                                    onclick="toggleEdit(<?= $fb_id ?>)">
                                <i class="fas fa-pen"></i> Edit Feedback
                            </button>

                            <form method="post" action="my_feedback.php" style="display:inline;"
                                  onsubmit="return confirm('Delete this feedback? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="feedback_id" value="<?= $fb_id ?>">
                                <button type="submit" class="wf-btn wf-btn-danger wf-btn-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>

                            <span class="wf-edit-hint">
                                <i class="fas fa-info-circle"></i> Editable for 24 hours
                            </span>
                        </div>

                        <!-- Inline edit panel -->
                        <div class="wf-edit-panel" id="edit-panel-<?= $fb_id ?>">
                            <form method="post" action="my_feedback.php" class="wf-feedback-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="feedback_id" value="<?= $fb_id ?>">
                                <input type="hidden" name="rating" class="rating-input" value="<?= (int)$f['rating'] ?>">

                                <div class="wf-fb-label">
                                    <i class="fas fa-star"></i> Update Rating
                                </div>
                                <div class="wf-star-picker" data-picker data-current="<?= (int)$f['rating'] ?>">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= (int)$f['rating'] ? 'fas' : 'far' ?> fa-star <?= $i <= (int)$f['rating'] ? 'active' : '' ?>"
                                           data-value="<?= $i ?>"></i>
                                    <?php endfor; ?>
                                    <span class="wf-rating-text"></span>
                                </div>

                                <div class="wf-fb-label" style="margin-top:0.4rem;">
                                    <i class="fas fa-comment-dots"></i> Update Comment
                                </div>
                                <textarea name="comment"
                                          class="wf-textarea"
                                          maxlength="1000"><?= e($f['comment']) ?></textarea>

                                <div class="wf-form-actions">
                                    <button type="submit" class="wf-btn wf-btn-primary wf-btn-sm">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="button"
                                            class="wf-btn wf-btn-ghost wf-btn-sm"
                                            onclick="toggleEdit(<?= $fb_id ?>)">
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
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> feedback
                    </div>
                    <div class="wf-pagination">
                        <a href="<?= e(build_url(['page' => max(1, $page - 1)])) ?>"
                           class="wf-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
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
                            <a href="<?= e(build_url(['page' => $p])) ?>"
                               class="wf-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span>
                            <?php endif; ?>
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
        <?php endif; ?>

    </main>
</div>

<script>
/* ══════════════════════════════════════════
   STAR PICKER (works for both Rate & Edit forms)
   ══════════════════════════════════════════ */
const RATING_TEXT = {
    1: 'Bad 😞',
    2: 'Poor 😕',
    3: 'Average 😐',
    4: 'Good 🙂',
    5: 'Excellent 🤩'
};

function initStarPicker(form) {
    const picker = form.querySelector('[data-picker]');
    if (!picker) return;
    const stars = picker.querySelectorAll('i');
    const input = form.querySelector('.rating-input');
    const label = picker.querySelector('.wf-rating-text');

    function paint(value, isHover = false) {
        stars.forEach(s => {
            const v = +s.dataset.value;
            const filled = v <= value;
            s.classList.toggle('far', !filled);
            s.classList.toggle('fas', filled);
            s.classList.toggle('active', filled && !isHover);
            s.classList.toggle('hover', filled && isHover);
        });
        if (!isHover) {
            label.textContent = value > 0 ? RATING_TEXT[value] : '';
        }
    }

    // Set initial state
    paint(+input.value);

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => paint(+star.dataset.value, true));
        star.addEventListener('mouseleave', () => paint(+input.value));
        star.addEventListener('click', () => {
            input.value = star.dataset.value;
            paint(+input.value);
        });
    });

    picker.addEventListener('mouseleave', () => paint(+input.value));

    form.addEventListener('submit', e => {
        if (+input.value === 0) {
            e.preventDefault();
            picker.style.animation = 'shake 0.4s';
            setTimeout(() => picker.style.animation = '', 400);
            alert('Please select a star rating before submitting.');
        }
    });
}

document.querySelectorAll('.wf-feedback-form').forEach(initStarPicker);

/* Shake animation */
const style = document.createElement('style');
style.textContent = `
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
}`;
document.head.appendChild(style);

/* ══════════════════════════════════════════
   TOGGLE EDIT PANEL
   ══════════════════════════════════════════ */
function toggleEdit(feedbackId) {
    const panel = document.getElementById('edit-panel-' + feedbackId);
    if (!panel) return;
    panel.classList.toggle('open');

    // Re-init picker when opened (if not yet initialized)
    if (panel.classList.contains('open')) {
        const form = panel.querySelector('.wf-feedback-form');
        if (form && !form.dataset.starInit) {
            initStarPicker(form);
            form.dataset.starInit = '1';
        }
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