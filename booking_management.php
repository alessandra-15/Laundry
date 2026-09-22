<?php
/**
 * booking_management.php
 * WashFlow — STAFF Booking Management (Verify + Execute)
 *
 * ✅ Staff-only (auto-redirect admin → admin_bookings.php, guest → staff_login.php)
 * ✅ Walk-in + Online bookings
 * ✅ Verify payments (mark as paid)
 * ✅ Status workflow (Pending → Confirmed/Processing → Ready → Completed)
 * ✅ Auto-notify customer on payment confirmation
 * ✅ Payment verification filter (?payment=pending)
 * ✅ Payment verification modal (?payment=verify&id=X)
 * ✅ CSV export
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* ══════════════════════════════════════════
   AUTH — STAFF ONLY
   Admin → admin_bookings.php
   Guest → staff_login.php
   ══════════════════════════════════════════ */
if (empty($_SESSION['staff_id'])) {
    if (!empty($_SESSION['admin_id'])) {
        header('Location: admin_bookings.php');
        exit();
    }
    header('Location: staff_login.php');
    exit();
}

$current_id   = (int)$_SESSION['staff_id'];
$current_name = $_SESSION['staff_name'] ?? $_SESSION['staff_username'] ?? 'Staff';
$current_role = 'staff';
$is_admin     = false;
$is_staff     = true;
$role_label   = 'Staff';

$admin_id   = $current_id;      // Logger compat
$admin_name = $current_name;

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
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

if (!function_exists('payment_badge')) {
    function payment_badge($status) {
        $s = strtolower(trim((string)$status));
        switch ($s) {
            case 'paid':
                return ['label' => 'Paid',                'color' => '#1E7E45', 'bg' => '#EAF7F0', 'icon' => 'fa-check-circle'];
            case 'pending_verification':
            case 'pending':
                return ['label' => 'Pending Verification', 'color' => '#946200', 'bg' => '#FEF9E7', 'icon' => 'fa-hourglass-half'];
            case 'unpaid':
                return ['label' => 'Unpaid',               'color' => '#A8322D', 'bg' => '#FDEDEC', 'icon' => 'fa-times-circle'];
            case 'refunded':
                return ['label' => 'Refunded',             'color' => '#5B2E91', 'bg' => '#F4ECFB', 'icon' => 'fa-undo'];
            case 'failed':
                return ['label' => 'Failed',               'color' => '#A8322D', 'bg' => '#FDEDEC', 'icon' => 'fa-exclamation-circle'];
            case 'no payment':
            case 'none':
                return ['label' => 'No Payment',           'color' => '#5A7184', 'bg' => '#F4F7F9', 'icon' => 'fa-minus-circle'];
            default:
                return ['label' => 'Unpaid',               'color' => '#A8322D', 'bg' => '#FDEDEC', 'icon' => 'fa-times-circle'];
        }
    }
}

if (!function_exists('notify_customer')) {
    function notify_customer(mysqli $conn, array $opts): bool {
        $sent = false;

        $hasNotif = false;
        if ($res = $conn->query("SHOW TABLES LIKE 'notifications'")) {
            $hasNotif = ($res->num_rows > 0);
            $res->free();
        }
        if ($hasNotif) {
            $cols = [];
            $res = $conn->query("SHOW COLUMNS FROM notifications");
            if ($res) while ($r = $res->fetch_assoc()) $cols[] = strtolower($r['Field']);

            $fields = [];
            $values = [];
            $types  = '';

            $map = [
                'customer_id' => $opts['customer_id'] ?? null,
                'online_id'   => $opts['online_id']   ?? null,
                'title'       => $opts['subject']     ?? 'Payment Verified',
                'subject'     => $opts['subject']     ?? 'Payment Verified',
                'message'     => $opts['message']     ?? '',
                'body'        => $opts['message']     ?? '',
                'channel'     => 'system',
                'type'        => 'payment',
                'status'      => 'unread',
                'is_read'     => 0,
                'created_at'  => date('Y-m-d H:i:s'),
                'timestamp'   => date('Y-m-d H:i:s'),
                'recipient'   => $opts['name'] ?? '',
                'contact'     => $opts['contact'] ?? '',
                'email'       => $opts['email'] ?? '',
            ];
            foreach ($map as $col => $val) {
                if (in_array($col, $cols, true) && $val !== null) {
                    $fields[] = "`$col`";
                    $values[] = $val;
                    $types   .= is_int($val) ? 'i' : 's';
                }
            }
            if ($fields) {
                $sql = "INSERT INTO notifications (" . implode(',', $fields) . ") VALUES (" .
                       implode(',', array_fill(0, count($fields), '?')) . ")";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param($types, ...$values);
                    $sent = $stmt->execute();
                    $stmt->close();
                }
            }
        }

        if (!empty($opts['email']) && function_exists('mail')) {
            $to      = $opts['email'];
            $subject = $opts['subject'] ?? 'Payment Verified';
            $body    = $opts['message'] ?? '';
            $headers = "From: WashFlow <no-reply@washflow.local>\r\n" .
                       "Content-Type: text/plain; charset=UTF-8\r\n";
            @mail($to, $subject, $body, $headers);
            $sent = true;
        }

        if (class_exists('Logger')) {
            Logger::info('Customer notified', [
                'name'      => $opts['name']    ?? '',
                'contact'   => $opts['contact'] ?? '',
                'email'     => $opts['email']   ?? '',
                'subject'   => $opts['subject'] ?? '',
                'delivered' => $sent,
            ]);
        }

        return $sent;
    }
}

/* ══════════════════════════════════════════
   PAYMENT TABLE DETECTION
   ══════════════════════════════════════════ */
$has_payments_table = false;
if ($res = $conn->query("SHOW TABLES LIKE 'payments_online'")) {
    $has_payments_table = ($res->num_rows > 0);
    $res->free();
}

$pay_ref_col = 'booking_id';
if ($has_payments_table) {
    $found = false;
    $res = $conn->query("SHOW COLUMNS FROM payments_online LIKE 'booking_id'");
    if ($res && $res->num_rows > 0) { $found = true; }
    if ($res) $res->free();

    if (!$found) {
        foreach (['booking_ref','online_id','booking_online_id'] as $cand) {
            $r = $conn->query("SHOW COLUMNS FROM payments_online LIKE '" . $conn->real_escape_string($cand) . "'");
            if ($r && $r->num_rows > 0) { $pay_ref_col = $cand; $found = true; }
            if ($r) $r->free();
            if ($found) break;
        }
    }
}

$pay_has = ['method'=>false,'ref'=>false,'proof'=>false,'notes'=>false,'verified_at'=>false,'verified_by'=>false];
if ($has_payments_table) {
    $res = $conn->query("SHOW COLUMNS FROM payments_online");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $f = strtolower($r['Field']);
            if ($f === 'payment_method')   $pay_has['method']      = true;
            if ($f === 'reference_number') $pay_has['ref']         = true;
            if ($f === 'payment_proof')    $pay_has['proof']       = true;
            if ($f === 'notes')            $pay_has['notes']       = true;
            if ($f === 'verified_at')      $pay_has['verified_at'] = true;
            if ($f === 'verified_by')      $pay_has['verified_by'] = true;
        }
        $res->free();
    }
}

