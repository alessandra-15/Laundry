<?php
/**
 * notifications.php
 * WashFlow — Customer Notifications (Full Page)
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';
require_once 'csrf_helper.php';
require_once 'notifications_helper.php';

require_customer_login();

$customer_id = (int)$_SESSION['customer_id'];
$firstName   = $_SESSION['first_name'] ?? '';
$lastName    = $_SESSION['last_name'] ?? '';
$fullName    = trim($firstName . ' ' . $lastName);
$customer_initial = strtoupper(substr($firstName ?: 'U', 0, 1));

$filter = $_GET['type'] ?? 'all';
$allowed_types = ['all', 'booking', 'status', 'payment', 'promo', 'system'];
if (!in_array($filter, $allowed_types, true)) $filter = 'all';

/* Mark single as read (GET link from notification) */
if (!empty($_GET['read']) && ctype_digit($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE notifications_user SET is_read=1, read_at=NOW(), status='read' WHERE notif_id=? AND user_id=?");
    $stmt->bind_param('ii', $nid, $customer_id);
    $stmt->execute();
    $stmt->close();
    header('Location: notifications.php'); exit();
}

/* Mark all as read */
if (isset($_POST['mark_all_read']) && validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $stmt = $conn->prepare("UPDATE notifications_user SET is_read=1, read_at=NOW(), status='read' WHERE user_id=? AND is_read=0");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $stmt->close();
    header('Location: notifications.php'); exit();
}

/* Fetch list */
$per_page = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = "user_id = ?";
$params = [$customer_id];
$types = 'i';
if ($filter !== 'all') {
    $where .= " AND type = ?";
    $params[] = $filter;
    $types .= 's';
}

$total = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications_user WHERE $where");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$total_pages = max(1, (int)ceil($total / $per_page));

$stmt = $conn->prepare("
    SELECT notif_id, title, message, type, icon, link, booking_id, is_read, created_at
    FROM notifications_user
    WHERE $where
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$bind_types = $types . 'ii';
$bind_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_count = CustomerNotify::unreadCount($conn, $customer_id);

function notif_tab_url($type, $page = 1) {
    $qs = http_build_query(['type' => $type, 'page' => $page]);
    return 'notifications.php?' . $qs;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — WashFlow</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --dark-blue: #063452;
    --dark-blue-deep: #042640;
    --primary: #005A85;
    --primary-mid: #0076A8;
    --light-blue: #A8E8F9;
    --light-blue-soft: #E8F6FC;
    --light-blue-pale: #F2FAFD;
    --yellow: #FFD93D;
    --yellow-soft: #FFF9DB;
    --yellow-dark: #B88A00;
    --bg: #F5F9FC;
    --text-primary: #0A2540;
    --text-secondary: #5A7184;
    --text-muted: #94A9B8;
    --border: #E1EEF5;
    --border-soft: #F0F6FA;
    --green: #27AE60;
    --red: #E74C3C;
    --gold: #F0B400;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; }
body {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--text-primary); background: var(--bg);
    font-size: 14px; line-height: 1.6;
    -webkit-font-smoothing: antialiased; overflow: hidden;
}
a { text-decoration: none; }

/* Layout + sidebar (same as dashboard) */
.wf-layout { display: grid; grid-template-columns: 280px 1fr; height: 100vh; overflow: hidden; }
.wf-sidebar {
    background: var(--dark-blue-deep); color: white; height: 100vh;
    overflow-y: auto; display: flex; flex-direction: column;
    scrollbar-width: none;
}
.wf-sidebar::-webkit-scrollbar { display: none; }
.wf-sidebar-brand {
    display: flex; align-items: center; gap: 14px;
    padding: 1.75rem 1.5rem;
    border-bottom: 1px solid rgba(168, 232, 249, 0.08);
}
.wf-sidebar-brand-logo { width: 44px; height: 44px; flex-shrink: 0; }
.wf-sidebar-brand-logo svg { width: 100%; height: 100%; }
.wf-sidebar-brand-text { display: flex; flex-direction: column; line-height: 1; }
.wf-sidebar-brand-name { font-size: 1.35rem; font-weight: 800; color: white; letter-spacing: -0.04em; }
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
    padding: 0.8rem 0.95rem; border-radius: 10px;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.9rem; font-weight: 500;
    transition: all 0.2s; margin-bottom: 0.2rem;
    position: relative;
}
.wf-nav-item i { width: 22px; text-align: center; font-size: 1rem; flex-shrink: 0; }
.wf-nav-item span { flex: 1; }
.wf-nav-item:hover { background: rgba(168, 232, 249, 0.06); color: rgba(255, 255, 255, 0.95); }
.wf-nav-item.active { background: rgba(255, 217, 61, 0.1); color: white; font-weight: 600; }
.wf-nav-item.active::before {
    content: ''; position: absolute; left: 0; top: 50%;
    transform: translateY(-50%); width: 3px; height: 22px;
    background: var(--yellow); border-radius: 0 3px 3px 0;
}
.wf-nav-item.active i { color: var(--yellow); }
.wf-nav-badge {
    margin-left: auto; min-width: 22px; height: 20px;
    padding: 0 6px; border-radius: 6px;
    background: rgba(231, 76, 60, 0.15); color: #FF8B7E;
    font-size: 0.68rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid rgba(231, 76, 60, 0.25);
}
.wf-nav-item.active .wf-nav-badge { background: var(--yellow); color: var(--dark-blue-deep); border-color: var(--yellow); }
.wf-sidebar-footer { padding: 1rem 0.875rem; border-top: 1px solid rgba(168, 232, 249, 0.08); }
.wf-user-card {
    display: flex; align-items: center; gap: 0.875rem;
    padding: 0.8rem; border-radius: 11px;
    background: rgba(168, 232, 249, 0.05); margin-bottom: 0.6rem;
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
    padding: 0.7rem 0.85rem; border-radius: 11px;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.9rem; font-weight: 500; transition: all 0.2s;
}
.wf-btn-logout:hover { background: rgba(231, 76, 60, 0.12); color: #FF8B7E; }
.wf-btn-logout i { width: 22px; text-align: center; font-size: 1rem; }

/* MAIN */
.wf-main { padding: 1.75rem 2.25rem 3rem; overflow-y: auto; height: 100vh; scrollbar-width: none; }
.wf-main::-webkit-scrollbar { display: none; }

.wf-page-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;
}
.wf-page-head h1 {
    font-size: 1.5rem; font-weight: 800;
    color: var(--dark-blue); margin: 0; letter-spacing: -0.03em;
}
.wf-page-head p {
    margin: 0.2rem 0 0; font-size: 0.875rem;
    color: var(--text-secondary); font-weight: 500;
}

/* TABS */
.wf-tabs {
    display: flex; gap: 0.4rem;
    background: white;
    padding: 0.35rem;
    border-radius: 12px;
    border: 1px solid var(--border);
    margin-bottom: 1.25rem;
    width: fit-content;
    flex-wrap: wrap;
}
.wf-tab {
    padding: 0.6rem 1rem;
    border-radius: 9px;
    font-size: 0.82rem; font-weight: 600;
    color: var(--text-secondary);
    transition: all 0.2s;
    display: inline-flex; align-items: center; gap: 0.4rem;
}
.wf-tab:hover { background: var(--light-blue-pale); color: var(--primary); }
.wf-tab.active {
    background: var(--dark-blue); color: white;
    box-shadow: 0 4px 12px rgba(6, 52, 82, 0.2);
}

/* CARD */
.wf-card {
    background: white; border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
}
.wf-card-head {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
}
.wf-card-head h3 {
    font-size: 1rem; font-weight: 700; color: var(--dark-blue); margin: 0;
    display: flex; align-items: center; gap: 0.5rem;
}
.wf-card-head h3 i { color: var(--primary); }

.wf-btn-mark-all {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.5rem 0.9rem;
    border-radius: 9px;
    background: var(--light-blue-pale);
    color: var(--primary);
    font-size: 0.78rem; font-weight: 600;
    border: 1px solid var(--border);
    cursor: pointer; transition: all 0.2s;
}
.wf-btn-mark-all:hover { background: var(--light-blue-soft); border-color: var(--light-blue); }

/* NOTIFICATION LIST */
.wf-notif-list { padding: 0; }

.wf-notif-row {
    display: flex; gap: 1rem;
    padding: 1.15rem 1.5rem;
    border-bottom: 1px solid var(--border-soft);
    transition: background 0.15s;
    text-decoration: none; color: inherit;
    position: relative;
}
.wf-notif-row:last-child { border-bottom: none; }
.wf-notif-row:hover { background: var(--light-blue-pale); color: inherit; }
.wf-notif-row.unread { background: linear-gradient(90deg, rgba(255,217,61,0.08) 0%, transparent 60%); }
.wf-notif-row.unread::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: var(--yellow);
}

