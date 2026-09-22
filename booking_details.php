<?php
/**
 * booking_details.php
 * WashFlow — Single Booking Details + Status Timeline
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

$booking_id = (int)($_GET['id'] ?? 0);
if ($booking_id <= 0) {
    header('Location: my_bookings.php');
    exit();
}

/* Fetch booking (must belong to this customer) */
$booking = null;
$stmt = $conn->prepare("
    SELECT b.* 
    FROM booking_online b
    WHERE b.id = ? AND b.customer_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $booking_id, $customer_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    $_SESSION['flash_error'] = 'Booking not found.';
    header('Location: my_bookings.php');
    exit();
}

/* ══════════════════════════════════════════
   🆕 FETCH PAYMENT SUMMARY (from payments_online)
   ══════════════════════════════════════════ */
$payment_summary = [
    'paid_amount'    => 0.0,
    'pending_amount' => 0.0,
    'failed_amount'  => 0.0,
    'methods'        => [],
    'latest'         => null,
    'rows'           => [],
];

$stmt = $conn->prepare("
    SELECT payment_id, amount, payment_method, payment_status,
           reference_number, payment_date, payment_proof, notes
    FROM payments_online
    WHERE booking_id = ?
    ORDER BY payment_date DESC
");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$payment_summary['rows'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($payment_summary['rows'] as $p) {
    $amt = (float)$p['amount'];
    $st  = strtolower(trim($p['payment_status'] ?? ''));

    if ($st === 'paid') {
        $payment_summary['paid_amount'] += $amt;
        $payment_summary['methods'][] = $p['payment_method'];
    } elseif ($st === 'pending') {
        $payment_summary['pending_amount'] += $amt;
        if (!in_array($p['payment_method'], $payment_summary['methods'], true)) {
            $payment_summary['methods'][] = $p['payment_method'];
        }
    } elseif ($st === 'failed') {
        $payment_summary['failed_amount'] += $amt;
    }
}

$payment_summary['methods'] = array_unique($payment_summary['methods']);
$payment_summary['latest']  = $payment_summary['rows'][0] ?? null;

/* ══════════════════════════════════════════
   🆕 COMPUTE PAYMENT STATE
   ══════════════════════════════════════════ */
$total_amount = (float)$booking['total_amount'];
$paid_amount  = (float)$payment_summary['paid_amount'];
$pending      = (float)$payment_summary['pending_amount'];
$balance      = max(0, $total_amount - $paid_amount);

$is_fully_paid = ($paid_amount >= $total_amount && $total_amount > 0);
$is_partial    = ($paid_amount > 0 && $paid_amount < $total_amount);
$has_pending   = ($pending > 0);
$is_completed  = (strtolower($booking['status'] ?? '') === 'completed');
$is_cancelled  = (strtolower($booking['status'] ?? '') === 'cancelled');

/* Payment status label */
if ($is_fully_paid) {
    $pay_label = 'Paid';
    $pay_color = '#1E7E45';
    $pay_bg    = '#EAF7F0';
} elseif ($is_partial) {
    $pay_label = 'Partially Paid';
    $pay_color = '#946200';
    $pay_bg    = '#FEF9E7';
} elseif ($has_pending) {
    $pay_label = 'Pending Verification';
    $pay_color = '#946200';
    $pay_bg    = '#FEF9E7';
} else {
    $pay_label = 'Unpaid';
    $pay_color = '#A8322D';
    $pay_bg    = '#FDEDEC';
}

/* 🆕 Show Pay Now only when:
   - Hindi cancelled
   - Hindi completed (final na ang booking)
   - May balance pa
   - Walang pending payment na hinihintay
*/
$show_pay_now = (
    !$is_cancelled
    && !$is_completed
    && $balance > 0
    && !$has_pending
);

/* Flash messages */
$flash_success = $_SESSION['flash_booking_success'] ?? null;
unset($_SESSION['flash_booking_success']);

