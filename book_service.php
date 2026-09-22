<?php
/**
 * book_service.php
 * WashFlow — Customer New Booking (4-Step Wizard, Polished)
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';
require_once 'csrf_helper.php';
require_once 'notifications_helper.php';

require_customer_login();

/* ══════════════════════════════════════════
   RESOLVE CUSTOMER ID (handles customer_id = 0)
   ══════════════════════════════════════════ */
$customer_id = (int)($_SESSION['customer_id'] ?? 0);

if ($customer_id <= 0) {
    $email_lookup = $_SESSION['email'] ?? '';
    if ($email_lookup !== '') {
        $stmt = $conn->prepare("SELECT Customer_ID, first_name, last_name, contact_number, Address FROM customer_info WHERE email = ? AND Customer_ID > 0 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email_lookup);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $customer_id = (int)$row['Customer_ID'];
                $_SESSION['customer_id'] = $customer_id;
                $_SESSION['first_name']  = $row['first_name'];
                $_SESSION['last_name']   = $row['last_name'];
            }
            $stmt->close();
        }
    }
}

if ($customer_id <= 0) {
    die('Session error: invalid customer ID. Please <a href="logout.php">logout</a> and login again.');
}

$firstName   = $_SESSION['first_name'] ?? '';
$lastName    = $_SESSION['last_name']  ?? '';
$fullName    = trim($firstName . ' ' . $lastName);
$customer_initial = strtoupper(substr($firstName ?: 'U', 0, 1));

/* Fetch customer info */
$cust = ['contact_number' => '', 'address' => ''];
$stmt = $conn->prepare("SELECT contact_number, Address FROM customer_info WHERE Customer_ID = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $cust['contact_number'] = $row['contact_number'] ?? '';
        $cust['address']        = $row['Address'] ?? '';
    }
    $stmt->close();
}

