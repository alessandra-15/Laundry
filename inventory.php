<?php
/**
 * inventory.php
 * WashFlow — Inventory Management
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

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

/* ══════════════════════════════════════════
   POST HANDLERS
   ══════════════════════════════════════════ */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    /* ---------- ADD ITEM ---------- */
    if ($action === 'add_item') {
        $category_id     = (int)($_POST['category_id'] ?? 0);
        $item_name       = trim($_POST['item_name'] ?? '');
        $item_code       = trim($_POST['item_code'] ?? '');
        $description     = trim($_POST['description'] ?? '');
        $unit            = trim($_POST['unit'] ?? 'pcs');
        $current_stock   = (float)($_POST['current_stock'] ?? 0);
        $min_stock_level = (float)($_POST['min_stock_level'] ?? 5);
        $max_stock_level = (float)($_POST['max_stock_level'] ?? 100);
        $cost_per_unit   = (float)($_POST['cost_per_unit'] ?? 0);
        $supplier_id     = (int)($_POST['supplier_id'] ?? 0) ?: null;
        $location        = trim($_POST['location'] ?? '');
        $expiry_date     = trim($_POST['expiry_date'] ?? '') ?: null;

        $errors = [];
        if ($category_id <= 0) $errors[] = 'Please select a category.';
        if ($item_name === '') $errors[] = 'Item name is required.';
        if ($unit === '')      $unit = 'pcs';

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO inventory_items
                        (category_id, item_name, item_code, description, unit,
                         current_stock, min_stock_level, max_stock_level, cost_per_unit,
                         supplier_id, location, expiry_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    'issssddddsss',
                    $category_id, $item_name, $item_code, $description, $unit,
                    $current_stock, $min_stock_level, $max_stock_level, $cost_per_unit,
                    $supplier_id, $location, $expiry_date
                );
                $ok = $stmt->execute();
                $new_id = $conn->insert_id;
                $stmt->close();

                if ($ok) {
                    if ($current_stock > 0) {
                        $ref_type = 'Manual';
                        $notes = 'Initial stock on item creation';
                        $stmt = $conn->prepare("
                            INSERT INTO inventory_transactions
                                (item_id, transaction_type, quantity, previous_stock, new_stock,
                                 reference_type, unit_cost, notes, created_by)
                            VALUES (?, 'Stock In', ?, 0, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param('iddsdsi',
                            $new_id, $current_stock, $current_stock,
                            $ref_type, $cost_per_unit, $notes, $admin_id
                        );
                        $stmt->execute();
                        $stmt->close();
                    }

                    if (class_exists('Logger')) {
                        Logger::info('Inventory item added', [
                            'item_id' => $new_id, 'name' => $item_name, 'admin' => $admin_id
                        ]);
                    }
                    $flash = ['type'=>'success','message'=>"Item #$new_id added successfully."];
                } else {
                    $flash = ['type'=>'error','message'=>'Failed to add item.'];
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Add item failed', ['error'=>$ex->getMessage()]);
                $flash = ['type'=>'error','message'=>'Database error: '.$ex->getMessage()];
            }
        } else {
            $flash = ['type'=>'error','message'=>implode(' ', $errors)];
        }
    }

    /* ---------- UPDATE ITEM ---------- */
    if ($action === 'update_item') {
        $item_id         = (int)($_POST['item_id'] ?? 0);
        $category_id     = (int)($_POST['category_id'] ?? 0);
        $item_name       = trim($_POST['item_name'] ?? '');
        $item_code       = trim($_POST['item_code'] ?? '');
        $description     = trim($_POST['description'] ?? '');
        $unit            = trim($_POST['unit'] ?? 'pcs');
        $min_stock_level = (float)($_POST['min_stock_level'] ?? 5);
        $max_stock_level = (float)($_POST['max_stock_level'] ?? 100);
        $cost_per_unit   = (float)($_POST['cost_per_unit'] ?? 0);
        $supplier_id     = (int)($_POST['supplier_id'] ?? 0) ?: null;
        $location        = trim($_POST['location'] ?? '');
        $expiry_date     = trim($_POST['expiry_date'] ?? '') ?: null;

        $errors = [];
        if ($item_id <= 0) $errors[] = 'Invalid item ID.';
        if ($category_id <= 0) $errors[] = 'Please select a category.';
        if ($item_name === '') $errors[] = 'Item name is required.';
        if ($unit === '') $unit = 'pcs';

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    UPDATE inventory_items SET
                        category_id=?, item_name=?, item_code=?, description=?, unit=?,
                        min_stock_level=?, max_stock_level=?, cost_per_unit=?,
                        supplier_id=?, location=?, expiry_date=?
                    WHERE item_id=?
                ");
                $stmt->bind_param(
                    'issssdddsssi',
                    $category_id, $item_name, $item_code, $description, $unit,
                    $min_stock_level, $max_stock_level, $cost_per_unit,
                    $supplier_id, $location, $expiry_date, $item_id
                );
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    if (class_exists('Logger')) {
                        Logger::info('Inventory item updated', ['item_id'=>$item_id, 'admin'=>$admin_id]);
                    }
                    $flash = ['type'=>'success','message'=>"Item #$item_id updated."];
                } else {
                    $flash = ['type'=>'error','message'=>'Failed to update item.'];
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Update item failed', ['error'=>$ex->getMessage()]);
                $flash = ['type'=>'error','message'=>'Database error: '.$ex->getMessage()];
            }
        } else {
            $flash = ['type'=>'error','message'=>implode(' ', $errors)];
        }
    }

    /* ---------- STOCK ADJUSTMENT ---------- */
    if ($action === 'adjust_stock') {
        $item_id    = (int)($_POST['item_id'] ?? 0);
        $type       = $_POST['transaction_type'] ?? 'Stock In';
        $quantity   = (float)($_POST['quantity'] ?? 0);
        $notes      = trim($_POST['notes'] ?? '');
        $supplier_id = (int)($_POST['supplier_id'] ?? 0) ?: null;
        $unit_cost  = (float)($_POST['unit_cost'] ?? 0);

        $allowed_types = ['Stock In','Stock Out','Adjustment','Waste'];
        $errors = [];
        if ($item_id <= 0) $errors[] = 'Invalid item.';
        if (!in_array($type, $allowed_types, true)) $errors[] = 'Invalid transaction type.';
        if ($quantity <= 0) $errors[] = 'Quantity must be greater than 0.';

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("SELECT current_stock, item_name, cost_per_unit FROM inventory_items WHERE item_id=?");
                $stmt->bind_param('i', $item_id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$item) {
                    $flash = ['type'=>'error','message'=>'Item not found.'];
                } else {
                    $prev = (float)$item['current_stock'];
                    if ($unit_cost <= 0) $unit_cost = (float)$item['cost_per_unit'];

                    $new = $prev;
                    if ($type === 'Stock In')     $new = $prev + $quantity;
                    if ($type === 'Stock Out')    $new = max(0, $prev - $quantity);
                    if ($type === 'Waste')        $new = max(0, $prev - $quantity);
                    if ($type === 'Adjustment')   $new = $quantity;

                    $ref_type = ($type === 'Stock In') ? 'Purchase' : (($type === 'Waste') ? 'Waste' : 'Manual');

                    $stmt = $conn->prepare("
                        UPDATE inventory_items SET current_stock=?, last_restock_date=IF(?='Stock In', CURDATE(), last_restock_date)
                        WHERE item_id=?
                    ");
                    $stmt->bind_param('dsi', $new, $type, $item_id);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare("
                        INSERT INTO inventory_transactions
                            (item_id, transaction_type, quantity, previous_stock, new_stock,
                             reference_type, supplier_id, unit_cost, notes, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param('isdddssdsi',
                        $item_id, $type, $quantity, $prev, $new,
                        $ref_type, $supplier_id, $unit_cost, $notes, $admin_id
                    );
                    $stmt->execute();
                    $stmt->close();

                    if (class_exists('Logger')) {
                        Logger::info('Stock adjusted', [
                            'item_id'=>$item_id, 'type'=>$type, 'qty'=>$quantity,
                            'from'=>$prev, 'to'=>$new, 'admin'=>$admin_id
                        ]);
                    }
                    $flash = ['type'=>'success','message'=>"Stock updated: $prev → $new"];
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Stock adjust failed', ['error'=>$ex->getMessage()]);
                $flash = ['type'=>'error','message'=>'Database error: '.$ex->getMessage()];
            }
        } else {
            $flash = ['type'=>'error','message'=>implode(' ', $errors)];
        }
    }

    /* ---------- DELETE ITEM ---------- */
    if ($action === 'delete_item') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) c FROM inventory_transactions WHERE item_id=?");
                $stmt->bind_param('i', $item_id);
                $stmt->execute();
                $tx_count = (int)$stmt->get_result()->fetch_assoc()['c'];
                $stmt->close();

                if ($tx_count > 0) {
                    $flash = ['type'=>'error','message'=>"Cannot delete — may $tx_count transaction(s) na naka-link sa item na ito."];
                } else {
                    $stmt = $conn->prepare("DELETE FROM inventory_items WHERE item_id=?");
                    $stmt->bind_param('i', $item_id);
                    $ok = $stmt->execute();
                    $stmt->close();

                    if ($ok) {
                        if (class_exists('Logger')) {
                            Logger::info('Inventory item deleted', ['item_id'=>$item_id, 'admin'=>$admin_id]);
                        }
                        $flash = ['type'=>'success','message'=>"Item #$item_id deleted."];
                    } else {
                        $flash = ['type'=>'error','message'=>'Failed to delete item.'];
                    }
                }
            } catch (Exception $ex) {
                if (class_exists('Logger')) Logger::error('Delete item failed', ['error'=>$ex->getMessage()]);
                $flash = ['type'=>'error','message'=>'Database error: '.$ex->getMessage()];
            }
        }
    }

    /* ---------- EXPORT CSV ---------- */
    if ($action === 'export_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventory_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['ID','Item Code','Item Name','Category','Unit','Stock','Min','Max','Cost/Unit','Total Value','Status','Supplier','Location','Expiry']);

        $q = $conn->query("
            SELECT i.item_id, i.item_code, i.item_name, c.category_name, i.unit,
                   i.current_stock, i.min_stock_level, i.max_stock_level,
                   i.cost_per_unit, (i.current_stock * i.cost_per_unit) AS total_value,
                   i.status, s.supplier_name, i.location, i.expiry_date
            FROM inventory_items i
            LEFT JOIN inventory_categories c ON i.category_id = c.category_id
            LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
            ORDER BY i.item_id ASC
        ");
        if ($q) while ($r = $q->fetch_assoc()) {
            fputcsv($out, [
                $r['item_id'],
                $r['item_code'] ?? '',
                $r['item_name'] ?? '',
                $r['category_name'] ?? '',
                $r['unit'] ?? '',
                number_format((float)$r['current_stock'], 2, '.', ''),
                number_format((float)$r['min_stock_level'], 2, '.', ''),
                number_format((float)$r['max_stock_level'], 2, '.', ''),
                number_format((float)$r['cost_per_unit'], 2, '.', ''),
                number_format((float)$r['total_value'], 2, '.', ''),
                $r['status'] ?? '',
                $r['supplier_name'] ?? '',
                $r['location'] ?? '',
                $r['expiry_date'] ?? ''
            ]);
        }
        fclose($out);
        exit();
    }
}

