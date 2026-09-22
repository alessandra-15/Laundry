<?php
/**
 * customer_management.php
 * WashFlow — Customer Management
 * Single-file: PHP + HTML + CSS + JS
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

/* ==========================================
   HELPERS
   ========================================== */
if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/* ==========================================
   POST HANDLERS
   ========================================== */
$flash = null;
$upload_dir = __DIR__ . '/uploads/student_ids/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    /* ---------- ADD CUSTOMER ---------- */
    if ($action === 'add_customer') {
        $first_name     = trim($_POST['first_name'] ?? '');
        $last_name      = trim($_POST['last_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $account_type   = $_POST['account_type'] ?? 'regular';
        $discount_rate  = (float)($_POST['discount_rate'] ?? 0);
        $password       = $_POST['password'] ?? '';

        $errors = [];
        if ($first_name === '') $errors[] = 'First name is required.';
        if ($last_name === '')  $errors[] = 'Last name is required.';
        if ($contact_number === '') $errors[] = 'Contact number is required.';
        if ($password === '' || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (!in_array($account_type, ['regular','student'], true)) $account_type = 'regular';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

        $student_id_path = null;
        if ($account_type === 'student' && !empty($_FILES['student_id']['name'])) {
            $allowed = ['jpg','jpeg','png','pdf'];
            $ext = strtolower(pathinfo($_FILES['student_id']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Student ID must be JPG, PNG, or PDF.';
            } elseif ($_FILES['student_id']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Student ID file must be under 5MB.';
            } else {
                $fname = 'sid_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['student_id']['tmp_name'], $upload_dir . $fname)) {
                    $student_id_path = 'uploads/student_ids/' . $fname;
                }
            }
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    INSERT INTO customer_info
                        (first_name, last_name, email, register_date, contact_number, Address,
                         account_type, student_id_path, discount_rate, password)
                    VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    'sssssssss',
                    $first_name, $last_name, $email,
                    $contact_number, $address,
                    $account_type, $student_id_path,
                    $discount_rate, $hash
                );
                $ok = $stmt->execute();
                $new_id = $conn->insert_id;
                $stmt->close();

                if ($ok) {
                    if (class_exists('Logger')) {
                        Logger::info('Customer added', [
                            'customer_id' => $new_id,
                            'name' => "$first_name $last_name",
                            'admin' => $admin_id
                        ]);
                    }
                    $flash = ['type'=>'success','message'=>"Customer #$new_id added successfully."];
                } else {
                    $flash = ['type'=>'error','message'=>'Failed to add customer.'];
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Add customer failed', ['error'=>$ex->getMessage()]);
                $flash = ['type'=>'error','message'=>'Database error: '.$ex->getMessage()];
            }
        } else {
            $flash = ['type'=>'error','message'=>implode(' ', $errors)];
        }
    }

    /* ---------- UPDATE CUSTOMER ---------- */
    if ($action === 'update_customer') {
        $id             = (int)($_POST['id'] ?? 0);
        $first_name     = trim($_POST['first_name'] ?? '');
        $last_name      = trim($_POST['last_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $account_type   = $_POST['account_type'] ?? 'regular';
        $discount_rate  = (float)($_POST['discount_rate'] ?? 0);
        $new_password   = $_POST['password'] ?? '';

        $errors = [];
        if ($id <= 0) $errors[] = 'Invalid customer ID.';
        if ($first_name === '') $errors[] = 'First name is required.';
        if ($last_name === '')  $errors[] = 'Last name is required.';
        if ($contact_number === '') $errors[] = 'Contact number is required.';
        if (!in_array($account_type, ['regular','student'], true)) $account_type = 'regular';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

        $student_id_path = null;
        if ($account_type === 'student' && !empty($_FILES['student_id']['name'])) {
            $allowed = ['jpg','jpeg','png','pdf'];
            $ext = strtolower(pathinfo($_FILES['student_id']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Student ID must be JPG, PNG, or PDF.';
            } elseif ($_FILES['student_id']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Student ID file must be under 5MB.';
            } else {
                $fname = 'sid_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['student_id']['tmp_name'], $upload_dir . $fname)) {
                    $student_id_path = 'uploads/student_ids/' . $fname;
                }
            }
        }

        if (empty($errors)) {
            try {
                if ($student_id_path !== null && $new_password !== '') {
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE customer_info SET
                            first_name=?, last_name=?, email=?, contact_number=?, Address=?,
                            account_type=?, student_id_path=?, discount_rate=?, password=?
                        WHERE Customer_ID=?
                    ");
                    $stmt->bind_param('sssssssssi',
                        $first_name, $last_name, $email, $contact_number, $address,
                        $account_type, $student_id_path, $discount_rate, $hash, $id
                    );
                } elseif ($student_id_path !== null) {
                    $stmt = $conn->prepare("
                        UPDATE customer_info SET
                            first_name=?, last_name=?, email=?, contact_number=?, Address=?,
                            account_type=?, student_id_path=?, discount_rate=?
                        WHERE Customer_ID=?
                    ");
                    $stmt->bind_param('ssssssssi',
                        $first_name, $last_name, $email, $contact_number, $address,
                        $account_type, $student_id_path, $discount_rate, $id
                    );
                } elseif ($new_password !== '') {
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE customer_info SET
                            first_name=?, last_name=?, email=?, contact_number=?, Address=?,
                            account_type=?, discount_rate=?, password=?
                        WHERE Customer_ID=?
                    ");
                    $stmt->bind_param('ssssssssi',
                        $first_name, $last_name, $email, $contact_number, $address,
                        $account_type, $discount_rate, $hash, $id
                    );
                } else {
                    $stmt = $conn->prepare("
                        UPDATE customer_info SET
                            first_name=?, last_name=?, email=?, contact_number=?, Address=?,
                            account_type=?, discount_rate=?
                        WHERE Customer_ID=?
                    ");
                    $stmt->bind_param('ssssssdi',
                        $first_name, $last_name, $email, $contact_number, $address,
                        $account_type, $discount_rate, $id
                    );
                }

                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    if (class_exists('Logger')) {
                        Logger::info('Customer updated', ['customer_id'=>$id, 'admin'=>$admin_id]);
                    }
                    $flash = ['type'=>'success','message'=>"Customer #$id updated."];
                } else {
                    $flash = ['type'=>'error','message'=>'Failed to update customer.'];
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Update customer failed', ['error'=>$ex->getMessage()]);
                $flash = ['type'=>'error','message'=>'Database error: '.$ex->getMessage()];
            }
        } else {
            $flash = ['type'=>'error','message'=>implode(' ', $errors)];
        }
    }

    /* ---------- DELETE CUSTOMER ---------- */
    if ($action === 'delete_customer') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) c FROM booking WHERE Customer_ID=?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $c1 = (int)$stmt->get_result()->fetch_assoc()['c'];
                $stmt->close();

                $stmt = $conn->prepare("SELECT COUNT(*) c FROM booking_online WHERE customer_id=?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $c2 = (int)$stmt->get_result()->fetch_assoc()['c'];
                $stmt->close();

                if ($c1 + $c2 > 0) {
                    $flash = ['type'=>'error','message'=>"Cannot delete — customer has ".($c1+$c2)." booking(s)."];
                } else {
                    $stmt = $conn->prepare("DELETE FROM customer_info WHERE Customer_ID=?");
                    $stmt->bind_param('i', $id);
                    $ok = $stmt->execute();
                    $stmt->close();

                    if ($ok) {
                        if (class_exists('Logger')) {
                            Logger::info('Customer deleted', ['customer_id'=>$id, 'admin'=>$admin_id]);
                        }
                        $flash = ['type'=>'success','message'=>"Customer #$id deleted."];
                    } else {
                        $flash = ['type'=>'error','message'=>'Failed to delete customer.'];
                    }
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Delete customer failed', ['error'=>$ex->getMessage()]);
                $flash = ['type'=>'error','message'=>'Database error: '.$ex->getMessage()];
            }
        }
    }

    /* ---------- EXPORT CSV ---------- */
    if ($action === 'export_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="customers_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['ID','First Name','Last Name','Email','Contact','Address','Account Type','Discount Rate','Registered','Bookings','Total Spent']);

        $q = $conn->query("
            SELECT c.Customer_ID, c.first_name, c.last_name, c.email, c.contact_number,
                   c.Address, c.account_type, c.discount_rate, c.register_date,
                   (SELECT COUNT(*) FROM booking b WHERE b.Customer_ID = c.Customer_ID) AS walkin_count,
                   (SELECT COUNT(*) FROM booking_online bo WHERE bo.customer_id = c.Customer_ID) AS online_count,
                   (SELECT COALESCE(SUM(b.total_amount),0) FROM booking b WHERE b.Customer_ID = c.Customer_ID) AS walkin_spent,
                   (SELECT COALESCE(SUM(bo.total_amount),0) FROM booking_online bo WHERE bo.customer_id = c.Customer_ID) AS online_spent
            FROM customer_info c
            ORDER BY c.Customer_ID ASC
        ");
        if ($q) while ($r = $q->fetch_assoc()) {
            fputcsv($out, [
                $r['Customer_ID'],
                $r['first_name'],
                $r['last_name'],
                $r['email'] ?? '',
                $r['contact_number'] ?? '',
                $r['Address'] ?? '',
                $r['account_type'] ?? 'regular',
                number_format((float)$r['discount_rate'], 2, '.', ''),
                $r['register_date'] ?? '',
                ((int)$r['walkin_count'] + (int)$r['online_count']),
                number_format((float)$r['walkin_spent'] + (float)$r['online_spent'], 2, '.', '')
            ]);
        }
        fclose($out);
        exit();
    }
}

/* ==========================================
   FILTERS + PAGINATION
   ========================================== */
$search     = trim($_GET['q'] ?? '');
$type_f     = trim($_GET['type'] ?? '');
$date_from  = trim($_GET['date_from'] ?? '');
$date_to    = trim($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;
$sort       = $_GET['sort'] ?? 'id_desc';

$order_map = [
    'id_desc'   => 'c.Customer_ID DESC',
    'id_asc'    => 'c.Customer_ID ASC',
    'name_asc'  => 'c.first_name ASC, c.last_name ASC',
    'name_desc' => 'c.first_name DESC, c.last_name DESC',
    'date_desc' => 'c.register_date DESC',
    'date_asc'  => 'c.register_date ASC',
];
$order_sql = $order_map[$sort] ?? 'c.Customer_ID DESC';

/* ==========================================
   STATS
   ========================================== */
$stats = [
    'total'         => 0,
    'regular'       => 0,
    'student'       => 0,
    'new_this_month'=> 0,
];

try {
    $q = $conn->query("SELECT COUNT(*) c FROM customer_info");
    if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM customer_info WHERE account_type='regular'");
    if ($q) $stats['regular'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM customer_info WHERE account_type='student'");
    if ($q) $stats['student'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM customer_info WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())");
    if ($q) $stats['new_this_month'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Customer stats failed', ['error'=>$ex->getMessage()]);
}

/* ==========================================
   FETCH LIST
   ========================================== */
$customers   = [];
$total_rows  = 0;
$total_pages = 1;

try {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[] = "(c.Customer_ID LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.contact_number LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 5; $i++) { $params[] = $like; $types .= 's'; }
    }
    if ($type_f !== '' && in_array($type_f, ['regular','student'], true)) {
        $where[] = "c.account_type = ?";
        $params[] = $type_f;
        $types .= 's';
    }
    if ($date_from !== '') {
        $where[] = "DATE(c.register_date) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    if ($date_to !== '') {
        $where[] = "DATE(c.register_date) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }

    $where_sql = implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) c FROM customer_info c WHERE $where_sql";
    $stmt = $conn->prepare($count_sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    $list_sql = "
        SELECT c.*,
            (SELECT COUNT(*) FROM booking b WHERE b.Customer_ID = c.Customer_ID) AS walkin_count,
            (SELECT COUNT(*) FROM booking_online bo WHERE bo.customer_id = c.Customer_ID) AS online_count,
            (SELECT COALESCE(SUM(b.total_amount),0) FROM booking b WHERE b.Customer_ID = c.Customer_ID) AS walkin_spent,
            (SELECT COALESCE(SUM(bo.total_amount),0) FROM booking_online bo WHERE bo.customer_id = c.Customer_ID) AS online_spent
        FROM customer_info c
        WHERE $where_sql
        ORDER BY $order_sql
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($list_sql);
    $bind_types  = $types . 'ii';
    $bind_params = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch customers failed', ['error'=>$ex->getMessage()]);
}

/* ==========================================
   URL BUILDER
   ========================================== */
function build_url($overrides = []) {
    $base = [
        'q'         => $_GET['q']         ?? '',
        'type'      => $_GET['type']      ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'sort'      => $_GET['sort']      ?? 'id_desc',
        'page'      => $_GET['page']      ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'customer_management.php?' . http_build_query(array_filter($merged, function($v){
        return $v !== '' && $v !== null;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Management — WashFlow</title>

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
.wf-btn-outline { background: white; color: var(--primary); border-color: var(--border); }
.wf-btn-outline:hover { border-color: var(--primary); background: var(--light-blue-pale); }
.wf-btn-yellow { background: var(--yellow); color: var(--dark-blue-deep); }
.wf-btn-yellow:hover { background: #FFCE00; }
.wf-btn-danger { background: var(--red); color: white; }
.wf-btn-danger:hover { background: #C0392B; }
.wf-btn-sm { padding: 0.5rem 0.85rem; font-size: 0.78rem; }

/* CARD */
.wf-card {
    background: white;
    border-radius: 14px;
    border: 1px solid var(--border);
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.wf-card-body { padding: 0; }

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
.wf-table thead th a {
    color: inherit;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: color 0.15s;
}
.wf-table thead th a:hover { color: var(--primary); }
.wf-table thead th a i { font-size: 0.65rem; opacity: 0.6; }
.wf-table tbody td {
    padding: 0.95rem 1rem;
    border-bottom: 1px solid var(--border-soft);
    vertical-align: middle;
}
.wf-table tbody tr:last-child td { border-bottom: none; }
.wf-table tbody tr { transition: background 0.15s; }
.wf-table tbody tr:hover { background: var(--light-blue-pale); }

.wf-cust-id {
    font-weight: 700;
    color: var(--primary);
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.8rem;
}
.wf-cust-name { font-weight: 600; color: var(--dark-blue); }
.wf-cust-sub  { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; }
.wf-amount    { font-weight: 700; color: var(--dark-blue); }
.wf-date      { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }

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
.wf-badge-regular { color: #00537A; background: #EBF5FB; }
.wf-badge-student { color: #5B2E91; background: #F4ECFB; }

.wf-row-actions {
    display: flex;
    gap: 0.4rem;
    align-items: center;
}
.wf-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 0.8rem;
}
.wf-icon-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--light-blue-pale); }
.wf-icon-btn.edit:hover   { border-color: var(--primary); color: var(--primary); }
.wf-icon-btn.delete:hover { border-color: var(--red); color: var(--red); background: #FDEDEC; }

/* EMPTY */
.wf-empty {
    text-align: center;
    padding: 3.5rem 1rem;
    color: var(--text-muted);
}
.wf-empty i {
    font-size: 3rem;
    color: var(--light-blue);
    margin-bottom: 0.9rem;
    display: block;
}
.wf-empty p { margin: 0; font-size: 0.9rem; font-weight: 500; }

/* PAGINATION */
.wf-pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-soft);
    flex-wrap: wrap;
    gap: 0.75rem;
}
.wf-pagination-info {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 500;
}
.wf-pagination { display: flex; gap: 0.3rem; align-items: center; }
.wf-page-btn {
    min-width: 36px;
    height: 36px;
    padding: 0 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.wf-page-btn:hover:not(.disabled):not(.active) {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--light-blue-pale);
}
.wf-page-btn.active {
    background: var(--dark-blue);
    color: white;
    border-color: var(--dark-blue);
}
.wf-page-btn.disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

/* PANEL */
.wf-panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(6, 52, 82, 0.4);
    backdrop-filter: blur(2px);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1040;
}
.wf-panel-overlay.open { opacity: 1; visibility: visible; }

.wf-panel {
    position: fixed;
    top: 0; right: 0; bottom: 0;
    width: 600px;
    max-width: 100vw;
    background: white;
    z-index: 1050;
    transform: translateX(100%);
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    box-shadow: -20px 0 60px rgba(6, 52, 82, 0.15);
}
.wf-panel.open { transform: translateX(0); }

.wf-panel-header {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    background: linear-gradient(135deg, var(--light-blue-pale) 0%, white 100%);
}
.wf-panel-title {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    flex: 1;
    min-width: 0;
}
.wf-panel-title-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: var(--dark-blue);
    color: var(--yellow);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.wf-panel-title h3 { font-size: 1.05rem; color: var(--dark-blue); margin: 0; font-weight: 700; }
.wf-panel-title p { font-size: 0.78rem; color: var(--text-muted); margin: 0.15rem 0 0; font-weight: 500; }
.wf-panel-close {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    flex-shrink: 0;
}
.wf-panel-close:hover { border-color: var(--red); color: var(--red); background: #FDEDEC; }

.wf-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 1.75rem;
}
.wf-panel-body::-webkit-scrollbar { width: 6px; }
.wf-panel-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

.wf-detail-section { margin-bottom: 1.5rem; }
.wf-detail-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.wf-detail-section-title i { color: var(--primary); }
.wf-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem 1rem;
}
.wf-detail-item { display: flex; flex-direction: column; gap: 0.2rem; }
.wf-detail-item.full { grid-column: 1 / -1; }
.wf-detail-label {
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.wf-detail-value {
    font-size: 0.88rem;
    color: var(--text-primary);
    font-weight: 600;
    word-break: break-word;
}
.wf-detail-value.mono { font-family: 'SF Mono', Monaco, monospace; color: var(--primary); }
.wf-detail-value.muted { color: var(--text-secondary); font-weight: 500; }

.wf-mini-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}
.wf-mini-stat {
    background: var(--light-blue-pale);
    border: 1px solid var(--border);
    border-radius: 11px;
    padding: 0.9rem;
    text-align: center;
}
.wf-mini-stat-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--dark-blue);
    line-height: 1;
    margin-bottom: 0.35rem;
}
.wf-mini-stat-label {
    font-size: 0.72rem;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.wf-panel-footer {
    padding: 1.25rem 1.75rem;
    border-top: 1px solid var(--border);
    background: #FAFCFE;
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
}

/* MODAL */
.wf-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(6, 52, 82, 0.5);
    backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1060;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s;
    padding: 1rem;
}
.wf-modal-overlay.open { opacity: 1; visibility: visible; }

.wf-modal {
    background: white;
    border-radius: 16px;
    padding: 1.75rem;
    max-width: 460px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(6, 52, 82, 0.25);
    transform: scale(0.95);
    transition: transform 0.25s;
}
.wf-modal-overlay.open .wf-modal { transform: scale(1); }
.wf-modal.wide { max-width: 620px; }

.wf-modal-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 1rem;
}
.wf-modal-icon.warn { background: #FDEDEC; color: var(--red); }
.wf-modal-icon.info { background: var(--light-blue-soft); color: var(--primary); }
.wf-modal h3 {
    font-size: 1.15rem;
    color: var(--dark-blue);
    text-align: center;
    margin-bottom: 0.5rem;
}
.wf-modal p {
    font-size: 0.88rem;
    color: var(--text-secondary);
    text-align: center;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}
.wf-modal-actions {
    display: flex;
    gap: 0.6rem;
    justify-content: center;
}

/* FORM INSIDE MODAL */
.wf-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.85rem;
    text-align: left;
    margin-bottom: 1rem;
}
.wf-form-grid .full { grid-column: 1 / -1; }
.wf-form-group { display: flex; flex-direction: column; gap: 0.35rem; }
.wf-form-group label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.wf-form-group label .req { color: var(--red); }
.wf-form-control {
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
.wf-form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 90, 133, 0.1);
}
textarea.wf-form-control { resize: vertical; min-height: 60px; }

.wf-modal-header-custom {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--border);
}
.wf-modal-header-custom .icon {
    width: 44px; height: 44px;
    border-radius: 11px;
    background: var(--light-blue-soft);
    color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}
.wf-modal-header-custom .icon.danger { background: #FDEDEC; color: var(--red); }
.wf-modal-header-custom h3 { margin: 0; font-size: 1.05rem; color: var(--dark-blue); text-align: left; }
.wf-modal-header-custom p  { margin: 0.15rem 0 0; font-size: 0.78rem; color: var(--text-muted); text-align: left; }

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

/* RESPONSIVE */
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
    .wf-panel { width: 500px; }
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
    .wf-topbar-greeting h1 { font-size: 1.35rem; }
    .wf-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .wf-panel { width: 100%; }
    .wf-detail-grid { grid-template-columns: 1fr; }
    .wf-form-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .wf-stats-grid { grid-template-columns: 1fr; }
    .wf-filter-bar { flex-direction: column; align-items: stretch; }
    .wf-filter-group { min-width: 100%; }
    .wf-mini-stats { grid-template-columns: 1fr; }
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
                <span class="wf-sidebar-brand-tag">Admin Panel</span>
            </div>
        </div>

        <nav class="wf-nav">
            <div class="wf-nav-section-label">Overview</div>
            <a href="dashboard.php" class="wf-nav-item">
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
            <a href="customer_management.php" class="wf-nav-item active">
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

    <!-- MAIN -->
    <main class="wf-main">

        <!-- TOPBAR -->
        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1>Customer <span class="accent">Management</span></h1>
                <p>Manage customer records, accounts, and booking history</p>
            </div>
            <div class="wf-topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, F j, Y') ?>
            </div>
        </div>

        <!-- FLASH -->
        <?php if ($flash): ?>
        <div id="serverFlash" data-type="<?= e($flash['type']) ?>" data-message="<?= e($flash['message']) ?>" style="display:none;"></div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="wf-stats-grid">
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-users"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Total Customers</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-user"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['regular']) ?></div>
                    <div class="wf-stat-label">Regular Accounts</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-purple"><i class="fas fa-graduation-cap"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['student']) ?></div>
                    <div class="wf-stat-label">Student Accounts</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-user-plus"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['new_this_month']) ?></div>
                    <div class="wf-stat-label">New This Month</div>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form method="get" action="customer_management.php" class="wf-filter-bar">
            <input type="hidden" name="page" value="1">

            <div class="wf-filter-group" style="flex: 1 1 240px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="ID, name, email, contact..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 170px;">
                <label>Account Type</label>
                <select name="type" class="wf-select">
                    <option value="">All Types</option>
                    <option value="regular" <?= $type_f === 'regular' ? 'selected' : '' ?>>Regular</option>
                    <option value="student" <?= $type_f === 'student' ? 'selected' : '' ?>>Student</option>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 150px;">
                <label>From</label>
                <input type="date" name="date_from" class="wf-input" value="<?= e($date_from) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 150px;">
                <label>To</label>
                <input type="date" name="date_to" class="wf-input" value="<?= e($date_to) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 auto; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="customer_management.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
                <button type="button" class="wf-btn wf-btn-yellow" onclick="document.getElementById('exportForm').submit();">
                    <i class="fas fa-download"></i> Export CSV
                </button>
                <button type="button" class="wf-btn wf-btn-primary" onclick="openAddModal()">
                    <i class="fas fa-user-plus"></i> Add Customer
                </button>
            </div>
        </form>

        <form method="post" id="exportForm" style="display:none;">
            <input type="hidden" name="action" value="export_csv">
        </form>

        <!-- TABLE -->
        <div class="wf-card">
            <div class="wf-card-body">
                <?php if (empty($customers)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-user-slash"></i>
                        <p>No customers found</p>
                    </div>
                <?php else: ?>
                <div class="wf-table-wrap">
                    <table class="wf-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'id_asc' ? 'id_desc' : 'id_asc', 'page' => 1])) ?>">
                                        ID <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'name_asc' ? 'name_desc' : 'name_asc', 'page' => 1])) ?>">
                                        Customer <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Contact</th>
                                <th>Account Type</th>
                                <th style="text-align:center;">Bookings</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'date_desc' ? 'date_asc' : 'date_desc', 'page' => 1])) ?>">
                                        Registered <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c):
                                $full_name  = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                                if ($full_name === '') $full_name = 'Customer #' . $c['Customer_ID'];
                                $is_student = ($c['account_type'] ?? 'regular') === 'student';
                                $total_bk   = (int)($c['walkin_count'] ?? 0) + (int)($c['online_count'] ?? 0);
                                $total_sp   = (float)($c['walkin_spent'] ?? 0) + (float)($c['online_spent'] ?? 0);
                                $reg_date   = !empty($c['register_date']) ? date('M j, Y', strtotime($c['register_date'])) : '—';
                            ?>
                            <tr>
                                <td><span class="wf-cust-id">#<?= e($c['Customer_ID']) ?></span></td>
                                <td>
                                    <div class="wf-cust-name"><?= e($full_name) ?></div>
                                    <?php if (!empty($c['email'])): ?>
                                    <div class="wf-cust-sub">
                                        <i class="fas fa-envelope" style="font-size:0.65rem;"></i> <?= e($c['email']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['contact_number'])): ?>
                                    <div style="font-size:0.82rem;color:var(--text-secondary);font-weight:600;">
                                        <i class="fas fa-phone" style="font-size:0.7rem;"></i> <?= e($c['contact_number']) ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="wf-cust-sub">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="wf-badge <?= $is_student ? 'wf-badge-student' : 'wf-badge-regular' ?>">
                                        <span class="wf-badge-dot"></span>
                                        <?= $is_student ? 'Student' : 'Regular' ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div style="font-weight:700;color:var(--dark-blue);"><?= number_format($total_bk) ?></div>
                                    <div class="wf-cust-sub">₱<?= number_format($total_sp, 2) ?></div>
                                </td>
                                <td><span class="wf-date"><?= e($reg_date) ?></span></td>
                                <td>
                                    <div class="wf-row-actions" style="justify-content: flex-end;">
                                        <button type="button" class="wf-icon-btn" title="View Details"
                                            onclick='openPanel(<?= json_encode([
                                                "id" => $c["Customer_ID"],
                                                "first_name" => $c["first_name"] ?? "",
                                                "last_name" => $c["last_name"] ?? "",
                                                "email" => $c["email"] ?? "",
                                                "contact" => $c["contact_number"] ?? "",
                                                "address" => $c["Address"] ?? "",
                                                "account_type" => $c["account_type"] ?? "regular",
                                                "discount_rate" => $c["discount_rate"] ?? 0,
                                                "student_id_path" => $c["student_id_path"] ?? "",
                                                "register_date" => $c["register_date"] ?? "",
                                                "created_at" => $c["created_at"] ?? "",
                                                "bookings" => $total_bk,
                                                "spent" => $total_sp,
                                            ]) ?>)'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="wf-icon-btn edit" title="Edit"
                                            onclick='openEditModal(<?= json_encode([
                                                "id" => $c["Customer_ID"],
                                                "first_name" => $c["first_name"] ?? "",
                                                "last_name" => $c["last_name"] ?? "",
                                                "email" => $c["email"] ?? "",
                                                "contact" => $c["contact_number"] ?? "",
                                                "address" => $c["Address"] ?? "",
                                                "account_type" => $c["account_type"] ?? "regular",
                                                "discount_rate" => $c["discount_rate"] ?? 0,
                                            ]) ?>)'>
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button type="button" class="wf-icon-btn delete" title="Delete"
                                            onclick='openDeleteModal(<?= json_encode([
                                                "id" => $c["Customer_ID"],
                                                "name" => $full_name,
                                                "bookings" => $total_bk,
                                            ]) ?>)'>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="wf-pagination-wrap">
                    <div class="wf-pagination-info">
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> customers
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

