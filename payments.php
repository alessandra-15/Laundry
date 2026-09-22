<?php
/**
 * payments.php
 * WashFlow — Customer Payments (GCash + Cash with Staff Notification)
 *
 * ✅ FIX: Blocks duplicate pending payments — isang payment row lang per booking
 * ✅ GCash upload checks for any existing Pending payment
 * ✅ Cash notify checks for any existing Pending payment
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';
require_once 'csrf_helper.php';
require_once 'notifications_helper.php';

require_customer_login();

$customer_id = (int)($_SESSION['customer_id'] ?? 0);

/* Resolve customer_id if 0 */
if ($customer_id <= 0) {
    $email_lookup = $_SESSION['email'] ?? '';
    if ($email_lookup !== '') {
        $stmt = $conn->prepare("SELECT Customer_ID, first_name, last_name FROM customer_info WHERE email = ? AND Customer_ID > 0 LIMIT 1");
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

$flash_success = null;
$flash_error   = null;

/* ══════════════════════════════════════════
   HANDLE CASH NOTIFICATION (notify staff)
   ══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'notify_cash') {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash_error = 'Security token expired. Please refresh.';
    } else {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $amount     = (float)($_POST['amount'] ?? 0);

        /* Verify booking belongs to customer */
        $stmt = $conn->prepare("
            SELECT id, total_amount, payment_status 
            FROM booking_online 
            WHERE id = ? AND customer_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('ii', $booking_id, $customer_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            $flash_error = 'Booking not found.';
        } elseif (strtolower($booking['payment_status']) === 'paid') {
            $flash_error = 'This booking is already paid.';
        } else {
            /* Check kung may existing Cash+Pending na */
            $stmt = $conn->prepare("
                SELECT payment_id FROM payments_online 
                WHERE booking_id = ? AND payment_method = 'Cash' AND payment_status = 'Pending'
                LIMIT 1
            ");
            $stmt->bind_param('i', $booking_id);
            $stmt->execute();
            $existing_cash = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing_cash) {
                $flash_error = 'You already notified the staff. Please wait for confirmation.';
            } else {
                /* 🆕 Check kung may IBANG pending payment (hal. GCash) */
                $stmt = $conn->prepare("
                    SELECT payment_id, payment_method FROM payments_online 
                    WHERE booking_id = ? AND payment_status = 'Pending'
                    LIMIT 1
                ");
                $stmt->bind_param('i', $booking_id);
                $stmt->execute();
                $other_pending = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($other_pending) {
                    $flash_error = "You already have a pending {$other_pending['payment_method']} payment for this booking. Please wait for staff confirmation.";
                } else {
                    try {
                        $conn->begin_transaction();

                        /* Insert a payments_online record with Cash + Pending */
                        $stmt = $conn->prepare("
                            INSERT INTO payments_online
                            (booking_id, amount, payment_method, payment_status, payment_date, notes)
                            VALUES (?, ?, 'Cash', 'Pending', NOW(), 'Customer opted to pay cash — awaiting staff confirmation')
                        ");
                        $stmt->bind_param('id', $booking_id, $amount);
                        $stmt->execute();
                        $stmt->close();

                        /* Update booking payment_status to 'Partially Paid' */
                        $stmt = $conn->prepare("
                            UPDATE booking_online 
                            SET payment_status = 'Partially Paid'
                            WHERE id = ?
                        ");
                        $stmt->bind_param('i', $booking_id);
                        $stmt->execute();
                        $stmt->close();

                        /* Notify customer */
                        CustomerNotify::create(
                            $conn,
                            $customer_id,
                            'Cash Payment Noted',
                            "You've indicated you'll pay booking #$booking_id in cash. Staff will confirm once received.",
                            'payment',
                            'fas fa-money-bill-wave',
                            $booking_id,
                            'booking_details.php?id=' . $booking_id
                        );

                        /* Notify ADMIN/STAFF */
                        $admin_msg = "Customer #$customer_id indicated CASH payment for Booking #$booking_id (₱" . number_format($amount, 2) . ")";
                        $stmt = $conn->prepare("
                            INSERT INTO notifications_admin (booking_id, user_id, message, status, created_at)
                            VALUES (?, ?, ?, 'pending', NOW())
                        ");
                        $stmt->bind_param('iis', $booking_id, $customer_id, $admin_msg);
                        $stmt->execute();
                        $stmt->close();

                        $conn->commit();

                        Logger::info('Customer notified cash payment', [
                            'customer_id' => $customer_id,
                            'booking_id'  => $booking_id,
                            'amount'      => $amount,
                        ]);

                        $flash_success = "Staff has been notified! They will confirm once your cash payment is received.";
                    } catch (Exception $ex) {
                        $conn->rollback();
                        Logger::error('Cash notify failed', ['error' => $ex->getMessage()]);
                        $flash_error = 'Could not notify staff. Please try again.';
                    }
                }
            }
        }
    }
}

/* ══════════════════════════════════════════
   HANDLE GCASH PROOF UPLOAD
   ══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_proof') {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash_error = 'Security token expired. Please refresh.';
    } else {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $ref_number = trim($_POST['reference_number'] ?? '');
        $amount     = (float)($_POST['amount'] ?? 0);

        $stmt = $conn->prepare("
            SELECT id, total_amount, payment_status 
            FROM booking_online 
            WHERE id = ? AND customer_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('ii', $booking_id, $customer_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            $flash_error = 'Booking not found.';
        } elseif (strtolower($booking['payment_status']) === 'paid') {
            $flash_error = 'This booking is already paid.';
        } elseif ($ref_number === '') {
            $flash_error = 'Reference number is required.';
        } elseif (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
            $flash_error = 'Please upload a valid proof of payment (image or PDF).';
        } else {
            /* 🆕 Check kung may existing Pending payment na para sa booking na ito */
            $stmt = $conn->prepare("
                SELECT payment_id, payment_method FROM payments_online 
                WHERE booking_id = ? AND payment_status = 'Pending'
                LIMIT 1
            ");
            $stmt->bind_param('i', $booking_id);
            $stmt->execute();
            $existing_pending = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing_pending) {
                $flash_error = "You already have a pending {$existing_pending['payment_method']} payment for this booking. Please wait for staff confirmation or contact support.";
            } else {
                $file = $_FILES['proof'];

                if ($file['size'] > 5 * 1024 * 1024) {
                    $flash_error = 'File is too large. Maximum is 5MB.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowed = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'application/pdf' => 'pdf',
                    ];

                    if (!isset($allowed[$mime])) {
                        $flash_error = 'Invalid file type. Only JPG, PNG, or PDF allowed.';
                    } else {
                        $ext = $allowed[$mime];
                        $newFileName = 'gcash_' . $booking_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetDir = __DIR__ . '/uploads/payment_proofs/';

                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        $htaccess = $targetDir . '.htaccess';
                        if (!file_exists($htaccess)) {
                            file_put_contents($htaccess, "php_flag engine off\nOptions -ExecCGI\n");
                        }

                        $targetPath = $targetDir . $newFileName;

                        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $flash_error = 'Failed to save uploaded file.';
                        } else {
                            $rel_path = 'uploads/payment_proofs/' . $newFileName;

                            try {
                                $conn->begin_transaction();

                                $stmt = $conn->prepare("
                                    INSERT INTO payments_online
                                    (booking_id, amount, payment_method, payment_status, reference_number, payment_date, payment_proof)
                                    VALUES (?, ?, 'GCash', 'Pending', ?, NOW(), ?)
                                ");
                                $stmt->bind_param('idss', $booking_id, $amount, $ref_number, $rel_path);
                                $stmt->execute();
                                $stmt->close();

                                $stmt = $conn->prepare("
                                    UPDATE booking_online 
                                    SET payment_status = 'Partially Paid'
                                    WHERE id = ?
                                ");
                                $stmt->bind_param('i', $booking_id);
                                $stmt->execute();
                                $stmt->close();

                                CustomerNotify::create(
                                    $conn,
                                    $customer_id,
                                    'GCash Proof Submitted',
                                    "We received your GCash payment proof for booking #$booking_id. Verification is in progress.",
                                    'payment',
                                    'fas fa-receipt',
                                    $booking_id,
                                    'booking_details.php?id=' . $booking_id
                                );

                                /* Notify admin/staff rin */
                                $admin_msg = "Customer #$customer_id submitted GCash proof for Booking #$booking_id (Ref: $ref_number)";
                                $stmt = $conn->prepare("
                                    INSERT INTO notifications_admin (booking_id, user_id, message, status, created_at)
                                    VALUES (?, ?, ?, 'pending', NOW())
                                ");
                                $stmt->bind_param('iis', $booking_id, $customer_id, $admin_msg);
                                $stmt->execute();
                                $stmt->close();

                                $conn->commit();

                                Logger::info('Customer uploaded GCash proof', [
                                    'customer_id' => $customer_id,
                                    'booking_id'  => $booking_id,
                                    'ref'         => $ref_number,
                                ]);

                                $flash_success = "Proof of payment uploaded! We'll verify your payment shortly.";
                            } catch (Exception $ex) {
                                $conn->rollback();
                                @unlink($targetPath);
                                Logger::error('GCash upload failed', ['error' => $ex->getMessage()]);
                                $flash_error = 'Could not save payment. Please try again.';
                            }
                        }
                    }
                }
            }
        }
    }
}

/* ══════════════════════════════════════════
   FETCH STATS
   ══════════════════════════════════════════ */
$total_paid = 0.0;
$total_pending = 0.0;
$total_unpaid = 0.0;

try {
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN p.payment_status='Paid' THEN p.amount ELSE 0 END),0) AS paid,
            COALESCE(SUM(CASE WHEN p.payment_status='Pending' THEN p.amount ELSE 0 END),0) AS pending
        FROM payments_online p
        JOIN booking_online b ON p.booking_id = b.id
        WHERE b.customer_id = ?
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $total_paid    = (float)($row['paid'] ?? 0);
    $total_pending = (float)($row['pending'] ?? 0);
    $stmt->close();

    /* 🆕 Unpaid Balance — computed from booking totals minus Paid amounts */
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(
            b.total_amount - COALESCE(
                (SELECT SUM(p.amount) FROM payments_online p 
                 WHERE p.booking_id = b.id AND p.payment_status = 'Paid'), 
                0
            )
        ), 0) AS unpaid
        FROM booking_online b
        WHERE b.customer_id = ? 
          AND b.status != 'Cancelled'
          AND COALESCE(
                (SELECT SUM(p.amount) FROM payments_online p 
                 WHERE p.booking_id = b.id AND p.payment_status = 'Paid'), 
                0
              ) < b.total_amount
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $total_unpaid = (float)($stmt->get_result()->fetch_assoc()['unpaid'] ?? 0);
    $stmt->close();
} catch (Exception $e) {
    Logger::error('Payment stats failed', ['error' => $e->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH UNPAID BOOKINGS
   With live payment sums (Paid + Pending)
   ══════════════════════════════════════════ */
$unpaid_bookings = [];
try {
    $stmt = $conn->prepare("
        SELECT b.id, b.service, b.total_amount, b.dropoff_date, b.timestamp, b.payment_status,
               COALESCE((SELECT SUM(p.amount) FROM payments_online p 
                         WHERE p.booking_id = b.id AND p.payment_status = 'Paid'), 0) AS paid_amount,
               COALESCE((SELECT SUM(p.amount) FROM payments_online p 
                         WHERE p.booking_id = b.id AND p.payment_status = 'Pending'), 0) AS pending_amount
        FROM booking_online b
        WHERE b.customer_id = ? 
          AND b.status != 'Cancelled'
          AND COALESCE((SELECT SUM(p.amount) FROM payments_online p 
                        WHERE p.booking_id = b.id AND p.payment_status = 'Paid'), 0) < b.total_amount
        ORDER BY b.timestamp DESC
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $unpaid_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    Logger::error('Unpaid bookings fetch failed', ['error' => $e->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH PAYMENT HISTORY
   ══════════════════════════════════════════ */
$payments = [];
try {
    $stmt = $conn->prepare("
        SELECT p.*, b.service
        FROM payments_online p
        JOIN booking_online b ON p.booking_id = b.id
        WHERE b.customer_id = ?
        ORDER BY p.payment_date DESC
        LIMIT 30
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    Logger::error('Payments fetch failed', ['error' => $e->getMessage()]);
}

$unread_count = CustomerNotify::unreadCount($conn, $customer_id);

function pay_status_pill($s) {
    $s = strtolower(trim($s));
    return match ($s) {
        'paid'      => ['label' => 'Paid',      'color' => '#1E7E45', 'bg' => '#EAF7F0'],
        'pending'   => ['label' => 'Pending',   'color' => '#946200', 'bg' => '#FEF9E7'],
        'failed'    => ['label' => 'Failed',    'color' => '#A8322D', 'bg' => '#FDEDEC'],
        'refunded'  => ['label' => 'Refunded',  'color' => '#5A7184', 'bg' => '#F4F7F9'],
        'partially paid' => ['label' => 'Verifying', 'color' => '#5B2E91', 'bg' => '#F4ECFB'],
        default     => ['label' => ucfirst($s ?: '—'), 'color' => '#5A7184', 'bg' => '#F4F7F9'],
    };
}

function booking_pay_pill($s) {
    $s = strtolower(trim($s));
    return match ($s) {
        'paid'           => ['label' => 'Paid',           'color' => '#1E7E45', 'bg' => '#EAF7F0'],
        'unpaid'         => ['label' => 'Unpaid',         'color' => '#A8322D', 'bg' => '#FDEDEC'],
        'partially paid' => ['label' => 'Verifying',      'color' => '#5B2E91', 'bg' => '#F4ECFB'],
        default          => ['label' => ucfirst($s ?: '—'), 'color' => '#5A7184', 'bg' => '#F4F7F9'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments — WashFlow</title>
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

.wf-alert {
    display: flex; align-items: flex-start; gap: 0.7rem;
    padding: 0.9rem 1.15rem;
    border-radius: 12px;
    margin-bottom: 1.25rem;
    font-size: 0.85rem;
    animation: slideDown 0.3s ease-out;
    word-break: break-word;
}
.wf-alert-success { border: 1px solid #A9DFBF; background: linear-gradient(135deg, #EAF7F0 0%, #F5FCF8 100%); color: #14532d; }
.wf-alert-success i { color: var(--green); }
.wf-alert-error { border: 1px solid #FADBD8; background: linear-gradient(135deg, #FDEDEC 0%, #FFF5F5 100%); color: #7f1d1d; }
.wf-alert-error i { color: var(--red); }
.wf-alert i { font-size: 1rem; margin-top: 2px; flex-shrink: 0; }
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

.wf-stats-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 1rem; margin-bottom: 1.5rem;
}
.wf-stat-card {
    background: white; border-radius: 14px;
    padding: 1.35rem 1.25rem;
    border: 1px solid var(--border);
    transition: all 0.25s;
    min-height: 120px;
    display: flex; flex-direction: column; justify-content: space-between;
}
.wf-stat-card:hover {
    border-color: var(--light-blue);
    box-shadow: var(--shadow-lg);
    transform: translateY(-3px);
}
.wf-stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.9rem; }
.wf-stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.wf-stat-icon.icon-green  { background: #EAF7F0; color: #1E7E45; }
.wf-stat-icon.icon-yellow { background: var(--yellow-soft); color: var(--yellow-dark); }
.wf-stat-icon.icon-red    { background: #FDEDEC; color: #A8322D; }
.wf-stat-value { font-size: 1.65rem; font-weight: 800; color: var(--dark-blue); line-height: 1.1; letter-spacing: -0.03em; margin-bottom: 0.2rem; }
.wf-stat-label { font-size: 0.78rem; color: var(--text-secondary); font-weight: 500; }

.wf-card { background: white; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 1.25rem; }
.wf-card-head {
    padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
}
.wf-card-title { display: flex; align-items: center; gap: 0.8rem; }
.wf-card-title-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--yellow-soft); color: var(--yellow-dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.wf-card-title h3 { font-size: 1rem; font-weight: 700; color: var(--dark-blue); margin: 0; }
.wf-card-title p { font-size: 0.75rem; color: var(--text-muted); margin: 0.1rem 0 0; font-weight: 500; }

.wf-table-wrap { overflow-x: auto; }
.wf-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.wf-table thead th {
    text-align: left; padding: 0.85rem 1.15rem;
    font-size: 0.68rem; font-weight: 700;
    color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;
    background: #FAFCFE; border-bottom: 1px solid var(--border-soft); white-space: nowrap;
}
.wf-table tbody td {
    padding: 0.9rem 1.15rem;
    border-bottom: 1px solid var(--border-soft);
    vertical-align: middle;
}
.wf-table tbody tr:last-child td { border-bottom: none; }
.wf-table tbody tr:hover { background: var(--light-blue-pale); }

.wf-booking-id { font-family: 'SF Mono', Monaco, monospace; font-weight: 700; color: var(--primary); font-size: 0.8rem; }
.wf-amount { font-weight: 700; color: var(--dark-blue); }
.wf-date { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }
.wf-method { font-size: 0.83rem; color: var(--text-secondary); font-weight: 600; }
.wf-ref { font-family: 'SF Mono', Monaco, monospace; font-size: 0.75rem; color: var(--text-secondary); }

.wf-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.32rem 0.65rem; border-radius: 7px;
    font-size: 0.72rem; font-weight: 600;
    white-space: nowrap;
}
.wf-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.wf-icon-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid var(--border); background: white;
    color: var(--text-secondary);
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.15s; font-size: 0.8rem; text-decoration: none;
}
.wf-icon-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale); }

.wf-btn-pay {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.55rem 1rem;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
    color: var(--dark-blue-deep);
    font-size: 0.78rem; font-weight: 800;
    border: none; cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    box-shadow: 0 4px 10px rgba(255, 217, 61, 0.3);
}
.wf-btn-pay:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(255, 217, 61, 0.5); color: var(--dark-blue-deep); }

.wf-btn-waiting {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.55rem 1rem;
    border-radius: 9px;
    background: var(--light-blue-pale);
    color: var(--primary);
    font-size: 0.75rem; font-weight: 700;
    border: 1px solid var(--light-blue);
    cursor: default;
}

.wf-empty { text-align: center; padding: 3.5rem 1rem; color: var(--text-muted); }
.wf-empty i { font-size: 3rem; color: var(--light-blue); margin-bottom: 1rem; display: block; }
.wf-empty p { margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); }

.wf-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(6, 52, 82, 0.55);
    backdrop-filter: blur(3px);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999;
    opacity: 0; visibility: hidden;
    transition: all 0.25s;
    padding: 1rem;
}
.wf-modal-overlay.open { opacity: 1; visibility: visible; }
.wf-modal {
    background: white; border-radius: 18px;
    max-width: 520px; width: 100%;
    max-height: 90vh; overflow-y: auto;
    box-shadow: 0 25px 60px rgba(6, 52, 82, 0.3);
    transform: scale(0.95);
    transition: transform 0.25s;
}
.wf-modal-overlay.open .wf-modal { transform: scale(1); }
.wf-modal-head {
    padding: 1.5rem 1.75rem 1rem;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border-soft);
}
.wf-modal-head h3 {
    font-size: 1.1rem; font-weight: 800;
    color: var(--dark-blue); margin: 0;
    display: flex; align-items: center; gap: 0.5rem;
}
.wf-modal-close {
    width: 34px; height: 34px; border-radius: 9px;
    border: 1px solid var(--border); background: white;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-secondary); cursor: pointer;
    transition: all 0.2s;
}
.wf-modal-close:hover { border-color: var(--red); color: var(--red); background: #FDEDEC; }
.wf-modal-body { padding: 1.5rem 1.75rem; }
.wf-modal-footer {
    padding: 1rem 1.75rem 1.5rem;
    display: flex; gap: 0.6rem; justify-content: flex-end;
}

.wf-method-tabs {
    display: flex; gap: 0.5rem;
    margin-bottom: 1.25rem;
    background: var(--light-blue-pale);
    padding: 0.35rem;
    border-radius: 12px;
}
.wf-method-tab {
    flex: 1;
    padding: 0.7rem 0.9rem;
    text-align: center;
    border-radius: 9px;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    background: transparent;
    font-family: inherit;
}
.wf-method-tab.active {
    background: white;
    color: var(--dark-blue);
    box-shadow: 0 4px 10px rgba(6, 52, 82, 0.08);
}
.wf-method-tab i { margin-right: 0.35rem; }

.wf-form-group { margin-bottom: 1rem; }
.wf-form-group:last-child { margin-bottom: 0; }
.wf-label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--dark-blue); margin-bottom: 0.5rem; }
.wf-label .req { color: var(--red); }
.wf-input, .wf-file {
    width: 100%; padding: 0.75rem 1rem;
    border: 1.5px solid var(--border); border-radius: 11px;
    font-size: 0.875rem; font-family: inherit;
    color: var(--text-primary); background: white;
    transition: all 0.2s; outline: none;
}
.wf-input:focus { border-color: var(--primary-mid); box-shadow: 0 0 0 4px rgba(0, 118, 168, 0.1); }
.wf-file { padding: 0.6rem; cursor: pointer; }
.wf-hint { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem; display: block; }

.wf-gcash-info {
    background: linear-gradient(135deg, #E8F6FC 0%, #F2FAFD 100%);
    border: 1.5px dashed var(--light-blue);
    border-radius: 12px;
    padding: 1rem 1.15rem;
    margin-bottom: 1.25rem;
}
.wf-gcash-info h4 {
    font-size: 0.85rem; font-weight: 800;
    color: var(--primary); margin: 0 0 0.4rem;
    display: flex; align-items: center; gap: 0.4rem;
}
.wf-gcash-info p { font-size: 0.78rem; color: var(--text-secondary); margin: 0; line-height: 1.55; }
.wf-gcash-info .acct {
    font-family: 'SF Mono', Monaco, monospace;
    font-weight: 800; color: var(--dark-blue);
    background: white; padding: 0.15rem 0.5rem;
    border-radius: 6px;
    display: inline-block; margin-top: 0.3rem;
}

.wf-btn-cancel {
    padding: 0.75rem 1.25rem; border-radius: 10px;
    background: white; color: var(--text-secondary);
    border: 1.5px solid var(--border);
    font-size: 0.85rem; font-weight: 700;
    cursor: pointer; transition: all 0.2s;
    font-family: inherit;
}
.wf-btn-cancel:hover { border-color: var(--primary); color: var(--primary); }

.wf-btn-submit {
    padding: 0.75rem 1.5rem; border-radius: 10px;
    background: var(--primary); color: white;
    border: none; font-size: 0.85rem; font-weight: 800;
    cursor: pointer; transition: all 0.2s;
    font-family: inherit;
    display: inline-flex; align-items: center; gap: 0.5rem;
}
.wf-btn-submit:hover { background: var(--primary-mid); transform: translateY(-1px); }
.wf-btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.wf-hidden { display: none !important; }

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
    .wf-stats-grid { grid-template-columns: 1fr; }
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
            <a href="my_bookings.php" class="wf-nav-item"><i class="fas fa-clipboard-list"></i><span>My Bookings</span></a>
            <a href="payments.php" class="wf-nav-item active"><i class="fas fa-credit-card"></i><span>Payments</span></a>
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
                <h1><i class="fas fa-credit-card" style="color:var(--primary);"></i> Payments</h1>
                <p>View your payment history and settle unpaid bookings</p>
            </div>
        </div>

        <?php if ($flash_success): ?>
            <div class="wf-alert wf-alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= e($flash_success) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flash_error): ?>
            <div class="wf-alert wf-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= e($flash_error) ?></div>
            </div>
        <?php endif; ?>

        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($total_paid, 2) ?></div>
                    <div class="wf-stat-label">Total Paid</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-hourglass-half"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($total_pending, 2) ?></div>
                    <div class="wf-stat-label">Pending Verification</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-red"><i class="fas fa-exclamation-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($total_unpaid, 2) ?></div>
                    <div class="wf-stat-label">Unpaid Balance</div>
                </div>
            </div>
        </div>

        <div class="wf-card">
            <div class="wf-card-head">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div>
                        <h3>Unpaid Bookings</h3>
                        <p>Bookings that need payment</p>
                    </div>
                </div>
            </div>

            <?php if (empty($unpaid_bookings)): ?>
                <div class="wf-empty">
                    <i class="fas fa-check-circle" style="color: var(--green);"></i>
                    <p>All your bookings are paid. Great! 🎉</p>
                </div>
            <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unpaid_bookings as $b):
                                $bp = booking_pay_pill($b['payment_status']);
                                $is_verifying = ((float)$b['pending_amount'] > 0);
                            ?>
                            <tr>
                                <td><span class="wf-booking-id">#<?= e($b['id']) ?></span></td>
                                <td><span class="wf-service"><?= e($b['service'] ?: '—') ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format((float)$b['total_amount'], 2) ?></span></td>
                                <td><span class="wf-date"><?= date('M j, Y', strtotime($b['timestamp'])) ?></span></td>
                                <td>
                                    <span class="wf-badge" style="color:<?= $bp['color'] ?>;background:<?= $bp['bg'] ?>;">
                                        <span class="wf-badge-dot"></span> <?= e($bp['label']) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($is_verifying): ?>
                                        <span class="wf-btn-waiting">
                                            <i class="fas fa-clock"></i> Waiting for staff
                                        </span>
                                    <?php else: ?>
                                        <button type="button" class="wf-btn-pay"
                                                onclick='openPayModal(<?= json_encode([
                                                    "id" => $b["id"],
                                                    "amount" => (float)$b["total_amount"],
                                                    "service" => $b["service"]
                                                ]) ?>)'>
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="wf-card">
            <div class="wf-card-head">
                <div class="wf-card-title">
                    <div class="wf-card-title-icon"><i class="fas fa-history"></i></div>
                    <div>
                        <h3>Payment History</h3>
                        <p>All your transactions</p>
                    </div>
                </div>
            </div>

            <?php if (empty($payments)): ?>
                <div class="wf-empty">
                    <i class="fas fa-inbox"></i>
                    <p>No payment records yet</p>
                </div>
            <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="text-align:right;">Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p):
                                $badge = pay_status_pill($p['payment_status']);
                            ?>
                            <tr>
                                <td><span class="wf-booking-id">#<?= e($p['booking_id']) ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format((float)$p['amount'], 2) ?></span></td>
                                <td><span class="wf-method"><?= e($p['payment_method']) ?></span></td>
                                <td><span class="wf-ref"><?= e($p['reference_number'] ?: '—') ?></span></td>
                                <td><span class="wf-date"><?= date('M j, Y', strtotime($p['payment_date'])) ?></span></td>
                                <td>
                                    <span class="wf-badge" style="color:<?= $badge['color'] ?>;background:<?= $badge['bg'] ?>;">
                                        <span class="wf-badge-dot"></span> <?= e($badge['label']) ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <?php if (!empty($p['payment_proof'])): ?>
                                        <a href="<?= e($p['payment_proof']) ?>" target="_blank" class="wf-icon-btn" title="View proof">
                                            <i class="fas fa-file-image"></i>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<div class="wf-modal-overlay" id="payModal">
    <div class="wf-modal">
        <div class="wf-modal-head">
            <h3><i class="fas fa-credit-card" style="color: var(--primary);"></i> Pay Booking <span id="payModalId" style="color: var(--primary);">#—</span></h3>
            <button type="button" class="wf-modal-close" onclick="closePayModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="wf-modal-body">

            <div style="background: var(--light-blue-pale); padding: 0.85rem 1.1rem; border-radius: 11px; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Amount to pay</span>
                <span id="payModalAmount" style="font-size: 1.25rem; font-weight: 800; color: var(--dark-blue);">₱0.00</span>
            </div>

            <div class="wf-method-tabs">
                <button type="button" class="wf-method-tab active" data-method="gcash">
                    <i class="fas fa-mobile-alt"></i> GCash
                </button>
                <button type="button" class="wf-method-tab" data-method="cash">
                    <i class="fas fa-money-bill-wave"></i> Cash
                </button>
            </div>

            <div id="payGcashPanel">
                <div class="wf-gcash-info">
                    <h4><i class="fas fa-info-circle"></i> How to pay via GCash</h4>
                    <p>
                        1. Send payment to GCash number: <span class="acct">0912-345-6789</span><br>
                        2. Take a screenshot of the confirmation<br>
                        3. Upload the proof + enter the reference number below
                    </p>
                </div>

                <form method="POST" action="payments.php" enctype="multipart/form-data" id="gcashForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_proof">
                    <input type="hidden" name="booking_id" id="gcashBookingId" value="">
                    <input type="hidden" name="amount" id="gcashAmount" value="">

                    <div class="wf-form-group">
                        <label class="wf-label">GCash Reference Number <span class="req">*</span></label>
                        <input type="text" name="reference_number" class="wf-input"
                               placeholder="e.g. 1234 567 890123" required maxlength="50">
                    </div>

                    <div class="wf-form-group">
                        <label class="wf-label">Proof of Payment <span class="req">*</span></label>
                        <input type="file" name="proof" class="wf-file"
                               accept=".jpg,.jpeg,.png,.pdf" required>
                        <span class="wf-hint">Accepted: JPG, PNG, PDF. Max 5MB.</span>
                    </div>

                    <button type="submit" class="wf-btn-submit" style="width:100%;justify-content:center;margin-top:1rem;">
                        <i class="fas fa-upload"></i> Submit Proof of Payment
                    </button>
                </form>
            </div>

            <div id="payCashPanel" class="wf-hidden">
                <div class="wf-gcash-info" style="background: linear-gradient(135deg, #EAF7F0 0%, #F5FCF8 100%); border-color: #A9DFBF;">
                    <h4 style="color: var(--green);"><i class="fas fa-money-bill-wave"></i> Pay with Cash</h4>
                    <p>
                        You can pay in cash when you:
                    </p>
                    <ul style="margin: 0.5rem 0 0 1.2rem; font-size: 0.8rem; color: var(--text-secondary); line-height: 1.7;">
                        <li><strong>Drop off</strong> your laundry at the shop</li>
                        <li><strong>Receive</strong> your laundry (for delivery)</li>
                    </ul>
                    <p style="margin-top: 0.6rem;">
                        Click the button below to notify the staff that you'll pay in cash. The staff will mark your payment as <strong>Paid</strong> once received.
                    </p>
                </div>

                <form method="POST" action="payments.php" id="cashNotifyForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="notify_cash">
                    <input type="hidden" name="booking_id" id="cashBookingId" value="">
                    <input type="hidden" name="amount" id="cashAmount" value="">

                    <button type="submit" class="wf-btn-submit" style="width:100%;justify-content:center;margin-top:1.2rem;background: linear-gradient(135deg, var(--green) 0%, #52BE80 100%);">
                        <i class="fas fa-bell"></i> Notify Staff — I'll Pay Cash
                    </button>
                </form>
            </div>

        </div>

        <div class="wf-modal-footer">
            <button type="button" class="wf-btn-cancel" onclick="closePayModal()">Close</button>
        </div>
    </div>
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

