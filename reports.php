<?php
/**
 * reports.php
 * WashFlow — Admin Reports (Clean Sidebar, No Badges)
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

if (!function_exists('status_badge')) {
    function status_badge($status) {
        $s = strtolower(trim((string)$status));
        switch ($s) {
            case 'pending':     return ['label' => 'Pending',     'color' => '#946200', 'bg' => '#FEF9E7'];
            case 'confirmed':   return ['label' => 'Confirmed',   'color' => '#00537A', 'bg' => '#EBF5FB'];
            case 'in progress': return ['label' => 'In Progress', 'color' => '#5B2E91', 'bg' => '#F4ECFB'];
            case 'processing':  return ['label' => 'Processing',  'color' => '#5B2E91', 'bg' => '#F4ECFB'];
            case 'ready':       return ['label' => 'Ready',       'color' => '#946200', 'bg' => '#FFF9DB'];
            case 'completed':   return ['label' => 'Completed',   'color' => '#1E7E45', 'bg' => '#EAF7F0'];
            case 'cancelled':   return ['label' => 'Cancelled',   'color' => '#A8322D', 'bg' => '#FDEDEC'];
            default:            return ['label' => $status ? ucfirst($status) : 'Pending', 'color' => '#5A7184', 'bg' => '#F4F7F9'];
        }
    }
}

/* ══════════════════════════════════════════
   POST HANDLERS (CSV Export)
   ══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'export_csv') {
        $from_exp = $_POST['from'] ?? date('Y-m-01');
        $to_exp   = $_POST['to']   ?? date('Y-m-d');
        $report   = $_POST['report_type'] ?? 'summary';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="report_' . $report . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        if ($report === 'summary' || $report === 'sales') {
            fputcsv($out, ['Date', 'Orders', 'Sales']);
            $stmt = $conn->prepare("
                SELECT DATE(booking_date) AS d,
                       COUNT(*) AS orders,
                       COALESCE(SUM(total_amount),0) AS sales
                FROM booking
                WHERE status = 'Completed'
                  AND DATE(booking_date) BETWEEN ? AND ?
                GROUP BY DATE(booking_date)
                ORDER BY d ASC
            ");
            $stmt->bind_param('ss', $from_exp, $to_exp);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                fputcsv($out, [$r['d'], $r['orders'], number_format((float)$r['sales'], 2, '.', '')]);
            }
            $stmt->close();
        } elseif ($report === 'services') {
            fputcsv($out, ['Service', 'Orders', 'Revenue']);
            $stmt = $conn->prepare("
                SELECT service, COUNT(*) AS orders,
                       COALESCE(SUM(total_amount),0) AS revenue
                FROM booking
                WHERE status = 'Completed'
                  AND DATE(booking_date) BETWEEN ? AND ?
                GROUP BY service
                ORDER BY revenue DESC
            ");
            $stmt->bind_param('ss', $from_exp, $to_exp);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                fputcsv($out, [$r['service'], $r['orders'], number_format((float)$r['revenue'], 2, '.', '')]);
            }
            $stmt->close();
        } elseif ($report === 'customers') {
            fputcsv($out, ['Customer', 'Orders', 'Total Spent']);
            $stmt = $conn->prepare("
                SELECT CONCAT(c.first_name,' ',c.last_name) AS name,
                       COUNT(b.Booking_ID) AS orders,
                       COALESCE(SUM(b.total_amount),0) AS spent
                FROM booking b
                JOIN customer_info c ON c.Customer_ID = b.Customer_ID
                WHERE b.status = 'Completed'
                  AND DATE(b.booking_date) BETWEEN ? AND ?
                GROUP BY c.Customer_ID, name
                ORDER BY spent DESC
            ");
            $stmt->bind_param('ss', $from_exp, $to_exp);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                fputcsv($out, [$r['name'], $r['orders'], number_format((float)$r['spent'], 2, '.', '')]);
            }
            $stmt->close();
        }
        fclose($out);
        exit();
    }
}

/* ══════════════════════════════════════════
   DATE FILTER
   ══════════════════════════════════════════ */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

/* ══════════════════════════════════════════
   REPORT STATS
   ══════════════════════════════════════════ */
$summary = [
    'total_sales'   => 0,
    'total_orders'  => 0,
    'avg_order'     => 0,
    'cancelled'     => 0,
];