.wf-nr-icon {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.notif-blue   { background: var(--light-blue-soft); color: var(--primary); }
.notif-green  { background: #EAF7F0; color: #1E7E45; }
.notif-yellow { background: var(--yellow-soft); color: var(--yellow-dark); }
.notif-red    { background: #FDEDEC; color: #A8322D; }
.notif-dark   { background: #E6EDF3; color: var(--dark-blue); }

.wf-nr-body { flex: 1; min-width: 0; }
.wf-nr-body h4 {
    font-size: 0.92rem; font-weight: 700;
    color: var(--dark-blue); margin: 0 0 0.25rem;
    display: flex; align-items: center; gap: 0.5rem;
}
.wf-nr-body h4 .tag {
    font-size: 0.62rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em;
    padding: 0.15rem 0.45rem;
    border-radius: 5px;
    background: var(--light-blue-soft); color: var(--primary);
}
.wf-nr-body p {
    font-size: 0.83rem; color: var(--text-secondary);
    margin: 0 0 0.4rem; line-height: 1.55;
}
.wf-nr-time {
    font-size: 0.72rem; color: var(--text-muted);
    font-weight: 500; display: flex; align-items: center; gap: 0.35rem;
}
.wf-nr-time i { font-size: 0.65rem; }

.wf-nr-actions { display: flex; align-items: center; flex-shrink: 0; }
.wf-nr-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--yellow);
    box-shadow: 0 0 0 4px rgba(255, 217, 61, 0.2);
}

/* EMPTY */
.wf-empty {
    text-align: center;
    padding: 4rem 1rem;
    color: var(--text-muted);
}
.wf-empty i { font-size: 3rem; color: var(--light-blue); margin-bottom: 1rem; display: block; }
.wf-empty p { margin: 0 0 0.5rem; font-size: 0.95rem; font-weight: 600; color: var(--text-secondary); }
.wf-empty span { font-size: 0.83rem; }

/* PAGINATION */
.wf-pagination {
    display: flex; justify-content: center; align-items: center;
    gap: 0.35rem; padding: 1.25rem;
    border-top: 1px solid var(--border-soft);
}
.wf-pg-btn {
    min-width: 36px; height: 36px; padding: 0 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white; color: var(--text-secondary);
    font-size: 0.82rem; font-weight: 600;
    display: inline-flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.wf-pg-btn:hover:not(.disabled):not(.active) {
    border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale);
}
.wf-pg-btn.active { background: var(--dark-blue); color: white; border-color: var(--dark-blue); }
.wf-pg-btn.disabled { opacity: 0.4; pointer-events: none; }

/* MOBILE */
.wf-sidebar-toggle {
    display: none; position: fixed;
    top: 1rem; left: 1rem; z-index: 1001;
    width: 44px; height: 44px;
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

@media (max-width: 900px) {
    .wf-layout { grid-template-columns: 1fr; }
    .wf-sidebar {
        position: fixed; top: 0; left: -300px;
        width: 280px; z-index: 1000;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 20px 0 60px rgba(0, 0, 0, 0.3);
    }
    .wf-sidebar.open { left: 0; }
    .wf-sidebar-toggle { display: flex; }
    .wf-main { padding: 1.25rem; padding-top: 4rem; }
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
            <a href="notifications.php" class="wf-nav-item active">
                <i class="fas fa-bell"></i><span>Notifications</span>
                <?php if ($unread_count > 0): ?>
                    <span class="wf-nav-badge"><?= $unread_count > 99 ? '99+' : (int)$unread_count ?></span>
                <?php endif; ?>
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

    <main class="wf-main">

        <div class="wf-page-head">
            <div>
                <h1>Notifications</h1>
                <p>Stay updated on your bookings, payments, and updates</p>
            </div>
            <?php if ($unread_count > 0): ?>
            <form method="POST" action="notifications.php" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" name="mark_all_read" value="1" class="wf-btn-mark-all">
                    <i class="fas fa-check-double"></i> Mark all as read
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- TABS -->
        <div class="wf-tabs">
            <a class="wf-tab <?= $filter === 'all' ? 'active' : '' ?>" href="<?= e(notif_tab_url('all')) ?>">
                <i class="fas fa-list"></i> All
            </a>
            <a class="wf-tab <?= $filter === 'booking' ? 'active' : '' ?>" href="<?= e(notif_tab_url('booking')) ?>">
                <i class="fas fa-calendar-check"></i> Bookings
            </a>
            <a class="wf-tab <?= $filter === 'status' ? 'active' : '' ?>" href="<?= e(notif_tab_url('status')) ?>">
                <i class="fas fa-spinner"></i> Status
            </a>
            <a class="wf-tab <?= $filter === 'payment' ? 'active' : '' ?>" href="<?= e(notif_tab_url('payment')) ?>">
                <i class="fas fa-credit-card"></i> Payments
            </a>
            <a class="wf-tab <?= $filter === 'promo' ? 'active' : '' ?>" href="<?= e(notif_tab_url('promo')) ?>">
                <i class="fas fa-gift"></i> Promos
            </a>
            <a class="wf-tab <?= $filter === 'system' ? 'active' : '' ?>" href="<?= e(notif_tab_url('system')) ?>">
                <i class="fas fa-info-circle"></i> System
            </a>
        </div>

        <!-- LIST -->
        <div class="wf-card">
            <div class="wf-card-head">
                <h3><i class="fas fa-bell"></i> <?= $filter === 'all' ? 'All Notifications' : ucfirst($filter) . ' Notifications' ?></h3>
                <span style="font-size:0.8rem;color:var(--text-muted);font-weight:600;">
                    <?= number_format($total) ?> total
                </span>
            </div>

            <?php if (empty($items)): ?>
                <div class="wf-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications here</p>
                    <span>You're all caught up! 🎉</span>
                </div>
            <?php else: ?>
                <div class="wf-notif-list">
                    <?php foreach ($items as $n):
                        $color_class = CustomerNotify::colorClass($n['type']);
                        $time_ago = CustomerNotify::timeAgo($n['created_at']);
                        $is_unread = !(int)$n['is_read'];
                        $link = $n['link'] ?: '#';
                        if ($link === '#' && !empty($n['booking_id'])) {
                            $link = 'booking_details.php?id=' . (int)$n['booking_id'];
                        }
                    ?>
                    <a class="wf-notif-row <?= $is_unread ? 'unread' : '' ?>"
                       href="<?= e($link) ?>"
                       data-id="<?= (int)$n['notif_id'] ?>">
                        <div class="wf-nr-icon <?= e($color_class) ?>">
                            <i class="<?= e($n['icon'] ?: 'fas fa-bell') ?>"></i>
                        </div>
                        <div class="wf-nr-body">
                            <h4>
                                <?= e($n['title'] ?: 'Notification') ?>
                                <span class="tag"><?= e(ucfirst($n['type'] ?: 'system')) ?></span>
                            </h4>
                            <p><?= e($n['message']) ?></p>
                            <div class="wf-nr-time">
                                <i class="fas fa-clock"></i> <?= e($time_ago) ?>
                                <?php if (!empty($n['booking_id'])): ?>
                                    &nbsp;•&nbsp;<i class="fas fa-receipt"></i> Booking #<?= (int)$n['booking_id'] ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="wf-nr-actions">
                            <?php if ($is_unread): ?>
                                <span class="wf-nr-dot" title="Unread"></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="wf-pagination">
                    <a class="wf-pg-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(notif_tab_url($filter, max(1, $page - 1))) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    if ($start > 1) {
                        echo '<a class="wf-pg-btn" href="' . e(notif_tab_url($filter, 1)) . '">1</a>';
                        if ($start > 2) echo '<span class="wf-pg-btn disabled" style="border:none;background:transparent;">…</span>';
                    }
                    for ($p = $start; $p <= $end; $p++) {
                        echo '<a class="wf-pg-btn ' . ($p === $page ? 'active' : '') . '" href="' . e(notif_tab_url($filter, $p)) . '">' . $p . '</a>';
                    }
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) echo '<span class="wf-pg-btn disabled" style="border:none;background:transparent;">…</span>';
                        echo '<a class="wf-pg-btn" href="' . e(notif_tab_url($filter, $total_pages)) . '">' . $total_pages . '</a>';
                    }
                    ?>
                    <a class="wf-pg-btn <?= $page >= $total_pages ? 'disabled' : '' ?>" href="<?= e(notif_tab_url($filter, min($total_pages, $page + 1))) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
(function () {
    const toggle = document.getElementById('wfSidebarToggle');
    const sidebar = document.getElementById('wfSidebar');
    const overlay = document.getElementById('wfSidebarOverlay');
    if (!toggle) return;
    toggle.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('open'); });
    overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
})();

/* Auto-mark notification as read when clicked */
document.querySelectorAll('.wf-notif-row.unread').forEach(el => {
    el.addEventListener('click', function () {
        const id = this.dataset.id;
        if (!id) return;
        const fd = new FormData();
        fd.append('notification_id', id);
        fd.append('csrf_token', '<?= csrf_token() ?>');
        // Fire-and-forget
        navigator.sendBeacon
            ? navigator.sendBeacon('notifications_api.php?action=mark_read', fd)
            : fetch('notifications_api.php?action=mark_read', { method: 'POST', body: fd }).catch(()=>{});
    });
});
</script>

</body>
</html>