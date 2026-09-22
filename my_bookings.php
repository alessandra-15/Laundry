<?php
/**
 * my_bookings.php
 * WashFlow — Customer Bookings List
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';
require_once 'notifications_helper.php';

require_customer_login();

$customer_id = (int)$_SESSION['customer_id'];
$firstName = $_SESSION['first_name'] ?? '';
$lastName  = $_SESSION['last_name']  ?? '';
$fullName  = trim($firstName . ' ' . $lastName);
$customer_initial = strtoupper(substr($firstName ?: 'U', 0, 1));

/* Filters */
$status_f   = trim($_GET['status'] ?? '');
$search     = trim($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

$allowed_status = ['', 'Pending', 'Processing', 'Ready', 'Completed', 'Cancelled'];
if (!in_array($status_f, $allowed_status, true)) $status_f = '';

$where = ["b.customer_id = ?"];
$params = [$customer_id];
$types = 'i';

if ($status_f !== '') {
    $where[] = "b.status = ?";
    $params[] = $status_f;
    $types .= 's';
}
if ($search !== '') {
    $where[] = "(b.id LIKE ? OR b.service LIKE ? OR b.addons LIKE ?)";
    $like = '%' . $search . '%';
    for ($i = 0; $i < 3; $i++) { $params[] = $like; $types .= 's'; }
}

$where_sql = implode(' AND ', $where);

/* Count */
$total = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM booking_online b WHERE $where_sql");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();
$total_pages = max(1, (int)ceil($total / $per_page));

/* Fetch */
$stmt = $conn->prepare("
    SELECT b.id, b.service, b.addons, b.delivery_option, b.dropoff_date, b.dropoff_time,
           b.status, b.payment_status, b.total_amount, b.timestamp
    FROM booking_online b
    WHERE $where_sql
    ORDER BY b.timestamp DESC
    LIMIT ? OFFSET ?
");
$bind_types = $types . 'ii';
$bind_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_count = CustomerNotify::unreadCount($conn, $customer_id);

function status_pill($s) {
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
function pay_pill($s) {
    $s = strtolower(trim($s));
    return match ($s) {
        'paid'    => ['label' => 'Paid',    'color' => '#1E7E45', 'bg' => '#EAF7F0'],
        'unpaid'  => ['label' => 'Unpaid',  'color' => '#A8322D', 'bg' => '#FDEDEC'],
        'pending' => ['label' => 'Pending', 'color' => '#946200', 'bg' => '#FEF9E7'],
        default   => ['label' => ucfirst($s ?: 'Unpaid'), 'color' => '#5A7184', 'bg' => '#F4F7F9'],
    };
}
function bookings_url($overrides = []) {
    $base = [
        'status' => $_GET['status'] ?? '',
        'q'      => $_GET['q'] ?? '',
        'page'   => $_GET['page'] ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'my_bookings.php?' . http_build_query(array_filter($merged, fn($v) => $v !== '' && $v !== null));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings — WashFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --dark-blue: #063452; --dark-blue-deep: #042640;
    --primary: #005A85; --primary-mid: #0076A8;
    --light-blue: #A8E8F9; --light-blue-soft: #E8F6FC; --light-blue-pale: #F2FAFD;
    --yellow: #FFD93D; --yellow-soft: #FFF9DB; --yellow-dark: #B88A00;
    --bg: #F5F9FC; --text-primary: #0A2540;
    --text-secondary: #5A7184; --text-muted: #94A9B8;
    --border: #E1EEF5; --border-soft: #F0F6FA;
    --green: #27AE60; --red: #E74C3C; --gold: #F0B400;
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

/* Sidebar + layout (same as dashboard) */
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
    transition: all 0.2s; margin-bottom: 0.2rem; position: relative;
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

.wf-main { padding: 1.75rem 2.25rem 3rem; overflow-y: auto; height: 100vh; scrollbar-width: none; }
.wf-main::-webkit-scrollbar { display: none; }

.wf-page-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;
}
.wf-page-head h1 {
    font-size: 1.5rem; font-weight: 800;
    color: var(--dark-blue); margin: 0; letter-spacing: -0.03em;
    display: flex; align-items: center; gap: 0.6rem;
}
.wf-page-head p { margin: 0.3rem 0 0; font-size: 0.875rem; color: var(--text-secondary); font-weight: 500; }

.wf-btn-primary {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.65rem 1.25rem; border-radius: 10px;
    background: var(--primary); color: white;
    font-size: 0.875rem; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.2s;
}
.wf-btn-primary:hover { background: var(--primary-mid); color: white; transform: translateY(-1px); }

/* FILTER BAR */
.wf-filter-bar {
    background: white; border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 0.75rem;
    flex-wrap: wrap;
}
.wf-search-wrap {
    position: relative;
    flex: 1 1 240px;
    min-width: 200px;
}
.wf-search-wrap i {
    position: absolute; left: 0.9rem; top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted); font-size: 0.85rem;
    pointer-events: none;
}
.wf-search {
    width: 100%;
    padding: 0.65rem 0.95rem 0.65rem 2.5rem;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: 0.85rem; font-family: inherit;
    outline: none;
    transition: all 0.2s;
}
.wf-search:focus { border-color: var(--primary-mid); box-shadow: 0 0 0 4px rgba(0,118,168,0.1); }

.wf-select {
    padding: 0.65rem 2.4rem 0.65rem 0.95rem;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: 0.85rem; font-family: inherit;
    background: white;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235A7184' d='M6 8L2 4h8z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.85rem center;
    appearance: none;
    outline: none;
    min-width: 150px;
    transition: all 0.2s;
}
.wf-select:focus { border-color: var(--primary-mid); box-shadow: 0 0 0 4px rgba(0,118,168,0.1); }

.wf-btn-filter {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.65rem 1.15rem; border-radius: 10px;
    background: var(--dark-blue); color: white;
    font-size: 0.85rem; font-weight: 600;
    border: none; cursor: pointer;
    transition: all 0.2s;
}
.wf-btn-filter:hover { background: var(--primary); color: white; }

.wf-btn-reset {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.65rem 1rem; border-radius: 10px;
    background: white; color: var(--text-secondary);
    border: 1.5px solid var(--border);
    font-size: 0.82rem; font-weight: 600;
    transition: all 0.2s;
}
.wf-btn-reset:hover { border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale); }

/* CARD + TABLE */
.wf-card {
    background: white;
    border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
}
.wf-table-wrap { overflow-x: auto; }
.wf-table {
    width: 100%; border-collapse: collapse;
    font-size: 0.85rem;
}
.wf-table thead th {
    text-align: left;
    padding: 0.9rem 1.15rem;
    font-size: 0.68rem; font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.08em;
    background: #FAFCFE;
    border-bottom: 1px solid var(--border-soft);
    white-space: nowrap;
}
.wf-table tbody td {
    padding: 0.95rem 1.15rem;
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
.wf-addon-text { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem; }
.wf-amount { font-weight: 700; color: var(--dark-blue); }
.wf-date { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }

.wf-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.32rem 0.65rem; border-radius: 7px;
    font-size: 0.72rem; font-weight: 600;
    white-space: nowrap;
}
.wf-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.wf-icon-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white; color: var(--text-secondary);
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.15s;
    font-size: 0.8rem; text-decoration: none;
}
.wf-icon-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale); }

