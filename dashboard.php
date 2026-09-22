<?php
/**
 * dashboard.php
 * WashFlow — Admin Dashboard (Balanced Layout)
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';
require_once 'auth_guard.php';

$admin_id   = require_role(ROLE_ADMIN);
$admin_name = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Admin';

$hour = (int)date('H');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 18)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';

// ══════════════════════════════════════════
// STATS
// ══════════════════════════════════════════
$stats = [
    'total_bookings'   => 0,
    'pending'          => 0,
    'processing'       => 0,
    'completed_today'  => 0,
    'total_revenue'    => 0,
    'revenue_today'    => 0,
    'total_customers'  => 0,
    'total_staff'      => 0,
    'low_stock'        => 0,
];

try {
    $q = $conn->query("SELECT COUNT(*) AS c FROM booking");
    if ($q) $stats['total_bookings'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) AS c FROM booking WHERE status = 'Pending'");
    if ($q) $stats['pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) AS c FROM booking WHERE status IN ('Confirmed','In Progress')");
    if ($q) $stats['processing'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) AS c FROM booking WHERE status = 'Completed' AND DATE(booking_date) = CURDATE()");
    if ($q) $stats['completed_today'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS s FROM booking WHERE status = 'Completed'");
    if ($q) $stats['total_revenue'] = (float)$q->fetch_assoc()['s'];

    $q = $conn->query("SELECT COALESCE(SUM(total_amount),0) AS s FROM booking WHERE status = 'Completed' AND DATE(booking_date) = CURDATE()");
    if ($q) $stats['revenue_today'] = (float)$q->fetch_assoc()['s'];

    $q = $conn->query("SELECT COUNT(DISTINCT Customer_ID) AS c FROM customer_info");
    if ($q) $stats['total_customers'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) AS c FROM staff WHERE status = 'active'");
    if ($q) $stats['total_staff'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) AS c FROM inventory_items WHERE current_stock <= min_stock_level");
    if ($q) $stats['low_stock'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $e) {
    Logger::error('Failed to fetch admin stats', ['error' => $e->getMessage()]);
}

// ══════════════════════════════════════════
// REVENUE CHART
// ══════════════════════════════════════════
$chart_labels = [];
$chart_values = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime($date));
    $chart_values[$date] = 0;
}

try {
    $from = date('Y-m-d', strtotime('-6 days'));
    $q = $conn->prepare("
        SELECT DATE(booking_date) AS d, COALESCE(SUM(total_amount),0) AS s
        FROM booking
        WHERE status = 'Completed' AND DATE(booking_date) >= ?
        GROUP BY DATE(booking_date)
    ");
    if ($q) {
        $q->bind_param('s', $from);
        $q->execute();
        $res = $q->get_result();
        while ($row = $res->fetch_assoc()) {
            if (isset($chart_values[$row['d']])) {
                $chart_values[$row['d']] = (float)$row['s'];
            }
        }
        $q->close();
    }
} catch (Exception $e) {
    Logger::error('Failed to fetch chart data', ['error' => $e->getMessage()]);
}
$chart_data = array_values($chart_values);

// ══════════════════════════════════════════
// RECENT BOOKINGS
// ══════════════════════════════════════════
$recent_bookings = [];
try {
    $q = $conn->query("
        SELECT b.Booking_ID, b.service, b.status, b.total_amount, b.booking_date,
               c.first_name, c.last_name
        FROM booking b
        LEFT JOIN customer_info c ON b.Customer_ID = c.Customer_ID
        ORDER BY b.booking_date DESC
        LIMIT 7
    ");
    if ($q) $recent_bookings = $q->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    Logger::error('Failed to fetch recent bookings', ['error' => $e->getMessage()]);
}

// ══════════════════════════════════════════
// LOW STOCK ITEMS
// ══════════════════════════════════════════
$low_stock_items = [];
try {
    $q = $conn->query("
        SELECT item_name, current_stock, min_stock_level, unit
        FROM inventory_items
        WHERE current_stock <= min_stock_level
        ORDER BY current_stock ASC
        LIMIT 5
    ");
    if ($q) $low_stock_items = $q->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    Logger::error('Failed to fetch low stock', ['error' => $e->getMessage()]);
}

function status_badge($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending':     return ['label' => 'Pending',     'color' => '#946200', 'bg' => '#FEF9E7'];
        case 'confirmed':   return ['label' => 'Confirmed',   'color' => '#00537A', 'bg' => '#EBF5FB'];
        case 'in progress': return ['label' => 'In Progress', 'color' => '#5B2E91', 'bg' => '#F4ECFB'];
        case 'completed':   return ['label' => 'Completed',   'color' => '#1E7E45', 'bg' => '#EAF7F0'];
        case 'cancelled':   return ['label' => 'Cancelled',   'color' => '#A8322D', 'bg' => '#FDEDEC'];
        default:            return ['label' => ucfirst($status), 'color' => '#5A7184', 'bg' => '#F4F7F9'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — WashFlow</title>

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

        /* ══════════════════════════════════════
           LAYOUT
           ══════════════════════════════════════ */
        .wf-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            height: 100vh;
            overflow: hidden;
        }

        /* ══════════════════════════════════════
           SIDEBAR
           ══════════════════════════════════════ */
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

        /* Brand */
        .wf-sidebar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 2rem 1.75rem;
            border-bottom: 1px solid rgba(168, 232, 249, 0.08);
        }
        .wf-sidebar-brand-logo {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
        }
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

        /* Nav */
        .wf-nav {
            padding: 1.5rem 1rem;
            flex: 1;
        }

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
        .wf-nav-item i {
            width: 22px;
            text-align: center;
            font-size: 1rem;
            transition: all 0.2s;
            flex-shrink: 0;
        }
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

        /* Sidebar footer */
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
        .wf-user-info {
            flex: 1;
            min-width: 0;
            line-height: 1.25;
        }
        .wf-user-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .wf-user-role {
            font-size: 0.72rem;
            color: rgba(168, 232, 249, 0.5);
            font-weight: 500;
        }

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
        .wf-btn-logout:hover {
            background: rgba(231, 76, 60, 0.12);
            color: #FF8B7E;
        }
        .wf-btn-logout i { width: 22px; text-align: center; font-size: 1rem; }

        /* ══════════════════════════════════════
           MAIN
           ══════════════════════════════════════ */
        .wf-main {
            padding: 2rem 2.5rem 3rem;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100vh;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .wf-main::-webkit-scrollbar { display: none; width: 0; }

        /* Topbar */
        .wf-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
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

        /* ══════════════════════════════════════
           STATS GRID
           ══════════════════════════════════════ */
        .wf-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .wf-stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem 1.5rem;
            border: 1px solid var(--border);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 155px;
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
            margin-bottom: 1.25rem;
        }
        .wf-stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
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
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.3rem 0.65rem;
            border-radius: 7px;
            background: var(--light-blue-pale);
            color: var(--primary);
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .wf-stat-chip i { font-size: 0.6rem; }

        .wf-stat-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--dark-blue);
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 0.3rem;
        }
        .wf-stat-label {
            font-size: 0.82rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* ══════════════════════════════════════
           CARDS
           ══════════════════════════════════════ */
        .wf-grid-2 {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        @media (max-width: 1200px) {
            .wf-grid-2 { grid-template-columns: 1fr; }
        }

        .wf-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            border: 1px solid var(--border);
        }

        .wf-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .wf-card-title {
            display: flex;
            align-items: center;
            gap: 0.95rem;
        }
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
        .wf-card-title-icon.icon-warn {
            background: #FEF3E2;
            color: #C2410C;
        }
        .wf-card-title h3 {
            color: var(--dark-blue);
            font-size: 1.05rem;
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
        .wf-card-link:hover {
            background: var(--light-blue-pale);
            color: var(--primary-mid);
        }

        /* ══════════════════════════════════════
           CHART
           ══════════════════════════════════════ */
        .wf-chart-wrap {
            position: relative;
            height: 210px;
            padding: 0.5rem 0;
        }
        .wf-chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 0.875rem;
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
            max-width: 40px;
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
            top: -28px;
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
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
        .wf-chart-summary-left {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }
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

        /* ══════════════════════════════════════
           LOW STOCK
           ══════════════════════════════════════ */
        .wf-stock-list {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }
        .wf-stock-item {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.9rem 1.1rem;
            border-radius: 11px;
            background: #FEF9E7;
            border: 1px solid #FCF0C5;
            transition: all 0.2s;
        }
        .wf-stock-item:hover {
            border-color: var(--gold);
            background: #FEF4D0;
        }
        .wf-stock-item-info { flex: 1; min-width: 0; }
        .wf-stock-item-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--dark-blue);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.15rem;
        }
        .wf-stock-item-level {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .wf-stock-item-qty {
            font-size: 0.88rem;
            font-weight: 800;
            color: #A8322D;
            white-space: nowrap;
        }

        .wf-empty-mini {
            text-align: center;
            padding: 2.25rem 1rem;
            color: var(--text-muted);
        }
        .wf-empty-mini i {
            font-size: 2.25rem;
            color: var(--light-blue);
            margin-bottom: 0.65rem;
            display: block;
        }
        .wf-empty-mini p {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* ══════════════════════════════════════
           TABLE
           ══════════════════════════════════════ */
        .wf-table-wrap { overflow-x: auto; margin: 0 -0.5rem; }
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
            padding: 0.85rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
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
        .wf-date { color: var(--text-muted); font-size: 0.8rem; }

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

        /* ══════════════════════════════════════
           QUICK ACTIONS
           ══════════════════════════════════════ */
        .wf-actions-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
        }
        @media (max-width: 1400px) {
            .wf-actions-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 900px) {
            .wf-actions-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .wf-action-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.35rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: white;
            color: var(--dark-blue);
            font-weight: 600;
            font-size: 0.82rem;
            transition: all 0.2s ease;
            text-align: center;
            min-height: 130px;
        }
        .wf-action-tile:hover {
            border-color: var(--primary);
            background: var(--light-blue-pale);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 83, 122, 0.08);
        }
        .wf-action-tile-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--light-blue-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: all 0.25s;
        }
        .wf-action-tile:hover .wf-action-tile-icon {
            background: var(--yellow);
            color: var(--dark-blue-deep);
            transform: scale(1.05);
        }

        /* ══════════════════════════════════════
           MOBILE SIDEBAR TOGGLE
           ══════════════════════════════════════ */
        .wf-sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            width: 44px;
            height: 44px;
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

        /* ══════════════════════════════════════
           RESPONSIVE
           ══════════════════════════════════════ */
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
                top: 0;
                left: -300px;
                width: 300px;
                z-index: 1000;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 20px 0 60px rgba(0, 0, 0, 0.3);
            }
            .wf-sidebar.open { left: 0; }
            .wf-sidebar-toggle { display: flex; }
            .wf-main { padding: 1.25rem; padding-top: 4rem; }
            .wf-topbar-greeting h1 { font-size: 1.35rem; }
            .wf-stats-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .wf-actions-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

    <button class="wf-sidebar-toggle" id="wfSidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="wf-sidebar-overlay" id="wfSidebarOverlay"></div>

    <div class="wf-layout">

        <!-- ══════════════ SIDEBAR ══════════════ -->
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
                <a href="dashboard.php" class="wf-nav-item active">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>

                <div class="wf-nav-section-label">Monitoring</div>
                <a href="admin_bookings.php" class="wf-nav-item">
                    <i class="fas fa-eye"></i>
                    <span>Bookings Monitor</span>
                </a>
                <a href="admin_payments.php" class="wf-nav-item">
                    <i class="fas fa-receipt"></i>
                    <span>Payments Monitor</span>
                </a>
                <a href="admin_tracking.php" class="wf-nav-item">
                    <i class="fas fa-route"></i>
                    <span>Tracking Monitor</span>
                </a>

                <div class="wf-nav-section-label">Operations</div>
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
                <a href="reports.php" class="wf-nav-item">
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

        <!-- ══════════════ MAIN ══════════════ -->
        <main class="wf-main">

            <!-- TOPBAR -->
            <div class="wf-topbar">
                <div class="wf-topbar-greeting">
                    <h1><?= e($greeting) ?>, <span class="accent">Admin</span></h1>
                    <p>Here's your business overview for today</p>
                </div>
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>

            <!-- STATS -->
            <div class="wf-stats-grid">

                <div class="wf-stat-card">
                    <div class="wf-stat-top">
                        <div class="wf-stat-icon icon-yellow"><i class="fas fa-coins"></i></div>
                        <span class="wf-stat-chip"><i class="fas fa-arrow-up"></i> ₱<?= number_format($stats['revenue_today'], 0) ?> today</span>
                    </div>
                    <div>
                        <div class="wf-stat-value">₱<?= number_format($stats['total_revenue'], 2) ?></div>
                        <div class="wf-stat-label">Total Revenue</div>
                    </div>
                </div>

                <div class="wf-stat-card">
                    <div class="wf-stat-top">
                        <div class="wf-stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
                    </div>
                    <div>
                        <div class="wf-stat-value"><?= number_format($stats['total_bookings']) ?></div>
                        <div class="wf-stat-label">Total Bookings</div>
                    </div>
                </div>

                <div class="wf-stat-card">
                    <div class="wf-stat-top">
                        <div class="wf-stat-icon icon-dark"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                    <div>
                        <div class="wf-stat-value"><?= number_format($stats['pending']) ?></div>
                        <div class="wf-stat-label">Pending Orders</div>
                    </div>
                </div>

                <div class="wf-stat-card">
                    <div class="wf-stat-top">
                        <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div>
                        <div class="wf-stat-value"><?= number_format($stats['completed_today']) ?></div>
                        <div class="wf-stat-label">Completed Today</div>
                    </div>
                </div>

                <div class="wf-stat-card">
                    <div class="wf-stat-top">
                        <div class="wf-stat-icon icon-purple"><i class="fas fa-users"></i></div>
                    </div>
                    <div>
                        <div class="wf-stat-value"><?= number_format($stats['total_customers']) ?></div>
                        <div class="wf-stat-label">Total Customers</div>
                    </div>
                </div>

                <div class="wf-stat-card">
                    <div class="wf-stat-top">
                        <div class="wf-stat-icon icon-blue"><i class="fas fa-user-tie"></i></div>
                    </div>
                    <div>
                        <div class="wf-stat-value"><?= number_format($stats['total_staff']) ?></div>
                        <div class="wf-stat-label">Active Staff</div>
                    </div>
                </div>

            </div>

            <!-- CHART + LOW STOCK -->
            <div class="wf-grid-2">

                <div class="wf-card">
                    <div class="wf-card-header">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h3>Revenue Overview</h3>
                                <p>Last 7 days performance</p>
                            </div>
                        </div>
                    </div>

                    <?php
                    $max_val = max($chart_data);
                    if ($max_val <= 0) $max_val = 1;
                    ?>
                    <div class="wf-chart-wrap">
                        <div class="wf-chart-bars">
                            <?php foreach ($chart_labels as $i => $label):
                                $val = $chart_data[$i];
                                $pct = ($val / $max_val) * 100;
                                $pct = max($pct, 3);
                            ?>
                            <div class="wf-chart-bar-group">
                                <div class="wf-chart-bar-wrap">
                                    <div class="wf-chart-bar" style="height: <?= $pct ?>%;">
                                        <span class="wf-chart-bar-value">₱<?= number_format($val, 0) ?></span>
                                    </div>
                                </div>
                                <div class="wf-chart-label"><?= e($label) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wf-chart-summary">
                        <div class="wf-chart-summary-left">
                            <div class="wf-chart-summary-value">₱<?= number_format(array_sum($chart_data), 2) ?></div>
                            <div class="wf-chart-summary-label">this week</div>
                        </div>
                        <div class="wf-chart-summary-chip">
                            <i class="fas fa-trophy"></i>
                            7-day total
                        </div>
                    </div>
                </div>

                <div class="wf-card">
                    <div class="wf-card-header">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon icon-warn">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h3>Low Stock Alert</h3>
                                <p><?= $stats['low_stock'] ?> item(s) need attention</p>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($low_stock_items)): ?>
                    <div class="wf-empty-mini">
                        <i class="fas fa-check-circle"></i>
                        <p>All items are well-stocked</p>
                    </div>
                    <?php else: ?>
                    <div class="wf-stock-list">
                        <?php foreach ($low_stock_items as $item): ?>
                        <div class="wf-stock-item">
                            <div class="wf-stock-item-info">
                                <div class="wf-stock-item-name"><?= e($item['item_name']) ?></div>
                                <div class="wf-stock-item-level">Min: <?= (float)$item['min_stock_level'] ?> <?= e($item['unit']) ?></div>
                            </div>
                            <div class="wf-stock-item-qty">
                                <?= (float)$item['current_stock'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- RECENT BOOKINGS -->
            <div class="wf-card" style="margin-bottom: 1.25rem;">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <h3>Recent Bookings</h3>
                            <p>Latest activity across the system</p>
                        </div>
                    </div>
                    <a href="admin_bookings.php" class="wf-card-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (empty($recent_bookings)): ?>
                <div class="wf-empty-mini">
                    <i class="fas fa-inbox"></i>
                    <p>No bookings yet</p>
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
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $b):
                                $badge = status_badge($b['status']);
                                $customer_name = trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? ''));
                                if ($customer_name === '') $customer_name = 'Walk-in';
                            ?>
                            <tr>
                                <td><span class="wf-booking-id">#<?= e($b['Booking_ID']) ?></span></td>
                                <td><span class="wf-customer"><?= e($customer_name) ?></span></td>
                                <td><span class="wf-service"><?= e($b['service'] ?? '-') ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format((float)$b['total_amount'], 2) ?></span></td>
                                <td>
                                    <span class="wf-badge" style="color: <?= $badge['color'] ?>; background: <?= $badge['bg'] ?>;">
                                        <span class="wf-badge-dot"></span>
                                        <?= e($badge['label']) ?>
                                    </span>
                                </td>
                                <td><span class="wf-date"><?= date('M j, Y', strtotime($b['booking_date'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="wf-card">
                <div class="wf-card-header">
                    <div class="wf-card-title">
                        <div class="wf-card-title-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h3>Quick Actions</h3>
                            <p>Common admin tasks</p>
                        </div>
                    </div>
                </div>

                <div class="wf-actions-grid">
                    <a href="admin_bookings.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-plus-circle"></i></div>
                        <span>New Booking</span>
                    </a>
                    <a href="staff_management.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-user-plus"></i></div>
                        <span>Add Staff</span>
                    </a>
                    <a href="inventory.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-boxes"></i></div>
                        <span>Inventory</span>
                    </a>
                    <a href="reports.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <span>Reports</span>
                    </a>
                    <a href="complaints.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-headset"></i></div>
                        <span>Complaints</span>
                    </a>
                    <a href="system_logs.php" class="wf-action-tile">
                        <div class="wf-action-tile-icon"><i class="fas fa-history"></i></div>
                        <span>Activity Logs</span>
                    </a>
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const sidebar = document.getElementById('wfSidebar');
            const sidebarOverlay = document.getElementById('wfSidebarOverlay');
            const sidebarToggle = document.getElementById('wfSidebarToggle');

            if (sidebarToggle && sidebar && sidebarOverlay) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    sidebarOverlay.classList.add('open');
                });
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('open');
                });
            }
        })();
    </script>
</body>
</html>