/* ══════════════════════════════════════════
   POST HANDLERS
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    /* ---------- STATUS UPDATE ---------- */
    if ($action === 'update_status') {
        $type   = $_POST['type']   ?? '';
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        $allowed_walkin = ['Pending','Confirmed','In Progress','Completed','Cancelled'];
        $allowed_online = ['Pending','Processing','Ready','Completed','Cancelled'];

        $ok = false;
        try {
            if ($type === 'walkin' && in_array($status, $allowed_walkin, true)) {
                $stmt = $conn->prepare("UPDATE booking SET status = ? WHERE Booking_ID = ?");
                $stmt->bind_param('si', $status, $id);
                $ok = $stmt->execute();
                $stmt->close();
            } elseif ($type === 'online' && in_array($status, $allowed_online, true)) {
                $stmt = $conn->prepare("UPDATE booking_online SET status = ? WHERE id = ?");
                $stmt->bind_param('si', $status, $id);
                $ok = $stmt->execute();
                $stmt->close();
            }

            if ($ok && class_exists('Logger')) {
                Logger::info('Staff updated booking status', [
                    'type' => $type, 'id' => $id, 'status' => $status,
                    'by' => $current_id, 'role' => 'staff'
                ]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Status update failed', ['error' => $ex->getMessage()]);
            $ok = false;
        }

        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok ? "Booking #$id updated to $status." : 'Failed to update booking status.'
        ];
    }

    /* ---------- MARK AS PAID ---------- */
    if ($action === 'mark_paid') {
        $payment_id = (int)($_POST['id'] ?? 0);
        $note       = trim($_POST['note'] ?? '');

        $ok = false;
        $customer_info = null;

        try {
            if ($has_payments_table && $payment_id > 0) {
                $stmt = $conn->prepare("
                    SELECT p.payment_id, p.`{$pay_ref_col}` AS booking_ref,
                           p.amount, p.payment_method, p.reference_number,
                           b.customer_name, b.contact_number, b.service, b.id AS online_id
                    FROM payments_online p
                    LEFT JOIN booking_online b ON p.`{$pay_ref_col}` = b.id
                    WHERE p.payment_id = ?
                    LIMIT 1
                ");
                if ($stmt) {
                    $stmt->bind_param('i', $payment_id);
                    $stmt->execute();
                    $customer_info = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }

                if ($customer_info) {
                    $set_parts = ["payment_status = 'Paid'"];
                    $types     = '';
                    $params    = [];

                    if ($pay_has['verified_at']) {
                        $set_parts[] = "verified_at = NOW()";
                    }
                    if ($pay_has['verified_by']) {
                        $set_parts[] = "verified_by = ?";
                        $params[]    = $current_name . ' (staff)';
                        $types      .= 's';
                    }
                    if ($pay_has['notes'] && $note !== '') {
                        $set_parts[] = "notes = CONCAT(COALESCE(notes, ''), ?)";
                        $params[]    = "\n[" . date('Y-m-d H:i') . " | " . $current_name . " | staff] " . $note;
                        $types      .= 's';
                    }

                    $sql = "UPDATE payments_online SET " . implode(', ', $set_parts) . " WHERE payment_id = ?";
                    $params[] = $payment_id;
                    $types   .= 'i';

                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $ok = $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            if ($ok && class_exists('Logger')) {
                Logger::info('Staff marked payment as paid', [
                    'payment_id' => $payment_id,
                    'note'       => $note,
                    'by'         => $current_id,
                    'role'       => 'staff'
                ]);
            }

            if ($ok && $customer_info) {
                $cust_name  = $customer_info['customer_name'] ?: 'Customer';
                $amount_str = '₱' . number_format((float)($customer_info['amount'] ?? 0), 2);
                $booking_id = $customer_info['booking_ref'] ?? '';

                notify_customer($conn, [
                    'online_id' => $customer_info['online_id'] ?? null,
                    'name'      => $cust_name,
                    'contact'   => $customer_info['contact_number'] ?? '',
                    'email'     => null,
                    'subject'   => "Payment Verified — WashFlow Booking #{$booking_id}",
                    'message'   => "Hi {$cust_name}, your payment of {$amount_str} for booking #{$booking_id} " .
                                   "has been verified. Thank you for choosing WashFlow!",
                ]);
            }
        } catch (Exception $ex) {
            if (class_exists('Logger')) Logger::error('Mark paid failed', ['error' => $ex->getMessage()]);
            $ok = false;
        }

        $flash = [
            'type'    => $ok ? 'success' : 'error',
            'message' => $ok
                ? "Payment #$payment_id marked as PAID. Customer notified."
                : 'Failed to mark payment as paid.'
        ];

        $back = $_POST['return_to'] ?? 'booking_management.php?tab=online';
        $_SESSION['wf_flash'] = $flash;
        header('Location: ' . $back);
        exit();
    }

    /* ---------- CSV EXPORT ---------- */
    if ($action === 'export_csv') {
        $type = $_POST['export_type'] ?? 'walkin';
        if (!in_array($type, ['walkin','online'], true)) $type = 'walkin';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookings_' . $type . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        if ($type === 'walkin') {
            fputcsv($out, ['Booking ID','Customer','Contact','Service','Add-ons','Pick/Deliver','Status','Total','Date']);
            $q = $conn->query("
                SELECT b.Booking_ID, b.service, b.add_ons, b.pick_deliver, b.status,
                       b.total_amount, b.booking_date,
                       c.first_name, c.last_name, c.contact_number
                FROM booking b
                LEFT JOIN customer_info c ON b.Customer_ID = c.Customer_ID
                GROUP BY b.Booking_ID
                ORDER BY b.booking_date DESC
            ");
            if ($q) while ($r = $q->fetch_assoc()) {
                fputcsv($out, [
                    $r['Booking_ID'],
                    trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: 'Walk-in',
                    $r['contact_number'] ?? '',
                    $r['service'] ?? '',
                    $r['add_ons'] ?? '',
                    $r['pick_deliver'] ?? '',
                    $r['status'] ?? 'Pending',
                    number_format((float)$r['total_amount'], 2, '.', ''),
                    $r['booking_date'] ?? ''
                ]);
            }
        } else {
            fputcsv($out, ['ID','Customer','Contact','Service','Add-ons','Delivery','Status','Payment','Total','Date']);
            $q = $conn->query("
                SELECT b.id, b.customer_name, b.contact_number, b.service, b.addons,
                       b.delivery_option, b.status, b.total_amount, b.timestamp,
                       COALESCE(p.payment_status, 'No Payment') AS payment_status
                FROM booking_online b
                " . ($has_payments_table ? "LEFT JOIN payments_online p ON p.`{$pay_ref_col}` = b.id" : "") . "
                ORDER BY b.timestamp DESC
            ");
            if ($q) while ($r = $q->fetch_assoc()) {
                fputcsv($out, [
                    $r['id'],
                    $r['customer_name'] ?? '',
                    $r['contact_number'] ?? '',
                    $r['service'] ?? '',
                    $r['addons'] ?? '',
                    $r['delivery_option'] ?? '',
                    $r['status'] ?: 'Pending',
                    $r['payment_status'] ?? 'No Payment',
                    number_format((float)$r['total_amount'], 2, '.', ''),
                    $r['timestamp'] ?? ''
                ]);
            }
        }
        fclose($out);
        exit();
    }
}

if (!$flash && !empty($_SESSION['wf_flash'])) {
    $flash = $_SESSION['wf_flash'];
    unset($_SESSION['wf_flash']);
}

/* ══════════════════════════════════════════
   FILTERS + PAGINATION
   ══════════════════════════════════════════ */
$payment_param = $_GET['payment'] ?? '';
$tab = $_GET['tab'] ?? null;
if ($tab === null) {
    if ($payment_param === 'pending' || $payment_param === 'verify') {
        $tab = 'online';
    } else {
        $tab = 'walkin';
    }
}

$search     = trim($_GET['q'] ?? '');
$status_f   = trim($_GET['status'] ?? '');
$payment_f  = trim($payment_param);
$date_from  = trim($_GET['date_from'] ?? '');
$date_to    = trim($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;
$sort       = $_GET['sort'] ?? 'date_desc';

$verify_id = (int)($_GET['id'] ?? $_GET['verify_id'] ?? 0);

if (!in_array($tab, ['walkin','online'], true)) $tab = 'walkin';
if ($payment_f === 'verify') $payment_f = '';

$order_map = [
    'date_desc'   => 'booking_date DESC',
    'date_asc'    => 'booking_date ASC',
    'id_desc'     => 'Booking_ID DESC',
    'id_asc'      => 'Booking_ID ASC',
    'amount_desc' => 'total_amount DESC',
    'amount_asc'  => 'total_amount ASC',
];
$order_sql = $order_map[$sort] ?? 'booking_date DESC';

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = [
    'walkin_total'    => 0,
    'walkin_pending'  => 0,
    'walkin_progress' => 0,
    'walkin_done'     => 0,
    'online_total'    => 0,
    'online_pending'  => 0,
    'online_progress' => 0,
    'online_done'     => 0,
    'pay_pending'     => 0,
    'pay_paid'        => 0,
];

try {
    $q = $conn->query("SELECT COUNT(DISTINCT Booking_ID) c FROM booking");
    if ($q) $stats['walkin_total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT Booking_ID) c FROM booking WHERE status='Pending'");
    if ($q) $stats['walkin_pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT Booking_ID) c FROM booking WHERE status IN ('Confirmed','In Progress')");
    if ($q) $stats['walkin_progress'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT Booking_ID) c FROM booking WHERE status='Completed'");
    if ($q) $stats['walkin_done'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking_online");
    if ($q) $stats['online_total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Pending' OR status=''");
    if ($q) $stats['online_pending'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status IN ('Processing','Ready')");
    if ($q) $stats['online_progress'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM booking_online WHERE status='Completed'");
    if ($q) $stats['online_done'] = (int)$q->fetch_assoc()['c'];

    if ($has_payments_table) {
        $q = $conn->query("SELECT COUNT(*) c FROM payments_online WHERE LOWER(payment_status) IN ('pending','pending_verification')");
        if ($q) $stats['pay_pending'] = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM payments_online WHERE LOWER(payment_status) = 'paid'");
        if ($q) $stats['pay_paid'] = (int)$q->fetch_assoc()['c'];
    }
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Stats failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH BOOKINGS
   ══════════════════════════════════════════ */
$bookings    = [];
$total_rows  = 0;
$total_pages = 1;

try {
    if ($tab === 'walkin') {
        $where  = ["1=1"];
        $params = [];
        $types  = '';

        if ($search !== '') {
            $where[] = "(b.Booking_ID LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR b.service LIKE ? OR c.contact_number LIKE ?)";
            $like = '%' . $search . '%';
            for ($i = 0; $i < 5; $i++) { $params[] = $like; $types .= 's'; }
        }
        if ($status_f !== '' && in_array($status_f, ['Pending','Confirmed','In Progress','Completed','Cancelled'], true)) {
            $where[] = "b.status = ?";
            $params[] = $status_f;
            $types .= 's';
        }
        if ($date_from !== '') {
            $where[] = "DATE(b.booking_date) >= ?";
            $params[] = $date_from;
            $types .= 's';
        }
        if ($date_to !== '') {
            $where[] = "DATE(b.booking_date) <= ?";
            $params[] = $date_to;
            $types .= 's';
        }

        $where_sql = implode(' AND ', $where);

        $count_sql = "SELECT COUNT(DISTINCT b.Booking_ID) c
                      FROM booking b
                      LEFT JOIN customer_info c ON b.Customer_ID = c.Customer_ID
                      WHERE $where_sql";
        $stmt = $conn->prepare($count_sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $total_pages = max(1, (int)ceil($total_rows / $per_page));

        $list_sql = "SELECT b.Booking_ID, b.Customer_ID, b.service, b.add_ons, b.pick_deliver,
                            b.special_instructions, b.status, b.total_amount, b.booking_date,
                            c.first_name, c.last_name, c.contact_number, c.email,
                            NULL AS payment_id,
                            NULL AS payment_status,
                            NULL AS payment_method,
                            NULL AS payment_reference,
                            NULL AS payment_proof
                     FROM booking b
                     LEFT JOIN customer_info c ON b.Customer_ID = c.Customer_ID
                     WHERE $where_sql
                     GROUP BY b.Booking_ID
                     ORDER BY $order_sql
                     LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($list_sql);
        $bind_types = $types . 'ii';
        $bind_params = array_merge($params, [$per_page, $offset]);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

    } else {
        $where  = ["1=1"];
        $params = [];
        $types  = '';

        if ($search !== '') {
            $where[] = "(b.id LIKE ? OR b.customer_name LIKE ? OR b.contact_number LIKE ? OR b.service LIKE ?)";
            $like = '%' . $search . '%';
            for ($i = 0; $i < 4; $i++) { $params[] = $like; $types .= 's'; }
        }
        if ($status_f !== '' && in_array($status_f, ['Pending','Processing','Ready','Completed','Cancelled'], true)) {
            $where[] = "b.status = ?";
            $params[] = $status_f;
            $types .= 's';
        }
        if ($payment_f !== '' && $has_payments_table) {
            if ($payment_f === 'pending') {
                $where[] = "LOWER(p.payment_status) IN ('pending','pending_verification')";
            } elseif ($payment_f === 'paid') {
                $where[] = "LOWER(p.payment_status) = 'paid'";
            } elseif ($payment_f === 'unpaid') {
                $where[] = "(p.payment_status IS NULL OR LOWER(p.payment_status) = 'unpaid')";
            }
        }
        if ($date_from !== '') {
            $where[] = "DATE(b.timestamp) >= ?";
            $params[] = $date_from;
            $types .= 's';
        }
        if ($date_to !== '') {
            $where[] = "DATE(b.timestamp) <= ?";
            $params[] = $date_to;
            $types .= 's';
        }

        $where_sql = implode(' AND ', $where);

        $pay_join = $has_payments_table
            ? "LEFT JOIN payments_online p ON p.`{$pay_ref_col}` = b.id"
            : "";

        $count_sql = "SELECT COUNT(*) c FROM booking_online b $pay_join WHERE $where_sql";
        $stmt = $conn->prepare($count_sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        $total_pages = max(1, (int)ceil($total_rows / $per_page));

        $online_order = str_replace(['Booking_ID','booking_date'], ['b.id','b.timestamp'], $order_sql);

        $pay_select = $has_payments_table
            ? ", p.payment_id,
                 p.payment_status,
                 " . ($pay_has['method'] ? "p.payment_method," : "NULL AS payment_method,") . "
                 " . ($pay_has['ref']    ? "p.reference_number AS payment_reference," : "NULL AS payment_reference,") . "
                 " . ($pay_has['proof']  ? "p.payment_proof" : "NULL AS payment_proof")
            : ", NULL AS payment_id,
                 NULL AS payment_status,
                 NULL AS payment_method,
                 NULL AS payment_reference,
                 NULL AS payment_proof";

        $list_sql = "SELECT b.id AS Booking_ID, b.customer_id AS Customer_ID, b.customer_name,
                            b.contact_number, b.service, b.addons AS add_ons,
                            b.delivery_option AS pick_deliver, b.special_instructions,
                            b.status, b.total_amount, b.timestamp AS booking_date,
                            NULL AS first_name, NULL AS last_name, NULL AS email
                            $pay_select
                     FROM booking_online b
                     $pay_join
                     WHERE $where_sql
                     ORDER BY $online_order
                     LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($list_sql);
        $bind_types = $types . 'ii';
        $bind_params = array_merge($params, [$per_page, $offset]);
        $stmt->bind_param($bind_types, ...$bind_params);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch bookings failed', ['error' => $ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH VERIFY TARGET
   ══════════════════════════════════════════ */
$verify_target = null;
if ($verify_id > 0 && $has_payments_table) {
    try {
        $pay_select = "
            p.payment_id,
            p.`{$pay_ref_col}` AS booking_ref,
            p.amount,
            p.payment_status,
            " . ($pay_has['method'] ? "p.payment_method," : "NULL AS payment_method,") . "
            " . ($pay_has['ref']    ? "p.reference_number," : "NULL AS reference_number,") . "
            " . ($pay_has['proof']  ? "p.payment_proof," : "NULL AS payment_proof,") . "
            p.payment_date
        ";

        $stmt = $conn->prepare("
            SELECT $pay_select,
                   b.customer_name, b.contact_number, b.service, b.total_amount, b.id AS online_id
            FROM payments_online p
            LEFT JOIN booking_online b ON p.`{$pay_ref_col}` = b.id
            WHERE p.payment_id = ?
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param('i', $verify_id);
            $stmt->execute();
            $verify_target = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Verify fetch failed', ['error' => $ex->getMessage()]);
    }
}

/* ══════════════════════════════════════════
   URL HELPER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'tab'       => $_GET['tab']       ?? 'walkin',
        'q'         => $_GET['q']         ?? '',
        'status'    => $_GET['status']    ?? '',
        'payment'   => $_GET['payment']   ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'sort'      => $_GET['sort']      ?? 'date_desc',
        'page'      => $_GET['page']      ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'booking_management.php?' . http_build_query(array_filter($merged, function($v) {
        return $v !== '' && $v !== null;
    }));
}

function js_json($data) {
    return json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Queue — WashFlow Staff</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    color:var(--text-primary); line-height:1.6; -webkit-font-smoothing:antialiased;
    background:var(--bg); overflow:hidden; font-size:14px;
}
h1, h2, h3, h4, h5 { font-weight:700; letter-spacing:-0.02em; }
a { text-decoration:none; }

.wf-layout { display:grid; grid-template-columns:300px 1fr; height:100vh; overflow:hidden; }

/* SIDEBAR */
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

/* MAIN */
.wf-main { padding:2rem 2.5rem 3rem; overflow-y:auto; overflow-x:hidden; height:100vh; scrollbar-width:none; }
.wf-main::-webkit-scrollbar { display:none; width:0; }

/* TOPBAR */
.wf-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; gap:1rem; flex-wrap:wrap; }
.wf-topbar-greeting h1 { color:var(--dark-blue); font-size:1.6rem; font-weight:800; margin:0; letter-spacing:-0.03em; line-height:1.3; }
.wf-topbar-greeting h1 .accent { color:var(--gold); }
.wf-topbar-greeting p { color:var(--text-secondary); font-size:0.88rem; margin:0.2rem 0 0; font-weight:500; }
.wf-topbar-right { display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; }
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

.wf-role-pill {
    display:inline-flex; align-items:center; gap:0.5rem;
    padding:0.55rem 0.95rem; border-radius:999px;
    font-size:0.78rem; font-weight:700; letter-spacing:0.03em;
    background:linear-gradient(135deg, #E8F6FC 0%, #D2EEF9 100%);
    color:#00537A; border:1px solid #A8E8F9;
}
.wf-role-pill i { font-size:0.8rem; }

/* FLASH */
.wf-flash { padding:0.9rem 1.15rem; border-radius:12px; margin-bottom:1.25rem; font-weight:600; font-size:0.88rem; display:flex; align-items:center; gap:0.7rem; animation:flashIn 0.35s; }
@keyframes flashIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.wf-flash.success { background:#EAF7F0; color:#1E7E45; border-left:4px solid var(--green); }
.wf-flash.error { background:#FDEDEC; color:#A8322D; border-left:4px solid var(--red); }

/* STATS */
.wf-stats-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:1rem; margin-bottom:1.5rem; }
.wf-stat-card { background:white; border-radius:14px; padding:1.25rem; border:1px solid var(--border); transition:all 0.25s ease; display:flex; flex-direction:column; justify-content:space-between; min-height:130px; }
.wf-stat-card:hover { border-color:var(--light-blue); box-shadow:0 6px 20px rgba(0,83,122,0.08); transform:translateY(-3px); }
.wf-stat-card.clickable { cursor:pointer; }
.wf-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.85rem; }
.wf-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-stat-icon.icon-blue   { background:var(--light-blue-soft); color:var(--primary); }
.wf-stat-icon.icon-yellow { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-stat-icon.icon-green  { background:#EAF7F0; color:#1E7E45; }
.wf-stat-icon.icon-purple { background:#F4ECFB; color:#5B2E91; }
.wf-stat-icon.icon-dark   { background:#E6EDF3; color:var(--dark-blue); }
.wf-stat-icon.icon-red    { background:#FDEDEC; color:#A8322D; }
.wf-stat-value { font-size:1.6rem; font-weight:800; color:var(--dark-blue); line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.2rem; }
.wf-stat-label { font-size:0.78rem; color:var(--text-secondary); font-weight:500; }

/* TABS */
.wf-tabs { display:flex; gap:0.5rem; background:white; padding:0.4rem; border-radius:12px; border:1px solid var(--border); margin-bottom:1.25rem; width:fit-content; }
.wf-tab { display:inline-flex; align-items:center; gap:0.55rem; padding:0.7rem 1.25rem; border-radius:9px; font-size:0.85rem; font-weight:600; color:var(--text-secondary); cursor:pointer; transition:all 0.2s; }
.wf-tab:hover { color:var(--primary); background:var(--light-blue-pale); }
.wf-tab.active { background:var(--dark-blue); color:white; box-shadow:0 4px 12px rgba(6,52,82,0.2); }
.wf-tab .tab-count { background:rgba(255,255,255,0.15); color:inherit; padding:0.15rem 0.55rem; border-radius:6px; font-size:0.72rem; font-weight:700; min-width:24px; text-align:center; }
.wf-tab:not(.active) .tab-count { background:var(--light-blue-soft); color:var(--primary); }

/* FILTER BAR */
.wf-filter-bar { background:white; border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; display:flex; align-items:flex-end; gap:1rem; flex-wrap:nowrap; }
.wf-filter-group { display:flex; flex-direction:column; gap:0.4rem; min-width:0; }
.wf-filter-group label { font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em; white-space:nowrap; }
.wf-input, .wf-select { width:100%; padding:0.65rem 0.9rem; border:1px solid var(--border); border-radius:9px; font-size:0.85rem; font-family:inherit; color:var(--text-primary); background:white; transition:all 0.2s; outline:none; }
.wf-input:focus, .wf-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }
.wf-btn { display:inline-flex; align-items:center; gap:0.5rem; padding:0.7rem 1.2rem; border-radius:9px; font-size:0.85rem; font-weight:600; cursor:pointer; transition:all 0.2s; border:1px solid transparent; font-family:inherit; white-space:nowrap; }
.wf-btn-primary { background:var(--primary); color:white; }
.wf-btn-primary:hover { background:var(--primary-mid); }
.wf-btn-outline { background:white; color:var(--primary); border-color:var(--border); }
.wf-btn-outline:hover { border-color:var(--primary); background:var(--light-blue-pale); }
.wf-btn-yellow { background:var(--yellow); color:var(--dark-blue-deep); }
.wf-btn-yellow:hover { background:#FFCE00; }
.wf-btn-danger { background:var(--red); color:white; }
.wf-btn-danger:hover { background:#C0392B; }
.wf-btn-green { background:var(--green); color:white; }
.wf-btn-green:hover { background:#1E7E45; }

/* CARD */
.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }
.wf-card-body { padding:0; }

/* TABLE */
.wf-table-wrap { overflow-x:auto; }
.wf-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.wf-table thead th { color:var(--text-muted); font-weight:600; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.1em; padding:0.9rem 1rem; text-align:left; border-bottom:1px solid var(--border); white-space:nowrap; background:#FAFCFE; }
.wf-table thead th a { color:inherit; display:inline-flex; align-items:center; gap:0.35rem; transition:color 0.15s; }
.wf-table thead th a:hover { color:var(--primary); }
.wf-table thead th a i { font-size:0.65rem; opacity:0.6; }
.wf-table tbody td { padding:0.95rem 1rem; border-bottom:1px solid var(--border-soft); vertical-align:middle; }
.wf-table tbody tr:last-child td { border-bottom:none; }
.wf-table tbody tr:hover { background:var(--light-blue-pale); }

.wf-booking-id { font-weight:700; color:var(--primary); font-family:'SF Mono',Monaco,monospace; font-size:0.8rem; }
.wf-customer { font-weight:600; color:var(--dark-blue); }
.wf-service { color:var(--text-secondary); font-size:0.84rem; }
.wf-amount { font-weight:700; color:var(--dark-blue); }
.wf-date { color:var(--text-muted); font-size:0.8rem; white-space:nowrap; }

.wf-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.35rem 0.7rem; border-radius:7px; font-size:0.72rem; font-weight:600; letter-spacing:0.01em; white-space:nowrap; }
.wf-badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

.wf-pay-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.3rem 0.7rem; border-radius:999px; font-size:0.7rem; font-weight:700; letter-spacing:0.02em; white-space:nowrap; border:1px solid transparent; cursor:pointer; transition:all 0.15s; }
.wf-pay-badge:hover { transform:translateY(-1px); }
.wf-pay-badge i { font-size:0.7rem; }
.wf-pay-badge.pending { background:#FEF9E7; color:#946200; border-color:#F5E6A8; }
.wf-pay-badge.pending:hover { background:#FCEFC4; box-shadow:0 4px 10px rgba(148,98,0,0.15); }
.wf-pay-badge.paid { background:#EAF7F0; color:#1E7E45; border-color:#B8E6CC; }
.wf-pay-badge.unpaid { background:#FDEDEC; color:#A8322D; border-color:#F5C6C2; }
.wf-pay-badge.refunded { background:#F4ECFB; color:#5B2E91; border-color:#DCC4EF; }
.wf-pay-badge.none { background:#F4F7F9; color:#5A7184; border-color:#DCE4EA; cursor:default; }
.wf-pay-badge.none:hover { transform:none; }

.wf-row-actions { display:flex; gap:0.4rem; align-items:center; justify-content:flex-end; }
.wf-icon-btn { width:32px; height:32px; border-radius:8px; border:1px solid var(--border); background:white; color:var(--text-secondary); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s; font-size:0.8rem; }
.wf-icon-btn:hover { border-color:var(--primary); color:var(--primary); background:var(--light-blue-pale); }
.wf-icon-btn.view:hover { border-color:var(--primary); color:var(--primary); }
.wf-icon-btn.confirm:hover { border-color:var(--green); color:var(--green); background:#EAF7F0; }
.wf-icon-btn.process:hover { border-color:var(--purple); color:var(--purple); background:#F4ECFB; }
.wf-icon-btn.ready:hover { border-color:var(--gold); color:var(--gold); background:var(--yellow-soft); }
.wf-icon-btn.complete:hover { border-color:var(--green); color:var(--green); background:#EAF7F0; }
.wf-icon-btn.cancel:hover { border-color:var(--red); color:var(--red); background:#FDEDEC; }
.wf-icon-btn.verify { border-color:#F5E6A8; background:#FEF9E7; color:#946200; }
.wf-icon-btn.verify:hover { border-color:var(--gold); background:#FCEFC4; color:#7A5A00; }

.wf-empty { text-align:center; padding:3.5rem 1rem; color:var(--text-muted); }
.wf-empty i { font-size:3rem; color:var(--light-blue); margin-bottom:0.9rem; display:block; }
.wf-empty p { margin:0; font-size:0.9rem; font-weight:500; }

/* PAGINATION */
.wf-pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-top:1px solid var(--border-soft); flex-wrap:wrap; gap:0.75rem; }
.wf-pagination-info { font-size:0.8rem; color:var(--text-muted); font-weight:500; }
.wf-pagination { display:flex; gap:0.3rem; align-items:center; }
.wf-page-btn { min-width:36px; height:36px; padding:0 0.6rem; border-radius:8px; border:1px solid var(--border); background:white; color:var(--text-secondary); font-size:0.8rem; font-weight:600; display:inline-flex; align-items:center; justify-content:center; transition:all 0.15s; }
.wf-page-btn:hover:not(.disabled):not(.active) { border-color:var(--primary); color:var(--primary); background:var(--light-blue-pale); }
.wf-page-btn.active { background:var(--dark-blue); color:white; border-color:var(--dark-blue); }
.wf-page-btn.disabled { opacity:0.4; cursor:not-allowed; pointer-events:none; }

/* PANEL */
.wf-panel-overlay { position:fixed; inset:0; background:rgba(6,52,82,0.4); backdrop-filter:blur(2px); opacity:0; visibility:hidden; transition:all 0.3s ease; z-index:1040; }
.wf-panel-overlay.open { opacity:1; visibility:visible; }
.wf-panel { position:fixed; top:0; right:0; bottom:0; width:600px; max-width:100vw; background:white; z-index:1050; transform:translateX(100%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1); display:flex; flex-direction:column; box-shadow:-20px 0 60px rgba(6,52,82,0.15); }
.wf-panel.open { transform:translateX(0); }
.wf-panel-header { padding:1.5rem 1.75rem; border-bottom:1px solid var(--border); display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; background:linear-gradient(135deg, var(--light-blue-pale) 0%, white 100%); }
.wf-panel-title { display:flex; align-items:center; gap:0.9rem; flex:1; min-width:0; }
.wf-panel-title-icon { width:46px; height:46px; border-radius:12px; background:var(--dark-blue); color:var(--yellow); display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.wf-panel-title h3 { font-size:1.05rem; color:var(--dark-blue); margin:0; font-weight:700; }
.wf-panel-title p { font-size:0.78rem; color:var(--text-muted); margin:0.15rem 0 0; font-weight:500; }
.wf-panel-close { width:36px; height:36px; border-radius:9px; border:1px solid var(--border); background:white; color:var(--text-secondary); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s; flex-shrink:0; }
.wf-panel-close:hover { border-color:var(--red); color:var(--red); background:#FDEDEC; }
.wf-panel-body { flex:1; overflow-y:auto; padding:1.5rem 1.75rem; }
.wf-panel-body::-webkit-scrollbar { width:6px; }
.wf-panel-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }
.wf-panel-footer { padding:1.25rem 1.75rem; border-top:1px solid var(--border); background:#FAFCFE; display:flex; gap:0.6rem; flex-wrap:wrap; }

.wf-detail-section { margin-bottom:1.5rem; }
.wf-detail-section-title { font-size:0.72rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.75rem; display:flex; align-items:center; gap:0.5rem; }
.wf-detail-section-title i { color:var(--primary); }
.wf-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1rem; }
.wf-detail-item { display:flex; flex-direction:column; gap:0.2rem; }
.wf-detail-item.full { grid-column:1 / -1; }
.wf-detail-label { font-size:0.72rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }
.wf-detail-value { font-size:0.88rem; color:var(--text-primary); font-weight:600; word-break:break-word; }
.wf-detail-value.mono { font-family:'SF Mono',Monaco,monospace; color:var(--primary); }
.wf-detail-value.muted { color:var(--text-secondary); font-weight:500; }

/* MODAL */
.wf-modal-overlay { position:fixed; inset:0; background:rgba(6,52,82,0.5); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; z-index:1060; opacity:0; visibility:hidden; transition:all 0.25s; padding:1rem; }
.wf-modal-overlay.open { opacity:1; visibility:visible; }
.wf-modal { background:white; border-radius:16px; padding:1.75rem; max-width:460px; width:100%; box-shadow:0 20px 60px rgba(6,52,82,0.25); transform:scale(0.95); transition:transform 0.25s; }
.wf-modal-overlay.open .wf-modal { transform:scale(1); }
.wf-modal.wide { max-width:560px; }
.wf-modal-icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin:0 auto 1rem; }
.wf-modal-icon.warn { background:#FDEDEC; color:var(--red); }
.wf-modal-icon.info { background:var(--light-blue-soft); color:var(--primary); }
.wf-modal-icon.gold { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-modal-icon.green { background:#EAF7F0; color:#1E7E45; }
.wf-modal h3 { font-size:1.15rem; color:var(--dark-blue); text-align:center; margin-bottom:0.5rem; }
.wf-modal p { font-size:0.88rem; color:var(--text-secondary); text-align:center; margin-bottom:1.5rem; line-height:1.6; }
.wf-modal-actions { display:flex; gap:0.6rem; justify-content:center; }

.wf-verify-summary { background:var(--light-blue-pale); border:1px solid var(--border); border-radius:12px; padding:1rem 1.15rem; margin-bottom:1rem; }
.wf-verify-row { display:flex; justify-content:space-between; align-items:center; padding:0.35rem 0; font-size:0.85rem; border-bottom:1px dashed var(--border); }
.wf-verify-row:last-child { border-bottom:none; }
.wf-verify-row .label { color:var(--text-muted); font-weight:600; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; }
.wf-verify-row .value { color:var(--dark-blue); font-weight:700; }
.wf-verify-proof { text-align:center; margin:0.75rem 0 1rem; padding:0.75rem; border:1px dashed var(--border); border-radius:10px; background:#FAFCFE; }
.wf-verify-proof img { max-width:100%; max-height:220px; border-radius:8px; box-shadow:0 4px 12px rgba(6,52,82,0.1); }
.wf-verify-proof a { display:inline-flex; align-items:center; gap:0.5rem; font-size:0.82rem; font-weight:600; color:var(--primary); }
.wf-verify-proof a:hover { color:var(--primary-mid); text-decoration:underline; }
.wf-verify-note { width:100%; padding:0.7rem 0.9rem; border:1px solid var(--border); border-radius:9px; font-family:inherit; font-size:0.85rem; resize:vertical; min-height:70px; outline:none; transition:all 0.2s; }
.wf-verify-note:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }

/* TOAST */
.wf-toast-wrap { position:fixed; top:1.5rem; right:1.5rem; z-index:1070; display:flex; flex-direction:column; gap:0.6rem; pointer-events:none; }
.wf-toast { display:flex; align-items:center; gap:0.75rem; padding:0.9rem 1.15rem; background:white; border-radius:11px; box-shadow:0 10px 30px rgba(6,52,82,0.15); border-left:4px solid var(--primary); font-size:0.85rem; font-weight:600; color:var(--dark-blue); min-width:260px; max-width:380px; pointer-events:auto; animation:toastIn 0.35s cubic-bezier(0.4,0,0.2,1); }
.wf-toast.success { border-left-color:var(--green); }
.wf-toast.error   { border-left-color:var(--red); }
.wf-toast i { font-size:1rem; }
.wf-toast.success i { color:var(--green); }
.wf-toast.error i { color:var(--red); }
@keyframes toastIn { from { transform:translateX(120%); opacity:0; } to { transform:translateX(0); opacity:1; } }
@keyframes toastOut { from { transform:translateX(0); opacity:1; } to { transform:translateX(120%); opacity:0; } }
.wf-toast.hide { animation:toastOut 0.3s forwards; }

/* MOBILE */
.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:1500px) { .wf-stats-grid { grid-template-columns:repeat(4, 1fr); } }
@media (max-width:1400px) {
    .wf-filter-bar { flex-wrap:wrap; }
    .wf-filter-bar > .wf-filter-group:last-child { margin-left:0 !important; flex:1 0 100% !important; justify-content:flex-end; margin-top:0.5rem; }
}
@media (max-width:1200px) { .wf-stats-grid { grid-template-columns:repeat(3, 1fr); } .wf-panel { width:500px; } }
@media (max-width:1024px) { .wf-layout { grid-template-columns:260px 1fr; } .wf-main { padding:1.5rem; } }
@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
    .wf-stats-grid { grid-template-columns:repeat(2, 1fr); }
    .wf-panel { width:100%; }
    .wf-detail-grid { grid-template-columns:1fr; }
}
@media (max-width:600px) {
    .wf-stats-grid { grid-template-columns:1fr; }
    .wf-filter-bar { flex-direction:column; align-items:stretch; }
    .wf-filter-group { min-width:100%; }
    .wf-tabs { width:100%; overflow-x:auto; }
}
</style>
</head>
<body>

<button class="wf-sidebar-toggle" id="wfSidebarToggle"><i class="fas fa-bars"></i></button>
<div class="wf-sidebar-overlay" id="wfSidebarOverlay"></div>

<div class="wf-layout">

    <!-- SIDEBAR (STAFF ONLY) -->
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
            <a href="booking_management.php" class="wf-nav-item active">
                <i class="fas fa-clipboard-list"></i>
                <span>My Queue</span>
            </a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="staff_walkin.php" class="wf-nav-item">
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
                <div class="wf-user-avatar"><?= e(strtoupper(substr($current_name, 0, 1))) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($current_name) ?></div>
                    <div class="wf-user-role">Staff</div>
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

        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1>My <span class="accent">Queue</span></h1>
                <p>Verify bookings, mark payments, update status</p>
            </div>
            <div class="wf-topbar-right">
                <span class="wf-role-pill"><i class="fas fa-id-badge"></i> Staff Mode</span>
                <div class="wf-topbar-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>

        <?php if ($flash): ?>
        <div id="serverFlash" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" style="display:none;"></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-store"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['walkin_total']) ?></div>
                    <div class="wf-stat-label">Walk-in Bookings</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-hourglass-half"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['walkin_pending']) ?></div>
                    <div class="wf-stat-label">Walk-in Pending</div>
                </div>
            </div>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-purple"><i class="fas fa-globe"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['online_total']) ?></div>
                    <div class="wf-stat-label">Online Bookings</div>
                </div>
            </div>

            <a href="<?= e(build_url(['tab' => 'online', 'payment' => 'pending', 'page' => 1])) ?>" style="text-decoration:none;">
                <div class="wf-stat-card clickable" style="border-color:#F5E6A8;">
                    <div class="wf-stat-top">
                        <div class="wf-stat-icon icon-yellow"><i class="fas fa-money-check-alt"></i></div>
                        <?php if ($stats['pay_pending'] > 0): ?>
                        <span class="wf-badge" style="color:#946200;background:#FEF9E7;">
                            <span class="wf-badge-dot"></span> Needs action
                        </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="wf-stat-value"><?= number_format($stats['pay_pending']) ?></div>
                        <div class="wf-stat-label">Payments to Verify</div>
                    </div>
                </div>
            </a>

            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['walkin_done'] + $stats['online_done']) ?></div>
                    <div class="wf-stat-label">Completed (All)</div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="wf-tabs">
            <a href="<?= e(build_url(['tab' => 'walkin', 'page' => 1, 'status' => '', 'payment' => ''])) ?>" class="wf-tab <?= $tab === 'walkin' ? 'active' : '' ?>">
                <i class="fas fa-store"></i>
                Walk-in
                <span class="tab-count"><?= (int)$stats['walkin_total'] ?></span>
            </a>
            <a href="<?= e(build_url(['tab' => 'online', 'page' => 1, 'status' => '', 'payment' => ''])) ?>" class="wf-tab <?= $tab === 'online' ? 'active' : '' ?>">
                <i class="fas fa-globe"></i>
                Online
                <span class="tab-count"><?= (int)$stats['online_total'] ?></span>
            </a>
        </div>

        <!-- FILTERS -->
        <form method="get" action="booking_management.php" class="wf-filter-bar">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="hidden" name="page" value="1">

            <div class="wf-filter-group" style="flex: 1 1 220px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="Booking ID, customer, service..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 160px;">
                <label>Status</label>
                <select name="status" class="wf-select">
                    <option value="">All Status</option>
                    <?php if ($tab === 'walkin'): ?>
                        <?php foreach (['Pending','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $status_f === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach (['Pending','Processing','Ready','Completed','Cancelled'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $status_f === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 175px;">
                <label>Payment</label>
                <select name="payment" class="wf-select">
                    <option value="">All Payments</option>
                    <option value="pending" <?= $payment_f === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="paid"    <?= $payment_f === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="unpaid"  <?= $payment_f === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 140px;">
                <label>From</label>
                <input type="date" name="date_from" class="wf-input" value="<?= e($date_from) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 140px;">
                <label>To</label>
                <input type="date" name="date_to" class="wf-input" value="<?= e($date_to) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 auto; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="booking_management.php?tab=<?= e($tab) ?>" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
                <button type="button" class="wf-btn wf-btn-yellow" onclick="document.getElementById('exportForm').submit();">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
        </form>

        <form method="post" id="exportForm" style="display:none;">
            <input type="hidden" name="action" value="export_csv">
            <input type="hidden" name="export_type" value="<?= e($tab) ?>">
        </form>

        <!-- TABLE -->
        <div class="wf-card">
            <div class="wf-card-body">
                <?php if (empty($bookings)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-inbox"></i>
                        <p>No bookings found<?= $payment_f === 'pending' ? ' with pending payment verification' : '' ?></p>
                    </div>
                <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'id_asc' ? 'id_desc' : 'id_asc', 'page' => 1])) ?>">
                                        Booking ID <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'amount_desc' ? 'amount_asc' : 'amount_desc', 'page' => 1])) ?>">
                                        Amount <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'date_desc' ? 'date_asc' : 'date_desc', 'page' => 1])) ?>">
                                        Date <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b):
                                $badge = status_badge($b['status']);

                                if ($tab === 'walkin') {
                                    $customer_name = trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? ''));
                                    if ($customer_name === '') $customer_name = 'Walk-in';
                                } else {
                                    $customer_name = $b['customer_name'] ?? 'Online Customer';
                                    if (trim($customer_name) === '') $customer_name = 'Online Customer';
                                }
                                $status_raw = trim((string)$b['status']);
                                if ($status_raw === '') $status_raw = 'Pending';

                                $has_payment = !empty($b['payment_id']);
                                $pay_raw = $has_payment
                                    ? strtolower(trim((string)($b['payment_status'] ?? 'pending')))
                                    : '';
                                if ($pay_raw === '') $pay_raw = $has_payment ? 'pending' : 'no payment';

                                $needs_verify = in_array($pay_raw, ['pending', 'pending_verification'], true);

                                if (!$has_payment) {
                                    $pay = ['label' => 'No Payment', 'icon' => 'fa-minus-circle'];
                                    $pay_cls = 'none';
                                } else {
                                    $pay = payment_badge($pay_raw);
                                    if ($pay_raw === 'paid')                          $pay_cls = 'paid';
                                    elseif ($pay_raw === 'unpaid')                    $pay_cls = 'unpaid';
                                    elseif ($pay_raw === 'refunded')                  $pay_cls = 'refunded';
                                    else                                              $pay_cls = 'pending';
                                }
                            ?>
                            <tr>
                                <td><span class="wf-booking-id">#<?= e($b['Booking_ID']) ?></span></td>
                                <td>
                                    <div class="wf-customer"><?= e($customer_name) ?></div>
                                    <?php if (!empty($b['contact_number'])): ?>
                                    <div style="font-size:0.75rem;color:var(--text-muted);font-weight:500;">
                                        <i class="fas fa-phone" style="font-size:0.65rem;"></i> <?= e($b['contact_number']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="wf-service"><?= e($b['service'] ?? '-') ?></span></td>
                                <td><span class="wf-amount">₱<?= number_format((float)$b['total_amount'], 2) ?></span></td>

                                <td>
                                    <?php if ($has_payment): ?>
                                    <span class="wf-pay-badge <?= $pay_cls ?>"
                                          title="Click to verify payment"
                                          onclick='openVerifyModal(<?= js_json([
                                              "id"     => $b["payment_id"],
                                              "type"   => "online",
                                              "name"   => $customer_name,
                                              "contact"=> $b["contact_number"] ?? "",
                                              "amount" => $b["total_amount"],
                                              "status" => $pay_raw,
                                              "method" => $b["payment_method"] ?? "",
                                              "ref"    => $b["payment_reference"] ?? "",
                                              "proof"  => $b["payment_proof"] ?? "",
                                              "service"=> $b["service"] ?? "",
                                              "date"   => $b["booking_date"],
                                          ]) ?>)'>
                                        <i class="fas <?= e($pay['icon']) ?>"></i>
                                        <?= e($pay['label']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="wf-pay-badge none" title="No payment record">
                                        <i class="fas fa-minus-circle"></i> No Payment
                                    </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="wf-badge" style="color: <?= $badge['color'] ?>; background: <?= $badge['bg'] ?>;">
                                        <span class="wf-badge-dot"></span>
                                        <?= e($badge['label']) ?>
                                    </span>
                                </td>
                                <td><span class="wf-date"><?= date('M j, Y', strtotime($b['booking_date'])) ?></span></td>
                                <td>
                                    <div class="wf-row-actions">
                                        <?php if ($has_payment && $needs_verify): ?>
                                        <button type="button" class="wf-icon-btn verify" title="Verify Payment"
                                            onclick='openVerifyModal(<?= js_json([
                                                "id"     => $b["payment_id"],
                                                "type"   => "online",
                                                "name"   => $customer_name,
                                                "contact"=> $b["contact_number"] ?? "",
                                                "amount" => $b["total_amount"],
                                                "status" => $pay_raw,
                                                "method" => $b["payment_method"] ?? "",
                                                "ref"    => $b["payment_reference"] ?? "",
                                                "proof"  => $b["payment_proof"] ?? "",
                                                "service"=> $b["service"] ?? "",
                                                "date"   => $b["booking_date"],
                                            ]) ?>)'>
                                            <i class="fas fa-money-check-alt"></i>
                                        </button>
                                        <?php endif; ?>

                                        <button type="button" class="wf-icon-btn view" title="View Details"
                                            onclick='openPanel(<?= js_json([
                                                "id" => $b["Booking_ID"],
                                                "type" => $tab,
                                                "customer" => $customer_name,
                                                "contact" => $b["contact_number"] ?? "",
                                                "service" => $b["service"] ?? "",
                                                "addons" => $b["add_ons"] ?? "",
                                                "delivery" => $b["pick_deliver"] ?? "",
                                                "instructions" => $b["special_instructions"] ?? "",
                                                "status" => $status_raw,
                                                "amount" => $b["total_amount"],
                                                "date" => $b["booking_date"],
                                                "payment_status" => $pay_raw,
                                                "payment_method" => $b["payment_method"] ?? "",
                                                "payment_reference" => $b["payment_reference"] ?? "",
                                                "payment_proof" => $b["payment_proof"] ?? "",
                                                "payment_id" => $b["payment_id"] ?? null,
                                            ]) ?>)'>
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <?php if ($tab === 'walkin'): ?>
                                            <?php if ($status_raw === 'Pending'): ?>
                                            <button type="button" class="wf-icon-btn confirm" title="Confirm"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"walkin","status"=>"Confirmed","label"=>"Confirm this booking?"]) ?>)'>
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($status_raw === 'Confirmed'): ?>
                                            <button type="button" class="wf-icon-btn process" title="Start Processing"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"walkin","status"=>"In Progress","label"=>"Start processing this booking?"]) ?>)'>
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($status_raw === 'In Progress'): ?>
                                            <button type="button" class="wf-icon-btn complete" title="Mark Completed"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"walkin","status"=>"Completed","label"=>"Mark this booking as completed?"]) ?>)'>
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (in_array($status_raw, ['Pending','Confirmed','In Progress'], true)): ?>
                                            <button type="button" class="wf-icon-btn cancel" title="Cancel"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"walkin","status"=>"Cancelled","label"=>"Cancel this booking?","danger"=>true]) ?>)'>
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <?php if ($status_raw === 'Pending'): ?>
                                            <button type="button" class="wf-icon-btn process" title="Start Processing"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"online","status"=>"Processing","label"=>"Start processing this online booking?"]) ?>)'>
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($status_raw === 'Processing'): ?>
                                            <button type="button" class="wf-icon-btn ready" title="Mark Ready for Pickup"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"online","status"=>"Ready","label"=>"Mark as ready for pickup/delivery?"]) ?>)'>
                                                <i class="fas fa-box-open"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($status_raw === 'Ready'): ?>
                                            <button type="button" class="wf-icon-btn complete" title="Mark Completed"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"online","status"=>"Completed","label"=>"Mark this booking as completed?"]) ?>)'>
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (in_array($status_raw, ['Pending','Processing','Ready'], true)): ?>
                                            <button type="button" class="wf-icon-btn cancel" title="Cancel"
                                                onclick='confirmAction(<?= js_json(["id"=>$b["Booking_ID"],"type"=>"online","status"=>"Cancelled","label"=>"Cancel this booking?","danger"=>true]) ?>)'>
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="wf-pagination-wrap">
                    <div class="wf-pagination-info">
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> bookings
                    </div>
                    <div class="wf-pagination">
                        <a href="<?= e(build_url(['page' => max(1, $page - 1)])) ?>" class="wf-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
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
                            <a href="<?= e(build_url(['page' => $p])) ?>" class="wf-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="wf-page-btn disabled" style="border:none;background:transparent;">…</span>
                            <?php endif; ?>
                            <a href="<?= e(build_url(['page' => $total_pages])) ?>" class="wf-page-btn"><?= $total_pages ?></a>
                        <?php endif; ?>

                        <a href="<?= e(build_url(['page' => min($total_pages, $page + 1)])) ?>" class="wf-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- PANEL -->