/* Fetch services */
$services = [];
$q = $conn->query("
    SELECT service_id, service_name, description, price_fixed, price_per_kg
    FROM services
    GROUP BY service_id
    ORDER BY service_id ASC
");
if ($q) while ($r = $q->fetch_assoc()) $services[] = $r;

/* Fetch add-ons (deduped by service + name) */
$addons_by_service = [];
$seen = [];
$q = $conn->query("
    SELECT addon_id, service_id, addon_name, addon_price, description
    FROM add_ons
    ORDER BY service_id ASC, addon_name ASC
");
if ($q) while ($r = $q->fetch_assoc()) {
    $key = $r['service_id'] . '|' . strtolower(trim($r['addon_name']));
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $sid = (int)$r['service_id'];
    if (!isset($addons_by_service[$sid])) $addons_by_service[$sid] = [];
    $addons_by_service[$sid][] = $r;
}

/* Handle POST */
$flash_error = null;
$form = [
    'service_ids'   => [],
    'addon_ids'     => [],
    'delivery'      => 'walkin',
    'dropoff_date'  => date('Y-m-d', strtotime('+1 day')),
    'dropoff_time'  => '08:00',
    'contact'       => $cust['contact_number'],
    'address'       => $cust['address'],
    'instructions'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash_error = 'Security token expired. Please refresh and try again.';
    } else {
        $form['service_ids']  = array_values(array_filter(array_map('intval', $_POST['service_ids'] ?? [])));
        $form['addon_ids']    = array_values(array_filter(array_map('intval', $_POST['addon_ids']   ?? [])));
        $form['delivery']     = $_POST['delivery']     ?? 'walkin';
        $form['dropoff_date'] = trim($_POST['dropoff_date'] ?? '');
        $form['dropoff_time'] = trim($_POST['dropoff_time'] ?? '');
        $form['contact']      = trim($_POST['contact']     ?? '');
        $form['address']      = trim($_POST['address']     ?? '');
        $form['instructions'] = trim($_POST['instructions'] ?? '');

        if (empty($form['service_ids'])) {
            $flash_error = 'Please select at least one service.';
        } elseif (!in_array($form['delivery'], ['walkin', 'pickup-only', 'pickup-delivery', 'delivery'], true)) {
            $flash_error = 'Invalid delivery option.';
        } elseif ($form['contact'] === '' || !validate_phone($form['contact'])) {
            $flash_error = 'Please enter a valid contact number (e.g. 09123456789).';
        } elseif (($form['delivery'] !== 'walkin') && $form['address'] === '') {
            $flash_error = 'Address is required for pickup/delivery.';
        } elseif ($form['dropoff_date'] === '' || strtotime($form['dropoff_date']) < strtotime(date('Y-m-d'))) {
            $flash_error = 'Please select a valid drop-off date (today or later).';
        } else {
            $total = 0.0;
            $service_names = [];
            $addon_names = [];

            $sids_sql = implode(',', array_map('intval', $form['service_ids']));
            $q = $conn->query("SELECT service_id, service_name, price_fixed FROM services WHERE service_id IN ($sids_sql)");
            if ($q) while ($s = $q->fetch_assoc()) {
                $service_names[] = $s['service_name'];
                $total += (float)$s['price_fixed'];
            }

            if (!empty($form['addon_ids'])) {
                $aids_sql = implode(',', array_map('intval', $form['addon_ids']));
                $q = $conn->query("SELECT addon_id, addon_name, addon_price FROM add_ons WHERE addon_id IN ($aids_sql)");
                if ($q) while ($a = $q->fetch_assoc()) {
                    $addon_names[] = $a['addon_name'];
                    $total += (float)$a['addon_price'];
                }
            }

            try {
                $conn->begin_transaction();

                $service_str = implode(', ', $service_names);
                $addon_str   = implode(', ', $addon_names);
                $customer_name_str = $fullName !== '' ? $fullName : 'Customer';
                $dropoff_date_v = $form['dropoff_date'];
                $dropoff_time_v = $form['dropoff_time'];
                $address_v      = $form['address'];
                $contact_v      = $form['contact'];
                $delivery_v     = $form['delivery'];
                $instructions_v = $form['instructions'];

                $stmt = $conn->prepare("
                    INSERT INTO booking_online
                    (customer_id, customer_name, contact_number, address, delivery_option,
                     dropoff_date, dropoff_time, special_instructions, addons, service,
                     status, payment_status, total_amount, timestamp)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Unpaid', ?, NOW())
                ");

                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param(
                    'isssssssssd',
                    $customer_id,
                    $customer_name_str,
                    $contact_v,
                    $address_v,
                    $delivery_v,
                    $dropoff_date_v,
                    $dropoff_time_v,
                    $instructions_v,
                    $addon_str,
                    $service_str,
                    $total
                );

                if (!$stmt->execute()) {
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
                $new_id = (int)$conn->insert_id;
                $stmt->close();

                CustomerNotify::create(
                    $conn,
                    $customer_id,
                    'Booking Received',
                    "Your booking #$new_id ({$service_str}) has been received. Total: ₱" . number_format($total, 2),
                    'booking',
                    'fas fa-calendar-check',
                    $new_id,
                    'booking_details.php?id=' . $new_id
                );

                $conn->commit();

                Logger::info('Customer new booking created', [
                    'customer_id' => $customer_id,
                    'booking_id'  => $new_id,
                    'total'       => $total,
                ]);

                $_SESSION['flash_booking_success'] = "Booking #$new_id created successfully! Total: ₱" . number_format($total, 2);
                header('Location: booking_details.php?id=' . $new_id);
                exit();

            } catch (Exception $ex) {
                $conn->rollback();
                Logger::error('Booking creation failed', ['error' => $ex->getMessage()]);

                /* TEMPORARY: show detailed error for debugging
                   Comment out this line once fixed */
                $flash_error = 'ERROR: ' . $ex->getMessage();

                // $flash_error = 'We could not save your booking. Please try again.'; // ← restore later
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Booking — WashFlow</title>
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
    --shadow-sm: 0 1px 2px rgba(6, 52, 82, 0.04);
    --shadow-md: 0 4px 12px rgba(6, 52, 82, 0.06);
    --shadow-lg: 0 10px 30px rgba(6, 52, 82, 0.08);
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

/* SIDEBAR + LAYOUT */
.wf-layout { display: grid; grid-template-columns: 280px 1fr; height: 100vh; overflow: hidden; }
.wf-sidebar {
    background: var(--dark-blue-deep); color: white; height: 100vh;
    overflow-y: auto; display: flex; flex-direction: column; scrollbar-width: none;
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

/* MAIN */
.wf-main { padding: 1.75rem 2.25rem 3rem; overflow-y: auto; height: 100vh; scrollbar-width: none; }
.wf-main::-webkit-scrollbar { display: none; }

.wf-page-head { margin-bottom: 1.5rem; }
.wf-page-head h1 {
    font-size: 1.5rem; font-weight: 800;
    color: var(--dark-blue); margin: 0; letter-spacing: -0.03em;
    display: flex; align-items: center; gap: 0.6rem;
}
.wf-page-head p { margin: 0.3rem 0 0; font-size: 0.875rem; color: var(--text-secondary); font-weight: 500; }

/* ALERT */
.wf-alert {
    display: flex; align-items: flex-start; gap: 0.7rem;
    padding: 0.9rem 1.15rem;
    border-radius: 12px;
    margin-bottom: 1.25rem;
    font-size: 0.85rem;
    border: 1px solid #FADBD8;
    background: linear-gradient(135deg, #FDEDEC 0%, #FFF5F5 100%);
    color: #7f1d1d;
    box-shadow: var(--shadow-sm);
    animation: slideDown 0.3s ease-out;
    word-break: break-word;
}
.wf-alert i { color: var(--red); font-size: 1rem; margin-top: 2px; flex-shrink: 0; }
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* STEPPER */
.wf-stepper {
    background: white;
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 1.75rem 2rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
    box-shadow: var(--shadow-md);
    overflow: hidden;
}
.wf-stepper-track {
    position: absolute;
    top: calc(1.75rem + 26px);
    left: calc(2rem + 26px);
    right: calc(2rem + 26px);
    height: 3px;
    background: var(--border-soft);
    border-radius: 2px;
    z-index: 0;
}
.wf-stepper-progress {
    position: absolute;
    top: calc(1.75rem + 26px);
    left: calc(2rem + 26px);
    height: 3px;
    background: linear-gradient(90deg, var(--yellow) 0%, var(--primary) 100%);
    border-radius: 2px;
    z-index: 1;
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    width: 0;
}
.wf-step {
    display: flex; flex-direction: column; align-items: center;
    gap: 0.5rem;
    position: relative;
    z-index: 2;
    flex: 1;
    min-width: 0;
}
.wf-step-circle {
    width: 54px; height: 54px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--border);
    color: var(--text-muted);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(6, 52, 82, 0.04);
}
.wf-step-circle i { font-size: 1.15rem; }
.wf-step-label {
    font-size: 0.78rem; font-weight: 700;
    color: var(--text-muted);
    text-align: center;
    transition: color 0.3s;
}
.wf-step-sub {
    font-size: 0.68rem;
    color: var(--text-muted);
    font-weight: 500;
    text-align: center;
}
.wf-step.active .wf-step-circle {
    background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
    border-color: var(--yellow);
    color: var(--dark-blue-deep);
    box-shadow: 0 8px 20px rgba(255, 217, 61, 0.45);
    transform: scale(1.08);
}
.wf-step.active .wf-step-label { color: var(--dark-blue); }
.wf-step.completed .wf-step-circle {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
    box-shadow: 0 4px 12px rgba(0, 90, 133, 0.3);
}
.wf-step.completed .wf-step-label { color: var(--primary); }

/* GRID */
.wf-form-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 1.5rem;
    align-items: start;
}
@media (max-width: 1100px) { .wf-form-grid { grid-template-columns: 1fr; } }

/* CARD */
.wf-card {
    background: white; border-radius: 16px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
    box-shadow: var(--shadow-sm);
}
.wf-card-head {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; gap: 0.85rem;
    background: linear-gradient(180deg, #FCFDFE 0%, #FFFFFF 100%);
}
.wf-card-head-icon {
    width: 42px; height: 42px; border-radius: 12px;
    background: linear-gradient(135deg, var(--yellow-soft) 0%, #FFFFFF 100%);
    color: var(--yellow-dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
    border: 1px solid rgba(255, 217, 61, 0.3);
}
.wf-card-head h3 {
    font-size: 1rem; font-weight: 800;
    color: var(--dark-blue); margin: 0;
    letter-spacing: -0.02em;
}
.wf-card-head p {
    font-size: 0.75rem; color: var(--text-muted);
    margin: 0.15rem 0 0; font-weight: 500;
}
.wf-card-body { padding: 1.5rem; }

/* STEP PANELS */
.wf-step-panel { display: none; }
.wf-step-panel.active {
    display: block;
    animation: panelIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes panelIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* SERVICE CARDS */
.wf-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.85rem;
}
.wf-service-opt { position: relative; cursor: pointer; display: block; }
.wf-service-opt input { position: absolute; opacity: 0; pointer-events: none; }
.wf-service-box {
    display: flex; align-items: flex-start; gap: 0.85rem;
    padding: 1.1rem 1.15rem;
    border: 1.5px solid var(--border);
    border-radius: 14px;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    height: 100%;
    position: relative;
    overflow: hidden;
}
.wf-service-box::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255, 217, 61, 0.08) 0%, transparent 60%);
    opacity: 0;
    transition: opacity 0.25s;
    pointer-events: none;
}
.wf-service-box:hover {
    border-color: var(--light-blue);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(6, 52, 82, 0.08);
}
.wf-service-opt input:checked ~ .wf-service-box {
    border-color: var(--yellow);
    background: linear-gradient(135deg, #FFFCF0 0%, #FFFFFF 100%);
    box-shadow: 0 8px 24px rgba(255, 217, 61, 0.28);
}
.wf-service-opt input:checked ~ .wf-service-box::before { opacity: 1; }

.wf-service-check {
    width: 24px; height: 24px; border-radius: 8px;
    border: 2px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 2px;
    transition: all 0.2s;
    color: transparent;
    font-size: 0.7rem;
    background: white;
}
.wf-service-opt input:checked ~ .wf-service-box .wf-service-check {
    background: var(--yellow);
    border-color: var(--yellow);
    color: var(--dark-blue-deep);
    transform: scale(1.05);
}
.wf-service-info { flex: 1; min-width: 0; position: relative; z-index: 1; }
.wf-service-info h5 {
    font-size: 0.95rem; font-weight: 800;
    color: var(--dark-blue); margin: 0 0 0.3rem;
    letter-spacing: -0.01em;
}
.wf-service-info p {
    font-size: 0.76rem; color: var(--text-muted);
    margin: 0 0 0.6rem; line-height: 1.5;
}
.wf-service-price {
    font-size: 1rem; font-weight: 800;
    color: var(--yellow-dark);
    letter-spacing: -0.02em;
}

/* ADD-ONS */
.wf-addons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.7rem;
}
.wf-addon-opt { position: relative; cursor: pointer; display: block; }
.wf-addon-opt input { position: absolute; opacity: 0; pointer-events: none; }
.wf-addon-box {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.95rem 1.1rem;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    transition: all 0.2s;
    background: white;
}
.wf-addon-box:hover {
    border-color: var(--light-blue);
    background: var(--light-blue-pale);
    transform: translateY(-1px);
}
.wf-addon-check {
    width: 20px; height: 20px; border-radius: 6px;
    border: 2px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: all 0.2s;
    color: transparent;
    font-size: 0.6rem;
    background: white;
}
.wf-addon-name {
    flex: 1; font-size: 0.85rem; font-weight: 700;
    color: var(--dark-blue);
}
.wf-addon-price {
    font-size: 0.82rem; font-weight: 800;
    color: var(--yellow-dark);
    white-space: nowrap;
}
.wf-addon-opt input:checked ~ .wf-addon-box {
    border-color: var(--yellow);
    background: linear-gradient(135deg, #FFFCF0 0%, var(--yellow-soft) 100%);
    box-shadow: 0 6px 16px rgba(255, 217, 61, 0.28);
}
.wf-addon-opt input:checked ~ .wf-addon-box .wf-addon-check {
    background: var(--yellow); border-color: var(--yellow); color: var(--dark-blue-deep);
}

/* DELIVERY */
.wf-delivery-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}
.wf-delivery-opt { position: relative; cursor: pointer; display: block; }
.wf-delivery-opt input { position: absolute; opacity: 0; pointer-events: none; }
.wf-delivery-box {
    display: flex; flex-direction: column; align-items: center;
    gap: 0.5rem;
    padding: 1.15rem 0.85rem;
    border: 1.5px solid var(--border);
    border-radius: 14px;
    background: white;
    transition: all 0.22s;
    text-align: center;
    height: 100%;
}
.wf-delivery-box:hover {
    border-color: var(--light-blue);
    background: var(--light-blue-pale);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(6, 52, 82, 0.06);
}
.wf-delivery-icon {
    width: 42px; height: 42px; border-radius: 12px;
    background: var(--light-blue-pale);
    color: var(--text-muted);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
    transition: all 0.2s;
}
.wf-delivery-box .t { font-size: 0.86rem; font-weight: 800; color: var(--dark-blue); letter-spacing: -0.01em; }
.wf-delivery-box .s { font-size: 0.72rem; color: var(--text-muted); font-weight: 500; line-height: 1.4; }
.wf-delivery-opt input:checked ~ .wf-delivery-box {
    border-color: var(--yellow);
    background: linear-gradient(135deg, #FFFCF0 0%, #FFFFFF 100%);
    box-shadow: 0 8px 20px rgba(255, 217, 61, 0.28);
}
.wf-delivery-opt input:checked ~ .wf-delivery-box .wf-delivery-icon {
    background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
    color: var(--dark-blue-deep);
}

/* FORM FIELDS */
.wf-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 600px) { .wf-row-2 { grid-template-columns: 1fr; } }
.wf-form-group { margin-bottom: 1.2rem; }
.wf-form-group:last-child { margin-bottom: 0; }
.wf-label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--dark-blue); margin-bottom: 0.5rem; }
.wf-label .req { color: var(--red); }
.wf-hint { display: block; font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem; font-weight: 500; }
.wf-input, .wf-textarea {
    width: 100%; padding: 0.75rem 1rem;
    border: 1.5px solid var(--border); border-radius: 11px;
    font-size: 0.875rem; font-family: inherit;
    color: var(--text-primary); background: white;
    transition: all 0.2s; outline: none;
}
.wf-input:focus, .wf-textarea:focus {
    border-color: var(--primary-mid);
    box-shadow: 0 0 0 4px rgba(0, 118, 168, 0.1);
}
.wf-textarea { resize: vertical; min-height: 84px; }

/* SUMMARY */
.wf-summary { position: sticky; top: 0; }
.wf-summary-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
}
.wf-summary-title {
    font-size: 0.95rem; font-weight: 800;
    color: var(--dark-blue); margin: 0 0 1.25rem;
    display: flex; align-items: center; gap: 0.5rem;
    letter-spacing: -0.02em;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-soft);
}
.wf-summary-title i { color: var(--primary); }
.wf-summary-list {
    margin-bottom: 1.25rem;
    max-height: 240px;
    overflow-y: auto;
    padding-right: 0.25rem;
}
.wf-summary-list::-webkit-scrollbar { width: 5px; }
.wf-summary-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
.wf-summary-item {
    display: flex; justify-content: space-between;
    padding: 0.55rem 0; gap: 1rem;
    font-size: 0.83rem;
    color: var(--text-secondary);
    border-bottom: 1px dashed var(--border-soft);
}
.wf-summary-item:last-child { border-bottom: none; }
.wf-summary-item .v { font-weight: 800; color: var(--dark-blue); white-space: nowrap; }
.wf-summary-empty {
    text-align: center; padding: 1.75rem 1rem;
    font-size: 0.83rem; color: var(--text-muted);
    background: var(--light-blue-pale);
    border-radius: 12px;
    border: 1px dashed var(--light-blue);
    line-height: 1.5;
}
.wf-summary-empty i {
    display: block;
    font-size: 1.5rem;
    color: var(--light-blue);
    margin-bottom: 0.5rem;
}
.wf-summary-total {
    display: flex; justify-content: space-between; align-items: center;
    padding-top: 1.25rem;
    border-top: 2px solid var(--border);
    margin-bottom: 1.25rem;
}
.wf-summary-total .l {
    font-size: 0.8rem; font-weight: 800;
    color: var(--text-secondary); text-transform: uppercase;
    letter-spacing: 0.08em;
}
.wf-summary-total .v {
    font-size: 1.65rem; font-weight: 800;
    color: var(--dark-blue);
    letter-spacing: -0.03em;
}

/* NAV BUTTONS */
.wf-step-nav {
    display: flex; gap: 0.6rem;
    margin-top: 0.5rem;
}
.wf-btn-nav {
    flex: 1;
    display: inline-flex; align-items: center; justify-content: center;
    gap: 0.5rem;
    padding: 0.9rem 1.25rem;
    border-radius: 50px;
    font-size: 0.875rem; font-weight: 800;
    border: none; cursor: pointer;
    transition: all 0.22s;
    font-family: inherit;
    letter-spacing: -0.01em;
}
.wf-btn-next {
    background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
    color: var(--dark-blue-deep);
    box-shadow: 0 6px 16px rgba(255, 217, 61, 0.4);
}
.wf-btn-next:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(255, 217, 61, 0.55);
}
.wf-btn-next:disabled { opacity: 0.55; cursor: not-allowed; }
.wf-btn-back {
    background: white; color: var(--text-secondary);
    border: 1.5px solid var(--border);
    max-width: 120px;
}
.wf-btn-back:hover { border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale); }
.wf-btn-submit {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%);
    color: white;
    box-shadow: 0 6px 16px rgba(0, 90, 133, 0.3);
}
.wf-btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(0, 90, 133, 0.45);
}
.wf-btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

