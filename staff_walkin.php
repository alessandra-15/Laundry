<?php
/**
 * staff_walkin.php
 * WashFlow — Staff Walk-in Booking Creation
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* AUTH — STAFF ONLY */
if (empty($_SESSION['staff_id'])) {
    if (!empty($_SESSION['admin_id'])) {
        header('Location: dashboard.php');
        exit();
    }
    header('Location: staff_login.php');
    exit();
}

$staff_id      = (int)$_SESSION['staff_id'];
$staff_name    = $_SESSION['staff_name'] ?? $_SESSION['staff_username'] ?? 'Staff';
$staff_role    = $_SESSION['staff_role'] ?? 'washer';
$staff_initial = strtoupper(substr($staff_name, 0, 1));

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$role_meta = [
    'washer'     => ['label' => 'Washer',     'icon' => 'fa-soap'],
    'dryer'      => ['label' => 'Dryer',      'icon' => 'fa-wind'],
    'ironing'    => ['label' => 'Ironing',    'icon' => 'fa-tshirt'],
    'packer'     => ['label' => 'Packer',     'icon' => 'fa-box'],
    'cashier'    => ['label' => 'Cashier',    'icon' => 'fa-cash-register'],
    'supervisor' => ['label' => 'Supervisor', 'icon' => 'fa-user-shield'],
];
$my_role = $role_meta[$staff_role] ?? $role_meta['washer'];

