<?php
/**
 * userdashboard.php
 * WashFlow — Customer Dashboard
 *
 * ✅ Total Spent — from payments_online (Paid only)
 * ✅ Unpaid count — computed from actual payment sums
 * ✅ Recent Bookings — includes live payment amounts
 * ✅ Payment badge — reflects real payment state (Paid / Partial / Pending / Unpaid)
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';
require_once 'notifications_helper.php';

require_customer_login();

$customer_id = (int)$_SESSION['customer_id'];
$firstName   = $_SESSION['first_name'] ?? '';
$lastName    = $_SESSION['last_name'] ?? '';
$fullName    = trim($firstName . ' ' . $lastName);
$email       = $_SESSION['email'] ?? '';

$hour = (int)date('H');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 18)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';

/* ══════════════════════════════════════════
   STATS — fixed to use payments_online
   ══════════════════════════════════════════ */
$stats = [
    'total_bookings'    => 0,
    'active_laundry'    => 0,
    'completed_laundry' => 0,
    'total_spent'       => 0.0,
    'unpaid_count'      => 0,
];

try {
    // Total bookings
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM booking_online WHERE customer_id = ?");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $stats['total_bookings'] = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    // Active laundry
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM booking_online WHERE customer_id = ? AND status IN ('Pending','Processing','Ready')");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $stats['active_laundry'] = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    // Completed laundry
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM booking_online WHERE customer_id = ? AND status = 'Completed'");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $stats['completed_laundry'] = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    // Total Spent — from payments_online (Paid only)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(p.amount),0) AS total
        FROM payments_online p
        JOIN booking_online b ON p.booking_id = b.id
        WHERE b.customer_id = ? AND p.payment_status = 'Paid'
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $stats['total_spent'] = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // 🆕 Unpaid count — bookings with pending/partial payments (from payments_online)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT b.id) AS c
        FROM booking_online b
        WHERE b.customer_id = ?
          AND b.status NOT IN ('Cancelled')
          AND (
              COALESCE((SELECT SUM(p.amount) FROM payments_online p 
                        WHERE p.booking_id = b.id AND p.payment_status = 'Paid'), 0) 
              < b.total_amount
          )
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $stats['unpaid_count'] = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

} catch (Exception $e) {
    Logger::error('Customer stats failed', ['error' => $e->getMessage()]);
}

/* ══════════════════════════════════════════
   RECENT BOOKINGS (5) — with live payment status
   ══════════════════════════════════════════ */