/* REVIEW */
.wf-review-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 700px) { .wf-review-grid { grid-template-columns: 1fr; } }
.wf-review-card {
    padding: 1rem 1.15rem;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: linear-gradient(180deg, var(--light-blue-pale) 0%, #FFFFFF 100%);
}
.wf-review-card .lbl {
    font-size: 0.68rem; font-weight: 800;
    color: var(--text-muted); text-transform: uppercase;
    letter-spacing: 0.08em; margin-bottom: 0.4rem;
}
.wf-review-card .val {
    font-size: 0.9rem; font-weight: 700;
    color: var(--dark-blue); line-height: 1.5;
}
.wf-review-card .val.muted { color: var(--text-secondary); font-weight: 500; font-size: 0.85rem; }

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
    .wf-stepper { padding: 1.25rem 1rem; }
    .wf-stepper-track, .wf-stepper-progress { display: none; }
    .wf-step-circle { width: 42px; height: 42px; font-size: 0.9rem; }
    .wf-step-label { font-size: 0.7rem; }
    .wf-step-sub { display: none; }
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
            <a href="book_service.php" class="wf-nav-item active"><i class="fas fa-plus-circle"></i><span>New Booking</span></a>
            <a href="my_bookings.php" class="wf-nav-item"><i class="fas fa-clipboard-list"></i><span>My Bookings</span></a>
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
            <h1><i class="fas fa-plus-circle" style="color:var(--primary);"></i> New Booking</h1>
            <p>Complete the 4 steps to schedule your laundry service</p>
        </div>

        <?php if ($flash_error): ?>
            <div class="wf-alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= e($flash_error) ?></div>
            </div>
        <?php endif; ?>

        <div class="wf-stepper">
            <div class="wf-stepper-track"></div>
            <div class="wf-stepper-progress" id="wfStepProgress"></div>

            <div class="wf-step active" data-step="1">
                <div class="wf-step-circle"><i class="fas fa-soap"></i></div>
                <div class="wf-step-label">Services</div>
                <div class="wf-step-sub">What to clean</div>
            </div>
            <div class="wf-step" data-step="2">
                <div class="wf-step-circle"><i class="fas fa-plus"></i></div>
                <div class="wf-step-label">Add-ons</div>
                <div class="wf-step-sub">Optional extras</div>
            </div>
            <div class="wf-step" data-step="3">
                <div class="wf-step-circle"><i class="fas fa-truck"></i></div>
                <div class="wf-step-label">Schedule</div>
                <div class="wf-step-sub">Delivery & contact</div>
            </div>
            <div class="wf-step" data-step="4">
                <div class="wf-step-circle"><i class="fas fa-check-double"></i></div>
                <div class="wf-step-label">Review</div>
                <div class="wf-step-sub">Confirm details</div>
            </div>
        </div>

        <form method="POST" action="book_service.php" id="wfBookingForm">
            <?= csrf_field() ?>

            <div class="wf-form-grid">

                <div>

                    <div class="wf-step-panel active" data-step="1">
                        <div class="wf-card">
                            <div class="wf-card-head">
                                <div class="wf-card-head-icon"><i class="fas fa-soap"></i></div>
                                <div>
                                    <h3>Select Services <span style="color:var(--red);">*</span></h3>
                                    <p>Pick one or more services you need</p>
                                </div>
                            </div>
                            <div class="wf-card-body">
                                <?php if (empty($services)): ?>
                                    <p style="color:var(--text-muted);">No services available right now.</p>
                                <?php else: ?>
                                <div class="wf-services-grid">
                                    <?php foreach ($services as $s): ?>
                                    <label class="wf-service-opt">
                                        <input type="checkbox" name="service_ids[]"
                                               value="<?= (int)$s['service_id'] ?>"
                                               data-price="<?= (float)$s['price_fixed'] ?>"
                                               data-name="<?= e($s['service_name']) ?>"
                                               <?= in_array((int)$s['service_id'], $form['service_ids']) ? 'checked' : '' ?>>
                                        <div class="wf-service-box">
                                            <div class="wf-service-check"><i class="fas fa-check"></i></div>
                                            <div class="wf-service-info">
                                                <h5><?= e($s['service_name']) ?></h5>
                                                <?php if (!empty($s['description'])): ?>
                                                    <p><?= e($s['description']) ?></p>
                                                <?php endif; ?>
                                                <div class="wf-service-price">₱<?= number_format((float)$s['price_fixed'], 2) ?></div>
                                            </div>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="wf-step-panel" data-step="2">
                        <div class="wf-card">
                            <div class="wf-card-head">
                                <div class="wf-card-head-icon"><i class="fas fa-plus"></i></div>
                                <div>
                                    <h3>Add-ons</h3>
                                    <p>Optional extras — filtered by your services</p>
                                </div>
                            </div>
                            <div class="wf-card-body">
                                <div id="wfAddonsContainer"></div>
                            </div>
                        </div>
                    </div>

                    <div class="wf-step-panel" data-step="3">
                        <div class="wf-card">
                            <div class="wf-card-head">
                                <div class="wf-card-head-icon"><i class="fas fa-truck"></i></div>
                                <div>
                                    <h3>Delivery Option <span style="color:var(--red);">*</span></h3>
                                    <p>How would you like to receive your laundry?</p>
                                </div>
                            </div>
                            <div class="wf-card-body">
                                <div class="wf-delivery-grid">
                                    <?php
                                    $dopts = [
                                        ['walkin', 'fa-store', 'Walk-in', 'Drop & pick up in shop'],
                                        ['delivery', 'fa-truck', 'Delivery', 'We deliver to you'],
                                        ['pickup-only', 'fa-hand-holding', 'Pickup Only', 'We pick up from you'],
                                        ['pickup-delivery', 'fa-exchange-alt', 'Pickup & Deliver', 'Full service'],
                                    ];
                                    foreach ($dopts as $opt):
                                        [$val, $icon, $title, $sub] = $opt;
                                    ?>
                                    <label class="wf-delivery-opt">
                                        <input type="radio" name="delivery" value="<?= e($val) ?>"
                                               <?= $form['delivery'] === $val ? 'checked' : '' ?>>
                                        <div class="wf-delivery-box">
                                            <div class="wf-delivery-icon"><i class="fas <?= e($icon) ?>"></i></div>
                                            <span class="t"><?= e($title) ?></span>
                                            <span class="s"><?= e($sub) ?></span>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="wf-card">
                            <div class="wf-card-head">
                                <div class="wf-card-head-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div>
                                    <h3>Schedule & Contact</h3>
                                    <p>When and where should we pick up?</p>
                                </div>
                            </div>
                            <div class="wf-card-body">
                                <div class="wf-row-2">
                                    <div class="wf-form-group">
                                        <label class="wf-label">Drop-off Date <span class="req">*</span></label>
                                        <input type="date" name="dropoff_date" class="wf-input"
                                               value="<?= e($form['dropoff_date']) ?>"
                                               min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="wf-form-group">
                                        <label class="wf-label">Preferred Time <span class="req">*</span></label>
                                        <input type="time" name="dropoff_time" class="wf-input"
                                               value="<?= e($form['dropoff_time']) ?>">
                                    </div>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Contact Number <span class="req">*</span></label>
                                    <input type="tel" name="contact" class="wf-input"
                                           value="<?= e($form['contact']) ?>"
                                           placeholder="09123456789">
                                    <span class="wf-hint">We'll contact you at this number for updates.</span>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Delivery Address</label>
                                    <textarea name="address" class="wf-textarea"
                                              placeholder="Your complete address (required for pickup/delivery)"
                                              rows="2"><?= e($form['address']) ?></textarea>
                                    <span class="wf-hint">Required if you selected Pickup or Delivery.</span>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Special Instructions</label>
                                    <textarea name="instructions" class="wf-textarea"
                                              placeholder="e.g. Separate whites from colors, no bleach, etc."
                                              rows="2"><?= e($form['instructions']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wf-step-panel" data-step="4">
                        <div class="wf-card">
                            <div class="wf-card-head">
                                <div class="wf-card-head-icon"><i class="fas fa-check-double"></i></div>
                                <div>
                                    <h3>Review Your Booking</h3>
                                    <p>Please check all details before confirming</p>
                                </div>
                            </div>
                            <div class="wf-card-body">
                                <div class="wf-review-grid">
                                    <div class="wf-review-card">
                                        <div class="lbl">Services</div>
                                        <div class="val" id="wfReviewServices">—</div>
                                    </div>
                                    <div class="wf-review-card">
                                        <div class="lbl">Add-ons</div>
                                        <div class="val muted" id="wfReviewAddons">None</div>
                                    </div>
                                    <div class="wf-review-card">
                                        <div class="lbl">Delivery Option</div>
                                        <div class="val" id="wfReviewDelivery">—</div>
                                    </div>
                                    <div class="wf-review-card">
                                        <div class="lbl">Drop-off Schedule</div>
                                        <div class="val" id="wfReviewSchedule">—</div>
                                    </div>
                                    <div class="wf-review-card">
                                        <div class="lbl">Contact Number</div>
                                        <div class="val" id="wfReviewContact">—</div>
                                    </div>
                                    <div class="wf-review-card">
                                        <div class="lbl">Address</div>
                                        <div class="val muted" id="wfReviewAddress">—</div>
                                    </div>
                                    <div class="wf-review-card" style="grid-column: 1 / -1;">
                                        <div class="lbl">Special Instructions</div>
                                        <div class="val muted" id="wfReviewInstructions">None</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div>
                    <div class="wf-summary">
                        <div class="wf-summary-card">
                            <h4 class="wf-summary-title"><i class="fas fa-receipt"></i> Booking Summary</h4>

                            <div class="wf-summary-list" id="wfSummaryList">
                                <div class="wf-summary-empty">
                                    <i class="fas fa-clipboard-list"></i>
                                    Select services to see your total
                                </div>
                            </div>

                            <div class="wf-summary-total">
                                <span class="l">Total</span>
                                <span class="v" id="wfSummaryTotal">₱0.00</span>
                            </div>

                            <div class="wf-step-nav">
                                <button type="button" class="wf-btn-nav wf-btn-back" id="wfPrevBtn" style="display:none;">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="wf-btn-nav wf-btn-next" id="wfNextBtn">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                                <button type="submit" class="wf-btn-nav wf-btn-submit" id="wfSubmitBtn" style="display:none;">
                                    <i class="fas fa-check-circle"></i> Confirm Booking
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

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

const ADDONS_BY_SERVICE = <?= json_encode($addons_by_service) ?>;

const form = document.getElementById('wfBookingForm');
const panels = document.querySelectorAll('.wf-step-panel');
const steps = document.querySelectorAll('.wf-step');
const stepProgress = document.getElementById('wfStepProgress');
const prevBtn = document.getElementById('wfPrevBtn');
const nextBtn = document.getElementById('wfNextBtn');
const submitBtn = document.getElementById('wfSubmitBtn');
const summaryList = document.getElementById('wfSummaryList');
const summaryTotal = document.getElementById('wfSummaryTotal');

let currentStep = 1;
const TOTAL_STEPS = 4;

function gotoStep(step) {
    currentStep = Math.max(1, Math.min(TOTAL_STEPS, step));
    updateStepUI();
    updateSummary();
    updateReview();
}

function updateStepUI() {
    panels.forEach(p => p.classList.toggle('active', parseInt(p.dataset.step) === currentStep));
    steps.forEach(s => {
        const n = parseInt(s.dataset.step);
        s.classList.remove('active', 'completed');
        if (n === currentStep) s.classList.add('active');
        else if (n < currentStep) s.classList.add('completed');
    });

    const pct = ((currentStep - 1) / (TOTAL_STEPS - 1)) * 100;
    stepProgress.style.width = pct + '%';

    prevBtn.style.display = currentStep > 1 ? '' : 'none';
    nextBtn.style.display = currentStep < TOTAL_STEPS ? '' : 'none';
    submitBtn.style.display = currentStep === TOTAL_STEPS ? '' : 'none';

    document.querySelector('.wf-main').scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(step) {
    if (step === 1) {
        const selected = form.querySelectorAll('input[name="service_ids[]"]:checked');
        if (selected.length === 0) {
            alert('Please select at least one service to continue.');
            return false;
        }
    }
    if (step === 3) {
        const date = form.querySelector('input[name="dropoff_date"]').value;
        const time = form.querySelector('input[name="dropoff_time"]').value;
        const contact = form.querySelector('input[name="contact"]').value.trim();
        const delivery = form.querySelector('input[name="delivery"]:checked').value;
        const address = form.querySelector('textarea[name="address"]').value.trim();

        if (!date) { alert('Please select a drop-off date.'); return false; }
        if (!time) { alert('Please select a preferred time.'); return false; }
        if (!contact) { alert('Please enter your contact number.'); return false; }
        if (delivery !== 'walkin' && !address) {
            alert('Address is required for pickup/delivery.');
            return false;
        }
    }
    return true;
}

nextBtn.addEventListener('click', () => {
    if (!validateStep(currentStep)) return;
    if (currentStep < TOTAL_STEPS) gotoStep(currentStep + 1);
});

prevBtn.addEventListener('click', () => {
    if (currentStep > 1) gotoStep(currentStep - 1);
});

const addonsContainer = document.getElementById('wfAddonsContainer');

function renderAddons() {
    const selectedServices = Array.from(form.querySelectorAll('input[name="service_ids[]"]:checked'))
        .map(el => parseInt(el.value));

    if (selectedServices.length === 0) {
        addonsContainer.innerHTML = `
            <div class="wf-summary-empty">
                <i class="fas fa-info-circle"></i>
                Please select services first. Add-ons depend on your chosen services.
            </div>`;
        return;
    }

    const seen = new Set();
    const addons = [];

    selectedServices.forEach(sid => {
        const list = ADDONS_BY_SERVICE[sid] || [];
        list.forEach(a => {
            const key = a.addon_name.toLowerCase().trim();
            if (seen.has(key)) return;
            seen.add(key);
            addons.push(a);
        });
    });

    if (addons.length === 0) {
        addonsContainer.innerHTML = `
            <div class="wf-summary-empty">
                <i class="fas fa-check-circle" style="color:var(--green);"></i>
                No add-ons available for your selected services.
            </div>`;
        return;
    }

    const checkedIds = new Set(
        Array.from(form.querySelectorAll('input[name="addon_ids[]"]:checked')).map(el => el.value)
    );

    let html = '<div class="wf-addons-grid">';
    addons.forEach(a => {
        const checked = checkedIds.has(String(a.addon_id)) ? 'checked' : '';
        html += `
            <label class="wf-addon-opt">
                <input type="checkbox" name="addon_ids[]"
                       value="${a.addon_id}"
                       data-price="${a.addon_price}"
                       data-name="${escapeAttr(a.addon_name)}"
                       ${checked}>
                <div class="wf-addon-box">
                    <div class="wf-addon-check"><i class="fas fa-check"></i></div>
                    <span class="wf-addon-name">${escapeHtml(a.addon_name)}</span>
                    <span class="wf-addon-price">+₱${parseFloat(a.addon_price).toFixed(2)}</span>
                </div>
            </label>
        `;
    });
    html += '</div>';
    addonsContainer.innerHTML = html;

    addonsContainer.querySelectorAll('input[type="checkbox"]').forEach(el => {
        el.addEventListener('change', updateSummary);
    });
}

function updateSummary() {
    const services = Array.from(form.querySelectorAll('input[name="service_ids[]"]:checked'));
    const addons = Array.from(form.querySelectorAll('#wfAddonsContainer input[name="addon_ids[]"]:checked'));

    let total = 0;
    let html = '';

    if (services.length === 0) {
        summaryList.innerHTML = `
            <div class="wf-summary-empty">
                <i class="fas fa-clipboard-list"></i>
                Select services to see your total
            </div>`;
        summaryTotal.textContent = '₱0.00';
        return;
    }

    services.forEach(s => {
        const price = parseFloat(s.dataset.price || 0);
        total += price;
        html += `<div class="wf-summary-item"><span>${escapeHtml(s.dataset.name)}</span><span class="v">₱${price.toFixed(2)}</span></div>`;
    });

    if (addons.length) {
        addons.forEach(a => {
            const price = parseFloat(a.dataset.price || 0);
            total += price;
            html += `<div class="wf-summary-item"><span>+ ${escapeHtml(a.dataset.name)}</span><span class="v">₱${price.toFixed(2)}</span></div>`;
        });
    }

    summaryList.innerHTML = html;
    summaryTotal.textContent = '₱' + total.toFixed(2);
}

function updateReview() {
    const services = Array.from(form.querySelectorAll('input[name="service_ids[]"]:checked')).map(el => el.dataset.name);
    const addons = Array.from(form.querySelectorAll('#wfAddonsContainer input[name="addon_ids[]"]:checked')).map(el => el.dataset.name);
    const delivery = form.querySelector('input[name="delivery"]:checked')?.value || '';
    const date = form.querySelector('input[name="dropoff_date"]').value;
    const time = form.querySelector('input[name="dropoff_time"]').value;
    const contact = form.querySelector('input[name="contact"]').value.trim();
    const address = form.querySelector('textarea[name="address"]').value.trim();
    const instructions = form.querySelector('textarea[name="instructions"]').value.trim();

    const deliveryMap = {
        'walkin': 'Walk-in (Drop & Pickup)',
        'pickup-only': 'Pickup Only',
        'delivery': 'Delivery Only',
        'pickup-delivery': 'Pickup & Delivery'
    };

    document.getElementById('wfReviewServices').textContent = services.join(', ') || '—';
    document.getElementById('wfReviewAddons').textContent = addons.join(', ') || 'None';
    document.getElementById('wfReviewDelivery').textContent = deliveryMap[delivery] || '—';
    document.getElementById('wfReviewSchedule').textContent = (date && time) ? `${date} at ${time}` : '—';
    document.getElementById('wfReviewContact').textContent = contact || '—';
    document.getElementById('wfReviewAddress').textContent = address || '—';
    document.getElementById('wfReviewInstructions').textContent = instructions || 'None';
}

form.querySelectorAll('input[name="service_ids[]"]').forEach(el => {
    el.addEventListener('change', () => { renderAddons(); updateSummary(); });
});

form.querySelectorAll('input[name="delivery"]').forEach(el => {
    el.addEventListener('change', updateReview);
});

form.querySelectorAll('input[name="dropoff_date"], input[name="dropoff_time"], input[name="contact"], textarea[name="address"], textarea[name="instructions"]').forEach(el => {
    el.addEventListener('input', updateReview);
});

form.addEventListener('submit', function (e) {
    if (currentStep !== TOTAL_STEPS) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }

    for (let s = 1; s <= TOTAL_STEPS; s++) {
        if (!validateStep(s)) {
            e.preventDefault();
            gotoStep(s);
            return false;
        }
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating booking...';
});

form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        if (currentStep !== TOTAL_STEPS) {
            e.preventDefault();
            nextBtn.click();
        }
    }
});

function escapeHtml(str) {
    if (str == null) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function escapeAttr(str) { return escapeHtml(str); }

gotoStep(1);
renderAddons();
</script>

</body>
</html>