<div class="wf-panel-overlay" id="panelOverlay" onclick="closePanel()"></div>
<aside class="wf-panel" id="bookingPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-receipt"></i></div>
            <div>
                <h3 id="panelTitle">Booking Details</h3>
                <p id="panelSubtitle">Loading...</p>
            </div>
        </div>
        <button type="button" class="wf-panel-close" onclick="closePanel()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="wf-panel-body" id="panelBody"></div>
    <div class="wf-panel-footer" id="panelFooter"></div>
</aside>

<!-- VERIFY MODAL -->
<div class="wf-modal-overlay" id="verifyModal">
    <div class="wf-modal wide">
        <div class="wf-modal-icon gold" id="verifyIcon">
            <i class="fas fa-money-check-alt"></i>
        </div>
        <h3 id="verifyTitle">Verify Payment</h3>
        <p id="verifySubtitle">Review the payment details below.</p>

        <div class="wf-verify-summary" id="verifySummary"></div>

        <div class="wf-verify-proof" id="verifyProofWrap" style="display:none;"></div>

        <label style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.4rem;">
            Note (optional)
        </label>
        <textarea class="wf-verify-note" id="verifyNote" placeholder="Add a note about this verification..."></textarea>

        <div class="wf-modal-actions" style="margin-top:1.25rem;">
            <button type="button" class="wf-btn wf-btn-outline" onclick="closeVerifyModal()">Cancel</button>
            <button type="button" class="wf-btn wf-btn-green" id="verifyConfirmBtn" onclick="executeMarkPaid()">
                <i class="fas fa-check-circle"></i> Mark as Paid
            </button>
        </div>
    </div>