/* EMPTY */
.wf-empty {
    text-align: center; padding: 4rem 1rem;
    color: var(--text-muted);
}
.wf-empty i { font-size: 3rem; color: var(--light-blue); margin-bottom: 1rem; display: block; }
.wf-empty p { margin: 0 0 0.5rem; font-size: 0.95rem; font-weight: 600; color: var(--text-secondary); }
.wf-empty span { font-size: 0.83rem; display: block; margin-bottom: 1rem; }
.wf-empty a { color: var(--primary); font-weight: 700; font-size: 0.85rem; }

/* PAGINATION */
.wf-pagination {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-soft);
    gap: 1rem; flex-wrap: wrap;
}
.wf-pg-info { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; }
.wf-pg-list { display: flex; gap: 0.3rem; align-items: center; }
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
    display: none; position: fixed; top: 1rem; left: 1rem;
    z-index: 1001; width: 44px; height: 44px;
    background: var(--dark-blue-deep); color: white;
    border: none; border-radius: 10px;
    align-items: center; justify-content: center;
    font-size: 1rem; box-shadow: 0 4px 12px rgba(4, 38, 64, 0.3);
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
                <span class="wf-sidebar-brand-tag">Customer</span>
            </div>
        </div>

        <nav class="wf-nav">
            <div class="wf-nav-section-label">Overview</div>
            <a href="userdashboard.php" class="wf-nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <div class="wf-nav-section-label">Laundry</div>
            <a href="book_service.php" class="wf-nav-item"><i class="fas fa-plus-circle"></i><span>New Booking</span></a>
            <a href="my_bookings.php" class="wf-nav-item active"><i class="fas fa-clipboard-list"></i><span>My Bookings</span></a>
            <a href="payments.php" class="wf-nav-item"><i class="fas fa-credit-card"></i><span>Payments</span></a>
            <div class="wf-nav-section-label">Support</div>
            <a href="notifications.php" class="wf-nav-item"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="my_feedback.php" class="wf-nav-item"><i class="fas fa-star"></i><span>Feedback</span></a>
            <a href="my_complaints.php" class="wf-nav-item"><i class="fas fa-headset"></i><span>Complaints</span></a>
            <div class="wf-nav-section-label">Account</div>
            <a href="profile.php" class="wf-nav-item"><i class="fas fa-user"></i><span>Profile</span></a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e($customer_initial) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($fullName ?: 'Customer') ?></div>
                    <div class="wf-user-role">Customer</div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="wf-main">

        <div class="wf-page-head">
            <div>
                <h1><i class="fas fa-clipboard-list" style="color:var(--primary);"></i> My Bookings</h1>
                <p>Track all your laundry bookings in one place</p>
            </div>
            <a href="book_service.php" class="wf-btn-primary">
                <i class="fas fa-plus"></i> New Booking
            </a>
        </div>

        <!-- FILTER -->
        <form method="GET" action="my_bookings.php" class="wf-filter-bar">
            <div class="wf-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" name="q" class="wf-search"
                       placeholder="Search by ID, service, or add-on..."
                       value="<?= e($search) ?>">
            </div>

            <select name="status" class="wf-select">
                <option value="">All Status</option>
                <?php foreach (['Pending','Processing','Ready','Completed','Cancelled'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status_f === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="wf-btn-filter">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="my_bookings.php" class="wf-btn-reset">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <!-- TABLE -->
        <div class="wf-card">

            <?php if (empty($bookings)): ?>
                <div class="wf-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No bookings found</p>
                    <span>Try different filters, or create a new booking</span>
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
                            <?php foreach ($bookings as $b):
                                $badge = status_pill($b['status']);
                                $pb = pay_pill($b['payment_status']);
                            ?>
                            <tr>
                                <td><span class="wf-booking-id">#<?= e($b['id']) ?></span></td>
                                <td>
                                    <div class="wf-service-text"><?= e($b['service'] ?: '—') ?></div>
                                    <?php if (!empty($b['addons'])): ?>
                                        <div class="wf-addon-text">+ <?= e($b['addons']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="wf-date"><?= e($b['dropoff_date'] ?: '—') ?> <?= $b['dropoff_time'] ? '• ' . e($b['dropoff_time']) : '' ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format((float)$b['total_amount'], 2) ?></span></td>
                                <td>
                                    <span class="wf-badge" style="color:<?= $badge['color'] ?>;background:<?= $badge['bg'] ?>;">
                                        <span class="wf-badge-dot"></span> <?= e($badge['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="wf-badge" style="color:<?= $pb['color'] ?>;background:<?= $pb['bg'] ?>;">
                                        <span class="wf-badge-dot"></span> <?= e($pb['label']) ?>
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

                <?php if ($total_pages > 1): ?>
                <div class="wf-pagination">
                    <div class="wf-pg-info">
                        Showing <?= number_format(min($offset + 1, $total)) ?>–<?= number_format(min($offset + $per_page, $total)) ?> of <?= number_format($total) ?>
                    </div>
                    <div class="wf-pg-list">
                        <a class="wf-pg-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(bookings_url(['page' => max(1, $page - 1)])) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        if ($start > 1) {
                            echo '<a class="wf-pg-btn" href="' . e(bookings_url(['page' => 1])) . '">1</a>';
                            if ($start > 2) echo '<span class="wf-pg-btn disabled" style="border:none;background:transparent;">…</span>';
                        }
                        for ($p = $start; $p <= $end; $p++) {
                            echo '<a class="wf-pg-btn ' . ($p === $page ? 'active' : '') . '" href="' . e(bookings_url(['page' => $p])) . '">' . $p . '</a>';
                        }
                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<span class="wf-pg-btn disabled" style="border:none;background:transparent;">…</span>';
                            echo '<a class="wf-pg-btn" href="' . e(bookings_url(['page' => $total_pages])) . '">' . $total_pages . '</a>';
                        }
                        ?>
                        <a class="wf-pg-btn <?= $page >= $total_pages ? 'disabled' : '' ?>" href="<?= e(bookings_url(['page' => min($total_pages, $page + 1)])) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
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
</script>

</body>
</html>