/* Helpers */
function detail_status($s) {
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

/* Timeline steps */
$status = strtolower($booking['status'] ?: 'pending');
$timeline = [
    ['key' => 'pending',    'label' => 'Booking Placed',   'icon' => 'fa-calendar-check', 'desc' => 'Your booking was received'],
    ['key' => 'processing', 'label' => 'Processing',       'icon' => 'fa-spinner',         'desc' => 'We are cleaning your laundry'],
    ['key' => 'ready',      'label' => 'Ready',            'icon' => 'fa-box-open',        'desc' => 'Ready for pickup/delivery'],
    ['key' => 'completed',  'label' => 'Completed',        'icon' => 'fa-check-double',    'desc' => 'Laundry delivered to you'],
];
$order = ['pending' => 0, 'processing' => 1, 'ready' => 2, 'completed' => 3];
$current_step = $order[$status] ?? 0;

$badge = detail_status($booking['status']);
$unread_count = CustomerNotify::unreadCount($conn, $customer_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking #<?= (int)$booking['id'] ?> — WashFlow</title>
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

/* HEAD */
.wf-detail-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.wf-detail-head-left h1 {
    font-size: 1.5rem; font-weight: 800;
    color: var(--dark-blue); margin: 0;
    letter-spacing: -0.03em;
    display: flex; align-items: center; gap: 0.7rem;
    flex-wrap: wrap;
}
.wf-detail-head-left h1 .id {
    font-family: 'SF Mono', Monaco, monospace;
    color: var(--primary);
    background: var(--light-blue-pale);
    padding: 0.2rem 0.7rem;
    border-radius: 8px;
    font-size: 1.15rem;
}
.wf-detail-head-left p {
    margin: 0.3rem 0 0;
    font-size: 0.85rem;
    color: var(--text-secondary);
}
.wf-back-link {
    display: inline-flex; align-items: center; gap: 0.4rem;
    font-size: 0.82rem; font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 0.8rem;
    transition: color 0.2s;
}
.wf-back-link:hover { color: var(--primary); }

/* ALERT */
.wf-alert {
    display: flex; align-items: flex-start; gap: 0.7rem;
    padding: 0.85rem 1.1rem;
    border-radius: 11px;
    margin-bottom: 1.25rem;
    font-size: 0.83rem;
    border-left: 4px solid var(--green);
    background: #EAF7F0;
    color: #14532d;
}
.wf-alert i { color: var(--green); font-size: 1rem; margin-top: 2px; flex-shrink: 0; }

/* GRID */
.wf-detail-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 1100px) { .wf-detail-grid { grid-template-columns: 1fr; } }

.wf-card {
    background: white; border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.wf-card-head {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; gap: 0.8rem;
}
.wf-card-head-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--yellow-soft); color: var(--yellow-dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.wf-card-head h3 {
    font-size: 1rem; font-weight: 700;
    color: var(--dark-blue); margin: 0;
}
.wf-card-head p {
    font-size: 0.75rem; color: var(--text-muted);
    margin: 0.1rem 0 0; font-weight: 500;
}
.wf-card-body { padding: 1.5rem; }

/* STATUS HERO */
.wf-status-hero {
    display: flex; align-items: center; gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-radius: 14px;
    margin-bottom: 1.25rem;
    background: linear-gradient(135deg, var(--light-blue-pale) 0%, white 100%);
    border: 1px solid var(--border);
}
.wf-status-icon {
    width: 60px; height: 60px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; flex-shrink: 0;
}
.wf-status-hero .info { flex: 1; min-width: 0; }
.wf-status-hero .info h3 {
    font-size: 1.15rem; font-weight: 800;
    color: var(--dark-blue); margin: 0;
    letter-spacing: -0.02em;
}
.wf-status-hero .info p {
    font-size: 0.82rem; color: var(--text-secondary);
    margin: 0.2rem 0 0;
}

/* TIMELINE */
.wf-timeline {
    display: flex; flex-direction: column;
    gap: 0;
    padding: 0.5rem 0;
}
.wf-tl-step {
    display: flex; align-items: flex-start; gap: 1rem;
    position: relative;
    padding-bottom: 1.75rem;
}
.wf-tl-step:last-child { padding-bottom: 0; }
.wf-tl-step::before {
    content: ''; position: absolute;
    left: 20px; top: 42px; bottom: 0;
    width: 2px;
    background: var(--border);
    border-radius: 2px;
}
.wf-tl-step:last-child::before { display: none; }
.wf-tl-step.done::before { background: var(--green); }
.wf-tl-step.active::before {
    background: linear-gradient(180deg, var(--yellow) 0%, var(--border) 100%);
}
.wf-tl-icon {
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    background: white;
    border: 2px solid var(--border);
    color: var(--text-muted);
    transition: all 0.3s;
    position: relative; z-index: 2;
}
.wf-tl-step.done .wf-tl-icon {
    background: var(--green); border-color: var(--green); color: white;
}
.wf-tl-step.active .wf-tl-icon {
    background: var(--yellow); border-color: var(--yellow); color: var(--dark-blue-deep);
    box-shadow: 0 0 0 6px rgba(255, 217, 61, 0.25);
    animation: pulseStep 2s infinite;
}
@keyframes pulseStep {
    0%, 100% { box-shadow: 0 0 0 6px rgba(255, 217, 61, 0.25); }
    50% { box-shadow: 0 0 0 10px rgba(255, 217, 61, 0.1); }
}
.wf-tl-info { flex: 1; padding-top: 0.35rem; }
.wf-tl-info h5 {
    font-size: 0.92rem; font-weight: 700;
    color: var(--dark-blue); margin: 0;
}
.wf-tl-step:not(.done):not(.active) .wf-tl-info h5 { color: var(--text-muted); }
.wf-tl-info p {
    font-size: 0.78rem; color: var(--text-muted);
    margin: 0.15rem 0 0;
}

/* CANCELLED ALERT */
.wf-cancelled-box {
    display: flex; align-items: flex-start; gap: 0.9rem;
    padding: 1.15rem 1.25rem;
    border-radius: 12px;
    background: #FDEDEC;
    border-left: 4px solid var(--red);
    color: #7f1d1d;
}
.wf-cancelled-box i { font-size: 1.35rem; color: var(--red); flex-shrink: 0; }
.wf-cancelled-box h5 { font-size: 0.95rem; font-weight: 700; margin: 0 0 0.25rem; }
.wf-cancelled-box p { font-size: 0.83rem; margin: 0; line-height: 1.5; }

/* DETAIL ROWS */
.wf-info-list { display: flex; flex-direction: column; gap: 0; }
.wf-info-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px dashed var(--border-soft);
    font-size: 0.85rem;
}
.wf-info-row:last-child { border-bottom: none; }
.wf-info-row .lbl {
    color: var(--text-muted); font-weight: 600;
    font-size: 0.78rem; text-transform: uppercase;
    letter-spacing: 0.04em;
    flex-shrink: 0;
}
.wf-info-row .val {
    color: var(--dark-blue);
    font-weight: 600;
    text-align: right;
    word-break: break-word;
    max-width: 60%;
}
.wf-info-row .val.muted { color: var(--text-secondary); font-weight: 500; }

/* SUMMARY */
.wf-summary-total {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 0;
    border-top: 2px solid var(--border);
    border-bottom: 2px solid var(--border);
    margin: 1rem 0;
}
.wf-summary-total .l {
    font-size: 0.82rem; font-weight: 700;
    color: var(--text-secondary); text-transform: uppercase;
    letter-spacing: 0.06em;
}
.wf-summary-total .v {
    font-size: 1.4rem; font-weight: 800;
    color: var(--dark-blue);
    letter-spacing: -0.03em;
}
.wf-summary-sub {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.4rem 0;
    font-size: 0.82rem;
}
.wf-summary-sub .l { color: var(--text-muted); font-weight: 600; }
.wf-summary-sub .v { font-weight: 700; }

/* ACTION BUTTONS */
.wf-actions {
    display: flex; flex-direction: column; gap: 0.6rem;
}
.wf-btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 0.5rem;
    padding: 0.8rem 1.25rem;
    border-radius: 50px;
    font-size: 0.875rem; font-weight: 700;
    border: none; cursor: pointer;
    transition: all 0.25s;
    font-family: inherit;
    text-decoration: none;
}
.wf-btn-primary {
    background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
    color: var(--dark-blue-deep);
    box-shadow: 0 6px 16px rgba(255, 217, 61, 0.4);
}
.wf-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(255, 217, 61, 0.55); color: var(--dark-blue-deep); }
.wf-btn-outline {
    background: white; color: var(--primary);
    border: 1.5px solid var(--border);
}
.wf-btn-outline:hover { border-color: var(--primary); background: var(--light-blue-pale); color: var(--primary); }
.wf-btn-soft {
    background: var(--light-blue-pale);
    color: var(--primary);
    border: 1.5px solid var(--light-blue);
}
.wf-btn-soft:hover { background: var(--light-blue-soft); color: var(--primary); }