$recent_bookings = [];
try {
    $stmt = $conn->prepare("
        SELECT b.id, b.service, b.addons, b.delivery_option, b.dropoff_date, b.dropoff_time,
               b.status, b.total_amount, b.timestamp,
               COALESCE((SELECT SUM(p.amount) FROM payments_online p 
                         WHERE p.booking_id = b.id AND p.payment_status = 'Paid'), 0) AS paid_amount,
               COALESCE((SELECT SUM(p.amount) FROM payments_online p 
                         WHERE p.booking_id = b.id AND p.payment_status = 'Pending'), 0) AS pending_amount
        FROM booking_online b
        WHERE b.customer_id = ?
        ORDER BY b.timestamp DESC
        LIMIT 5
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $recent_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    Logger::error('Recent bookings fetch failed', ['error' => $e->getMessage()]);
}

/* ══════════════════════════════════════════
   UNREAD NOTIFICATIONS
   ══════════════════════════════════════════ */
$unread_count = CustomerNotify::unreadCount($conn, $customer_id);
$recent_notifs = CustomerNotify::recent($conn, $customer_id, 5);

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
function dash_status_badge($s) {
    $s = strtolower(trim($s));
    return match ($s) {
        'pending'    => ['label' => 'Pending',    'color' => '#946200', 'bg' => '#FEF9E7'],
        'processing' => ['label' => 'Processing', 'color' => '#5B2E91', 'bg' => '#F4ECFB'],
        'ready'      => ['label' => 'Ready',      'color' => '#946200', 'bg' => '#FFF9DB'],
        'completed'  => ['label' => 'Completed',  'color' => '#1E7E45', 'bg' => '#EAF7F0'],
        'cancelled'  => ['label' => 'Cancelled',  'color' => '#A8322D', 'bg' => '#FDEDEC'],
        default      => ['label' => ucfirst($s ?: 'Pending'), 'color' => '#5A7184', 'bg' => '#F4F7F9'],
    };
}

/* 🆕 Payment badge — accepts whole booking row para computed mula sa actual payments */
function dash_payment_badge($booking) {
    $total   = (float)($booking['total_amount'] ?? 0);
    $paid    = (float)($booking['paid_amount'] ?? 0);
    $pending = (float)($booking['pending_amount'] ?? 0);

    if ($total > 0 && $paid >= $total) {
        return ['label' => 'Paid',           'color' => '#1E7E45', 'bg' => '#EAF7F0'];
    } elseif ($paid > 0) {
        return ['label' => 'Partially paid', 'color' => '#946200', 'bg' => '#FEF9E7'];
    } elseif ($pending > 0) {
        return ['label' => 'Pending',        'color' => '#946200', 'bg' => '#FEF9E7'];
    } else {
        return ['label' => 'Unpaid',         'color' => '#A8322D', 'bg' => '#FDEDEC'];
    }
}

$customer_initial = strtoupper(substr($firstName ?: 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — WashFlow</title>

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
    background: var(--bg);
    font-size: 14px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    overflow: hidden;
}

a { text-decoration: none; }

/* LAYOUT */
.wf-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    height: 100vh;
    overflow: hidden;
}

/* SIDEBAR */
.wf-sidebar {
    background: var(--dark-blue-deep);
    color: white;
    height: 100vh;
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
    padding: 1.75rem 1.5rem;
    border-bottom: 1px solid rgba(168, 232, 249, 0.08);
}
.wf-sidebar-brand-logo { width: 44px; height: 44px; flex-shrink: 0; }
.wf-sidebar-brand-logo svg { width: 100%; height: 100%; }
.wf-sidebar-brand-text { display: flex; flex-direction: column; line-height: 1; }
.wf-sidebar-brand-name {
    font-size: 1.35rem; font-weight: 800; color: white; letter-spacing: -0.04em;
}
.wf-sidebar-brand-name .flow { color: var(--yellow); }
.wf-sidebar-brand-tag {
    font-size: 0.62rem; color: rgba(168, 232, 249, 0.5);
    letter-spacing: 0.2em; text-transform: uppercase; margin-top: 5px; font-weight: 600;
}

.wf-nav { padding: 1.25rem 0.875rem; flex: 1; }
.wf-nav-section-label {
    font-size: 0.66rem; font-weight: 700; color: rgba(168, 232, 249, 0.35);
    letter-spacing: 0.22em; text-transform: uppercase;
    padding: 1.1rem 0.875rem 0.55rem;
}
.wf-nav-section-label:first-child { padding-top: 0.25rem; }

.wf-nav-item {
    display: flex; align-items: center; gap: 0.875rem;
    padding: 0.8rem 0.95rem;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.9rem; font-weight: 500;
    transition: all 0.2s;
    margin-bottom: 0.2rem;
    position: relative;
}
.wf-nav-item i { width: 22px; text-align: center; font-size: 1rem; flex-shrink: 0; }
.wf-nav-item span { flex: 1; }
.wf-nav-item:hover { background: rgba(168, 232, 249, 0.06); color: rgba(255, 255, 255, 0.95); }
.wf-nav-item.active {
    background: rgba(255, 217, 61, 0.1);
    color: white; font-weight: 600;
}
.wf-nav-item.active::before {
    content: ''; position: absolute; left: 0; top: 50%;
    transform: translateY(-50%); width: 3px; height: 22px;
    background: var(--yellow); border-radius: 0 3px 3px 0;
}
.wf-nav-item.active i { color: var(--yellow); }

.wf-sidebar-footer {
    padding: 1rem 0.875rem;
    border-top: 1px solid rgba(168, 232, 249, 0.08);
}
.wf-user-card {
    display: flex; align-items: center; gap: 0.875rem;
    padding: 0.8rem;
    border-radius: 11px;
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
    font-size: 0.9rem; font-weight: 700; color: white;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.wf-user-role { font-size: 0.72rem; color: rgba(168, 232, 249, 0.5); font-weight: 500; }

.wf-btn-logout {
    display: flex; align-items: center; gap: 0.875rem;
    padding: 0.7rem 0.85rem;
    border-radius: 11px;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.9rem; font-weight: 500;
    transition: all 0.2s;
}
.wf-btn-logout:hover { background: rgba(231, 76, 60, 0.12); color: #FF8B7E; }
.wf-btn-logout i { width: 22px; text-align: center; font-size: 1rem; }

/* MAIN */
.wf-main {
    padding: 1.75rem 2.25rem 3rem;
    overflow-y: auto;
    height: 100vh;
    scrollbar-width: none;
}
.wf-main::-webkit-scrollbar { display: none; }

/* TOPBAR */
.wf-topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.75rem;
    gap: 1rem; flex-wrap: wrap;
}
.wf-topbar-greeting h1 {
    color: var(--dark-blue);
    font-size: 1.55rem; font-weight: 800;
    margin: 0; letter-spacing: -0.03em; line-height: 1.3;
}
.wf-topbar-greeting h1 .accent { color: var(--gold); }
.wf-topbar-greeting p {
    color: var(--text-secondary);
    font-size: 0.875rem; margin: 0.2rem 0 0; font-weight: 500;
}

.wf-topbar-actions { display: flex; align-items: center; gap: 0.75rem; }

.wf-topbar-icon {
    position: relative;
    width: 42px; height: 42px; border-radius: 11px;
    background: white; border: 1px solid var(--border);
    color: var(--dark-blue);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.wf-topbar-icon:hover { border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale); }

.wf-notif-badge {
    position: absolute;
    top: -4px; right: -4px;
    min-width: 20px; height: 20px;
    padding: 0 5px;
    border-radius: 10px;
    background: var(--red); color: white;
    font-size: 0.68rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    border: 2px solid var(--bg);
    animation: badgePop 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes badgePop {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.wf-btn-primary {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    border-radius: 10px;
    background: var(--primary); color: white;
    font-size: 0.875rem; font-weight: 600;
    border: none; cursor: pointer;
    transition: all 0.2s;
}
.wf-btn-primary:hover { background: var(--primary-mid); color: white; transform: translateY(-1px); }

/* NOTIF DROPDOWN */
.wf-notif-wrap { position: relative; }
.wf-notif-panel {
    position: absolute;
    top: calc(100% + 10px); right: 0;
    width: 380px;
    max-height: 480px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(6, 52, 82, 0.15);
    z-index: 1000;
    opacity: 0; visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s;
    overflow: hidden;
    display: flex; flex-direction: column;
}
.wf-notif-panel.open { opacity: 1; visibility: visible; transform: translateY(0); }

.wf-notif-head {
    padding: 1rem 1.15rem;
    border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; justify-content: space-between;
    background: var(--light-blue-pale);
}
.wf-notif-head h6 {
    font-size: 0.92rem; font-weight: 700;
    color: var(--dark-blue); margin: 0;
    display: flex; align-items: center; gap: 0.5rem;
}
.wf-notif-head a {
    font-size: 0.78rem; font-weight: 600;
    color: var(--primary);
}
.wf-notif-head a:hover { color: var(--primary-mid); }

.wf-notif-body { flex: 1; overflow-y: auto; }
.wf-notif-body::-webkit-scrollbar { width: 5px; }
.wf-notif-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

.wf-notif-item {
    display: flex; gap: 0.75rem;
    padding: 0.9rem 1.15rem;
    border-bottom: 1px solid var(--border-soft);
    cursor: pointer;
    transition: background 0.15s;
    text-decoration: none;
    color: inherit;
    position: relative;
}
.wf-notif-item:hover { background: var(--light-blue-pale); color: inherit; }
.wf-notif-item.unread { background: linear-gradient(90deg, rgba(255,217,61,0.08) 0%, transparent 100%); }
.wf-notif-item.unread::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: var(--yellow);
}

.wf-notif-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.notif-blue   { background: var(--light-blue-soft); color: var(--primary); }
.notif-green  { background: #EAF7F0; color: #1E7E45; }
.notif-yellow { background: var(--yellow-soft); color: var(--yellow-dark); }
.notif-red    { background: #FDEDEC; color: #A8322D; }
.notif-dark   { background: #E6EDF3; color: var(--dark-blue); }

.wf-notif-content { flex: 1; min-width: 0; }
.wf-notif-content h6 {
    font-size: 0.83rem; font-weight: 700;
    color: var(--dark-blue); margin: 0 0 0.15rem;
    line-height: 1.35;
}
.wf-notif-content p {
    font-size: 0.78rem; color: var(--text-secondary);
    margin: 0 0 0.3rem; line-height: 1.45;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
}
.wf-notif-time {
    font-size: 0.68rem; color: var(--text-muted); font-weight: 500;
    display: flex; align-items: center; gap: 0.3rem;
}
.wf-notif-time i { font-size: 0.62rem; }

.wf-notif-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: var(--text-muted);
}
.wf-notif-empty i { font-size: 2rem; margin-bottom: 0.6rem; display: block; color: var(--light-blue); }
.wf-notif-empty p { margin: 0; font-size: 0.83rem; }

.wf-notif-foot {
    padding: 0.75rem 1.15rem;
    border-top: 1px solid var(--border-soft);
    text-align: center;
    background: var(--light-blue-pale);
}
.wf-notif-foot a {
    font-size: 0.8rem; font-weight: 700;
    color: var(--primary);
}

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
    padding: 1.35rem 1.25rem;
    border: 1px solid var(--border);
    transition: all 0.25s;
    display: flex; flex-direction: column; justify-content: space-between;
    min-height: 132px;
}
.wf-stat-card:hover {
    border-color: var(--light-blue);
    box-shadow: 0 8px 20px rgba(0, 83, 122, 0.08);
    transform: translateY(-3px);
}
.wf-stat-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 0.9rem;
}
.wf-stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.wf-stat-icon.icon-blue   { background: var(--light-blue-soft); color: var(--primary); }
.wf-stat-icon.icon-yellow { background: var(--yellow-soft); color: var(--yellow-dark); }
.wf-stat-icon.icon-green  { background: #EAF7F0; color: #1E7E45; }
.wf-stat-icon.icon-purple { background: #F4ECFB; color: #5B2E91; }
.wf-stat-icon.icon-gold   { background: #FEF9E7; color: #946200; }

.wf-stat-value {
    font-size: 1.75rem; font-weight: 800;
    color: var(--dark-blue); line-height: 1.1;
    letter-spacing: -0.03em; margin-bottom: 0.2rem;
}
.wf-stat-label {
    font-size: 0.78rem; color: var(--text-secondary); font-weight: 500;
}

/* CARD */
.wf-card {
    background: white;
    border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.wf-card-header {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
}
.wf-card-title {
    display: flex; align-items: center; gap: 0.8rem;
}
.wf-card-title-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--yellow-soft); color: var(--yellow-dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.wf-card-title h3 {
    font-size: 1rem; font-weight: 700;
    color: var(--dark-blue); margin: 0;
}
.wf-card-title p {
    font-size: 0.75rem; color: var(--text-muted);
    margin: 0.1rem 0 0; font-weight: 500;
}
.wf-card-link {
    font-size: 0.8rem; font-weight: 600;
    color: var(--primary);
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    border-radius: 7px;
    transition: all 0.2s;
}
.wf-card-link:hover { background: var(--light-blue-pale); color: var(--primary-mid); }

.wf-card-body { padding: 0; }

/* TABLE */
.wf-table-wrap { overflow-x: auto; }
.wf-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.wf-table thead th {
    text-align: left;
    padding: 0.85rem 1.5rem;
    font-size: 0.68rem; font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.08em;
    background: #FAFCFE;
    border-bottom: 1px solid var(--border-soft);
    white-space: nowrap;
}
.wf-table tbody td {
    padding: 0.9rem 1.5rem;
    border-bottom: 1px solid var(--border-soft);
    vertical-align: middle;
}
.wf-table tbody tr:last-child td { border-bottom: none; }
.wf-table tbody tr { transition: background 0.15s; }
.wf-table tbody tr:hover { background: var(--light-blue-pale); }

.wf-booking-id {
    font-family: 'SF Mono', Monaco, monospace;
    font-weight: 700; color: var(--primary);
    font-size: 0.8rem;
}
.wf-service-text { color: var(--dark-blue); font-weight: 600; font-size: 0.85rem; }
.wf-amount { font-weight: 700; color: var(--dark-blue); }
.wf-date { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }

.wf-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.32rem 0.65rem;
    border-radius: 7px;
    font-size: 0.72rem; font-weight: 600;
    white-space: nowrap;
}
.wf-badge-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: currentColor;
}

.wf-icon-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-secondary);
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.15s;
    font-size: 0.8rem;
}
.wf-icon-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale); }

/* EMPTY */
.wf-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-muted);
}
.wf-empty i { font-size: 2.75rem; color: var(--light-blue); margin-bottom: 0.75rem; display: block; }
.wf-empty p { margin: 0 0 0.5rem; font-size: 0.9rem; font-weight: 500; }
.wf-empty a { color: var(--primary); font-weight: 700; font-size: 0.85rem; }