</div>

<!-- CONFIRM MODAL -->
<div class="wf-modal-overlay" id="confirmModal">
    <div class="wf-modal">
        <div class="wf-modal-icon warn" id="confirmIcon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 id="confirmTitle">Are you sure?</h3>
        <p id="confirmMessage">This action cannot be undone.</p>
        <div class="wf-modal-actions">
            <button type="button" class="wf-btn wf-btn-outline" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="wf-btn wf-btn-primary" id="confirmBtn" onclick="executeConfirm()">Confirm</button>
        </div>
    </div>
</div>

<!-- HIDDEN FORMS -->
<form method="post" id="statusUpdateForm" style="display:none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="type" id="updateType">
    <input type="hidden" name="id" id="updateId">
    <input type="hidden" name="status" id="updateStatus">
</form>

<form method="post" id="markPaidForm" style="display:none;">
    <input type="hidden" name="action" value="mark_paid">
    <input type="hidden" name="id" id="paidId">
    <input type="hidden" name="note" id="paidNote">
    <input type="hidden" name="return_to" id="paidReturnTo" value="<?= e($_SERVER['REQUEST_URI'] ?? 'booking_management.php') ?>">
</form>

<!-- TOAST -->
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

(function() {
    const flash = document.getElementById('serverFlash');
    if (flash) showToast(flash.dataset.type, flash.dataset.message);
})();