try {
    $q = $conn->prepare("
        SELECT COALESCE(SUM(total_amount),0) AS sales, COUNT(*) AS orders
        FROM booking
        WHERE status = 'Completed'
          AND DATE(booking_date) BETWEEN ? AND ?
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $summary['total_sales']  = (float)$row['sales'];
    $summary['total_orders'] = (int)$row['orders'];
    $summary['avg_order']    = $summary['total_orders'] > 0
        ? $summary['total_sales'] / $summary['total_orders'] : 0;
    $q->close();

    $q = $conn->prepare("
        SELECT COUNT(*) c FROM booking
        WHERE status = 'Cancelled' AND DATE(booking_date) BETWEEN ? AND ?
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $summary['cancelled'] = (int)$q->get_result()->fetch_assoc()['c'];
    $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Summary failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   SALES TREND
   ══════════════════════════════════════════ */
$sales_trend = [];
try {
    $q = $conn->prepare("
        SELECT DATE(booking_date) AS d, COALESCE(SUM(total_amount),0) AS s
        FROM booking
        WHERE status = 'Completed'
          AND DATE(booking_date) BETWEEN ? AND ?
        GROUP BY DATE(booking_date)
        ORDER BY d ASC
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $res = $q->get_result();
    while ($row = $res->fetch_assoc()) {
        $sales_trend[] = ['date' => $row['d'], 'sales' => (float)$row['s']];
    }
    $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Sales trend failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   ORDERS BY STATUS
   ══════════════════════════════════════════ */
$status_breakdown = [];
try {
    $q = $conn->prepare("
        SELECT status, COUNT(*) AS count
        FROM booking
        WHERE DATE(booking_date) BETWEEN ? AND ?
        GROUP BY status
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $status_breakdown = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Status breakdown failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   TOP SERVICES
   ══════════════════════════════════════════ */
$top_services = [];
try {
    $q = $conn->prepare("
        SELECT service, COUNT(*) AS orders,
               COALESCE(SUM(total_amount),0) AS revenue
        FROM booking
        WHERE status = 'Completed'
          AND DATE(booking_date) BETWEEN ? AND ?
        GROUP BY service
        ORDER BY revenue DESC
        LIMIT 6
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $top_services = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Top services failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   PAYMENT METHODS
   ══════════════════════════════════════════ */
$payment_methods = [];
try {
    $q = $conn->prepare("
        SELECT payment_method, COUNT(*) AS count,
               COALESCE(SUM(amount),0) AS total
        FROM payments
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY total DESC
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $payment_methods = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Payment methods failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   TOP CUSTOMERS
   ══════════════════════════════════════════ */
$top_customers = [];
try {
    $q = $conn->prepare("
        SELECT c.Customer_ID,
               CONCAT(c.first_name,' ',c.last_name) AS name,
               COUNT(b.Booking_ID) AS orders,
               COALESCE(SUM(b.total_amount),0) AS spent
        FROM booking b
        JOIN customer_info c ON c.Customer_ID = b.Customer_ID
        WHERE b.status = 'Completed'
          AND DATE(b.booking_date) BETWEEN ? AND ?
        GROUP BY c.Customer_ID, name
        ORDER BY spent DESC
        LIMIT 5
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $top_customers = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Top customers failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   RECENT COMPLETED BOOKINGS
   ══════════════════════════════════════════ */
$recent_bookings = [];
try {
    $q = $conn->prepare("
        SELECT b.Booking_ID, b.service, b.total_amount, b.booking_date,
               c.first_name, c.last_name
        FROM booking b
        LEFT JOIN customer_info c ON b.Customer_ID = c.Customer_ID
        WHERE b.status = 'Completed'
          AND DATE(b.booking_date) BETWEEN ? AND ?
        ORDER BY b.booking_date DESC
        LIMIT 8
    ");
    $q->bind_param('ss', $from, $to);
    $q->execute();
    $recent_bookings = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Recent bookings failed', ['error' => $ex->getMessage()]);
}

$max_trend = 1;
foreach ($sales_trend as $t) {
    if ($t['sales'] > $max_trend) $max_trend = $t['sales'];
}

$total_status_count = 0;
foreach ($status_breakdown as $s) $total_status_count += (int)$s['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — WashFlow</title>

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

.wf-stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.25rem 0.55rem;
    border-radius: 6px;
    background: var(--light-blue-pale);
    color: var(--primary);
    letter-spacing: 0.02em;
    white-space: nowrap;
}
.wf-stat-chip i { font-size: 0.6rem; }
.wf-stat-chip.chip-good { background: #EAF7F0; color: #1E7E45; }
.wf-stat-chip.chip-bad  { background: #FDEDEC; color: #A8322D; }

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

/* CARD */
.wf-card {
    background: white;
    border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
}

/* GRID 2 COLUMN */
.wf-grid-2 {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}
.wf-grid-2-eq {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}
@media (max-width: 1200px) {
    .wf-grid-2, .wf-grid-2-eq { grid-template-columns: 1fr; }
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
.wf-card-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--primary);
    font-weight: 600;
    font-size: 0.8rem;
    padding: 0.4rem 0.75rem;
    border-radius: 7px;
    transition: all 0.2s;
}
.wf-card-link:hover { background: var(--light-blue-pale); }
.wf-card-body-pad { padding: 1.5rem 1.6rem; }

/* CHART */
.wf-chart-wrap { position: relative; height: 240px; padding: 0.5rem 0; }
.wf-chart-bars {
    display: flex;
    align-items: flex-end;
    gap: 0.6rem;
    height: 100%;
}
.wf-chart-bar-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
    height: 100%;
    justify-content: flex-end;
    min-width: 0;
}
.wf-chart-bar-wrap {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    position: relative;
}
.wf-chart-bar {
    width: 100%;
    max-width: 44px;
    background: linear-gradient(180deg, var(--light-blue) 0%, #C9EDF9 100%);
    border-radius: 7px 7px 3px 3px;
    min-height: 6px;
    transition: all 0.3s ease;
    position: relative;
    cursor: pointer;
}
.wf-chart-bar:hover {
    background: linear-gradient(180deg, var(--primary-mid) 0%, var(--primary) 100%);
}
.wf-chart-bar-value {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--dark-blue);
    opacity: 0;
    transition: opacity 0.2s;
    white-space: nowrap;
    background: white;
    padding: 0.25rem 0.55rem;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0, 83, 122, 0.12);
    pointer-events: none;
    border: 1px solid var(--border);
}
.wf-chart-bar:hover .wf-chart-bar-value { opacity: 1; }
.wf-chart-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
.wf-chart-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--border-soft);
    gap: 1rem;
    flex-wrap: wrap;
}
.wf-chart-summary-left { display: flex; align-items: baseline; gap: 0.5rem; }
.wf-chart-summary-value {
    font-size: 1.45rem;
    font-weight: 800;
    color: var(--dark-blue);
    letter-spacing: -0.03em;
}
.wf-chart-summary-label {
    font-size: 0.82rem;
    color: var(--text-secondary);
    font-weight: 500;
}
.wf-chart-summary-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    background: var(--light-blue-pale);
    border-radius: 7px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--primary);
}
.wf-chart-summary-chip i { color: var(--gold); font-size: 0.72rem; }

/* PROGRESS BARS */
.wf-progress-list { display: flex; flex-direction: column; gap: 1.1rem; }
.wf-progress-item { display: flex; flex-direction: column; gap: 0.5rem; }
.wf-progress-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}
.wf-progress-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--dark-blue);
}
.wf-progress-dot { width: 9px; height: 9px; border-radius: 50%; }
.wf-progress-value { font-size: 0.82rem; font-weight: 700; color: var(--dark-blue); }
.wf-progress-track {
    height: 8px;
    background: var(--border-soft);
    border-radius: 6px;
    overflow: hidden;
}
.wf-progress-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.5s ease;
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

.wf-booking-id {
    font-weight: 700;
    color: var(--primary);
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.8rem;
}
.wf-customer { font-weight: 600; color: var(--dark-blue); }
.wf-service { color: var(--text-secondary); font-size: 0.84rem; }
.wf-amount { font-weight: 700; color: var(--dark-blue); }
.wf-date { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }

.wf-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: var(--light-blue-soft);
    color: var(--primary);
    font-size: 0.75rem;
    font-weight: 800;
}
.wf-rank.gold   { background: #FFF4CC; color: #8A6400; }
.wf-rank.silver { background: #ECF1F5; color: #55677A; }
.wf-rank.bronze { background: #FBE9DC; color: #8B4A1F; }

/* EMPTY */
.wf-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-muted);
}
.wf-empty i {
    font-size: 2.5rem;
    color: var(--light-blue);
    margin-bottom: 0.9rem;
    display: block;
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

    <!-- ══════════ SIDEBAR — CLEAN, NO BADGES ══════════ -->
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
            <a href="reports.php" class="wf-nav-item active">
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
            <a href="system_logs.php" class="wf-nav-item">
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

    <!-- ══════════ MAIN ══════════ -->
    <main class="wf-main">

        <!-- TOPBAR -->
        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1>Business <span class="accent">Reports</span></h1>
                <p>Analyze sales, services, and customer performance</p>
            </div>
            <div class="wf-topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, F j, Y') ?>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form method="get" action="reports.php" class="wf-filter-bar">
            <div class="wf-filter-group" style="flex: 0 0 170px;">
                <label>From Date</label>
                <input type="date" name="from" class="wf-input" value="<?= e($from) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 170px;">
                <label>To Date</label>
                <input type="date" name="to" class="wf-input" value="<?= e($to) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 auto; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                <a href="reports.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
                <button type="button" class="wf-btn wf-btn-yellow"
                        onclick="document.getElementById('exportForm').submit();">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </form>

        <!-- Export form -->
        <form method="post" id="exportForm" style="display:none;">
            <input type="hidden" name="action" value="export_csv">
            <input type="hidden" name="report_type" value="summary">
            <input type="hidden" name="from" value="<?= e($from) ?>">
            <input type="hidden" name="to" value="<?= e($to) ?>">
        </form>

        <!-- SUMMARY CARDS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-coins"></i></div>
                    <span class="wf-stat-chip chip-good">
                        <i class="fas fa-arrow-up"></i> <?= number_format($summary['total_orders']) ?> orders
                    </span>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($summary['total_sales'], 2) ?></div>
                    <div class="wf-stat-label">Total Sales</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-receipt"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($summary['total_orders']) ?></div>
                    <div class="wf-stat-label">Total Orders</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-purple"><i class="fas fa-calculator"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($summary['avg_order'], 2) ?></div>
                    <div class="wf-stat-label">Average Order Value</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-red"><i class="fas fa-circle-xmark"></i></div>
                    <span class="wf-stat-chip chip-bad">
                        <i class="fas fa-triangle-exclamation"></i> Lost
                    </span>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($summary['cancelled']) ?></div>
                    <div class="wf-stat-label">Cancelled Orders</div>
                </div>
            </div>
        </div>

        <!-- SALES TREND + STATUS BREAKDOWN -->
        <div class="wf-grid-2">

            <div class="wf-card" style="margin-bottom:0;">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon icon-blue">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h3>Sales Trend</h3>
                            <p>Daily revenue for the selected period</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($sales_trend)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-chart-column"></i>
                        <p>No sales data for this period</p>
                    </div>
                <?php else: ?>
                    <div class="wf-card-body-pad" style="padding-top:0.5rem;">
                        <div class="wf-chart-wrap">
                            <div class="wf-chart-bars">
                                <?php foreach ($sales_trend as $row):
                                    $pct = ($row['sales'] / $max_trend) * 100;
                                    $pct = max($pct, 3);
                                ?>
                                <div class="wf-chart-bar-group">
                                    <div class="wf-chart-bar-wrap">
                                        <div class="wf-chart-bar" style="height: <?= $pct ?>%;">
                                            <span class="wf-chart-bar-value">₱<?= number_format($row['sales'], 0) ?></span>
                                        </div>
                                    </div>
                                    <div class="wf-chart-label"><?= date('M j', strtotime($row['date'])) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="wf-chart-summary">
                            <div class="wf-chart-summary-left">
                                <div class="wf-chart-summary-value">₱<?= number_format($summary['total_sales'], 2) ?></div>
                                <div class="wf-chart-summary-label">total in period</div>
                            </div>
                            <div class="wf-chart-summary-chip">
                                <i class="fas fa-calendar-days"></i>
                                <?= count($sales_trend) ?> day(s)
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="wf-card" style="margin-bottom:0;">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div>
                            <h3>Orders by Status</h3>
                            <p>Breakdown of all bookings</p>
                        </div>
                    </div>
                </div>

                <div class="wf-card-body-pad">
                    <?php if (empty($status_breakdown)): ?>
                        <div class="wf-empty">
                            <i class="fas fa-inbox"></i>
                            <p>No orders in this period</p>
                        </div>
                    <?php else: ?>
                        <div class="wf-progress-list">
                            <?php foreach ($status_breakdown as $s):
                                $b = status_badge($s['status']);
                                $pct = $total_status_count > 0 ? ((int)$s['count'] / $total_status_count) * 100 : 0;
                            ?>
                            <div class="wf-progress-item">
                                <div class="wf-progress-header">
                                    <div class="wf-progress-label">
                                        <span class="wf-progress-dot" style="background: <?= $b['color'] ?>;"></span>
                                        <?= e($b['label']) ?>
                                    </div>
                                    <div class="wf-progress-value">
                                        <?= (int)$s['count'] ?>
                                        <span style="color:var(--text-muted); font-weight:500;">(<?= number_format($pct, 1) ?>%)</span>
                                    </div>
                                </div>
                                <div class="wf-progress-track">
                                    <div class="wf-progress-fill"
                                         style="width: <?= $pct ?>%; background: <?= $b['color'] ?>;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- TOP SERVICES + PAYMENT METHODS -->
        <div class="wf-grid-2-eq">

            <div class="wf-card" style="margin-bottom:0;">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon icon-blue">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <h3>Top Services</h3>
                            <p>Best performing services by revenue</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($top_services)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-box-open"></i>
                        <p>No service data for this period</p>
                    </div>
                <?php else: ?>
                    <div class="wf-table-wrap">
                        <table class="wf-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Service</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_services as $i => $s):
                                    $rank_class = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                                ?>
                                <tr>
                                    <td><span class="wf-rank <?= $rank_class ?>"><?= $i + 1 ?></span></td>
                                    <td><span class="wf-customer"><?= e($s['service'] ?: '—') ?></span></td>
                                    <td><span class="wf-service"><?= number_format($s['orders']) ?></span></td>
                                    <td><span class="wf-amount">₱<?= number_format($s['revenue'], 2) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="wf-card" style="margin-bottom:0;">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon icon-good">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>
                            <h3>Payment Methods</h3>
                            <p>How customers are paying</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($payment_methods)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-money-bill-wave"></i>
                        <p>No payments recorded in this period</p>
                    </div>
                <?php else: ?>
                    <div class="wf-table-wrap">
                        <table class="wf-table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Transactions</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_methods as $p): ?>
                                <tr>
                                    <td><span class="wf-customer"><?= e($p['payment_method']) ?></span></td>
                                    <td><span class="wf-service"><?= number_format($p['count']) ?></span></td>
                                    <td><span class="wf-amount">₱<?= number_format($p['total'], 2) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- TOP CUSTOMERS -->
        <div class="wf-card">
            <div class="wf-card-header">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <h3>Top Customers</h3>
                        <p>Highest spending customers in this period</p>
                    </div>
                </div>
            </div>

            <?php if (empty($top_customers)): ?>
                <div class="wf-empty">
                    <i class="fas fa-user-group"></i>
                    <p>No customer data for this period</p>
                </div>
            <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_customers as $i => $c):
                                $rank_class = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                            ?>
                            <tr>
                                <td><span class="wf-rank <?= $rank_class ?>"><?= $i + 1 ?></span></td>
                                <td><span class="wf-customer"><?= e($c['name']) ?></span></td>
                                <td><span class="wf-service"><?= number_format($c['orders']) ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format($c['spent'], 2) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENT COMPLETED BOOKINGS -->
        <div class="wf-card">
            <div class="wf-card-header">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon icon-good">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div>
                        <h3>Recent Completed Bookings</h3>
                        <p>Latest <?= count($recent_bookings) ?> completed transactions</p>
                    </div>
                </div>
                <a href="booking_management.php" class="wf-card-link">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($recent_bookings)): ?>
                <div class="wf-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No completed bookings in this period</p>
                </div>
            <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $b):
                                $name = trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? ''));
                                if ($name === '') $name = 'Walk-in';
                            ?>
                            <tr>
                                <td><span class="wf-booking-id">#<?= e($b['Booking_ID']) ?></span></td>
                                <td><span class="wf-customer"><?= e($name) ?></span></td>
                                <td><span class="wf-service"><?= e($b['service'] ?: '—') ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format((float)$b['total_amount'], 2) ?></span></td>
                                <td><span class="wf-date"><?= date('M j, Y', strtotime($b['booking_date'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<div class="wf-toast-wrap" id="toastWrap"></div>

<script>
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