/* QUICK ACTIONS */
.wf-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    padding: 1.5rem;
}
.wf-action-tile {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 0.65rem;
    padding: 1.35rem 1rem;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: white;
    color: var(--dark-blue);
    font-weight: 600; font-size: 0.83rem;
    transition: all 0.2s;
    text-align: center;
}
.wf-action-tile:hover {
    border-color: var(--primary);
    background: var(--light-blue-pale);
    color: var(--primary);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 83, 122, 0.08);
}
.wf-action-tile-icon {
    width: 46px; height: 46px; border-radius: 12px;
    background: var(--light-blue-soft); color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; transition: all 0.25s;
}
.wf-action-tile:hover .wf-action-tile-icon {
    background: var(--yellow); color: var(--dark-blue-deep);
    transform: scale(1.06);
}

/* TOAST */
.wf-toast-wrap {
    position: fixed;
    top: 1.5rem; right: 1.5rem;
    z-index: 9999;
    display: flex; flex-direction: column; gap: 0.65rem;
    pointer-events: none;
}
.wf-toast {
    background: white;
    padding: 0.9rem 1.15rem;
    border-radius: 12px;
    border-left: 4px solid var(--primary);
    box-shadow: 0 10px 30px rgba(6, 52, 82, 0.15);
    display: flex; align-items: flex-start; gap: 0.75rem;
    min-width: 280px; max-width: 380px;
    animation: toastIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: auto;
}
.wf-toast.hide { animation: toastOut 0.3s forwards; }
.wf-toast i { font-size: 1rem; margin-top: 2px; flex-shrink: 0; }
.wf-toast.booking { border-left-color: var(--primary); }
.wf-toast.booking i { color: var(--primary); }
.wf-toast.status { border-left-color: var(--green); }
.wf-toast.status i { color: var(--green); }
.wf-toast.payment { border-left-color: var(--gold); }
.wf-toast.payment i { color: var(--gold); }
.wf-toast.promo { border-left-color: var(--red); }
.wf-toast.promo i { color: var(--red); }
.wf-toast.system { border-left-color: var(--text-secondary); }
.wf-toast.system i { color: var(--text-secondary); }