/* PAYMENT STATUS BOX */
.wf-payment-status {
    display: flex; align-items: center; gap: 0.6rem;
    padding: 0.8rem 1rem;
    border-radius: 10px;
    background: var(--light-blue-pale);
    margin-bottom: 1rem;
    font-size: 0.83rem;
    font-weight: 600;
}
.wf-payment-status i { font-size: 1.1rem; }

.wf-payment-status.pending  { background: #FEF9E7; color: #946200; }
.wf-payment-status.pending i { color: #B88A00; }
.wf-payment-status.paid     { background: #EAF7F0; color: #1E7E45; }
.wf-payment-status.paid i   { color: var(--green); }
.wf-payment-status.completed { background: var(--light-blue-pale); color: var(--primary); }
.wf-payment-status.completed i { color: var(--primary); }
.wf-payment-status.danger   { background: #FDEDEC; color: #A8322D; }
.wf-payment-status.danger i { color: var(--red); }

/* Payment rows list */
.wf-payment-list {
    display: flex; flex-direction: column; gap: 0.5rem;
    margin: 0.75rem 0 1rem;
}
.wf-payment-item {
    display: flex; align-items: center; gap: 0.65rem;
    padding: 0.55rem 0.75rem;
    border-radius: 8px;
    background: #FAFCFE;
    border: 1px solid var(--border-soft);
    font-size: 0.8rem;
}
.wf-payment-item .icon {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 0.75rem;
}
.wf-payment-item .icon.paid    { background: #EAF7F0; color: #1E7E45; }
.wf-payment-item .icon.pending { background: #FEF9E7; color: #946200; }
.wf-payment-item .icon.failed  { background: #FDEDEC; color: #A8322D; }
.wf-payment-item .body { flex: 1; min-width: 0; }
.wf-payment-item .body .method { font-weight: 700; color: var(--dark-blue); }
.wf-payment-item .body .meta { font-size: 0.7rem; color: var(--text-muted); }
.wf-payment-item .amt { font-weight: 700; color: var(--dark-blue); font-size: 0.85rem; }

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
            <a href="feedback.php" class="wf-nav-item"><i class="fas fa-star"></i><span>Feedback</span></a>
            <a href="complaints.php" class="wf-nav-item"><i class="fas fa-headset"></i><span>Complaints</span></a>
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

        <a href="my_bookings.php" class="wf-back-link"><i class="fas fa-arrow-left"></i> Back to My Bookings</a>

        <?php if ($flash_success): ?>
        <div class="wf-alert">
            <i class="fas fa-check-circle"></i>
            <div><?= e($flash_success) ?></div>
        </div>
        <?php endif; ?>

        <div class="wf-detail-head">
            <div class="wf-detail-head-left">
                <h1>Booking <span class="id">#<?= (int)$booking['id'] ?></span></h1>
                <p>Placed on <?= date('F j, Y \a\t g:i A', strtotime($booking['timestamp'])) ?></p>
            </div>
            <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
                <span class="wf-badge" style="color:<?= $badge['color'] ?>;background:<?= $badge['bg'] ?>;padding:0.55rem 1rem;font-size:0.82rem;">
                    <span class="wf-badge-dot"></span> <?= e($badge['label']) ?>
                </span>
                <span class="wf-badge" style="color:<?= $pay_color ?>;background:<?= $pay_bg ?>;padding:0.55rem 1rem;font-size:0.82rem;">
                    <span class="wf-badge-dot"></span> <?= e($pay_label) ?>
                </span>
            </div>
        </div>

        <div class="wf-detail-grid">

            <!-- LEFT -->
            <div>

                <!-- STATUS HERO -->
                <div class="wf-status-hero">
                    <div class="wf-status-icon" style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
                        <i class="fas <?= $is_cancelled ? 'fa-times-circle' : 'fa-clipboard-check' ?>"></i>
                    </div>
                    <div class="info">
                        <h3>Status: <?= e($badge['label']) ?></h3>
                        <p>
                            <?php if ($is_cancelled): ?>
                                This booking has been cancelled.
                            <?php elseif ($is_completed): ?>
                                Your laundry has been completed. Thank you! 🎉
                            <?php elseif ($status === 'ready'): ?>
                                Your laundry is ready. We'll contact you for pickup/delivery.
                            <?php elseif ($status === 'processing'): ?>
                                We're currently processing your laundry.
                            <?php else: ?>
                                Your booking is waiting for confirmation.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- TIMELINE -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-head-icon"><i class="fas fa-route"></i></div>
                        <div>
                            <h3>Order Progress</h3>
                            <p>Follow your laundry's journey</p>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <?php if ($is_cancelled): ?>
                            <div class="wf-cancelled-box">
                                <i class="fas fa-times-circle"></i>
                                <div>
                                    <h5>Booking Cancelled</h5>
                                    <p>This booking was cancelled. If this was a mistake, please contact us or create a new booking.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="wf-timeline">
                                <?php foreach ($timeline as $i => $step):
                                    $cls = '';
                                    if ($i < $current_step) $cls = 'done';
                                    elseif ($i === $current_step) $cls = 'active';
                                ?>
                                <div class="wf-tl-step <?= $cls ?>">
                                    <div class="wf-tl-icon">
                                        <?php if ($i < $current_step): ?>
                                            <i class="fas fa-check"></i>
                                        <?php else: ?>
                                            <i class="fas <?= e($step['icon']) ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="wf-tl-info">
                                        <h5><?= e($step['label']) ?></h5>
                                        <p><?= e($step['desc']) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BOOKING INFO -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-head-icon"><i class="fas fa-info-circle"></i></div>
                        <div>
                            <h3>Booking Information</h3>
                            <p>Details you provided</p>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <div class="wf-info-list">
                            <div class="wf-info-row">
                                <span class="lbl">Service(s)</span>
                                <span class="val"><?= e($booking['service'] ?: '—') ?></span>
                            </div>
                            <div class="wf-info-row">
                                <span class="lbl">Add-ons</span>
                                <span class="val muted"><?= e($booking['addons'] ?: 'None') ?></span>
                            </div>
                            <div class="wf-info-row">
                                <span class="lbl">Delivery Option</span>
                                <span class="val muted">
                                    <?php
                                    $dopts = [
                                        'walkin' => 'Walk-in (Drop & Pickup)',
                                        'pickup-only' => 'Pickup Only',
                                        'delivery' => 'Delivery Only',
                                        'pickup-delivery' => 'Pickup & Delivery',
                                    ];
                                    echo e($dopts[strtolower($booking['delivery_option'] ?? '')] ?? ucfirst($booking['delivery_option'] ?? '—'));
                                    ?>
                                </span>
                            </div>
                            <div class="wf-info-row">
                                <span class="lbl">Drop-off Date</span>
                                <span class="val"><?= e($booking['dropoff_date'] ?: '—') ?></span>
                            </div>
                            <div class="wf-info-row">
                                <span class="lbl">Preferred Time</span>
                                <span class="val"><?= e($booking['dropoff_time'] ?: '—') ?></span>
                            </div>
                            <div class="wf-info-row">
                                <span class="lbl">Contact</span>
                                <span class="val"><?= e($booking['contact_number'] ?: '—') ?></span>
                            </div>
                            <div class="wf-info-row">
                                <span class="lbl">Address</span>
                                <span class="val muted"><?= e($booking['address'] ?: '—') ?></span>
                            </div>
                            <?php if (!empty($booking['special_instructions'])): ?>
                            <div class="wf-info-row">
                                <span class="lbl">Instructions</span>
                                <span class="val muted"><?= e($booking['special_instructions']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT -->
            <div>

                <!-- PAYMENT SUMMARY -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-head-icon"><i class="fas fa-receipt"></i></div>
                        <div>
                            <h3>Payment Summary</h3>
                            <p>Amount and status</p>
                        </div>
                    </div>
                    <div class="wf-card-body">

                        <div class="wf-info-list">
                            <div class="wf-info-row">
                                <span class="lbl">Status</span>
                                <span class="val" style="color:<?= $pay_color ?>;"><?= e($pay_label) ?></span>
                            </div>
                            <?php if (!empty($payment_summary['methods'])): ?>
                            <div class="wf-info-row">
                                <span class="lbl">Method</span>
                                <span class="val muted"><?= e(implode(', ', $payment_summary['methods'])) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Individual payment rows -->
                        <?php if (!empty($payment_summary['rows'])): ?>
                        <div class="wf-payment-list">
                            <?php foreach ($payment_summary['rows'] as $p):
                                $ps = strtolower(trim($p['payment_status'] ?? ''));
                                if ($ps === 'paid')       { $icon_cls = 'paid';    $icon = 'fa-check'; }
                                elseif ($ps === 'pending'){ $icon_cls = 'pending'; $icon = 'fa-hourglass-half'; }
                                else                      { $icon_cls = 'failed';  $icon = 'fa-times'; }
                            ?>
                            <div class="wf-payment-item">
                                <div class="icon <?= $icon_cls ?>"><i class="fas <?= $icon ?>"></i></div>
                                <div class="body">
                                    <div class="method"><?= e($p['payment_method']) ?> — ₱<?= number_format((float)$p['amount'], 2) ?></div>
                                    <div class="meta">
                                        <?= e(ucfirst($ps)) ?>
                                        <?php if (!empty($p['payment_date'])): ?>
                                            • <?= date('M j, Y g:i A', strtotime($p['payment_date'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="amt">₱<?= number_format((float)$p['amount'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_partial): ?>
                        <div class="wf-summary-sub">
                            <span class="l">Paid</span>
                            <span class="v" style="color:var(--green);">₱<?= number_format($paid_amount, 2) ?></span>
                        </div>
                        <div class="wf-summary-sub">
                            <span class="l">Balance</span>
                            <span class="v" style="color:var(--red);">₱<?= number_format($balance, 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="wf-summary-total">
                            <span class="l">Total</span>
                            <span class="v">₱<?= number_format($total_amount, 2) ?></span>
                        </div>

                        <?php if ($is_cancelled): ?>
                            <div class="wf-payment-status danger">
                                <i class="fas fa-times-circle"></i>
                                Booking cancelled
                            </div>

                        <?php elseif ($is_completed && $is_fully_paid): ?>
                            <div class="wf-payment-status paid">
                                <i class="fas fa-check-circle"></i>
                                Fully paid — Thank you! 🎉
                            </div>

                        <?php elseif ($is_completed && !$is_fully_paid): ?>
                            <div class="wf-payment-status completed">
                                <i class="fas fa-check-circle"></i>
                                Booking completed — Please settle ₱<?= number_format($balance, 2) ?> at the shop
                            </div>

                        <?php elseif ($is_fully_paid): ?>
                            <div class="wf-payment-status paid">
                                <i class="fas fa-check-circle"></i>
                                Payment complete
                            </div>

                        <?php elseif ($has_pending): ?>
                            <div class="wf-payment-status pending">
                                <i class="fas fa-hourglass-half"></i>
                                Payment submitted — waiting for staff verification
                            </div>

                        <?php elseif ($show_pay_now): ?>
                            <div class="wf-payment-status danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Payment is still pending
                            </div>
                            <div class="wf-actions">
                                <a href="payments.php?booking_id=<?= (int)$booking['id'] ?>" class="wf-btn wf-btn-primary">
                                    <i class="fas fa-credit-card"></i> Pay Now — ₱<?= number_format($balance, 2) ?>
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-head-icon"><i class="fas fa-bolt"></i></div>
                        <div>
                            <h3>Actions</h3>
                            <p>Things you can do</p>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <div class="wf-actions">
                            <a href="my_bookings.php" class="wf-btn wf-btn-outline">
                                <i class="fas fa-list"></i> All Bookings
                            </a>
                            <a href="book_service.php" class="wf-btn wf-btn-soft">
                                <i class="fas fa-plus-circle"></i> Book Again
                            </a>
                            <a href="feedback.php" class="wf-btn wf-btn-outline">
                                <i class="fas fa-star"></i> Leave Feedback
                            </a>
                            <a href="complaints.php" class="wf-btn wf-btn-outline">
                                <i class="fas fa-headset"></i> File a Complaint
                            </a>
                        </div>
                    </div>
                </div>

            </div>

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