/* ══════════════════════════════════════════
   PANEL
   ══════════════════════════════════════════ */
const panelOverlay = document.getElementById('panelOverlay');
const bookingPanel = document.getElementById('bookingPanel');

function openPanel(data) {
    const isWalkin = data.type === 'walkin';
    const statusMap = {
        'pending':     { color:'#946200', bg:'#FEF9E7' },
        'confirmed':   { color:'#00537A', bg:'#EBF5FB' },
        'in progress': { color:'#5B2E91', bg:'#F4ECFB' },
        'processing':  { color:'#5B2E91', bg:'#F4ECFB' },
        'ready':       { color:'#946200', bg:'#FFF9DB' },
        'completed':   { color:'#1E7E45', bg:'#EAF7F0' },
        'cancelled':   { color:'#A8322D', bg:'#FDEDEC' }
    };
    const st = statusMap[String(data.status).toLowerCase()] || { color:'#5A7184', bg:'#F4F7F9' };

    const payMap = {
        'paid':                 { color:'#1E7E45', bg:'#EAF7F0', icon:'fa-check-circle', label:'Paid' },
        'pending':              { color:'#946200', bg:'#FEF9E7', icon:'fa-hourglass-half', label:'Pending Verification' },
        'pending_verification': { color:'#946200', bg:'#FEF9E7', icon:'fa-hourglass-half', label:'Pending Verification' },
        'unpaid':               { color:'#A8322D', bg:'#FDEDEC', icon:'fa-times-circle', label:'Unpaid' },
        'refunded':             { color:'#5B2E91', bg:'#F4ECFB', icon:'fa-undo', label:'Refunded' },
        'failed':               { color:'#A8322D', bg:'#FDEDEC', icon:'fa-exclamation-circle', label:'Failed' },
        'no payment':           { color:'#5A7184', bg:'#F4F7F9', icon:'fa-minus-circle', label:'No Payment' }
    };
    const payKey = String(data.payment_status || 'no payment').toLowerCase();
    const pay = payMap[payKey] || payMap['no payment'];

    document.getElementById('panelTitle').textContent = 'Booking #' + data.id;
    document.getElementById('panelSubtitle').textContent = (isWalkin ? 'Walk-in Booking' : 'Online Booking') + ' • ' + formatDate(data.date);

    let html = '';

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-info-circle"></i> Status Overview</div>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
            <span class="wf-badge" style="color:${st.color};background:${st.bg};font-size:0.8rem;padding:0.5rem 0.9rem;">
                <span class="wf-badge-dot"></span> ${escapeHtml(data.status)}
            </span>
            <span class="wf-pay-badge ${payKey === 'paid' ? 'paid' : (payKey === 'unpaid' ? 'unpaid' : (payKey === 'refunded' ? 'refunded' : (payKey === 'no payment' ? 'none' : 'pending')))}" style="font-size:0.8rem;padding:0.5rem 0.9rem;">
                <i class="fas ${pay.icon}"></i> ${pay.label}
            </span>
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-user"></i> Customer Information</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Name</div>
                <div class="wf-detail-value">${escapeHtml(data.customer)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Contact</div>
                <div class="wf-detail-value">${escapeHtml(data.contact) || '—'}</div>
            </div>
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-soap"></i> Service Details</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Service</div>
                <div class="wf-detail-value">${escapeHtml(data.service) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Add-ons</div>
                <div class="wf-detail-value muted">${escapeHtml(data.addons) || 'None'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Pick/Deliver</div>
                <div class="wf-detail-value muted">${escapeHtml(data.delivery) || '—'}</div>
            </div>
            ${data.instructions ? `
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Special Instructions</div>
                <div class="wf-detail-value muted">${escapeHtml(data.instructions)}</div>
            </div>` : ''}
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-receipt"></i> Payment & Date</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Total Amount</div>
                <div class="wf-detail-value" style="color:var(--dark-blue);font-size:1.05rem;">₱${parseFloat(data.amount).toFixed(2)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Booking Date</div>
                <div class="wf-detail-value">${formatDate(data.date)}</div>
            </div>
            ${data.payment_method ? `
            <div class="wf-detail-item">
                <div class="wf-detail-label">Payment Method</div>
                <div class="wf-detail-value muted">${escapeHtml(data.payment_method)}</div>
            </div>` : ''}
            ${data.payment_reference ? `
            <div class="wf-detail-item">
                <div class="wf-detail-label">Reference No.</div>
                <div class="wf-detail-value mono">${escapeHtml(data.payment_reference)}</div>
            </div>` : ''}
        </div>
    </div>`;

    document.getElementById('panelBody').innerHTML = html;

    let footerHtml = '';
    const status = String(data.status);

    if (isWalkin) {
        if (status === 'Pending') {
            footerHtml += `<button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"walkin",status:"Confirmed",label:"Confirm this booking?"})})'><i class="fas fa-check"></i> Confirm</button>`;
        }
        if (status === 'Confirmed') {
            footerHtml += `<button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"walkin",status:"In Progress",label:"Start processing this booking?"})})'><i class="fas fa-play"></i> Start Processing</button>`;
        }
        if (status === 'In Progress') {
            footerHtml += `<button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"walkin",status:"Completed",label:"Mark this booking as completed?"})})'><i class="fas fa-check-double"></i> Complete</button>`;
        }
        if (['Pending','Confirmed','In Progress'].includes(status)) {
            footerHtml += `<button type="button" class="wf-btn wf-btn-danger" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"walkin",status:"Cancelled",label:"Cancel this booking?",danger:true})})'><i class="fas fa-times"></i> Cancel</button>`;
        }
    } else {
        if (status === 'Pending') {
            footerHtml += `<button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"online",status:"Processing",label:"Start processing this online booking?"})})'><i class="fas fa-play"></i> Start Processing</button>`;
        }
        if (status === 'Processing') {
            footerHtml += `<button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"online",status:"Ready",label:"Mark as ready for pickup/delivery?"})})'><i class="fas fa-box-open"></i> Mark Ready</button>`;
        }
        if (status === 'Ready') {
            footerHtml += `<button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"online",status:"Completed",label:"Mark this booking as completed?"})})'><i class="fas fa-check-double"></i> Complete</button>`;
        }
        if (['Pending','Processing','Ready'].includes(status)) {
            footerHtml += `<button type="button" class="wf-btn wf-btn-danger" onclick='closePanel(); confirmAction(${JSON.stringify({id:data.id,type:"online",status:"Cancelled",label:"Cancel this booking?",danger:true})})'><i class="fas fa-times"></i> Cancel</button>`;
        }
    }

    if (data.payment_id && payKey !== 'paid') {
        footerHtml += `<button type="button" class="wf-btn wf-btn-green" onclick='closePanel(); openVerifyModal(${JSON.stringify({
            id: data.payment_id,
            type: data.type,
            name: data.customer,
            contact: data.contact,
            amount: data.amount,
            status: payKey,
            method: data.payment_method || '',
            ref: data.payment_reference || '',
            proof: data.payment_proof || '',
            service: data.service,
            date: data.date
        })})'><i class="fas fa-money-check-alt"></i> Mark as Paid</button>`;
    }

    if (!footerHtml) {
        footerHtml = '<p style="font-size:0.82rem;color:var(--text-muted);margin:0;">No actions available.</p>';
    }

    document.getElementById('panelFooter').innerHTML = footerHtml;

    panelOverlay.classList.add('open');
    bookingPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    panelOverlay.classList.remove('open');
    bookingPanel.classList.remove('open');
    document.body.style.overflow = '';
}