.wf-toast-title {
    font-size: 0.85rem; font-weight: 700;
    color: var(--dark-blue); margin: 0 0 0.15rem;
}
.wf-toast-msg {
    font-size: 0.8rem; color: var(--text-secondary);
    margin: 0; line-height: 1.4;
}

@keyframes toastIn {
    from { transform: translateX(120%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes toastOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(120%); opacity: 0; }
}

/* MOBILE */
.wf-sidebar-toggle {
    display: none;
    position: fixed;
    top: 1rem; left: 1rem;
    z-index: 1001;
    width: 44px; height: 44px;
    background: var(--dark-blue-deep);
    color: white; border: none;
    border-radius: 10px;
    align-items: center; justify-content: center;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(4, 38, 64, 0.3);
    cursor: pointer;
}
.wf-sidebar-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}
.wf-sidebar-overlay.open { display: block; }

@media (max-width: 1200px) {
    .wf-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .wf-actions-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 900px) {
    .wf-layout { grid-template-columns: 1fr; }
    .wf-sidebar {
        position: fixed;
        top: 0; left: -300px;
        width: 280px;
        z-index: 1000;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 20px 0 60px rgba(0, 0, 0, 0.3);
    }
    .wf-sidebar.open { left: 0; }
    .wf-sidebar-toggle { display: flex; }
    .wf-main { padding: 1.25rem; padding-top: 4rem; }
    .wf-topbar-greeting h1 { font-size: 1.3rem; }
    .wf-notif-panel { width: 340px; right: -60px; }
}
@media (max-width: 600px) {
    .wf-stats-grid { grid-template-columns: 1fr; }
    .wf-actions-grid { grid-template-columns: 1fr 1fr; }
    .wf-notif-panel { width: calc(100vw - 2rem); right: -80px; }
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
                <span class="wf-sidebar-brand-tag">Customer</span>
            </div>
        </div>

        <nav class="wf-nav">
            <div class="wf-nav-section-label">Overview</div>
            <a href="userdashboard.php" class="wf-nav-item active">
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
            <a href="my_complaints.php" class="wf-nav-item">
                <i class="fas fa-headset"></i><span>Complaints</span>
            </a>

            <div class="wf-nav-section-label">Account</div>
            <a href="profile.php" class="wf-nav-item">
                <i class="fas fa-user"></i><span>Profile</span>
            </a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e($customer_initial) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($fullName ?: 'Customer') ?></div>
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
                <h1><?= e($greeting) ?>, <span class="accent"><?= e($firstName ?: 'there') ?></span>!</h1>
                <p>Here's your laundry overview for today</p>
            </div>
            <div class="wf-topbar-actions">

                <div class="wf-notif-wrap">
                    <button class="wf-topbar-icon" id="wfNotifBtn" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="wf-notif-badge" id="wfNotifBadge" style="<?= $unread_count > 0 ? '' : 'display:none;' ?>">
                            <?= $unread_count > 99 ? '99+' : (int)$unread_count ?>
                        </span>
                    </button>

                    <div class="wf-notif-panel" id="wfNotifPanel">
                        <div class="wf-notif-head">
                            <h6><i class="fas fa-bell"></i> Notifications</h6>
                            <a href="#" id="wfMarkAllRead">Mark all read</a>
                        </div>
                        <div class="wf-notif-body" id="wfNotifBody">
                            <?php if (empty($recent_notifs)): ?>
                                <div class="wf-notif-empty">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_notifs as $n):
                                    $color_class = CustomerNotify::colorClass($n['type']);
                                    $time_ago = CustomerNotify::timeAgo($n['created_at']);
                                    $is_unread = !(int)$n['is_read'];
                                ?>
                                <a class="wf-notif-item <?= $is_unread ? 'unread' : '' ?>"
                                   href="<?= e($n['link'] ?: 'notifications.php') ?>"
                                   data-id="<?= (int)$n['notif_id'] ?>">
                                    <div class="wf-notif-icon <?= e($color_class) ?>">
                                        <i class="<?= e($n['icon'] ?: 'fas fa-bell') ?>"></i>
                                    </div>
                                    <div class="wf-notif-content">
                                        <h6><?= e($n['title'] ?: 'Notification') ?></h6>
                                        <p><?= e($n['message']) ?></p>
                                        <div class="wf-notif-time">
                                            <i class="fas fa-clock"></i> <?= e($time_ago) ?>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="wf-notif-foot">
                            <a href="notifications.php">View all notifications <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <a href="book_service.php" class="wf-btn-primary">
                    <i class="fas fa-plus"></i> New Booking
                </a>
            </div>
        </div>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-clock"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['active_laundry']) ?></div>
                    <div class="wf-stat-label">Active Laundry</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['completed_laundry']) ?></div>
                    <div class="wf-stat-label">Completed Laundry</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-gold"><i class="fas fa-peso-sign"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($stats['total_spent'], 2) ?></div>
                    <div class="wf-stat-label">Total Spent</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-purple"><i class="fas fa-clipboard-list"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total_bookings']) ?></div>
                    <div class="wf-stat-label">Total Bookings</div>
                </div>
            </div>
        </div>

        <!-- RECENT BOOKINGS -->
        <div class="wf-card">
            <div class="wf-card-header">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div>
                        <h3>Recent Bookings</h3>
                        <p>Your latest laundry activity</p>
                    </div>
                </div>
                <a href="my_bookings.php" class="wf-card-link">
                    View all <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="wf-card-body">
                <?php if (empty($recent_bookings)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-inbox"></i>
                        <p>You haven't made any bookings yet</p>
                        <a href="book_service.php">Create your first booking <i class="fas fa-arrow-right"></i></a>
                    </div>
                <?php else: ?>
                    <div class="wf-table-wrap">
                        <table class="wf-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Service</th>
                                    <th>Drop-off</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $b):
                                    $badge = dash_status_badge($b['status']);
                                    $pay_badge = dash_payment_badge($b);  // ← whole row, hindi $b['payment_status']
                                ?>
                                <tr>
                                    <td><span class="wf-booking-id">#<?= e($b['id']) ?></span></td>
                                    <td><span class="wf-service-text"><?= e($b['service'] ?: '—') ?></span></td>
                                    <td><span class="wf-date"><?= e($b['dropoff_date'] ?: '—') ?> <?= $b['dropoff_time'] ? '• ' . e($b['dropoff_time']) : '' ?></span></td>
                                    <td><span class="wf-amount">₱<?= number_format((float)$b['total_amount'], 2) ?></span></td>
                                    <td>
                                        <span class="wf-badge" style="color: <?= $badge['color'] ?>; background: <?= $badge['bg'] ?>;">
                                            <span class="wf-badge-dot"></span> <?= e($badge['label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="wf-badge" style="color: <?= $pay_badge['color'] ?>; background: <?= $pay_badge['bg'] ?>;">
                                            <span class="wf-badge-dot"></span> <?= e($pay_badge['label']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="booking_details.php?id=<?= (int)$b['id'] ?>" class="wf-icon-btn" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="wf-card">
            <div class="wf-card-header">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <h3>Quick Actions</h3>
                        <p>What would you like to do today?</p>
                    </div>
                </div>
            </div>
            <div class="wf-actions-grid">
                <a href="book_service.php" class="wf-action-tile">
                    <div class="wf-action-tile-icon"><i class="fas fa-plus-circle"></i></div>
                    <span>New Booking</span>
                </a>
                <a href="my_bookings.php" class="wf-action-tile">
                    <div class="wf-action-tile-icon"><i class="fas fa-clipboard-list"></i></div>
                    <span>My Bookings</span>
                </a>
                <a href="payments.php" class="wf-action-tile">
                    <div class="wf-action-tile-icon"><i class="fas fa-credit-card"></i></div>
                    <span>Payments</span>
                </a>
                <a href="my_feedback.php" class="wf-action-tile">
                    <div class="wf-action-tile-icon"><i class="fas fa-star"></i></div>
                    <span>Give Feedback</span>
                </a>
            </div>
        </div>

    </main>
</div>

<div class="wf-toast-wrap" id="wfToastWrap"></div>

<script>
/* ══════════════════════════════════════════
   SIDEBAR TOGGLE (mobile)
   ══════════════════════════════════════════ */
(function () {
    const toggle = document.getElementById('wfSidebarToggle');
    const sidebar = document.getElementById('wfSidebar');
    const overlay = document.getElementById('wfSidebarOverlay');
    if (!toggle) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('open');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    });
})();

/* ══════════════════════════════════════════
   NOTIFICATION PANEL
   ══════════════════════════════════════════ */
(function () {
    const btn = document.getElementById('wfNotifBtn');
    const panel = document.getElementById('wfNotifPanel');
    const markAllBtn = document.getElementById('wfMarkAllRead');

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
        if (!panel.contains(e.target) && e.target !== btn) {
            panel.classList.remove('open');
        }
    });

    document.querySelectorAll('.wf-notif-item.unread').forEach(el => {
        el.addEventListener('click', function () {
            const id = this.dataset.id;
            if (!id) return;
            fetch('notifications_api.php?action=mark_read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'notification_id=' + encodeURIComponent(id) +
                      '&csrf_token=' + encodeURIComponent('<?= csrf_token() ?>')
            }).catch(() => {});
        });
    });

    markAllBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        fetch('notifications_api.php?action=mark_all_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent('<?= csrf_token() ?>')
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.wf-notif-item.unread').forEach(el => el.classList.remove('unread'));
                updateBadge(0);
            }
        })
        .catch(() => {});
    });
})();