/* POST — CREATE WALK-IN BOOKING */
$flash = null;
$last_booking = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $customer_type     = $_POST['customer_type'] ?? 'guest';
    $customer_id       = (int)($_POST['customer_id'] ?? 0);
    $guest_name        = trim($_POST['guest_name'] ?? '');
    $guest_contact     = trim($_POST['guest_contact'] ?? '');
    $guest_address     = trim($_POST['guest_address'] ?? '');

    $services          = $_POST['services'] ?? [];
    $addons            = $_POST['addons'] ?? [];
    $special_notes     = trim($_POST['special_instructions'] ?? '');
    $weight            = (float)($_POST['weight'] ?? 0);
    $payment_method    = $_POST['payment_method'] ?? 'Cash';
    $payment_status    = $_POST['payment_status'] ?? 'Unpaid';

    try {
        if (empty($services)) {
            throw new Exception('Please select at least one service.');
        }
        if ($weight <= 0) {
            throw new Exception('Please enter the weight.');
        }

        /* Resolve customer_id */
        if ($customer_type === 'guest') {
            if ($guest_name === '') {
                throw new Exception('Guest name is required.');
            }
            $found_id = 0;
            if ($guest_contact !== '') {
                $chk = $conn->prepare("SELECT Customer_ID FROM customer_info WHERE contact_number = ? LIMIT 1");
                $chk->bind_param('s', $guest_contact);
                $chk->execute();
                $row = $chk->get_result()->fetch_assoc();
                $chk->close();
                if ($row) $found_id = (int)$row['Customer_ID'];
            }

            if ($found_id <= 0) {
                $default_pass = password_hash('walkin_' . bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
                $name_parts   = explode(' ', $guest_name, 2);
                $first_name   = $name_parts[0];
                $last_name    = $name_parts[1] ?? '';

                $ins = $conn->prepare("
                    INSERT INTO customer_info
                        (first_name, last_name, email, contact_number, Address, password, created_at, updated_at)
                    VALUES (?, ?, NULL, ?, ?, ?, NOW(), NOW())
                ");
                $ins->bind_param('sssss', $first_name, $last_name, $guest_contact, $guest_address, $default_pass);
                $ins->execute();
                $found_id = (int)$ins->insert_id;
                $ins->close();
            }
            $customer_id = $found_id;
        }

        if ($customer_id <= 0) {
            throw new Exception('Customer is required.');
        }

        /* Compute total */
        $services_list = [];
        foreach ($services as $sid) {
            $sid = (int)$sid;
            if ($sid > 0) $services_list[] = $sid;
        }

        $base_total = 0;
        $service_names = [];
        if (!empty($services_list)) {
            $ph = implode(',', array_fill(0, count($services_list), '?'));
            $types = str_repeat('i', count($services_list));
            $stmt = $conn->prepare("SELECT service_id, service_name, price_fixed, price_per_kg FROM services WHERE service_id IN ($ph)");
            $stmt->bind_param($types, ...$services_list);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($s = $res->fetch_assoc()) {
                $service_names[] = $s['service_name'];
                if ((float)$s['price_per_kg'] > 0) {
                    $base_total += (float)$s['price_per_kg'] * $weight;
                } else {
                    $base_total += (float)$s['price_fixed'];
                }
            }
            $stmt->close();
        }

        /* Add-ons */
        $addon_total = 0;
        $addon_names = [];
        if (!empty($addons)) {
            foreach ($addons as $aid) {
                $aid = (int)$aid;
                if ($aid <= 0) continue;
                $a_stmt = $conn->prepare("SELECT addon_name, addon_price FROM add_ons WHERE addon_id = ? LIMIT 1");
                $a_stmt->bind_param('i', $aid);
                $a_stmt->execute();
                $a = $a_stmt->get_result()->fetch_assoc();
                $a_stmt->close();
                if ($a) {
                    $addon_total += (float)$a['addon_price'];
                    $addon_names[] = $a['addon_name'] . ' (+₱' . number_format((float)$a['addon_price'], 2) . ')';
                }
            }
        }

        $total_amount = $base_total + $addon_total;
        $service_str  = implode(', ', $service_names);
        $addon_str    = !empty($addon_names) ? implode(', ', $addon_names) : 'None';

        /* Create schedule */
        $sched = $conn->prepare("
            INSERT INTO schedule
                (Customer_ID, date, time, pick_deliver, drop_off_time, service, add_ons, admin_confirmation)
            VALUES (?, CURDATE(), CURTIME(), 'walkin', ?, ?, ?, 'Approved')
        ");
        $drop_off = date('g:i A');
        $sched->bind_param('isss', $customer_id, $drop_off, $service_str, $addon_str);
        $sched->execute();
        $schedule_id = (int)$sched->insert_id;
        $sched->close();

        /* Create booking */
        $stmt = $conn->prepare("
            INSERT INTO booking
                (Customer_ID, Admin_ID, Schedule_ID, service, add_ons, pick_deliver,
                 special_instructions, status, total_amount, booking_date)
            VALUES (?, NULL, ?, ?, ?, 'walkin', ?, 'Pending', ?, NOW())
        ");
        $stmt->bind_param('iisssd',
            $customer_id, $schedule_id, $service_str, $addon_str,
            $special_notes, $total_amount
        );
        $stmt->execute();
        $new_booking_id = (int)$stmt->insert_id;
        $stmt->close();

        /* Create payment */
        $pay_stmt = $conn->prepare("
            INSERT INTO payments
                (customer_id, booking_id, amount, payment_method, payment_status, payment_date, remarks)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $remarks = 'Walk-in booking created by staff ' . $staff_name;
        $pay_stmt->bind_param('iidsss',
            $customer_id, $new_booking_id, $total_amount,
            $payment_method, $payment_status, $remarks
        );
        $pay_stmt->execute();
        $pay_stmt->close();

        if (class_exists('Logger')) {
            Logger::info('Walk-in booking created', [
                'staff_id'       => $staff_id,
                'booking_id'     => $new_booking_id,
                'customer_id'    => $customer_id,
                'total'          => $total_amount,
                'payment_status' => $payment_status,
            ]);
        }

        $last_booking = [
            'id'             => $new_booking_id,
            'customer_id'    => $customer_id,
            'service'        => $service_str,
            'addons'         => $addon_str,
            'weight'         => $weight,
            'total'          => $total_amount,
            'payment_method' => $payment_method,
            'payment_status' => $payment_status,
            'notes'          => $special_notes,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $flash = ['type' => 'success', 'message' => "Walk-in booking #$new_booking_id created!"];

    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Walk-in creation failed', ['error' => $ex->getMessage()]);
        $flash = ['type' => 'error', 'message' => $ex->getMessage()];
    }
}

/* FETCH — Services, Add-ons, Recent customers */
$services = [];
try {
    $q = $conn->query("SELECT service_id, service_name, price_fixed, price_per_kg FROM services ORDER BY service_id");
    if ($q) $services = $q->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {}

$addons = [];
try {
    $q = $conn->query("SELECT addon_id, service_id, addon_name, addon_price FROM add_ons ORDER BY addon_name");
    if ($q) $addons = $q->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {}

$recent_customers = [];
try {
    $q = $conn->query("
        SELECT DISTINCT c.Customer_ID, c.first_name, c.last_name, c.contact_number, c.Address
        FROM booking b
        JOIN customer_info c ON c.Customer_ID = b.Customer_ID
        WHERE b.pick_deliver = 'walkin'
        ORDER BY b.booking_date DESC
        LIMIT 10
    ");
    if ($q) $recent_customers = $q->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {}

$today_stats = ['count' => 0, 'revenue' => 0.0];
try {
    $q = $conn->query("
        SELECT COUNT(DISTINCT Booking_ID) c, COALESCE(SUM(total_amount),0) s
        FROM booking
        WHERE pick_deliver = 'walkin' AND DATE(booking_date) = CURDATE()
    ");
    if ($q) {
        $row = $q->fetch_assoc();
        $today_stats['count'] = (int)$row['c'];
        $today_stats['revenue'] = (float)$row['s'];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Walk-in Booking — WashFlow Staff</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --dark-blue:#063452; --dark-blue-deep:#042640; --primary:#005A85; --primary-mid:#0076A8;
    --light-blue:#A8E8F9; --light-blue-soft:#E8F6FC; --light-blue-pale:#F2FAFD;
    --yellow:#FFD93D; --yellow-soft:#FFF9DB; --yellow-dark:#B88A00;
    --bg:#F5F9FC; --card:#FFFFFF;
    --text-primary:#0A2540; --text-secondary:#5A7184; --text-muted:#94A9B8;
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

.wf-sidebar { background:var(--dark-blue-deep); color:white; height:100vh; overflow-y:auto; overflow-x:hidden; display:flex; flex-direction:column; scrollbar-width:none; }
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

.wf-main { padding:2rem 2.5rem 3rem; overflow-y:auto; overflow-x:hidden; height:100vh; scrollbar-width:none; }
.wf-main::-webkit-scrollbar { display:none; width:0; }

.wf-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; gap:1rem; flex-wrap:wrap; }
.wf-topbar-greeting h1 { color:var(--dark-blue); font-size:1.6rem; font-weight:800; margin:0; letter-spacing:-0.03em; line-height:1.3; display:flex; align-items:center; gap:0.6rem; }
.wf-topbar-greeting h1 .accent { color:var(--gold); }
.wf-topbar-greeting p { color:var(--text-secondary); font-size:0.88rem; margin:0.2rem 0 0; font-weight:500; }
.wf-topbar-right { display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; }
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

.wf-flash { padding:0.9rem 1.15rem; border-radius:12px; margin-bottom:1.25rem; font-weight:600; font-size:0.88rem; display:flex; align-items:center; gap:0.7rem; animation:flashIn 0.35s; }
@keyframes flashIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.wf-flash.success { background:#EAF7F0; color:#1E7E45; border-left:4px solid var(--green); }
.wf-flash.error { background:#FDEDEC; color:#A8322D; border-left:4px solid var(--red); }

.wf-stats-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:1rem; margin-bottom:1.5rem; }
.wf-stat-card { background:white; border-radius:14px; padding:1.25rem; border:1px solid var(--border); display:flex; flex-direction:column; justify-content:space-between; min-height:110px; }
.wf-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.6rem; }
.wf-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-stat-icon.icon-blue { background:var(--light-blue-soft); color:var(--primary); }
.wf-stat-icon.icon-green { background:#EAF7F0; color:#1E7E45; }
.wf-stat-value { font-size:1.6rem; font-weight:800; color:var(--dark-blue); line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.2rem; }
.wf-stat-label { font-size:0.78rem; color:var(--text-secondary); font-weight:500; }

.wf-grid-2 { display:grid; grid-template-columns:1.7fr 1fr; gap:1.25rem; align-items:flex-start; }
@media (max-width:1100px) { .wf-grid-2 { grid-template-columns:1fr; } }

.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }
.wf-card:last-child { margin-bottom:0; }
.wf-card-head { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.wf-card-title { display:flex; align-items:center; gap:0.9rem; }
.wf-card-title-icon { width:42px; height:42px; border-radius:11px; background:var(--light-blue-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-card-title-icon.gold { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-card-title-icon.green { background:#EAF7F0; color:#1E7E45; }
.wf-card-title h3 { font-size:1rem; font-weight:700; color:var(--dark-blue); margin:0; }
.wf-card-title p { font-size:0.75rem; color:var(--text-muted); margin:0.1rem 0 0; font-weight:500; }
.wf-card-body { padding:1.5rem; }

.wf-section-label {
    font-size:0.72rem; font-weight:700; color:var(--text-muted);
    text-transform:uppercase; letter-spacing:0.08em;
    margin-bottom:0.75rem;
    display:flex; align-items:center; gap:0.5rem;
}
.wf-section-label i { color:var(--primary); }

.wf-form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
@media (max-width:700px) { .wf-form-row { grid-template-columns:1fr; } }

.wf-form-group { margin-bottom:0.75rem; }
.wf-form-group:last-child { margin-bottom:0; }
.wf-label { display:block; font-size:0.75rem; font-weight:700; color:var(--dark-blue); margin-bottom:0.4rem; }
.wf-label .req { color:var(--red); }

.wf-input, .wf-select, .wf-textarea {
    width:100%;
    padding:0.7rem 0.95rem;
    border:1.5px solid var(--border);
    border-radius:10px;
    font-size:0.85rem; font-family:inherit;
    color:var(--text-primary); background:white;
    transition:all 0.2s; outline:none;
}
.wf-input:focus, .wf-select:focus, .wf-textarea:focus {
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(0,90,133,0.1);
}
.wf-textarea { resize:vertical; min-height:70px; }

.wf-type-tabs { display:flex; gap:0.4rem; background:var(--light-blue-pale); padding:0.35rem; border-radius:10px; margin-bottom:1rem; }
.wf-type-tab { flex:1; padding:0.6rem 0.9rem; text-align:center; border-radius:7px; font-size:0.82rem; font-weight:700; color:var(--text-secondary); cursor:pointer; transition:all 0.2s; border:none; background:transparent; font-family:inherit; }
.wf-type-tab.active { background:white; color:var(--dark-blue); box-shadow:0 2px 8px rgba(6,52,82,0.08); }

.wf-check-list { display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; }
@media (max-width:600px) { .wf-check-list { grid-template-columns:1fr; } }
.wf-check-item { display:flex; align-items:center; gap:0.7rem; padding:0.85rem 1rem; border:1.5px solid var(--border); border-radius:10px; cursor:pointer; transition:all 0.2s; background:white; }
.wf-check-item:hover { border-color:var(--light-blue); background:var(--light-blue-pale); }
.wf-check-item input { display:none; }
.wf-check-item input:checked + .check-box { background:var(--primary); border-color:var(--primary); }
.wf-check-item input:checked + .check-box::after { opacity:1; transform:scale(1); }
.wf-check-item input:checked ~ .check-label { color:var(--primary); font-weight:700; }
.wf-check-item.checked { border-color:var(--primary); background:var(--light-blue-pale); }
.check-box {
    width:20px; height:20px; border-radius:6px;
    border:2px solid var(--border); background:white;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; transition:all 0.15s; position:relative;
}
.check-box::after {
    content:'\f00c'; font-family:'Font Awesome 6 Free'; font-weight:900;
    color:white; font-size:0.65rem;
    opacity:0; transform:scale(0.5); transition:all 0.15s;
}
.check-label { flex:1; font-size:0.83rem; font-weight:500; color:var(--text-primary); line-height:1.3; }
.check-price { font-size:0.75rem; color:var(--text-muted); font-weight:600; white-space:nowrap; }

.wf-total-box {
    background:linear-gradient(135deg, var(--dark-blue) 0%, var(--primary) 100%);
    color:white; border-radius:14px; padding:1.25rem 1.5rem;
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:1.25rem;
    position:relative; overflow:hidden;
}
.wf-total-box::before {
    content:''; position:absolute; right:-40px; top:-40px;
    width:150px; height:150px; border-radius:50%;
    background:rgba(255,217,61,0.08);
}
.wf-total-box-left { position:relative; z-index:1; }
.wf-total-box-label { font-size:0.75rem; color:rgba(255,255,255,0.7); font-weight:600; text-transform:uppercase; letter-spacing:0.08em; }
.wf-total-box-value { font-size:1.9rem; font-weight:800; color:white; line-height:1.1; letter-spacing:-0.03em; margin-top:0.15rem; }
.wf-total-box-icon {
    width:56px; height:56px; border-radius:14px;
    background:rgba(255,217,61,0.15); color:var(--yellow);
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; position:relative; z-index:1;
    border:1px solid rgba(255,217,61,0.3);
}

.wf-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:0.5rem;
    padding:0.85rem 1.5rem; border-radius:10px;
    font-size:0.88rem; font-weight:800;
    cursor:pointer; transition:all 0.2s;
    border:none; font-family:inherit;
}
.wf-btn-primary { background:var(--primary); color:white; }
.wf-btn-primary:hover { background:var(--primary-mid); transform:translateY(-1px); }
.wf-btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
.wf-btn-outline { background:white; color:var(--primary); border:1.5px solid var(--border); }
.wf-btn-outline:hover { border-color:var(--primary); background:var(--light-blue-pale); }
.wf-btn-block { width:100%; }

.wf-receipt {
    background:#FAFCFE; border:1.5px dashed var(--border);
    border-radius:12px; padding:1.25rem 1.4rem;
    font-family:'SF Mono', Monaco, monospace;
    font-size:0.78rem; line-height:1.7;
}
.wf-receipt-head { text-align:center; padding-bottom:0.75rem; border-bottom:1px dashed var(--border); margin-bottom:0.75rem; }
.wf-receipt-head h4 { font-size:1rem; font-weight:800; color:var(--dark-blue); margin:0; letter-spacing:-0.02em; font-family:'Plus Jakarta Sans', sans-serif; }
.wf-receipt-head p { font-size:0.7rem; color:var(--text-muted); margin:0.15rem 0 0; }
.wf-receipt-row { display:flex; justify-content:space-between; padding:0.2rem 0; }
.wf-receipt-row.total { border-top:1px dashed var(--border); padding-top:0.5rem; margin-top:0.5rem; font-weight:800; color:var(--dark-blue); font-size:0.9rem; }
.wf-receipt-row .label { color:var(--text-muted); }
.wf-receipt-row .value { color:var(--dark-blue); font-weight:700; }

.wf-recent-item {
    display:flex; align-items:center; gap:0.85rem;
    padding:0.7rem 0.9rem; border-radius:10px;
    background:#FAFCFE; border:1px solid var(--border-soft);
    margin-bottom:0.5rem; cursor:pointer; transition:all 0.15s;
}
.wf-recent-item:hover { background:var(--light-blue-pale); border-color:var(--light-blue); }
.wf-recent-avatar {
    width:36px; height:36px; border-radius:9px;
    background:linear-gradient(135deg, var(--light-blue-soft) 0%, var(--light-blue-pale) 100%);
    color:var(--primary);
    display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:0.9rem; flex-shrink:0;
}
.wf-recent-info { flex:1; min-width:0; line-height:1.3; }
.wf-recent-name { font-size:0.83rem; font-weight:700; color:var(--dark-blue); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wf-recent-contact { font-size:0.7rem; color:var(--text-muted); font-weight:500; }

.wf-empty-mini { text-align:center; padding:2rem 1rem; color:var(--text-muted); }
.wf-empty-mini i { font-size:2rem; color:var(--light-blue); margin-bottom:0.6rem; display:block; }
.wf-empty-mini p { margin:0; font-size:0.82rem; font-weight:500; }

.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
    .wf-stats-grid { grid-template-columns:1fr; }
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
                <span class="wf-sidebar-brand-tag">Staff Panel</span>
            </div>
        </div>

        <nav class="wf-nav">
            <div class="wf-nav-section-label">My Work</div>
            <a href="staff_dashboard.php" class="wf-nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="booking_management.php" class="wf-nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>My Queue</span>
            </a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="staff_walkin.php" class="wf-nav-item active">
                <i class="fas fa-walking"></i>
                <span>Walk-in Booking</span>
            </a>
            <a href="staff_payments.php" class="wf-nav-item">
                <i class="fas fa-money-check-alt"></i>
                <span>Verify Payments</span>
            </a>
            <a href="staff_inventory.php" class="wf-nav-item">
                <i class="fas fa-flask"></i>
                <span>Log Inventory</span>
            </a>

            <div class="wf-nav-section-label">Support</div>
            <a href="staff_feedback.php" class="wf-nav-item">
                <i class="fas fa-star"></i>
                <span>Reply Feedback</span>
            </a>
            <a href="staff_complaints.php" class="wf-nav-item">
                <i class="fas fa-headset"></i>
                <span>Handle Complaints</span>
            </a>

            <div class="wf-nav-section-label">Account</div>
            <a href="staff_shift.php" class="wf-nav-item">
                <i class="fas fa-clock"></i>
                <span>My Shift</span>
            </a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e($staff_initial) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($staff_name) ?></div>
                    <div class="wf-user-role"><?= e($my_role['label']) ?></div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="wf-main">

        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1><i class="fas fa-walking" style="color:var(--primary);"></i> Walk-in <span class="accent">Booking</span></h1>
                <p>Create a booking for a customer at the shop</p>
            </div>
            <div class="wf-topbar-right">
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="wf-flash <?= e($flash['type']) ?>">
            <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span><?= e($flash['message']) ?></span>
        </div>
        <?php endif; ?>

        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-walking"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($today_stats['count']) ?></div>
                    <div class="wf-stat-label">Walk-in Bookings Today</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-coins"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($today_stats['revenue'], 2) ?></div>
                    <div class="wf-stat-label">Revenue Today (Walk-in)</div>
                </div>
            </div>
        </div>

        <div class="wf-grid-2">

            <div>
                <?php if ($last_booking): ?>
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon green"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <h3>Booking Created Successfully</h3>
                                <p>Receipt for walk-in customer</p>
                            </div>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <div class="wf-receipt">
                            <div class="wf-receipt-head">
                                <h4>WashFlow Laundry</h4>
                                <p>Walk-in Receipt</p>
                            </div>

                            <div class="wf-receipt-row">
                                <span class="label">Booking ID:</span>
                                <span class="value">#<?= e($last_booking['id']) ?></span>
                            </div>
                            <div class="wf-receipt-row">
                                <span class="label">Customer ID:</span>
                                <span class="value">#<?= e($last_booking['customer_id']) ?></span>
                            </div>
                            <div class="wf-receipt-row">
                                <span class="label">Date:</span>
                                <span class="value"><?= date('M j, Y g:i A', strtotime($last_booking['created_at'])) ?></span>
                            </div>
                            <div class="wf-receipt-row">
                                <span class="label">Service:</span>
                                <span class="value"><?= e($last_booking['service']) ?></span>
                            </div>
                            <div class="wf-receipt-row">
                                <span class="label">Add-ons:</span>
                                <span class="value"><?= e($last_booking['addons']) ?></span>
                            </div>
                            <div class="wf-receipt-row">
                                <span class="label">Weight:</span>
                                <span class="value"><?= number_format($last_booking['weight'], 2) ?> kg</span>
                            </div>

                            <div class="wf-receipt-row total">
                                <span class="label">TOTAL:</span>
                                <span class="value">₱<?= number_format($last_booking['total'], 2) ?></span>
                            </div>

                            <div class="wf-receipt-row" style="margin-top:0.5rem;">
                                <span class="label">Payment:</span>
                                <span class="value"><?= e($last_booking['payment_method']) ?></span>
                            </div>
                            <div class="wf-receipt-row">
                                <span class="label">Status:</span>
                                <span class="value" style="color:<?= $last_booking['payment_status'] === 'Paid' ? '#1E7E45' : '#A8322D' ?>;">
                                    <?= e($last_booking['payment_status']) ?>
                                </span>
                            </div>
                            <?php if (!empty($last_booking['notes'])): ?>
                            <div class="wf-receipt-row">
                                <span class="label">Notes:</span>
                                <span class="value"><?= e($last_booking['notes']) ?></span>
                            </div>
                            <?php endif; ?>

                            <div style="text-align:center;padding-top:0.75rem;border-top:1px dashed var(--border);margin-top:0.75rem;font-size:0.7rem;color:var(--text-muted);">
                                Processed by: <?= e($staff_name) ?><br>
                                Thank you!
                            </div>
                        </div>

                        <div style="display:flex;gap:0.5rem;margin-top:1.25rem;flex-wrap:wrap;">
                            <button type="button" class="wf-btn wf-btn-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                            <a href="staff_walkin.php" class="wf-btn wf-btn-outline">
                                <i class="fas fa-plus"></i> New Walk-in
                            </a>
                            <a href="booking_management.php?tab=walkin" class="wf-btn wf-btn-outline">
                                <i class="fas fa-clipboard-list"></i> View Queue
                            </a>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <form method="POST" id="walkinForm">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="customer_type" id="customerTypeField" value="guest">

                    <div class="wf-card">
                        <div class="wf-card-head">
                            <div class="wf-card-title">
                                <div class="wf-card-title-icon"><i class="fas fa-user"></i></div>
                                <div>
                                    <h3>Customer Information</h3>
                                    <p>Existing customer or new walk-in</p>
                                </div>
                            </div>
                        </div>
                        <div class="wf-card-body">

                            <div class="wf-type-tabs">
                                <button type="button" class="wf-type-tab active" data-type="guest">
                                    <i class="fas fa-user-plus"></i> New / Guest
                                </button>
                                <button type="button" class="wf-type-tab" data-type="existing">
                                    <i class="fas fa-user-check"></i> Existing Customer
                                </button>
                            </div>

                            <div id="guestPanel">
                                <div class="wf-form-row">
                                    <div class="wf-form-group">
                                        <label class="wf-label">Full Name <span class="req">*</span></label>
                                        <input type="text" name="guest_name" class="wf-input" id="guestName"
                                               placeholder="Juan Dela Cruz" maxlength="100">
                                    </div>
                                    <div class="wf-form-group">
                                        <label class="wf-label">Contact Number</label>
                                        <input type="text" name="guest_contact" class="wf-input"
                                               placeholder="09XX XXX XXXX" maxlength="20">
                                    </div>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Address</label>
                                    <input type="text" name="guest_address" class="wf-input"
                                           placeholder="Optional address" maxlength="255">
                                </div>
                            </div>

                            <div id="existingPanel" style="display:none;">
                                <div class="wf-form-group">
                                    <label class="wf-label">Select Customer <span class="req">*</span></label>
                                    <select name="customer_id" class="wf-select" id="existingSelect">
                                        <option value="">— Select from recent —</option>
                                        <?php foreach ($recent_customers as $rc):
                                            $full = trim(($rc['first_name'] ?? '') . ' ' . ($rc['last_name'] ?? ''));
                                        ?>
                                        <option value="<?= (int)$rc['Customer_ID'] ?>"
                                                data-name="<?= e($full) ?>"
                                                data-contact="<?= e($rc['contact_number'] ?? '') ?>"
                                                data-address="<?= e($rc['Address'] ?? '') ?>">
                                            #<?= (int)$rc['Customer_ID'] ?> — <?= e($full ?: 'Customer') ?>
                                            <?= !empty($rc['contact_number']) ? ' (' . e($rc['contact_number']) . ')' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span style="font-size:0.72rem;color:var(--text-muted);margin-top:0.35rem;display:block;">
                                        Only shows recent walk-in customers.
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="wf-card">
                        <div class="wf-card-head">
                            <div class="wf-card-title">
                                <div class="wf-card-title-icon"><i class="fas fa-soap"></i></div>
                                <div>
                                    <h3>Services & Add-ons</h3>
                                    <p>Select at least one service</p>
                                </div>
                            </div>
                        </div>
                        <div class="wf-card-body">

                            <div class="wf-section-label"><i class="fas fa-soap"></i> Services <span style="color:var(--red);">*</span></div>
                            <div class="wf-check-list" style="margin-bottom:1.25rem;">
                                <?php foreach ($services as $s):
                                    $price_label = (float)$s['price_per_kg'] > 0
                                        ? '₱' . number_format((float)$s['price_per_kg'], 2) . '/kg'
                                        : '₱' . number_format((float)$s['price_fixed'], 2);
                                ?>
                                <label class="wf-check-item">
                                    <input type="checkbox" name="services[]" value="<?= (int)$s['service_id'] ?>"
                                           data-price="<?= e($s['price_per_kg'] > 0 ? $s['price_per_kg'] : $s['price_fixed']) ?>"
                                           data-perkg="<?= $s['price_per_kg'] > 0 ? '1' : '0' ?>">
                                    <span class="check-box"></span>
                                    <span class="check-label"><?= e($s['service_name']) ?></span>
                                    <span class="check-price"><?= e($price_label) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="wf-section-label"><i class="fas fa-plus-circle"></i> Add-ons (Optional)</div>
                            <div class="wf-check-list">
                                <?php foreach ($addons as $a): ?>
                                <label class="wf-check-item">
                                    <input type="checkbox" name="addons[]" value="<?= (int)$a['addon_id'] ?>"
                                           data-price="<?= e($a['addon_price']) ?>">
                                    <span class="check-box"></span>
                                    <span class="check-label"><?= e($a['addon_name']) ?></span>
                                    <span class="check-price">+₱<?= number_format((float)$a['addon_price'], 2) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-top:1.25rem;">
                                <div class="wf-form-row">
                                    <div class="wf-form-group">
                                        <label class="wf-label">Weight (kg) <span class="req">*</span></label>
                                        <input type="number" name="weight" id="weightInput" class="wf-input"
                                               step="0.01" min="0.1" max="999" placeholder="0.00" required>
                                    </div>
                                    <div class="wf-form-group">
                                        <label class="wf-label">Special Instructions</label>
                                        <input type="text" name="special_instructions" class="wf-input"
                                               placeholder="Optional notes" maxlength="255">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="wf-card">
                        <div class="wf-card-head">
                            <div class="wf-card-title">
                                <div class="wf-card-title-icon gold"><i class="fas fa-money-bill-wave"></i></div>
                                <div>
                                    <h3>Payment</h3>
                                    <p>How will the customer pay?</p>
                                </div>
                            </div>
                        </div>
                        <div class="wf-card-body">
                            <div class="wf-form-row">
                                <div class="wf-form-group">
                                    <label class="wf-label">Payment Method</label>
                                    <select name="payment_method" class="wf-select">
                                        <option value="Cash">Cash</option>
                                        <option value="GCash">GCash</option>
                                        <option value="Card">Card</option>
                                    </select>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Payment Status</label>
                                    <select name="payment_status" class="wf-select">
                                        <option value="Paid">Paid (bayad na)</option>
                                        <option value="Unpaid">Unpaid (bayad later)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wf-total-box">
                        <div class="wf-total-box-left">
                            <div class="wf-total-box-label">Total Amount</div>
                            <div class="wf-total-box-value" id="totalDisplay">₱0.00</div>
                        </div>
                        <div class="wf-total-box-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>

                    <button type="submit" class="wf-btn wf-btn-primary wf-btn-block" id="submitBtn">
                        <i class="fas fa-check-circle"></i> Create Booking & Generate Receipt
                    </button>

                </form>
                <?php endif; ?>
            </div>

            <div>
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon"><i class="fas fa-history"></i></div>
                            <div>
                                <h3>Recent Walk-in Customers</h3>
                                <p>Click to autofill form</p>
                            </div>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <?php if (empty($recent_customers)): ?>
                            <div class="wf-empty-mini">
                                <i class="fas fa-users"></i>
                                <p>No recent walk-in customers yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_customers as $rc):
                                $full = trim(($rc['first_name'] ?? '') . ' ' . ($rc['last_name'] ?? ''));
                                if ($full === '') $full = 'Customer';
                            ?>
                            <div class="wf-recent-item"
                                 onclick='fillCustomer(<?= json_encode([
                                     "id"      => (int)$rc["Customer_ID"],
                                     "name"    => $full,
                                     "contact" => $rc["contact_number"] ?? "",
                                     "address" => $rc["Address"] ?? "",
                                 ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                <div class="wf-recent-avatar"><?= e(strtoupper(substr($full, 0, 1))) ?></div>
                                <div class="wf-recent-info">
                                    <div class="wf-recent-name"><?= e($full) ?></div>
                                    <div class="wf-recent-contact">
                                        <?= e($rc['contact_number'] ?: '—') ?>
                                    </div>
                                </div>
                                <i class="fas fa-arrow-right" style="color:var(--text-muted);font-size:0.75rem;"></i>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<script>
document.querySelectorAll('.wf-type-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.wf-type-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const type = this.dataset.type;
        document.getElementById('customerTypeField').value = type;

        const guestPanel = document.getElementById('guestPanel');
        const existingPanel = document.getElementById('existingPanel');

        if (type === 'guest') {
            guestPanel.style.display = 'block';
            existingPanel.style.display = 'none';
            document.getElementById('guestName').setAttribute('required', 'required');
            document.getElementById('existingSelect').removeAttribute('required');
        } else {
            guestPanel.style.display = 'none';
            existingPanel.style.display = 'block';
            document.getElementById('guestName').removeAttribute('required');
            document.getElementById('existingSelect').setAttribute('required', 'required');
        }
    });
});

function fillCustomer(data) {
    document.querySelector('.wf-type-tab[data-type="existing"]').click();
    const select = document.getElementById('existingSelect');
    select.value = data.id;
    if (!select.value) {
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.textContent = '#' + data.id + ' — ' + data.name;
        select.appendChild(opt);
        select.value = data.id;
    }
}

document.querySelectorAll('.wf-check-item input').forEach(input => {
    input.addEventListener('change', function() {
        const label = this.closest('.wf-check-item');
        if (this.checked) {
            label.classList.add('checked');
        } else {
            label.classList.remove('checked');
        }
        recalcTotal();
    });
});

function recalcTotal() {
    let base = 0;
    let addonsTotal = 0;

    document.querySelectorAll('input[name="services[]"]:checked').forEach(cb => {
        const price = parseFloat(cb.dataset.price || 0);
        const perkg = cb.dataset.perkg === '1';
        if (perkg) {
            const weight = parseFloat(document.getElementById('weightInput').value || 0);
            base += price * weight;
        } else {
            base += price;
        }
    });

    document.querySelectorAll('input[name="addons[]"]:checked').forEach(cb => {
        addonsTotal += parseFloat(cb.dataset.price || 0);
    });

    const total = base + addonsTotal;
    document.getElementById('totalDisplay').textContent = '₱' + total.toFixed(2);
}

const weightEl = document.getElementById('weightInput');
if (weightEl) weightEl.addEventListener('input', recalcTotal);

const walkinForm = document.getElementById('walkinForm');
if (walkinForm) {
    walkinForm.addEventListener('submit', function(e) {
        const type = document.getElementById('customerTypeField').value;
        const services = document.querySelectorAll('input[name="services[]"]:checked');
        const weight = parseFloat(document.getElementById('weightInput').value || 0);

        if (services.length === 0) {
            e.preventDefault();
            alert('Please select at least one service.');
            return;
        }
        if (weight <= 0) {
            e.preventDefault();
            alert('Please enter the weight.');
            return;
        }
        if (type === 'guest') {
            const name = document.getElementById('guestName').value.trim();
            if (name === '') {
                e.preventDefault();
                alert('Guest name is required.');
                return;
            }
        } else {
            const cust = document.getElementById('existingSelect').value;
            if (cust === '') {
                e.preventDefault();
                alert('Please select a customer.');
                return;
            }
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating booking...';
    });
}

(function() {
    const toggle  = document.getElementById('wfSidebarToggle');
    const sidebar = document.getElementById('wfSidebar');
    const overlay = document.getElementById('wfSidebarOverlay');
    if (!toggle) return;
    toggle.addEventListener('click', () => { sidebar.classList.add('open'); overlay.classList.add('open'); });
    overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
})();
</script>
</body>
</html>