/* ══════════════════════════════════════════
   VERIFY MODAL
   ══════════════════════════════════════════ */
const verifyModal = document.getElementById('verifyModal');
let verifyTarget = null;

function openVerifyModal(data) {
    verifyTarget = data;
    const payKey = String(data.status || 'pending').toLowerCase();

    document.getElementById('verifyTitle').textContent = 'Verify Payment — #' + data.id;
    document.getElementById('verifySubtitle').textContent = data.name || 'Customer';

    let summary = '';
    summary += `<div class="wf-verify-row"><span class="label">Customer</span><span class="value">${escapeHtml(data.name) || '—'}</span></div>`;
    if (data.contact) summary += `<div class="wf-verify-row"><span class="label">Contact</span><span class="value">${escapeHtml(data.contact)}</span></div>`;
    if (data.service) summary += `<div class="wf-verify-row"><span class="label">Service</span><span class="value">${escapeHtml(data.service)}</span></div>`;
    summary += `<div class="wf-verify-row"><span class="label">Amount</span><span class="value" style="color:var(--dark-blue);font-size:1rem;">₱${parseFloat(data.amount).toFixed(2)}</span></div>`;
    if (data.method) summary += `<div class="wf-verify-row"><span class="label">Method</span><span class="value">${escapeHtml(data.method)}</span></div>`;
    if (data.ref)    summary += `<div class="wf-verify-row"><span class="label">Reference</span><span class="value" style="font-family:monospace;font-size:0.8rem;">${escapeHtml(data.ref)}</span></div>`;
    summary += `<div class="wf-verify-row"><span class="label">Current Status</span><span class="value">${escapeHtml(payKey === 'pending' ? 'Pending Verification' : payKey)}</span></div>`;

    document.getElementById('verifySummary').innerHTML = summary;

    const proofWrap = document.getElementById('verifyProofWrap');
    if (data.proof) {
        const proofStr = String(data.proof);
        const isUrl = /^https?:\/\//i.test(proofStr) || /^\/|^\.\//.test(proofStr) || /\.(png|jpe?g|gif|webp|pdf)$/i.test(proofStr);
        if (isUrl) {
            const isImg = /\.(png|jpe?g|gif|webp)$/i.test(proofStr);
            if (isImg) {
                proofWrap.innerHTML = `<img src="${escapeHtml(proofStr)}" alt="Payment proof" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"><a href="${escapeHtml(proofStr)}" target="_blank" style="display:none;"><i class="fas fa-external-link-alt"></i> Open proof in new tab</a>`;
            } else {
                proofWrap.innerHTML = `<a href="${escapeHtml(proofStr)}" target="_blank"><i class="fas fa-file-invoice"></i> View payment proof</a>`;
            }
        } else {
            proofWrap.innerHTML = `<div style="font-size:0.82rem;color:var(--text-secondary);"><i class="fas fa-info-circle"></i> Proof reference: <strong>${escapeHtml(proofStr)}</strong></div>`;
        }
        proofWrap.style.display = 'block';
    } else {
        proofWrap.style.display = 'none';
        proofWrap.innerHTML = '';
    }

    document.getElementById('verifyNote').value = '';
    verifyModal.classList.add('open');
}