/* ══════════════════════════════════════════
   FILTERS + PAGINATION
   ══════════════════════════════════════════ */
$search      = trim($_GET['q'] ?? '');
$category_f  = (int)($_GET['category'] ?? 0);
$status_f    = trim($_GET['status'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 20;
$offset      = ($page - 1) * $per_page;
$sort        = $_GET['sort'] ?? 'id_desc';

$order_map = [
    'id_desc'      => 'i.item_id DESC',
    'id_asc'       => 'i.item_id ASC',
    'name_asc'     => 'i.item_name ASC',
    'name_desc'    => 'i.item_name DESC',
    'stock_asc'    => 'i.current_stock ASC',
    'stock_desc'   => 'i.current_stock DESC',
    'updated_desc' => 'i.updated_at DESC',
];
$order_sql = $order_map[$sort] ?? 'i.item_id DESC';

/* ══════════════════════════════════════════
   STATS
   ══════════════════════════════════════════ */
$stats = [
    'total_items'   => 0,
    'in_stock'      => 0,
    'low_stock'     => 0,
    'out_of_stock'  => 0,
    'total_value'   => 0,
    'unread_notifs' => 0,
];

try {
    $q = $conn->query("SELECT COUNT(*) c FROM inventory_items");
    if ($q) $stats['total_items'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM inventory_items WHERE status='In Stock'");
    if ($q) $stats['in_stock'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM inventory_items WHERE status='Low Stock'");
    if ($q) $stats['low_stock'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COUNT(*) c FROM inventory_items WHERE status='Out of Stock'");
    if ($q) $stats['out_of_stock'] = (int)$q->fetch_assoc()['c'];

    $q = $conn->query("SELECT COALESCE(SUM(current_stock * cost_per_unit), 0) v FROM inventory_items");
    if ($q) $stats['total_value'] = (float)$q->fetch_assoc()['v'];

    $q = $conn->query("SELECT COUNT(*) c FROM inventory_notifications WHERE is_read=0");
    if ($q) $stats['unread_notifs'] = (int)$q->fetch_assoc()['c'];
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Inventory stats failed', ['error'=>$ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH CATEGORIES & SUPPLIERS
   ══════════════════════════════════════════ */
$categories = [];
$suppliers  = [];
try {
    $q = $conn->query("SELECT category_id, category_name, unit_type FROM inventory_categories ORDER BY category_name ASC");
    if ($q) while ($r = $q->fetch_assoc()) $categories[] = $r;

    $q = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status='Active' ORDER BY supplier_name ASC");
    if ($q) while ($r = $q->fetch_assoc()) $suppliers[] = $r;
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch dropdowns failed', ['error'=>$ex->getMessage()]);
}

/* ══════════════════════════════════════════
   FETCH ITEMS LIST
   ══════════════════════════════════════════ */
$items       = [];
$total_rows  = 0;
$total_pages = 1;

try {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[] = "(i.item_id LIKE ? OR i.item_name LIKE ? OR i.item_code LIKE ? OR i.location LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 4; $i++) { $params[] = $like; $types .= 's'; }
    }
    if ($category_f > 0) {
        $where[] = "i.category_id = ?";
        $params[] = $category_f;
        $types .= 'i';
    }
    if ($status_f !== '' && in_array($status_f, ['In Stock','Low Stock','Out of Stock'], true)) {
        $where[] = "i.status = ?";
        $params[] = $status_f;
        $types .= 's';
    }

    $where_sql = implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) c FROM inventory_items i WHERE $where_sql";
    $stmt = $conn->prepare($count_sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $total_pages = max(1, (int)ceil($total_rows / $per_page));

    $list_sql = "
        SELECT i.*, c.category_name, s.supplier_name,
               (i.current_stock * i.cost_per_unit) AS total_value
        FROM inventory_items i
        LEFT JOIN inventory_categories c ON i.category_id = c.category_id
        LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
        WHERE $where_sql
        ORDER BY $order_sql
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($list_sql);
    $bind_types  = $types . 'ii';
    $bind_params = array_merge($params, [$per_page, $offset]);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $ex) {
    if (class_exists('Logger')) Logger::error('Fetch items failed', ['error'=>$ex->getMessage()]);
}

/* ══════════════════════════════════════════
   URL BUILDER
   ══════════════════════════════════════════ */
function build_url($overrides = []) {
    $base = [
        'q'        => $_GET['q']        ?? '',
        'category' => $_GET['category'] ?? '',
        'status'   => $_GET['status']   ?? '',
        'sort'     => $_GET['sort']     ?? 'id_desc',
        'page'     => $_GET['page']     ?? 1,
    ];
    $merged = array_merge($base, $overrides);
    return 'inventory.php?' . http_build_query(array_filter($merged, function($v){
        return $v !== '' && $v !== null;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory — WashFlow</title>

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

.wf-nav-badge {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 24px;
    padding: 0 8px;
    font-size: 0.72rem;
    font-weight: 700;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.wf-nav-badge.alert {
    background: rgba(231, 76, 60, 0.15);
    color: #FF8B7E;
    border-color: rgba(231, 76, 60, 0.25);
}

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
.wf-stat-icon.icon-red    { background: #FDEDEC; color: #A8322D; }

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

.wf-item-id {
    font-weight: 700;
    color: var(--primary);
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.8rem;
}
.wf-item-name { font-weight: 600; color: var(--dark-blue); }
.wf-item-sub  { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; }
.wf-amount    { font-weight: 700; color: var(--dark-blue); }
.wf-date      { color: var(--text-muted); font-size: 0.8rem; white-space: nowrap; }

/* Stock bar */
.wf-stock-wrap { min-width: 110px; }
.wf-stock-numbers {
    display: flex;
    align-items: baseline;
    gap: 0.3rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dark-blue);
    margin-bottom: 0.3rem;
}
.wf-stock-numbers .unit {
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 600;
}
.wf-stock-bar {
    width: 100%;
    height: 5px;
    background: var(--border-soft);
    border-radius: 3px;
    overflow: hidden;
}
.wf-stock-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}
.wf-stock-bar-fill.in-stock    { background: var(--green); }
.wf-stock-bar-fill.low-stock   { background: var(--gold); }
.wf-stock-bar-fill.out-stock   { background: var(--red); }

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
.wf-badge-in-stock     { color: #1E7E45; background: #EAF7F0; }
.wf-badge-low-stock    { color: #946200; background: #FEF9E7; }
.wf-badge-out-of-stock { color: #A8322D; background: #FDEDEC; }

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
.wf-icon-btn.adjust:hover  { border-color: var(--green); color: var(--green); background: #EAF7F0; }
.wf-icon-btn.edit:hover    { border-color: var(--primary); color: var(--primary); }
.wf-icon-btn.delete:hover  { border-color: var(--red); color: var(--red); background: #FDEDEC; }

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

/* Transaction history */
.wf-tx-list { display: flex; flex-direction: column; gap: 0.5rem; }
.wf-tx-item {
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 0.75rem 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.wf-tx-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}
.wf-tx-icon.in       { background: #EAF7F0; color: #1E7E45; }
.wf-tx-icon.out      { background: #FDEDEC; color: #A8322D; }
.wf-tx-icon.adjust   { background: var(--light-blue-soft); color: var(--primary); }
.wf-tx-icon.waste    { background: #FEF9E7; color: #946200; }
.wf-tx-icon.usage    { background: #F4ECFB; color: #5B2E91; }

.wf-tx-body { flex: 1; min-width: 0; }
.wf-tx-title { font-size: 0.82rem; font-weight: 700; color: var(--dark-blue); }
.wf-tx-meta  { font-size: 0.72rem; color: var(--text-muted); font-weight: 500; margin-top: 0.15rem; }
.wf-tx-qty   { font-size: 0.85rem; font-weight: 700; white-space: nowrap; }
.wf-tx-qty.plus  { color: #1E7E45; }
.wf-tx-qty.minus { color: #A8322D; }

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
    max-height: 90vh;
    overflow-y: auto;
}
.wf-modal-overlay.open .wf-modal { transform: scale(1); }
.wf-modal.wide { max-width: 660px; }

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
.wf-modal-icon.success { background: #EAF7F0; color: var(--green); }
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

/* FORM */
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

/* Stock adjust segmented control */
.wf-segmented {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.4rem;
    margin-bottom: 1rem;
}
.wf-segment {
    padding: 0.6rem 0.4rem;
    border: 1px solid var(--border);
    background: white;
    border-radius: 9px;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    text-align: center;
    transition: all 0.15s;
    font-family: inherit;
}
.wf-segment:hover { border-color: var(--primary); color: var(--primary); }
.wf-segment.active {
    background: var(--dark-blue);
    color: white;
    border-color: var(--dark-blue);
}
.wf-segment i { display: block; font-size: 0.95rem; margin-bottom: 0.2rem; }

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
    .wf-mini-stats { grid-template-columns: 1fr; }
    .wf-segmented { grid-template-columns: repeat(2, 1fr); }
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
                <i class="fas fa-chart-pie"></i><span>Dashboard</span>
            </a>

            <div class="wf-nav-section-label">Monitoring</div>
            <a href="admin_bookings.php" class="wf-nav-item">
                <i class="fas fa-eye"></i><span>Bookings Monitor</span>
            </a>
            <a href="admin_payments.php" class="wf-nav-item">
                <i class="fas fa-receipt"></i><span>Payments Monitor</span>
            </a>
            <a href="admin_tracking.php" class="wf-nav-item">
                <i class="fas fa-route"></i><span>Tracking Monitor</span>
            </a>

            <div class="wf-nav-section-label">Operations</div>
            <a href="customer_management.php" class="wf-nav-item">
                <i class="fas fa-users"></i><span>Customers</span>
            </a>
            <a href="staff_management.php" class="wf-nav-item">
                <i class="fas fa-user-tie"></i><span>Staff</span>
            </a>
            <a href="inventory.php" class="wf-nav-item active">
                <i class="fas fa-boxes"></i><span>Inventory</span>
                <?php if ($stats['unread_notifs'] > 0): ?>
                <span class="wf-nav-badge alert"><?= (int)$stats['unread_notifs'] ?></span>
                <?php endif; ?>
            </a>

            <div class="wf-nav-section-label">Insights</div>
            <a href="reports.php" class="wf-nav-item">
                <i class="fas fa-chart-line"></i><span>Reports</span>
            </a>
            <a href="complaints.php" class="wf-nav-item">
                <i class="fas fa-headset"></i><span>Complaints</span>
            </a>
            <a href="feedback.php" class="wf-nav-item">
                <i class="fas fa-star"></i><span>Feedback</span>
            </a>
            <a href="system_logs.php" class="wf-nav-item">
                <i class="fas fa-history"></i><span>Activity Logs</span>
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
                <h1>Inventory <span class="accent">Management</span></h1>
                <p>Track supplies, stock levels, and transactions</p>
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
                    <div class="wf-stat-icon icon-blue"><i class="fas fa-boxes"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['total_items']) ?></div>
                    <div class="wf-stat-label">Total Items</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['in_stock']) ?></div>
                    <div class="wf-stat-label">In Stock</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-yellow"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value"><?= number_format($stats['low_stock'] + $stats['out_of_stock']) ?></div>
                    <div class="wf-stat-label">Needs Attention</div>
                </div>
            </div>
            <div class="wf-stat-card">
                <div class="wf-stat-top">
                    <div class="wf-stat-icon icon-purple"><i class="fas fa-coins"></i></div>
                </div>
                <div>
                    <div class="wf-stat-value">₱<?= number_format($stats['total_value'], 2) ?></div>
                    <div class="wf-stat-label">Total Stock Value</div>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form method="get" action="inventory.php" class="wf-filter-bar">
            <input type="hidden" name="page" value="1">

            <div class="wf-filter-group" style="flex: 1 1 240px;">
                <label>Search</label>
                <input type="text" name="q" class="wf-input" placeholder="ID, item name, code, location..." value="<?= e($search) ?>">
            </div>

            <div class="wf-filter-group" style="flex: 0 0 200px;">
                <label>Category</label>
                <select name="category" class="wf-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['category_id'] ?>" <?= $category_f === (int)$cat['category_id'] ? 'selected' : '' ?>>
                            <?= e($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 170px;">
                <label>Status</label>
                <select name="status" class="wf-select">
                    <option value="">All Status</option>
                    <option value="In Stock"     <?= $status_f === 'In Stock'     ? 'selected' : '' ?>>In Stock</option>
                    <option value="Low Stock"    <?= $status_f === 'Low Stock'    ? 'selected' : '' ?>>Low Stock</option>
                    <option value="Out of Stock" <?= $status_f === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>

            <div class="wf-filter-group" style="flex: 0 0 auto; flex-direction: row; gap: 0.5rem; align-items: flex-end; margin-left: auto;">
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="inventory.php" class="wf-btn wf-btn-outline">
                    <i class="fas fa-undo"></i> Reset
                </a>
                <button type="button" class="wf-btn wf-btn-yellow" onclick="document.getElementById('exportForm').submit();">
                    <i class="fas fa-download"></i> Export CSV
                </button>
                <button type="button" class="wf-btn wf-btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
        </form>

        <form method="post" id="exportForm" style="display:none;">
            <input type="hidden" name="action" value="export_csv">
        </form>

        <!-- TABLE -->
        <div class="wf-card">
            <div class="wf-card-body">
                <?php if (empty($items)): ?>
                    <div class="wf-empty">
                        <i class="fas fa-box-open"></i>
                        <p>No inventory items found</p>
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
                                        Item <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Category</th>
                                <th>
                                    <a href="<?= e(build_url(['sort' => $sort === 'stock_desc' ? 'stock_asc' : 'stock_desc', 'page' => 1])) ?>">
                                        Stock <i class="fas fa-sort"></i>
                                    </a>
                                </th>
                                <th>Cost/Value</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it):
                                $status_raw = $it['status'] ?? 'In Stock';
                                $status_key = 'in-stock';
                                if ($status_raw === 'Low Stock') $status_key = 'low-stock';
                                if ($status_raw === 'Out of Stock') $status_key = 'out-stock';

                                $cur = (float)$it['current_stock'];
                                $min = (float)$it['min_stock_level'];
                                $max = (float)$it['max_stock_level'];
                                $pct = ($max > 0) ? min(100, ($cur / $max) * 100) : 0;
                                if ($pct < 0) $pct = 0;

                                $badge_class = 'wf-badge-in-stock';
                                if ($status_raw === 'Low Stock')    $badge_class = 'wf-badge-low-stock';
                                if ($status_raw === 'Out of Stock') $badge_class = 'wf-badge-out-of-stock';
                            ?>
                            <tr>
                                <td><span class="wf-item-id">#<?= (int)$it['item_id'] ?></span></td>
                                <td>
                                    <div class="wf-item-name"><?= e($it['item_name']) ?></div>
                                    <?php if (!empty($it['item_code'])): ?>
                                    <div class="wf-item-sub">
                                        <i class="fas fa-barcode" style="font-size:0.65rem;"></i> <?= e($it['item_code']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="wf-item-sub" style="font-weight:600;color:var(--text-secondary);">
                                        <?= e($it['category_name'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="wf-stock-wrap">
                                        <div class="wf-stock-numbers">
                                            <?= number_format($cur, 2) ?> <span class="unit"><?= e($it['unit']) ?></span>
                                        </div>
                                        <div class="wf-stock-bar">
                                            <div class="wf-stock-bar-fill <?= e($status_key) ?>" style="width: <?= number_format($pct, 2, '.', '') ?>%;"></div>
                                        </div>
                                        <div class="wf-item-sub" style="margin-top:0.2rem;">min <?= number_format($min, 0) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="wf-amount">₱<?= number_format((float)$it['cost_per_unit'], 2) ?></div>
                                    <div class="wf-item-sub">₱<?= number_format((float)$it['total_value'], 2) ?> total</div>
                                </td>
                                <td>
                                    <span class="wf-badge <?= e($badge_class) ?>">
                                        <span class="wf-badge-dot"></span>
                                        <?= e($status_raw) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="wf-item-sub" style="font-weight:600;color:var(--text-secondary);">
                                        <?= e($it['location'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="wf-row-actions" style="justify-content: flex-end;">
                                        <button type="button" class="wf-icon-btn" title="View Details"
                                            onclick='openPanel(<?= json_encode([
                                                "id" => (int)$it["item_id"],
                                                "item_name" => $it["item_name"] ?? "",
                                                "item_code" => $it["item_code"] ?? "",
                                                "description" => $it["description"] ?? "",
                                                "category_name" => $it["category_name"] ?? "",
                                                "unit" => $it["unit"] ?? "",
                                                "current_stock" => (float)$it["current_stock"],
                                                "min_stock_level" => (float)$it["min_stock_level"],
                                                "max_stock_level" => (float)$it["max_stock_level"],
                                                "cost_per_unit" => (float)$it["cost_per_unit"],
                                                "total_value" => (float)$it["total_value"],
                                                "status" => $status_raw,
                                                "supplier_name" => $it["supplier_name"] ?? "",
                                                "location" => $it["location"] ?? "",
                                                "expiry_date" => $it["expiry_date"] ?? "",
                                                "last_restock_date" => $it["last_restock_date"] ?? "",
                                                "created_at" => $it["created_at"] ?? "",
                                                "updated_at" => $it["updated_at"] ?? "",
                                            ]) ?>)'>
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <button type="button" class="wf-icon-btn adjust" title="Adjust Stock"
                                            onclick='openAdjustModal(<?= json_encode([
                                                "id" => (int)$it["item_id"],
                                                "item_name" => $it["item_name"] ?? "",
                                                "unit" => $it["unit"] ?? "",
                                                "current_stock" => (float)$it["current_stock"],
                                                "cost_per_unit" => (float)$it["cost_per_unit"],
                                            ]) ?>)'>
                                            <i class="fas fa-sliders-h"></i>
                                        </button>

                                        <button type="button" class="wf-icon-btn edit" title="Edit"
                                            onclick='openEditModal(<?= json_encode([
                                                "id" => (int)$it["item_id"],
                                                "category_id" => (int)$it["category_id"],
                                                "item_name" => $it["item_name"] ?? "",
                                                "item_code" => $it["item_code"] ?? "",
                                                "description" => $it["description"] ?? "",
                                                "unit" => $it["unit"] ?? "pcs",
                                                "min_stock_level" => (float)$it["min_stock_level"],
                                                "max_stock_level" => (float)$it["max_stock_level"],
                                                "cost_per_unit" => (float)$it["cost_per_unit"],
                                                "supplier_id" => (int)($it["supplier_id"] ?? 0),
                                                "location" => $it["location"] ?? "",
                                                "expiry_date" => $it["expiry_date"] ?? "",
                                            ]) ?>)'>
                                            <i class="fas fa-pen"></i>
                                        </button>

                                        <button type="button" class="wf-icon-btn delete" title="Delete"
                                            onclick='openDeleteModal(<?= json_encode([
                                                "id" => (int)$it["item_id"],
                                                "name" => $it["item_name"] ?? "",
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
                        Showing <?= number_format(min($offset + 1, $total_rows)) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?> of <?= number_format($total_rows) ?> items
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

<!-- ══════════════════════════════════════════
     SIDE PANEL — Item Details
     ══════════════════════════════════════════ -->
<div class="wf-panel-overlay" id="panelOverlay" onclick="closePanel()"></div>
<aside class="wf-panel" id="itemPanel">
    <div class="wf-panel-header">
        <div class="wf-panel-title">
            <div class="wf-panel-title-icon"><i class="fas fa-box"></i></div>
            <div>
                <h3 id="panelTitle">Item Details</h3>
                <p id="panelSubtitle">Loading...</p>
            </div>
        </div>
        <button type="button" class="wf-panel-close" onclick="closePanel()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="wf-panel-body" id="panelBody"><!-- dynamic --></div>
    <div class="wf-panel-footer" id="panelFooter"><!-- dynamic --></div>
</aside>

<!-- ══════════════════════════════════════════
     MODAL — Add / Edit Item
     ══════════════════════════════════════════ -->
<div class="wf-modal-overlay" id="itemModal">
    <div class="wf-modal wide">
        <div class="wf-modal-header-custom">
            <div class="icon" id="modalIcon"><i class="fas fa-plus"></i></div>
            <div>
                <h3 id="modalTitle">Add New Item</h3>
                <p id="modalSubtitle">Fill in the item details below</p>
            </div>
        </div>

        <form method="post" id="itemForm">
            <input type="hidden" name="action" id="formAction" value="add_item">
            <input type="hidden" name="item_id" id="formId" value="">

            <div class="wf-form-grid">
                <div class="wf-form-group full">
                    <label>Item Name <span class="req">*</span></label>
                    <input type="text" name="item_name" id="f_item_name" class="wf-form-control" required>
                </div>
                <div class="wf-form-group">
                    <label>Category <span class="req">*</span></label>
                    <select name="category_id" id="f_category_id" class="wf-form-control" required>
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['category_id'] ?>"><?= e($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wf-form-group">
                    <label>Item Code</label>
                    <input type="text" name="item_code" id="f_item_code" class="wf-form-control" placeholder="e.g., DET-001">
                </div>
                <div class="wf-form-group">
                    <label>Unit <span class="req">*</span></label>
                    <input type="text" name="unit" id="f_unit" class="wf-form-control" required placeholder="pcs, liters, kg...">
                </div>
                <div class="wf-form-group">
                    <label>Cost per Unit (₱)</label>
                    <input type="number" name="cost_per_unit" id="f_cost_per_unit" class="wf-form-control" step="0.01" min="0" value="0">
                </div>
                <div class="wf-form-group" id="currentStockGroup">
                    <label>Current Stock</label>
                    <input type="number" name="current_stock" id="f_current_stock" class="wf-form-control" step="0.01" min="0" value="0">
                </div>
                <div class="wf-form-group">
                    <label>Min Stock Level</label>
                    <input type="number" name="min_stock_level" id="f_min_stock_level" class="wf-form-control" step="0.01" min="0" value="5">
                </div>
                <div class="wf-form-group">
                    <label>Max Stock Level</label>
                    <input type="number" name="max_stock_level" id="f_max_stock_level" class="wf-form-control" step="0.01" min="0" value="100">
                </div>
                <div class="wf-form-group">
                    <label>Supplier</label>
                    <select name="supplier_id" id="f_supplier_id" class="wf-form-control">
                        <option value="">— None —</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int)$sup['supplier_id'] ?>"><?= e($sup['supplier_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wf-form-group">
                    <label>Location</label>
                    <input type="text" name="location" id="f_location" class="wf-form-control" placeholder="e.g., Shelf A1">
                </div>
                <div class="wf-form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" id="f_expiry_date" class="wf-form-control">
                </div>
                <div class="wf-form-group full">
                    <label>Description</label>
                    <textarea name="description" id="f_description" class="wf-form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="wf-modal-actions" style="justify-content: flex-end;">
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeItemModal()">Cancel</button>
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-save"></i> Save Item
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL — Stock Adjustment
     ══════════════════════════════════════════ -->
<div class="wf-modal-overlay" id="adjustModal">
    <div class="wf-modal">
        <div class="wf-modal-header-custom">
            <div class="icon"><i class="fas fa-sliders-h"></i></div>
            <div>
                <h3>Adjust Stock</h3>
                <p id="adjustSubtitle">—</p>
            </div>
        </div>

        <form method="post" id="adjustForm">
            <input type="hidden" name="action" value="adjust_stock">
            <input type="hidden" name="item_id" id="adjust_item_id" value="">
            <input type="hidden" name="transaction_type" id="adjust_type" value="Stock In">

            <label style="font-size:0.75rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.5rem;display:block;">
                Transaction Type
            </label>
            <div class="wf-segmented">
                <button type="button" class="wf-segment active" data-type="Stock In" onclick="setAdjustType('Stock In', this)">
                    <i class="fas fa-arrow-down"></i> Stock In
                </button>
                <button type="button" class="wf-segment" data-type="Stock Out" onclick="setAdjustType('Stock Out', this)">
                    <i class="fas fa-arrow-up"></i> Stock Out
                </button>
                <button type="button" class="wf-segment" data-type="Adjustment" onclick="setAdjustType('Adjustment', this)">
                    <i class="fas fa-equals"></i> Set Value
                </button>
                <button type="button" class="wf-segment" data-type="Waste" onclick="setAdjustType('Waste', this)">
                    <i class="fas fa-trash-alt"></i> Waste
                </button>
            </div>

            <div class="wf-form-grid" style="grid-template-columns: 1fr;">
                <div class="wf-form-group">
                    <label id="qtyLabel">Quantity <span class="req">*</span></label>
                    <input type="number" name="quantity" id="adjust_qty" class="wf-form-control" step="0.01" min="0.01" required>
                    <div id="qtyHint" style="font-size:0.72rem;color:var(--text-muted);margin-top:0.2rem;">
                        Amount to add
                    </div>
                </div>
                <div class="wf-form-group">
                    <label>Unit Cost (₱) <span style="color:var(--text-muted);font-weight:500;text-transform:none;">(optional — default item cost)</span></label>
                    <input type="number" name="unit_cost" id="adjust_cost" class="wf-form-control" step="0.01" min="0">
                </div>
                <div class="wf-form-group">
                    <label>Supplier</label>
                    <select name="supplier_id" id="adjust_supplier" class="wf-form-control">
                        <option value="">— None —</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int)$sup['supplier_id'] ?>"><?= e($sup['supplier_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wf-form-group">
                    <label>Notes</label>
                    <textarea name="notes" id="adjust_notes" class="wf-form-control" rows="2" placeholder="Optional remarks..."></textarea>
                </div>
            </div>

            <div class="wf-modal-actions" style="justify-content: flex-end;">
                <button type="button" class="wf-btn wf-btn-outline" onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="wf-btn wf-btn-primary">
                    <i class="fas fa-check"></i> Apply Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL — Delete Confirmation
     ══════════════════════════════════════════ -->
<div class="wf-modal-overlay" id="deleteModal">
    <div class="wf-modal">
        <div class="wf-modal-icon warn">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Delete Item?</h3>
        <p id="deleteMessage">This action cannot be undone.</p>
        <div class="wf-modal-actions">
            <button type="button" class="wf-btn wf-btn-outline" onclick="closeDeleteModal()">Cancel</button>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="item_id" id="deleteId" value="">
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
/* ══════════════════════════════════════════
   TOASTS
   ══════════════════════════════════════════ */
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

/* ══════════════════════════════════════════
   SIDE PANEL
   ══════════════════════════════════════════ */
const panelOverlay = document.getElementById('panelOverlay');
const itemPanel    = document.getElementById('itemPanel');

function openPanel(data) {
    document.getElementById('panelTitle').textContent    = data.item_name;
    document.getElementById('panelSubtitle').textContent = 'Item #' + data.id + (data.item_code ? ' • ' + data.item_code : '');

    let html = '';

    /* Mini stats */
    html += `
    <div class="wf-detail-section">
        <div class="wf-mini-stats">
            <div class="wf-mini-stat">
                <div class="wf-mini-stat-value">${numberFmt(data.current_stock, 2)}</div>
                <div class="wf-mini-stat-label">${escapeHtml(data.unit)}</div>
            </div>
            <div class="wf-mini-stat">
                <div class="wf-mini-stat-value" style="font-size:1rem;">₱${numberFmt(data.cost_per_unit, 2)}</div>
                <div class="wf-mini-stat-label">Cost/Unit</div>
            </div>
            <div class="wf-mini-stat">
                <div class="wf-mini-stat-value" style="font-size:1rem;">₱${numberFmt(data.total_value, 2)}</div>
                <div class="wf-mini-stat-label">Total Value</div>
            </div>
        </div>
    </div>`;

    /* Status badge */
    let badge = 'wf-badge-in-stock';
    if (data.status === 'Low Stock')    badge = 'wf-badge-low-stock';
    if (data.status === 'Out of Stock') badge = 'wf-badge-out-of-stock';

    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-info-circle"></i> Status</div>
        <span class="wf-badge ${badge}" style="font-size:0.8rem;padding:0.5rem 0.9rem;">
            <span class="wf-badge-dot"></span> ${escapeHtml(data.status)}
        </span>
    </div>`;

    /* Item Info */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-box"></i> Item Information</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Category</div>
                <div class="wf-detail-value">${escapeHtml(data.category_name) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Item Code</div>
                <div class="wf-detail-value mono">${escapeHtml(data.item_code) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Unit</div>
                <div class="wf-detail-value">${escapeHtml(data.unit) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Location</div>
                <div class="wf-detail-value">${escapeHtml(data.location) || '—'}</div>
            </div>
            ${data.description ? `
            <div class="wf-detail-item full">
                <div class="wf-detail-label">Description</div>
                <div class="wf-detail-value muted">${escapeHtml(data.description)}</div>
            </div>` : ''}
        </div>
    </div>`;

    /* Stock Details */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-warehouse"></i> Stock Levels</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Current</div>
                <div class="wf-detail-value">${numberFmt(data.current_stock, 2)} ${escapeHtml(data.unit)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Min Level</div>
                <div class="wf-detail-value">${numberFmt(data.min_stock_level, 2)} ${escapeHtml(data.unit)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Max Level</div>
                <div class="wf-detail-value">${numberFmt(data.max_stock_level, 2)} ${escapeHtml(data.unit)}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Last Restock</div>
                <div class="wf-detail-value">${data.last_restock_date ? formatDate(data.last_restock_date) : '—'}</div>
            </div>
        </div>
    </div>`;

    /* Supplier */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-truck"></i> Supplier & Expiry</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Supplier</div>
                <div class="wf-detail-value">${escapeHtml(data.supplier_name) || '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Expiry Date</div>
                <div class="wf-detail-value">${data.expiry_date ? formatDate(data.expiry_date) : '—'}</div>
            </div>
        </div>
    </div>`;

    /* Timeline */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-clock"></i> Timeline</div>
        <div class="wf-detail-grid">
            <div class="wf-detail-item">
                <div class="wf-detail-label">Created</div>
                <div class="wf-detail-value">${data.created_at ? formatDate(data.created_at) : '—'}</div>
            </div>
            <div class="wf-detail-item">
                <div class="wf-detail-label">Last Updated</div>
                <div class="wf-detail-value">${data.updated_at ? formatDate(data.updated_at) : '—'}</div>
            </div>
        </div>
    </div>`;

    /* Transactions */
    html += `
    <div class="wf-detail-section">
        <div class="wf-detail-section-title"><i class="fas fa-history"></i> Recent Transactions</div>
        <div id="panelTxList" class="wf-tx-list">
            <div class="wf-tx-item">
                <div class="wf-tx-icon adjust"><i class="fas fa-spinner fa-spin"></i></div>
                <div class="wf-tx-body"><div class="wf-tx-title">Loading...</div></div>
            </div>
        </div>
    </div>`;

    document.getElementById('panelBody').innerHTML = html;

    /* Fetch transactions */
    fetch('inventory_transactions.php?item_id=' + encodeURIComponent(data.id))
        .then(r => r.ok ? r.json() : [])
        .then(rows => {
            const wrap = document.getElementById('panelTxList');
            if (!wrap) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                wrap.innerHTML = '<div style="font-size:0.82rem;color:var(--text-muted);">No transactions yet.</div>';
                return;
            }
            let out = '';
            rows.forEach(tx => {
                const t = tx.transaction_type || 'Adjustment';
                let cls = 'adjust';
                let sign = '';
                if (t === 'Stock In')  { cls = 'in';     sign = '+'; }
                if (t === 'Stock Out') { cls = 'out';    sign = '−'; }
                if (t === 'Waste')     { cls = 'waste';  sign = '−'; }
                if (t === 'Usage')     { cls = 'usage';  sign = '−'; }
                out += `
                <div class="wf-tx-item">
                    <div class="wf-tx-icon ${cls}"><i class="fas fa-${t === 'Stock In' ? 'arrow-down' : (t === 'Stock Out' ? 'arrow-up' : 'sliders-h')}"></i></div>
                    <div class="wf-tx-body">
                        <div class="wf-tx-title">${escapeHtml(t)}</div>
                        <div class="wf-tx-meta">${escapeHtml(tx.notes || '') || '—'} • ${formatDate(tx.transaction_date)}</div>
                    </div>
                    <div class="wf-tx-qty ${sign === '+' ? 'plus' : 'minus'}">
                        ${sign}${numberFmt(tx.quantity, 2)}
                    </div>
                </div>`;
            });
            wrap.innerHTML = out;
        })
        .catch(() => {
            const wrap = document.getElementById('panelTxList');
            if (wrap) wrap.innerHTML = '<div style="font-size:0.82rem;color:var(--text-muted);">Unable to load transactions.</div>';
        });

    /* Footer */
    document.getElementById('panelFooter').innerHTML = `
        <button type="button" class="wf-btn wf-btn-primary" onclick='closePanel(); openAdjustModal(${JSON.stringify({
            id: data.id, item_name: data.item_name, unit: data.unit,
            current_stock: data.current_stock, cost_per_unit: data.cost_per_unit
        })})'>
            <i class="fas fa-sliders-h"></i> Adjust Stock
        </button>
    `;

    panelOverlay.classList.add('open');
    itemPanel.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePanel() {
    panelOverlay.classList.remove('open');
    itemPanel.classList.remove('open');
    document.body.style.overflow = '';
}

/* ══════════════════════════════════════════
   ADD / EDIT MODAL
   ══════════════════════════════════════════ */
const itemModal = document.getElementById('itemModal');

function openAddModal() {
    document.getElementById('modalTitle').textContent    = 'Add New Item';
    document.getElementById('modalSubtitle').textContent = 'Fill in the item details below';
    document.getElementById('modalIcon').innerHTML       = '<i class="fas fa-plus"></i>';
    document.getElementById('formAction').value          = 'add_item';
    document.getElementById('formId').value              = '';
    document.getElementById('itemForm').reset();
    document.getElementById('currentStockGroup').style.display = 'flex';
    document.getElementById('f_current_stock').disabled = false;
    itemModal.classList.add('open');
}

function openEditModal(data) {
    document.getElementById('modalTitle').textContent    = 'Edit Item';
    document.getElementById('modalSubtitle').textContent = 'Update item #' + data.id;
    document.getElementById('modalIcon').innerHTML       = '<i class="fas fa-pen"></i>';
    document.getElementById('formAction').value          = 'update_item';
    document.getElementById('formId').value              = data.id;
    document.getElementById('f_item_name').value         = data.item_name || '';
    document.getElementById('f_category_id').value       = data.category_id || '';
    document.getElementById('f_item_code').value         = data.item_code || '';
    document.getElementById('f_unit').value              = data.unit || 'pcs';
    document.getElementById('f_cost_per_unit').value     = data.cost_per_unit || 0;
    document.getElementById('f_min_stock_level').value   = data.min_stock_level || 5;
    document.getElementById('f_max_stock_level').value   = data.max_stock_level || 100;
    document.getElementById('f_supplier_id').value       = data.supplier_id || '';
    document.getElementById('f_location').value          = data.location || '';
    document.getElementById('f_expiry_date').value       = data.expiry_date || '';
    document.getElementById('f_description').value       = data.description || '';
    document.getElementById('currentStockGroup').style.display = 'none';
    itemModal.classList.add('open');
}

function closeItemModal() {
    itemModal.classList.remove('open');
}

itemModal.addEventListener('click', function(e) {
    if (e.target === itemModal) closeItemModal();
});

/* ══════════════════════════════════════════
   ADJUST STOCK MODAL
   ══════════════════════════════════════════ */
const adjustModal = document.getElementById('adjustModal');

function openAdjustModal(data) {
    document.getElementById('adjust_item_id').value = data.id;
    document.getElementById('adjust_cost').value    = data.cost_per_unit || 0;
    document.getElementById('adjustSubtitle').textContent = data.item_name + ' • Current: ' + numberFmt(data.current_stock, 2) + ' ' + data.unit;

    setAdjustType('Stock In', document.querySelector('.wf-segment[data-type="Stock In"]'));

    document.getElementById('adjust_qty').value = '';
    document.getElementById('adjust_notes').value = '';
    document.getElementById('adjust_supplier').value = '';

    adjustModal.classList.add('open');
}

function setAdjustType(type, btn) {
    document.getElementById('adjust_type').value = type;
    document.querySelectorAll('.wf-segment').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const qtyLabel = document.getElementById('qtyLabel');
    const qtyHint  = document.getElementById('qtyHint');
    if (type === 'Stock In') {
        qtyLabel.innerHTML = 'Quantity to Add <span class="req">*</span>';
        qtyHint.textContent = 'Amount to add to current stock';
    } else if (type === 'Stock Out') {
        qtyLabel.innerHTML = 'Quantity to Remove <span class="req">*</span>';
        qtyHint.textContent = 'Amount to subtract from current stock';
    } else if (type === 'Adjustment') {
        qtyLabel.innerHTML = 'Set New Stock Value <span class="req">*</span>';
        qtyHint.textContent = 'Stock will be set to this exact value';
    } else if (type === 'Waste') {
        qtyLabel.innerHTML = 'Quantity Wasted <span class="req">*</span>';
        qtyHint.textContent = 'Amount to subtract (for damaged/spoiled items)';
    }
}

function closeAdjustModal() {
    adjustModal.classList.remove('open');
}

adjustModal.addEventListener('click', function(e) {
    if (e.target === adjustModal) closeAdjustModal();
});

/* ══════════════════════════════════════════
   DELETE MODAL
   ══════════════════════════════════════════ */
const deleteModal = document.getElementById('deleteModal');

function openDeleteModal(data) {
    document.getElementById('deleteId').value = data.id;
    document.getElementById('deleteMessage').innerHTML =
        'Are you sure you want to delete <strong>' + escapeHtml(data.name) + '</strong>?<br><span style="font-size:0.82rem;color:var(--text-muted);">This action cannot be undone.</span>';
    deleteModal.classList.add('open');
}

function closeDeleteModal() {
    deleteModal.classList.remove('open');
}

deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) closeDeleteModal();
});

/* ══════════════════════════════════════════
   KEYBOARD
   ══════════════════════════════════════════ */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeItemModal();
        closeAdjustModal();
        closeDeleteModal();
        closePanel();
    }
});

/* ══════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════ */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function numberFmt(n, decimals) {
    const v = parseFloat(n);
    if (isNaN(v)) return '0.00';
    return v.toLocaleString('en-PH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(String(dateStr).replace(' ', 'T'));
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
</script>

</body>
</html>