<!-- SIDE PANEL — Customer Details -->
<div class="wf-panel-overlay" id="panelOverlay" onclick="closePanel()"></div>
<aside class="wf-panel" id="customerPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-user"></i></div>
            <div>
                <h3 id="panelTitle">Customer Details</h3>
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

<!-- MODAL — Add / Edit Customer -->
<div class="wf-modal-overlay" id="customerModal">
    <div class="wf-modal wide">
        <div class="wf-modal-header-custom">
            <div class="icon" id="modalIcon"><i class="fas fa-user-plus"></i></div>
            <div>
                <h3 id="modalTitle">Add New Customer</h3>
                <p id="modalSubtitle">Fill in the customer details below</p>
            </div>
        </div>

        <form method="post" id="customerForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add_customer">
            <input type="hidden" name="id" id="formId" value="">

            <div class="wf-form-grid">
                <div class="wf-form-group">
                    <label>First Name <span class="req">*</span></label>
                    <input type="text" name="first_name" id="f_first_name" class="wf-form-control" required>
                </div>
                <div class="wf-form-group">
                    <label>Last Name <span class="req">*</span></label>
                    <input type="text" name="last_name" id="f_last_name" class="wf-form-control" required>
                </div>
                <div class="wf-form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="f_email" class="wf-form-control">
                </div>
                <div class="wf-form-group">
                    <label>Contact Number <span class="req">*</span></label>
                    <input type="text" name="contact_number" id="f_contact" class="wf-form-control" required>
                </div>
                <div class="wf-form-group full">
                    <label>Address</label>
                    <textarea name="address" id="f_address" class="wf-form-control" rows="2"></textarea>
                </div>
                <div class="wf-form-group">
                    <label>Account Type <span class="req">*</span></label>
                    <select name="account_type" id="f_account_type" class="wf-form-control" onchange="toggleStudentFields()">
                        <option value="regular">Regular</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="wf-form-group">
                    <label>Discount Rate (%)</label>
                    <input type="number" name="discount_rate" id="f_discount_rate" class="wf-form-control" step="0.01" min="0" max="100" value="0">
                </div>
                <div class="wf-form-group full" id="studentIdGroup" style="display:none;">
                    <label>Student ID (JPG/PNG/PDF, max 5MB)</label>
                    <input type="file" name="student_id" id="f_student_id" class="wf-form-control" accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <div class="wf-form-group full">
                    <label id="passwordLabel">Password <span class="req">*</span></label>
                    <input type="password" name="password" id="f_password" class="wf-form-control" autocomplete="new-password">
                    <div id="passwordHint" style="font-size:0.72rem;color:var(--text-muted);margin-top:0.2rem;">
                        Minimum 6 characters
                    </div>
                </div>
            </div>

            <div class="wf-modal-actions" style="justify-content: flex-end;">
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeCustomerModal()">Cancel</button>
                <button type="submit" class="wf-btn wf-btn-primary" id="modalSubmitBtn">
                    <i class="fas fa-save"></i> Save Customer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL — Delete Confirmation -->