function closeVerifyModal() {
    verifyModal.classList.remove('open');
    verifyTarget = null;
}

function executeMarkPaid() {
    if (!verifyTarget) return;
    document.getElementById('paidId').value   = verifyTarget.id;
    document.getElementById('paidNote').value = document.getElementById('verifyNote').value || '';
    document.getElementById('markPaidForm').submit();
}

verifyModal.addEventListener('click', function(e) {
    if (e.target === verifyModal) closeVerifyModal();
});

/* ══════════════════════════════════════════
   CONFIRM MODAL
   ══════════════════════════════════════════ */
const confirmModal = document.getElementById('confirmModal');
let pendingAction = null;

function confirmAction(opts) {
    pendingAction = opts;
    document.getElementById('confirmTitle').textContent = opts.label || 'Are you sure?';
    document.getElementById('confirmMessage').textContent = opts.danger
        ? 'This action cannot be undone.'
        : 'Please confirm to proceed.';

    const icon = document.getElementById('confirmIcon');
    const btn  = document.getElementById('confirmBtn');

    if (opts.danger) {
        icon.className = 'wf-modal-icon warn';
        icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        btn.className = 'wf-btn wf-btn-danger';
        btn.innerHTML = '<i class="fas fa-times"></i> Yes, Cancel it';
    } else {
        icon.className = 'wf-modal-icon info';
        icon.innerHTML = '<i class="fas fa-check-circle"></i>';
        btn.className = 'wf-btn wf-btn-primary';
        btn.innerHTML = '<i class="fas fa-check"></i> Yes, Proceed';
    }

    confirmModal.classList.add('open');
}

