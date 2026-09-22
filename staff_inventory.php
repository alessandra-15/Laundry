<?php
/**
 * staff_inventory.php
 * WashFlow — Staff Inventory Logging
 * Compatible sa laundry_db schema (inventory_items, inventory_categories, inventory_transactions)
 * WALANG auto-seed — existing DB tables lang
 */

define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* AUTH — STAFF ONLY */
if (!is_staff_logged_in()) {
    header('Location: staff_login.php');
    exit();
}

$user          = current_user();
$staff_id      = (int)$user['id'];
$staff_name    = $user['name'];
$staff_role    = $user['role'];
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

/* ──────────────────────────────────────────
   POST — STOCK IN / OUT / ADJUST
   ────────────────────────────────────────── */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $item_id  = (int)($_POST['item_id'] ?? 0);
    $qty      = (float)($_POST['quantity'] ?? 0);
    $notes    = trim($_POST['notes'] ?? '');

    try {
        if ($item_id <= 0) throw new Exception('Invalid inventory item.');
        if ($qty <= 0)     throw new Exception('Quantity must be greater than zero.');

        if (!in_array($action, ['in','out','adjust'], true)) {
            throw new Exception('Invalid action.');
        }

        $stmt = $conn->prepare("
            SELECT item_id, item_name, unit, current_stock, min_stock_level, cost_per_unit
            FROM inventory_items
            WHERE item_id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$item) throw new Exception('Item not found.');

        $prev_qty = (float)$item['current_stock'];
        $new_qty  = $prev_qty;

        /* transaction_type enum: 'Stock In','Stock Out','Adjustment','Waste','Usage' */
        if ($action === 'in') {
            $new_qty  = $prev_qty + $qty;
            $txn_type = 'Stock In';
        } elseif ($action === 'out') {
            if ($qty > $prev_qty) {
                throw new Exception("Cannot take out {$qty} — only {$prev_qty} {$item['unit']} available.");
            }
            $new_qty  = $prev_qty - $qty;
            $txn_type = 'Usage';
        } else { // adjust
            $new_qty  = $qty;
            $txn_type = 'Adjustment';
        }

        /* I-update ang inventory_items. Ang trigger ay auto-update ng status. */
        $stmt = $conn->prepare("
            UPDATE inventory_items
            SET current_stock = ?,
                last_restock_date = IF(? = 'Stock In', CURDATE(), last_restock_date)
            WHERE item_id = ?
        ");
        $stmt->bind_param('dsi', $new_qty, $txn_type, $item_id);
        $stmt->execute();
        $stmt->close();

        /* I-log sa inventory_transactions */
        $stmt = $conn->prepare("
            INSERT INTO inventory_transactions
                (item_id, transaction_type, quantity, previous_stock, new_stock,
                 reference_type, reference_id, unit_cost, notes, created_by)
            VALUES (?, ?, ?, ?, ?, 'Manual', NULL, ?, ?, ?)
        ");
        $unit_cost = (float)$item['cost_per_unit'];
        $stmt->bind_param(
            'isddddsi',
            $item_id, $txn_type, $qty, $prev_qty, $new_qty,
            $unit_cost, $notes, $staff_id
        );
        $stmt->execute();
        $stmt->close();

        if (class_exists('Logger')) {
            Logger::info('Inventory updated', [
                'staff_id'   => $staff_id,
                'item_id'    => $item_id,
                'action'     => $action,
                'txn_type'   => $txn_type,
                'qty'        => $qty,
                'prev'       => $prev_qty,
                'new'        => $new_qty,
            ]);
        }

        $verbs = [
            'in'     => 'added to',
            'out'    => 'used from',
            'adjust' => 'adjusted to',
        ];
        $verb = $verbs[$action] ?? 'updated';

        $msg = $action === 'adjust'
            ? "{$item['item_name']} adjusted to " . number_format($new_qty, 2) . " {$item['unit']}"
            : number_format($qty, 2) . " {$item['unit']} {$verb} {$item['item_name']}. New stock: " . number_format($new_qty, 2);

        $flash = ['type' => 'success', 'message' => $msg];

    } catch (Exception $ex) {
        if (class_exists('Logger')) Logger::error('Inventory action failed', ['error' => $ex->getMessage()]);
        $flash = ['type' => 'error', 'message' => $ex->getMessage()];
    }
}

/* ──────────────────────────────────────────
   FILTER & SEARCH
   ────────────────────────────────────────── */
$filter   = $_GET['filter'] ?? 'all';
$search   = trim($_GET['q'] ?? '');
$cat_filt = (int)($_GET['category'] ?? 0);

$where  = [];
$params = [];
$types  = '';

if ($filter === 'low') {
    $where[] = "i.status = 'Low Stock'";
} elseif ($filter === 'out') {
    $where[] = "i.status = 'Out of Stock'";
} elseif ($filter === 'ok') {
    $where[] = "i.status = 'In Stock'";
}

if ($cat_filt > 0) {
    $where[] = "i.category_id = ?";
    $params[] = $cat_filt;
    $types .= 'i';
}

if ($search !== '') {
    $where[] = "(i.item_name LIKE ? OR i.item_code LIKE ? OR i.location LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

/* ──────────────────────────────────────────
   FETCH INVENTORY ITEMS — GROUP BY para walang duplicates
   ────────────────────────────────────────── */
$items = [];
try {
    $sql = "
        SELECT
            i.item_id, i.category_id, i.item_name, i.item_code,
            i.unit, i.current_stock, i.min_stock_level, i.max_stock_level,
            i.cost_per_unit, i.supplier_id, i.location, i.expiry_date,
            i.status, i.last_restock_date,
            c.category_name,
            s.supplier_name
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON c.category_id = i.category_id
        LEFT JOIN suppliers s            ON s.supplier_id = i.supplier_id
        $where_sql
        ORDER BY
            CASE i.status
                WHEN 'Out of Stock' THEN 0
                WHEN 'Low Stock'    THEN 1
                ELSE 2
            END,
            i.item_name ASC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $items[] = $r;
        $stmt->close();
    }
} catch (Exception $e) {
    if (class_exists('Logger')) Logger::error('Fetch inventory failed', ['error' => $e->getMessage()]);
}

/* ──────────────────────────────────────────
   FETCH CATEGORIES (for filter dropdown) — DISTINCT
   ────────────────────────────────────────── */
$categories = [];
try {
    $q = $conn->query("
        SELECT DISTINCT category_id, category_name, unit_type
        FROM inventory_categories
        ORDER BY category_name
    ");
    if ($q) while ($r = $q->fetch_assoc()) $categories[] = $r;
} catch (Exception $e) {}

/* ──────────────────────────────────────────
   RECENT TRANSACTIONS
   ────────────────────────────────────────── */
$logs = [];
try {
    $q = $conn->query("
        SELECT
            t.transaction_id, t.item_id, t.transaction_type,
            t.quantity, t.previous_stock, t.new_stock,
            t.notes, t.transaction_date, t.created_by,
            i.item_name, i.unit
        FROM inventory_transactions t
        LEFT JOIN inventory_items i ON i.item_id = t.item_id
        ORDER BY t.transaction_date DESC, t.transaction_id DESC
        LIMIT 20
    ");
    if ($q) while ($r = $q->fetch_assoc()) $logs[] = $r;
} catch (Exception $e) {}

/* ──────────────────────────────────────────
   STATS — COUNT DISTINCT para walang dupes
   ────────────────────────────────────────── */
$stats = [
    'total' => 0,
    'low'   => 0,
    'out'   => 0,
    'value' => 0.0,
];

try {
    $q = $conn->query("SELECT COUNT(DISTINCT item_name) c FROM inventory_items");
    if ($q) $stats['total'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT item_name) c FROM inventory_items WHERE status = 'Low Stock'");
    if ($q) $stats['low'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(DISTINCT item_name) c FROM inventory_items WHERE status = 'Out of Stock'");
    if ($q) $stats['out'] = (int)$q->fetch_assoc()['c'];

    /* Total value = sum ng pinaka-unang record per item_name */
    $q = $conn->query("
        SELECT COALESCE(SUM(sub.current_stock * sub.cost_per_unit), 0) v
        FROM (
            SELECT item_name, MIN(item_id) AS keep_id
            FROM inventory_items
            GROUP BY item_name
        ) g
        JOIN inventory_items sub ON sub.item_id = g.keep_id
    ");
    if ($q) $stats['value'] = (float)$q->fetch_assoc()['v'];
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log Inventory — WashFlow Staff</title>

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
.wf-topbar-date { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; background:white; border:1px solid var(--border); border-radius:10px; font-size:0.82rem; font-weight:600; color:var(--dark-blue); }
.wf-topbar-date i { color:var(--gold); font-size:0.85rem; }

.wf-flash { padding:0.9rem 1.15rem; border-radius:12px; margin-bottom:1.25rem; font-weight:600; font-size:0.88rem; display:flex; align-items:center; gap:0.7rem; animation:flashIn 0.35s; }
@keyframes flashIn { from{opacity:0;transform:translateY(-8px);} to{opacity:1;transform:translateY(0);} }
.wf-flash.success { background:#EAF7F0; color:#1E7E45; border-left:4px solid var(--green); }
.wf-flash.error { background:#FDEDEC; color:#A8322D; border-left:4px solid var(--red); }

.wf-stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; margin-bottom:1.5rem; }
@media (max-width:1100px) { .wf-stats-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width:600px)  { .wf-stats-grid { grid-template-columns:1fr; } }

.wf-stat-card { background:white; border-radius:14px; padding:1.25rem; border:1px solid var(--border); min-height:110px; display:flex; flex-direction:column; justify-content:space-between; }
.wf-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.6rem; }
.wf-stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; }
.wf-stat-icon.icon-blue  { background:var(--light-blue-soft); color:var(--primary); }
.wf-stat-icon.icon-green { background:#EAF7F0; color:#1E7E45; }
.wf-stat-icon.icon-gold  { background:var(--yellow-soft); color:var(--yellow-dark); }
.wf-stat-icon.icon-red   { background:#FDEDEC; color:#A8322D; }
.wf-stat-value { font-size:1.6rem; font-weight:800; color:var(--dark-blue); line-height:1.1; letter-spacing:-0.03em; margin-bottom:0.2rem; }
.wf-stat-label { font-size:0.78rem; color:var(--text-secondary); font-weight:500; }

.wf-card { background:white; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.25rem; }
.wf-card-head { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-soft); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.wf-card-title { display:flex; align-items:center; gap:0.9rem; }
.wf-card-title-icon { width:42px; height:42px; border-radius:11px; background:var(--light-blue-soft); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-card-title h3 { font-size:1rem; font-weight:700; color:var(--dark-blue); margin:0; }
.wf-card-title p { font-size:0.75rem; color:var(--text-muted); margin:0.1rem 0 0; font-weight:500; }
.wf-card-body { padding:1.5rem; }

.wf-toolbar { display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap; margin-bottom:1.25rem; }
.wf-search { flex:1; min-width:220px; position:relative; }
.wf-search input { width:100%; padding:0.7rem 0.95rem 0.7rem 2.5rem; border:1.5px solid var(--border); border-radius:10px; font-size:0.85rem; font-family:inherit; outline:none; transition:all 0.2s; }
.wf-search input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }
.wf-search i { position:absolute; left:0.9rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem; }

.wf-select { padding:0.7rem 0.95rem; border:1.5px solid var(--border); border-radius:10px; font-size:0.85rem; font-family:inherit; outline:none; background:white; }

.wf-tabs { display:flex; gap:0.35rem; background:var(--light-blue-pale); padding:0.35rem; border-radius:10px; }
.wf-tab { padding:0.55rem 1rem; border-radius:7px; font-size:0.8rem; font-weight:700; color:var(--text-secondary); cursor:pointer; transition:all 0.2s; border:none; background:transparent; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem; white-space:nowrap; }
.wf-tab.active { background:white; color:var(--dark-blue); box-shadow:0 2px 8px rgba(6,52,82,0.08); }
.wf-tab:hover:not(.active) { color:var(--dark-blue); }

.wf-grid-2 { display:grid; grid-template-columns:1.6fr 1fr; gap:1.25rem; align-items:flex-start; }
@media (max-width:1100px) { .wf-grid-2 { grid-template-columns:1fr; } }

.wf-inv-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem; }
.wf-inv-card { background:#FAFCFE; border:1.5px solid var(--border-soft); border-radius:12px; padding:1.1rem; transition:all 0.2s; position:relative; overflow:hidden; }
.wf-inv-card:hover { border-color:var(--light-blue); background:var(--light-blue-pale); transform:translateY(-2px); }
.wf-inv-card.low { border-color:#F5DDA0; background:#FFFCF2; }
.wf-inv-card.out { border-color:#F5C6C2; background:#FEF5F4; }

.wf-inv-head { display:flex; align-items:flex-start; justify-content:space-between; gap:0.6rem; margin-bottom:0.75rem; }
.wf-inv-name { font-size:0.95rem; font-weight:800; color:var(--dark-blue); line-height:1.3; }
.wf-inv-cat  { font-size:0.7rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.06em; margin-top:0.15rem; }

.wf-inv-qty { font-size:2rem; font-weight:800; color:var(--dark-blue); line-height:1; letter-spacing:-0.04em; }
.wf-inv-qty .unit { font-size:0.85rem; font-weight:600; color:var(--text-muted); margin-left:0.3rem; letter-spacing:0; }
.wf-inv-card.low .wf-inv-qty { color:#B88A00; }
.wf-inv-card.out .wf-inv-qty { color:#A8322D; }

.wf-inv-meta { font-size:0.72rem; color:var(--text-muted); margin-top:0.4rem; font-weight:500; }

.wf-inv-actions { display:flex; gap:0.35rem; margin-top:0.85rem; flex-wrap:wrap; }

.wf-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.6rem; border-radius:20px; font-size:0.68rem; font-weight:800; }
.wf-badge.ok   { background:#EAF7F0; color:#1E7E45; }
.wf-badge.low  { background:#FFF9DB; color:#8A6D00; }
.wf-badge.out  { background:#FDEDEC; color:#A8322D; }

.wf-btn-sm {
    display:inline-flex; align-items:center; gap:0.35rem;
    padding:0.5rem 0.85rem; border-radius:8px;
    font-size:0.75rem; font-weight:800;
    cursor:pointer; transition:all 0.15s;
    border:none; font-family:inherit; white-space:nowrap;
}
.wf-btn-sm.in   { background:#EAF7F0; color:#1E7E45; border:1px solid #B8E6C9; }
.wf-btn-sm.in:hover   { background:#1E7E45; color:white; border-color:#1E7E45; }
.wf-btn-sm.out  { background:#FFF4E5; color:#B86A00; border:1px solid #F5DDA0; }
.wf-btn-sm.out:hover  { background:#B86A00; color:white; border-color:#B86A00; }
.wf-btn-sm.adjust { background:var(--light-blue-soft); color:var(--primary); border:1px solid var(--light-blue); }
.wf-btn-sm.adjust:hover { background:var(--primary); color:white; border-color:var(--primary); }

.wf-log-item { display:flex; align-items:center; gap:0.85rem; padding:0.75rem 0.9rem; border-radius:10px; background:#FAFCFE; border:1px solid var(--border-soft); margin-bottom:0.5rem; }
.wf-log-icon { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:0.8rem; flex-shrink:0; }
.wf-log-icon.txn-in  { background:#EAF7F0; color:#1E7E45; }
.wf-log-icon.txn-out { background:#FFF4E5; color:#B86A00; }
.wf-log-icon.txn-adj { background:var(--light-blue-soft); color:var(--primary); }
.wf-log-icon.txn-waste { background:#FDEDEC; color:#A8322D; }
.wf-log-info { flex:1; min-width:0; line-height:1.3; }
.wf-log-title { font-size:0.82rem; font-weight:700; color:var(--dark-blue); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.wf-log-meta  { font-size:0.7rem; color:var(--text-muted); font-weight:500; }

.wf-empty { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
.wf-empty i { font-size:2.5rem; color:var(--light-blue); margin-bottom:0.75rem; display:block; }
.wf-empty p { margin:0; font-size:0.88rem; font-weight:600; }

.wf-modal-overlay { display:none; position:fixed; inset:0; background:rgba(6,52,82,0.55); z-index:2000; align-items:center; justify-content:center; padding:1.5rem; backdrop-filter:blur(3px); }
.wf-modal-overlay.open { display:flex; }
.wf-modal { background:white; border-radius:16px; max-width:480px; width:100%; max-height:90vh; overflow-y:auto; padding:1.75rem; animation:modalIn 0.25s; }
@keyframes modalIn { from{opacity:0;transform:scale(0.94);} to{opacity:1;transform:scale(1);} }
.wf-modal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
.wf-modal-head h3 { font-size:1.1rem; font-weight:800; color:var(--dark-blue); margin:0; }
.wf-modal-close { width:34px; height:34px; border-radius:9px; background:var(--light-blue-pale); color:var(--text-secondary); display:flex; align-items:center; justify-content:center; cursor:pointer; border:none; font-size:0.9rem; transition:all 0.15s; }
.wf-modal-close:hover { background:var(--red); color:white; }

.wf-label { display:block; font-size:0.75rem; font-weight:700; color:var(--dark-blue); margin-bottom:0.4rem; }
.wf-label .req { color:var(--red); }
.wf-input, .wf-textarea { width:100%; padding:0.75rem 0.95rem; border:1.5px solid var(--border); border-radius:10px; font-size:0.85rem; font-family:inherit; outline:none; transition:all 0.2s; background:white; }
.wf-input:focus, .wf-textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,90,133,0.1); }
.wf-textarea { resize:vertical; min-height:70px; }
.wf-form-group { margin-bottom:1rem; }

.wf-modal-actions { display:flex; gap:0.6rem; margin-top:1.25rem; flex-wrap:wrap; }
.wf-btn { display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; padding:0.85rem 1.4rem; border-radius:10px; font-size:0.85rem; font-weight:800; cursor:pointer; transition:all 0.2s; border:none; font-family:inherit; text-decoration:none; }
.wf-btn-primary { background:var(--primary); color:white; }
.wf-btn-primary:hover { background:var(--primary-mid); transform:translateY(-1px); }
.wf-btn-success { background:#1E7E45; color:white; }
.wf-btn-success:hover { background:#166235; transform:translateY(-1px); }
.wf-btn-warn { background:#B86A00; color:white; }
.wf-btn-warn:hover { background:#985800; transform:translateY(-1px); }
.wf-btn-danger { background:#A8322D; color:white; }
.wf-btn-danger:hover { background:#8A2620; transform:translateY(-1px); }
.wf-btn-outline { background:white; color:var(--primary); border:1.5px solid var(--border); }
.wf-btn-outline:hover { border-color:var(--primary); background:var(--light-blue-pale); }

.wf-item-preview { display:flex; align-items:center; gap:0.85rem; padding:0.9rem 1rem; background:var(--light-blue-pale); border-radius:10px; margin-bottom:1.25rem; }
.wf-item-preview-icon { width:42px; height:42px; border-radius:11px; background:white; color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.wf-item-preview-info { flex:1; line-height:1.3; }
.wf-item-preview-name { font-size:0.9rem; font-weight:800; color:var(--dark-blue); }
.wf-item-preview-stock { font-size:0.75rem; color:var(--text-secondary); font-weight:600; }

.wf-sidebar-toggle { display:none; position:fixed; top:1rem; left:1rem; z-index:1001; width:44px; height:44px; background:var(--dark-blue-deep); color:white; border:none; border-radius:10px; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 4px 12px rgba(4,38,64,0.3); cursor:pointer; }
.wf-sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; }
.wf-sidebar-overlay.open { display:block; }

@media (max-width:900px) {
    .wf-layout { grid-template-columns:1fr; }
    .wf-sidebar { position:fixed; top:0; left:-300px; width:300px; z-index:1000; transition:left 0.3s cubic-bezier(0.4,0,0.2,1); box-shadow:20px 0 60px rgba(0,0,0,0.3); }
    .wf-sidebar.open { left:0; }
    .wf-sidebar-toggle { display:flex; }
    .wf-main { padding:1.25rem; padding-top:4rem; }
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
            <a href="staff_dashboard.php" class="wf-nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="booking_management.php" class="wf-nav-item"><i class="fas fa-clipboard-list"></i><span>My Queue</span></a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="staff_walkin.php" class="wf-nav-item"><i class="fas fa-walking"></i><span>Walk-in Booking</span></a>
            <a href="staff_payments.php" class="wf-nav-item"><i class="fas fa-money-check-alt"></i><span>Verify Payments</span></a>
            <a href="staff_inventory.php" class="wf-nav-item active"><i class="fas fa-flask"></i><span>Log Inventory</span></a>

            <div class="wf-nav-section-label">Support</div>
            <a href="staff_feedback.php" class="wf-nav-item"><i class="fas fa-star"></i><span>Reply Feedback</span></a>
            <a href="staff_complaints.php" class="wf-nav-item"><i class="fas fa-headset"></i><span>Handle Complaints</span></a>

            <div class="wf-nav-section-label">Account</div>
            <a href="staff_shift.php" class="wf-nav-item"><i class="fas fa-clock"></i><span>My Shift</span></a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= e($staff_initial) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= e($staff_name) ?></div>
                    <div class="wf-user-role"><?= e($my_role['label']) ?></div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="wf-main">

        <div class="wf-topbar">
            <div class="wf-topbar-greeting">
                <h1><i class="fas fa-flask" style="color:var(--primary);"></i> Log <span class="accent">Inventory</span></h1>
                <p>Track supplies, stock in/out, waste, and low-stock alerts</p>
            </div>
            <div class="wf-topbar-date">
                <i class="fas fa-calendar-alt"></i>
                <?= date('l, F j, Y') ?>
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
                <div class="wf-stat-top"><div class="wf-stat-icon icon-blue"><i class="fas fa-boxes"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total']) ?></div>
                    <div class="wf-stat-label">Unique Items</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-gold"><i class="fas fa-exclamation-triangle"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['low']) ?></div>
                    <div class="wf-stat-label">Low Stock</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-red"><i class="fas fa-times-circle"></i></div></div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['out']) ?></div>
                    <div class="wf-stat-label">Out of Stock</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top"><div class="wf-stat-icon icon-green"><i class="fas fa-coins"></i></div></div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($stats['value'], 2) ?></div>
                    <div class="wf-stat-label">Total Stock Value</div>
                </div>
            </div>
        </div>

        <div class="wf-grid-2">

            <div>
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon"><i class="fas fa-boxes-stacked"></i></div>
                            <div>
                                <h3>Inventory Items</h3>
                                <p>Click buttons to add, use, or adjust stock</p>
                            </div>
                        </div>
                    </div>
                    <div class="wf-card-body">

                        <div class="wf-toolbar">
                            <div class="wf-search">
                                <i class="fas fa-search"></i>
                                <form method="GET" style="margin:0;">
                                    <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                    <input type="hidden" name="category" value="<?= (int)$cat_filt ?>">
                                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search item, code, or location...">
                                </form>
                            </div>
                            <form method="GET" style="margin:0;">
                                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                <input type="hidden" name="q" value="<?= e($search) ?>">
                                <select name="category" class="wf-select" onchange="this.form.submit()">
                                    <option value="0">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int)$cat['category_id'] ?>" <?= $cat_filt === (int)$cat['category_id'] ? 'selected' : '' ?>>
                                            <?= e($cat['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <div class="wf-tabs">
                                <a href="?filter=all&category=<?= $cat_filt ?>&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                                <a href="?filter=low&category=<?= $cat_filt ?>&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'low' ? 'active' : '' ?>">Low</a>
                                <a href="?filter=out&category=<?= $cat_filt ?>&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'out' ? 'active' : '' ?>">Out</a>
                                <a href="?filter=ok&category=<?= $cat_filt ?>&q=<?= urlencode($search) ?>" class="wf-tab <?= $filter === 'ok' ? 'active' : '' ?>">OK</a>
                            </div>
                        </div>

                        <?php if (empty($items)): ?>
                            <div class="wf-empty">
                                <i class="fas fa-box-open"></i>
                                <p>No items found</p>
                            </div>
                        <?php else: ?>
                            <div class="wf-inv-grid">
                            <?php foreach ($items as $it):
                                $qty    = (float)$it['current_stock'];
                                $thresh = (float)$it['min_stock_level'];
                                $status = $qty <= 0 ? 'out' : ($qty <= $thresh ? 'low' : 'ok');
                                $status_l = $status === 'out' ? 'Out of Stock' : ($status === 'low' ? 'Low Stock' : 'In Stock');
                            ?>
                                <div class="wf-inv-card <?= $status ?>">
                                    <div class="wf-inv-head">
                                        <div style="min-width:0;flex:1;">
                                            <div class="wf-inv-name"><?= e($it['item_name']) ?></div>
                                            <div class="wf-inv-cat"><?= e($it['category_name'] ?: 'Uncategorized') ?></div>
                                        </div>
                                        <span class="wf-badge <?= $status ?>">
                                            <?= $status === 'out' ? '<i class="fas fa-times-circle"></i>' : ($status === 'low' ? '<i class="fas fa-exclamation-triangle"></i>' : '<i class="fas fa-check-circle"></i>') ?>
                                            <?= $status_l ?>
                                        </span>
                                    </div>

                                    <div class="wf-inv-qty">
                                        <?= number_format($qty, $qty == (int)$qty ? 0 : 2) ?>
                                        <span class="unit"><?= e($it['unit']) ?></span>
                                    </div>
                                    <div class="wf-inv-meta">
                                        <i class="fas fa-bell"></i> Alert at <?= number_format($thresh, 0) ?>
                                        &nbsp;·&nbsp;
                                        ₱<?= number_format((float)$it['cost_per_unit'], 2) ?>/<?= e($it['unit']) ?>
                                    </div>
                                    <?php if ($it['location']): ?>
                                    <div class="wf-inv-meta">
                                        <i class="fas fa-location-dot"></i> <?= e($it['location']) ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="wf-inv-actions">
                                        <button type="button" class="wf-btn-sm in" onclick='openStockModal("in", <?= json_encode([
                                            "id"   => (int)$it["item_id"],
                                            "name" => $it["item_name"],
                                            "unit" => $it["unit"],
                                            "qty"  => (float)$it["current_stock"],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                            <i class="fas fa-plus"></i> Stock In
                                        </button>
                                        <button type="button" class="wf-btn-sm out" onclick='openStockModal("out", <?= json_encode([
                                            "id"   => (int)$it["item_id"],
                                            "name" => $it["item_name"],
                                            "unit" => $it["unit"],
                                            "qty"  => (float)$it["current_stock"],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                            <i class="fas fa-minus"></i> Use
                                        </button>
                                        <button type="button" class="wf-btn-sm adjust" onclick='openStockModal("adjust", <?= json_encode([
                                            "id"   => (int)$it["item_id"],
                                            "name" => $it["item_name"],
                                            "unit" => $it["unit"],
                                            "qty"  => (float)$it["current_stock"],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                            <i class="fas fa-sliders-h"></i> Adjust
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <div>
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon"><i class="fas fa-history"></i></div>
                            <div>
                                <h3>Recent Activity</h3>
                                <p>Last 20 stock movements</p>
                            </div>
                        </div>
                    </div>
                    <div class="wf-card-body">
                        <?php if (empty($logs)): ?>
                            <div class="wf-empty">
                                <i class="fas fa-clipboard"></i>
                                <p>No activity yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($logs as $log):
                                $txn = $log['transaction_type'];
                                if ($txn === 'Stock In') {
                                    $icon_class = 'txn-in';  $icon = 'fa-plus';       $verb = 'Stock In';
                                } elseif ($txn === 'Stock Out' || $txn === 'Usage') {
                                    $icon_class = 'txn-out'; $icon = 'fa-minus';      $verb = 'Used';
                                } elseif ($txn === 'Waste') {
                                    $icon_class = 'txn-waste'; $icon = 'fa-trash';    $verb = 'Wasted';
                                } else {
                                    $icon_class = 'txn-adj'; $icon = 'fa-sliders-h';  $verb = 'Adjusted';
                                }
                            ?>
                                <div class="wf-log-item">
                                    <div class="wf-log-icon <?= $icon_class ?>"><i class="fas <?= $icon ?>"></i></div>
                                    <div class="wf-log-info">
                                        <div class="wf-log-title">
                                            <?= e($log['item_name'] ?? 'Item #' . $log['item_id']) ?>
                                        </div>
                                        <div class="wf-log-meta">
                                            <?= $verb ?> <?= number_format((float)$log['quantity'], 0) ?> <?= e($log['unit'] ?? '') ?>
                                            · Now: <?= number_format((float)$log['new_stock'], 0) ?>
                                        </div>
                                        <div class="wf-log-meta" style="margin-top:0.15rem;">
                                            <?= e($staff_name) ?> ·
                                            <?= date('M j, g:i A', strtotime($log['transaction_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- STOCK MODAL -->
<div class="wf-modal-overlay" id="stockModal">
    <div class="wf-modal">
        <div class="wf-modal-head">
            <h3 id="stockModalTitle"><i class="fas fa-boxes" style="color:var(--primary);"></i> Stock Movement</h3>
            <button type="button" class="wf-modal-close" onclick="closeModal('stockModal')"><i class="fas fa-times"></i></button>
        </div>

        <div class="wf-item-preview">
            <div class="wf-item-preview-icon"><i class="fas fa-flask"></i></div>
            <div class="wf-item-preview-info">
                <div class="wf-item-preview-name" id="stockItemName">—</div>
                <div class="wf-item-preview-stock" id="stockItemStock">—</div>
            </div>
        </div>

        <form method="POST" id="stockForm">
            <input type="hidden" name="action" id="stockAction">
            <input type="hidden" name="item_id" id="stockItemId">

            <div class="wf-form-group">
                <label class="wf-label" id="stockQtyLabel">Quantity <span class="req">*</span></label>
                <input type="number" name="quantity" id="stockQty" class="wf-input" step="0.01" min="0.01" required placeholder="0.00">
            </div>

            <div class="wf-form-group">
                <label class="wf-label">Notes (optional)</label>
                <textarea name="notes" class="wf-textarea" placeholder="e.g. Received from supplier, used for 3 loads..."></textarea>
            </div>

            <div class="wf-modal-actions">
                <button type="submit" class="wf-btn" id="stockSubmitBtn">
                    <i class="fas fa-check"></i> Confirm
                </button>
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeModal('stockModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStockModal(action, item) {
    const modal = document.getElementById('stockModal');
    const title = document.getElementById('stockModalTitle');
    const btn   = document.getElementById('stockSubmitBtn');
    const label = document.getElementById('stockQtyLabel');

    document.getElementById('stockAction').value   = action;
    document.getElementById('stockItemId').value   = item.id;
    document.getElementById('stockItemName').textContent = item.name;
    document.getElementById('stockItemStock').textContent = 'Current stock: ' + item.qty + ' ' + item.unit;
    document.getElementById('stockQty').value = '';
    document.getElementById('stockQty').placeholder = action === 'adjust' ? 'Enter new total' : '0.00';

    if (action === 'in') {
        title.innerHTML = '<i class="fas fa-plus-circle" style="color:#1E7E45;"></i> Stock In';
        btn.className = 'wf-btn wf-btn-success';
        btn.innerHTML = '<i class="fas fa-plus"></i> Add Stock';
        label.innerHTML = 'Quantity to Add <span class="req">*</span>';
    } else if (action === 'out') {
        title.innerHTML = '<i class="fas fa-minus-circle" style="color:#B86A00;"></i> Use / Take Out';
        btn.className = 'wf-btn wf-btn-warn';
        btn.innerHTML = '<i class="fas fa-minus"></i> Take Out';
        label.innerHTML = 'Quantity to Use <span class="req">*</span>';
    } else {
        title.innerHTML = '<i class="fas fa-sliders-h" style="color:var(--primary);"></i> Adjust Stock';
        btn.className = 'wf-btn wf-btn-primary';
        btn.innerHTML = '<i class="fas fa-check"></i> Set Quantity';
        label.innerHTML = 'New Total Quantity <span class="req">*</span>';
    }

    modal.classList.add('open');
    setTimeout(() => document.getElementById('stockQty').focus(), 100);
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.wf-modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.wf-modal-overlay.open').forEach(m => m.classList.remove('open'));
});

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