const payModal = document.getElementById('payModal');
const payModalId = document.getElementById('payModalId');
const payModalAmount = document.getElementById('payModalAmount');
const gcashBookingId = document.getElementById('gcashBookingId');
const gcashAmount = document.getElementById('gcashAmount');
const gcashPanel = document.getElementById('payGcashPanel');
const cashPanel = document.getElementById('payCashPanel');

function openPayModal(data) {
    payModalId.textContent = '#' + data.id;
    payModalAmount.textContent = '₱' + parseFloat(data.amount).toFixed(2);
    gcashBookingId.value = data.id;
    gcashAmount.value = data.amount;

    document.getElementById('cashBookingId').value = data.id;
    document.getElementById('cashAmount').value = data.amount;

    document.querySelectorAll('.wf-method-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('.wf-method-tab[data-method="gcash"]').classList.add('active');
    gcashPanel.classList.remove('wf-hidden');
    cashPanel.classList.add('wf-hidden');

    payModal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePayModal() {
    payModal.classList.remove('open');
    document.body.style.overflow = '';
}

payModal.addEventListener('click', function (e) {
    if (e.target === payModal) closePayModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && payModal.classList.contains('open')) {
        closePayModal();
    }
});

document.querySelectorAll('.wf-method-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.wf-method-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        const method = this.dataset.method;
        if (method === 'gcash') {
            gcashPanel.classList.remove('wf-hidden');
            cashPanel.classList.add('wf-hidden');
        } else {
            gcashPanel.classList.add('wf-hidden');
            cashPanel.classList.remove('wf-hidden');
        }
    });
});

document.getElementById('gcashForm').addEventListener('submit', function () {
    const btn = this.querySelector('.wf-btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
});

document.getElementById('cashNotifyForm').addEventListener('submit', function () {
    const btn = this.querySelector('.wf-btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Notifying staff...';
});
</script>

</body>
</html>