function closeConfirmModal() {
    confirmModal.classList.remove('open');
    pendingAction = null;
}

function executeConfirm() {
    if (!pendingAction) return;
    document.getElementById('updateType').value   = pendingAction.type;
    document.getElementById('updateId').value     = pendingAction.id;
    document.getElementById('updateStatus').value = pendingAction.status;
    document.getElementById('statusUpdateForm').submit();
}

confirmModal.addEventListener('click', function(e) {
    if (e.target === confirmModal) closeConfirmModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeConfirmModal();
        closeVerifyModal();
        closePanel();
    }
});

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    let h = d.getHours();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    const min = String(d.getMinutes()).padStart(2, '0');
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + ' • ' + h + ':' + min + ' ' + ampm;
}

/* ══════════════════════════════════════════
   MOBILE SIDEBAR
   ══════════════════════════════════════════ */
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

/* ══════════════════════════════════════════
   AUTO-OPEN VERIFY MODAL
   ══════════════════════════════════════════ */
(function autoOpenVerify() {
    const params = new URLSearchParams(window.location.search);
    const paymentFlag = params.get('payment');

    if (paymentFlag === 'verify') {
        const target = <?= $verify_target ? js_json([
            "id"      => $verify_target["payment_id"],
            "type"    => "online",
            "name"    => $verify_target["customer_name"] ?? "",
            "contact" => $verify_target["contact_number"] ?? "",
            "amount"  => $verify_target["amount"] ?? 0,
            "status"  => strtolower($verify_target["payment_status"] ?? "pending"),
            "method"  => $verify_target["payment_method"] ?? "",
            "ref"     => $verify_target["reference_number"] ?? "",
            "proof"   => $verify_target["payment_proof"] ?? "",
            "service" => $verify_target["service"] ?? "",
            "date"    => $verify_target["payment_date"] ?? "",
        ]) : 'null' ?>;

        if (target) {
            setTimeout(() => openVerifyModal(target), 250);
        }
    }
})();
</script>

</body>
</html>