<div class="wf-modal-overlay" id="deleteModal">
    <div class="wf-modal">
        <div class="wf-modal-icon warn">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 id="deleteTitle">Delete Customer?</h3>
        <p id="deleteMessage">This action cannot be undone.</p>
        <div class="wf-modal-actions">
            <button type="button" class="wf-btn wf-btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <form method="post" id="deleteForm" style="display:inline;">
                <input type="hidden" name="action" value="delete_customer">
                <input type="hidden" name="id" id="deleteId" value="">
                <button type="submit" class="wf-btn wf-btn-danger">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>

<!-- TOAST WRAP -->
<div class="wf-toast-wrap" id="toastWrap"></div>

<script>
function showToast(type, message) {
    const wrap = document.getElementById('toastWrap');
    const toast = document.createElement('div');
    toast.className = 'wf-toast ' + (type === 'success' ? 'success' : 'error');
    toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i><span>' + escapeHtml(message) + '</span>';
    wrap.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

(function() {
    const flash = document.getElementById('serverFlash');
    if (flash) {
        showToast(flash.dataset.type, flash.dataset.message);
    }
})();

const panelOverlay  = document.getElementById('panelOverlay');
const customerPanel = document.getElementById('customerPanel');

function openPanel(data) {
    const full_name = (data.first_name + ' ' + data.last_name).trim() || ('Customer #' + data.id);
    const is_student = data.account_type === 'student';

    document.getElementById('panelTitle').textContent    = full_name;
    document.getElementById('panelSubtitle').textContent = 'Customer #' + data.id + ' • ' + (is_student ? 'Student' : 'Regular');

    let html = '';

    html += `
    <div class="wf-detail-section">
        <div class="wf-mini-stats">
            <div class="wf-mini-stat">
                <div class="wf-mini-stat-value">${data.bookings}</div>
                <div class="wf-mini-stat-label">Bookings</div>
            </div>
            <div class="wf-mini-stat">
                <div class="wf-mini-stat-value" style="font-size:1rem;">₱${parseFloat(data.spent).toFixed(2)}</div>
                <div class="wf-mini-stat-label">Total Spent</div>
            </div>
            <div class="wf-mini-stat">
                <div class="wf-mini-stat-value">${parseFloat(data.discount_rate).toFixed(1)}%</div>
                <div class="wf-mini-stat-label">Discount</div>
            </div>
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-info-circle"></i> Account</div>
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <span class="wf-badge ${is_student ? 'wf-badge-student' : 'wf-badge-regular'}" style="font-size:0.8rem;padding:0.5rem 0.9rem;">
                <span class="wf-badge-dot"></span> ${is_student ? 'Student' : 'Regular'}
            </span>
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-address-card"></i> Contact Information</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Email</div>
                <div class="wf-detail-value">${escapeHtml(data.email) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Contact</div>
                <div class="wf-detail-value">${escapeHtml(data.contact) || '—'}</div>
            </div>
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Address</div>
                <div class="wf-detail-value muted">${escapeHtml(data.address) || '—'}</div>
            </div>
        </div>
    </div>`;

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-calendar-check"></i> Registration</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Registered</div>
                <div class="wf-detail-value">${data.register_date ? formatDate(data.register_date) : '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Created</div>
                <div class="wf-detail-value">${data.created_at ? formatDate(data.created_at) : '—'}</div>
            </div>
        </div>
    </div>`;

    if (is_student && data.student_id_path) {
        html += `
        <div class="wf-detail-section">
            <div class="wf-detail-section-title"><i class="fas fa-id-card"></i> Student ID</div>
            <a href="${escapeHtml(data.student_id_path)}" target="_blank" class="wf-btn wf-btn-outline wf-btn-sm">
                <i class="fas fa-external-link-alt"></i> View Student ID
            </a>
        </div>`;
    }

    document.getElementById('panelBody').innerHTML = html;

    document.getElementById('panelFooter').innerHTML = `
        <button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); openEditModalFromPanel(${JSON.stringify(data)})'>
            <i class="fas fa-pen"></i> Edit Customer
        </button>
    `;

    panelOverlay.classList.add('open');
    customerPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openEditModalFromPanel(data) {
    openEditModal({
        id: data.id,
        first_name: data.first_name,
        last_name: data.last_name,
        email: data.email,
        contact: data.contact,
        address: data.address,
        account_type: data.account_type,
        discount_rate: data.discount_rate,
    });
}

function closePanel() {
    panelOverlay.classList.remove('open');
    customerPanel.classList.remove('open');
    document.body.style.overflow = '';
}

const customerModal = document.getElementById('customerModal');

function openAddModal() {
    document.getElementById('modalTitle').textContent    = 'Add New Customer';
    document.getElementById('modalSubtitle').textContent = 'Fill in the customer details below';
    document.getElementById('modalIcon').innerHTML       = '<i class="fas fa-user-plus"></i>';
    document.getElementById('formAction').value          = 'add_customer';
    document.getElementById('formId').value              = '';
    document.getElementById('customerForm').reset();
    document.getElementById('passwordLabel').innerHTML   = 'Password <span class="req">*</span>';
    document.getElementById('passwordHint').textContent  = 'Minimum 6 characters';
    document.getElementById('f_password').required       = true;
    toggleStudentFields();
    customerModal.classList.add('open');
}

function openEditModal(data) {
    document.getElementById('modalTitle').textContent    = 'Edit Customer';
    document.getElementById('modalSubtitle').textContent = 'Update customer #' + data.id;
    document.getElementById('modalIcon').innerHTML       = '<i class="fas fa-user-edit"></i>';
    document.getElementById('formAction').value          = 'update_customer';
    document.getElementById('formId').value              = data.id;
    document.getElementById('f_first_name').value        = data.first_name || '';
    document.getElementById('f_last_name').value         = data.last_name  || '';
    document.getElementById('f_email').value             = data.email      || '';
    document.getElementById('f_contact').value           = data.contact    || '';
    document.getElementById('f_address').value           = data.address    || '';
    document.getElementById('f_account_type').value      = data.account_type || 'regular';
    document.getElementById('f_discount_rate').value     = data.discount_rate || 0;
    document.getElementById('f_password').value          = '';
    document.getElementById('passwordLabel').innerHTML   = 'New Password <span style="color:var(--text-muted);font-weight:500;text-transform:none;">(leave blank to keep current)</span>';
    document.getElementById('passwordHint').textContent  = 'Leave blank to keep current password';
    document.getElementById('f_password').required       = false;
    toggleStudentFields();
    customerModal.classList.add('open');
}

function closeCustomerModal() {
    customerModal.classList.remove('open');
}

function toggleStudentFields() {
    const val = document.getElementById('f_account_type').value;
    document.getElementById('studentIdGroup').style.display = (val === 'student') ? 'flex' : 'none';
}

customerModal.addEventListener('click', function(e) {
    if (e.target === customerModal) closeCustomerModal();
});

const deleteModal = document.getElementById('deleteModal');

function openDeleteModal(data) {
    document.getElementById('deleteId').value = data.id;

    if (data.bookings > 0) {
        document.getElementById('deleteTitle').textContent = 'Cannot Delete';
        document.getElementById('deleteMessage').innerHTML =
            'Customer <strong>' + escapeHtml(data.name) + '</strong> has <strong>' + data.bookings + '</strong> booking(s) and cannot be deleted.<br><br><span style="font-size:0.82rem;color:var(--text-muted);">To delete, first reassign or remove their bookings.</span>';
        document.getElementById('deleteForm').querySelector('button[type=submit]').disabled = true;
        document.getElementById('deleteForm').querySelector('button[type=submit]').style.opacity = '0.5';
        document.getElementById('deleteForm').querySelector('button[type=submit]').style.cursor = 'not-allowed';
    } else {
        document.getElementById('deleteTitle').textContent = 'Delete Customer?';
        document.getElementById('deleteMessage').innerHTML =
            'Are you sure you want to delete <strong>' + escapeHtml(data.name) + '</strong>?<br><span style="font-size:0.82rem;color:var(--text-muted);">This action cannot be undone.</span>';
        const btn = document.getElementById('deleteForm').querySelector('button[type=submit]');
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }

    deleteModal.classList.add('open');
}

function closeDeleteModal() {
    deleteModal.classList.remove('open');
}

deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) closeDeleteModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCustomerModal();
        closeDeleteModal();
        closePanel();
    }
});

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(String(dateStr).replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
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