/* ══════════════════════════════════════════
   NOTIFICATION BADGE
   ══════════════════════════════════════════ */
function updateBadge(count) {
    const badge = document.getElementById('wfNotifBadge');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = '';
    } else {
        badge.style.display = 'none';
    }
}

function showToast(type, title, message) {
    const wrap = document.getElementById('wfToastWrap');
    if (!wrap) return;

    const icons = {
        booking: 'fas fa-calendar-check',
        status:  'fas fa-check-circle',
        payment: 'fas fa-credit-card',
        promo:   'fas fa-gift',
        system:  'fas fa-info-circle'
    };

    const el = document.createElement('div');
    el.className = 'wf-toast ' + type;
    el.innerHTML = `
        <i class="${icons[type] || icons.system}"></i>
        <div>
            <p class="wf-toast-title">${escapeHtml(title)}</p>
            <p class="wf-toast-msg">${escapeHtml(message)}</p>
        </div>
    `;
    wrap.appendChild(el);

    setTimeout(() => {
        el.classList.add('hide');
        setTimeout(() => el.remove(), 300);
    }, 5500);
}

function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ══════════════════════════════════════════
   REAL-TIME NOTIFICATIONS (SSE)
   ══════════════════════════════════════════ */
(function () {
    let lastId = 0;
    let sse = null;
    let fallbackInterval = null;

    fetch('notifications_api.php?action=latest_id')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                lastId = data.last_id || 0;
                initSSE();
            }
        })
        .catch(() => initSSE());

    function initSSE() {
        if (!window.EventSource) {
            startFallbackPolling();
            return;
        }

        try {
            sse = new EventSource('notifications_sse.php?last_id=' + lastId);

            sse.addEventListener('notification', (event) => {
                try {
                    const data = JSON.parse(event.data);
                    if (data.items && data.items.length) {
                        data.items.forEach(item => {
                            if (item.notif_id > lastId) lastId = item.notif_id;
                            showToast(item.type || 'system', item.title || 'Notification', item.message);
                        });
                        updateBadge(data.unread || 0);

                        setTimeout(() => { window.location.reload(); }, 1200);
                    }
                } catch (e) { /* ignore */ }
            });

            sse.addEventListener('error', () => {
                if (sse) sse.close();
                startFallbackPolling();
            });
        } catch (e) {
            startFallbackPolling();
        }
    }

    function startFallbackPolling() {
        if (fallbackInterval) return;
        fallbackInterval = setInterval(() => {
            fetch('notifications_api.php?action=unread_count')
                .then(r => r.json())
                .then(data => {
                    if (data.success) updateBadge(data.unread || 0);
                })
                .catch(() => {});
        }, 15000);
    }

    setInterval(() => {
        fetch('notifications_api.php?action=unread_count')
            .then(r => r.json())
            .then(data => { if (data.success) updateBadge(data.unread || 0); })
            .catch(() => {});
    }, 60000);
})();
</script>

</body>
</html>