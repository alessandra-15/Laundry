<?php
/**
 * Secure Session Configuration
 * WashFlow — SEPARATE sessions per role
 *
 * ADMIN, STAFF, CUSTOMER each have their own isolated session cookie.
 * They can all be logged in simultaneously on different tabs without session leakage.
 *
 * @file session_config.php
 */

/* ══════════════════════════════════════════
   STEP 1: DETECT ROLE FROM PAGE / REQUEST
   ══════════════════════════════════════════ */

if (!function_exists('wf_detect_role')) {
    function wf_detect_role(): string {
        $page = basename($_SERVER['PHP_SELF'] ?? '');

        /* ── Explicit lists MUNA (priority) ──
           Ang explicit lists ang may pinakamataas na priority.
           Kapag nasa listahan ang page, doon siya mapupunta
           kahit ano pa ang prefix ng filename. */

        $admin_pages = [
            'dashboard.php',
            'customer_management.php',
            'system_logs.php',
            'admin_reports.php',
            'admin_profile.php',
            'admin_booking.php',
            'complaints.php',
            'feedback.php',
            'reports.php',
            'inventory.php',
            'staff_management.php',
        ];

        $staff_pages = [
            'booking_management.php',
            'staff_payments.php',
            'staff_complaints.php',
            'staff_feedback.php',
        ];

        $customer_pages = [
            'login.php',
            'register.php',
            'userdashboard.php',
            'my_bookings.php',
            'booking_details.php',
            'book_service.php',
            'payments.php',
            'notifications.php',
            'profile.php',
        ];

        /* Check explicit lists FIRST */
        if (in_array($page, $admin_pages, true))    return 'admin';
        if (in_array($page, $staff_pages, true))    return 'staff';
        if (in_array($page, $customer_pages, true)) return 'customer';

        /* ── Fallback: prefix-based ──
           Ginagamit lang ito kung hindi nakalista sa itaas. */
        if (str_starts_with($page, 'admin_')) return 'admin';
        if (str_starts_with($page, 'staff_')) return 'staff';

        return 'customer';
    }
}

/* Allow page to explicitly override role via ?_role=admin|staff|customer */
$wf_role_hint = null;
if (isset($_GET['_role']) && in_array($_GET['_role'], ['admin', 'staff', 'customer'], true)) {
    $wf_role_hint = $_GET['_role'];
} elseif (isset($_POST['_role']) && in_array($_POST['_role'], ['admin', 'staff', 'customer'], true)) {
    $wf_role_hint = $_POST['_role'];
}

$wf_current_role = $wf_role_hint ?? wf_detect_role();

/* ══════════════════════════════════════════
   STEP 2: START THE ROLE-SPECIFIC SESSION
   ══════════════════════════════════════════ */

$wf_session_names = [
    'admin'    => 'LAUNDRY_ADMIN',
    'staff'    => 'LAUNDRY_STAFF',
    'customer' => 'LAUNDRY_CUSTOMER',
];

$wf_session_name = $wf_session_names[$wf_current_role] ?? 'LAUNDRY_CUSTOMER';

/* Close active session if name does not match current target role */
if (session_status() === PHP_SESSION_ACTIVE && session_name() !== $wf_session_name) {
    session_write_close();
}

/* Start clean isolated session */
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 7200);   // 2 hours
    ini_set('session.cookie_lifetime', 0);

    session_name($wf_session_name);
    session_start();
}

/* Tag session role */
$_SESSION['__role'] = $wf_current_role;

/* ══════════════════════════════════════════
   STEP 3: SESSION TIMEOUT & SECURITY CHECKS
   ══════════════════════════════════════════ */

$session_timeout = 7200; // 2 Hours
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    $expired_role = $_SESSION['user_type'] ?? null;

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    session_start();
    $_SESSION['__role'] = $wf_current_role;

    $public_pages = ['login.php', 'register.php', 'admin_login.php', 'staff_login.php', 'homepage.php', 'index.php'];
    if (!in_array(basename($_SERVER['PHP_SELF']), $public_pages, true)) {
        if ($expired_role === 'admin') {
            header('Location: admin_login.php?timeout=1');
        } elseif ($expired_role === 'staff') {
            header('Location: staff_login.php?timeout=1');
        } else {
            header('Location: login.php?timeout=1');
        }
        exit();
    }
}
$_SESSION['last_activity'] = time();

/* Regenerate session ID every 30 min */
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

/* User Agent validation */
$ua_hash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
if (!isset($_SESSION['ua_hash'])) {
    $_SESSION['ua_hash'] = $ua_hash;
} elseif ($_SESSION['ua_hash'] !== $ua_hash) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['__role'] = $wf_current_role;
    $_SESSION['security_error'] = 'Session security violation detected. Please login again.';
}

/* ══════════════════════════════════════════
   STEP 4: ROLE MANAGEMENT HELPERS
   ══════════════════════════════════════════ */

if (!function_exists('current_user')) {
    function current_user(): ?array {
        static $cached = null;
        static $cached_at = 0;

        if ($cached !== null && (microtime(true) - $cached_at) < 1) {
            return $cached;
        }

        if (!empty($_SESSION['user_type']) && !empty($_SESSION['user_id'])) {
            $cached = [
                'id'    => (int)$_SESSION['user_id'],
                'type'  => $_SESSION['user_type'],
                'name'  => $_SESSION['user_name']  ?? 'User',
                'role'  => $_SESSION['user_role']  ?? ucfirst($_SESSION['user_type']),
                'extra' => $_SESSION['user_extra'] ?? [],
            ];
            $cached_at = microtime(true);
            return $cached;
        }

        if (!empty($_SESSION['customer_id'])) {
            $cached = [
                'id'    => (int)$_SESSION['customer_id'],
                'type'  => 'customer',
                'name'  => trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')),
                'role'  => 'Customer',
                'extra' => ['email' => $_SESSION['email'] ?? ''],
            ];
            $cached_at = microtime(true);
            return $cached;
        }

        if (!empty($_SESSION['admin_id']) || !empty($_SESSION['Admin_ID'])) {
            $id = (int)($_SESSION['admin_id'] ?? $_SESSION['Admin_ID']);
            $cached = [
                'id'    => $id,
                'type'  => 'admin',
                'name'  => $_SESSION['admin_username'] ?? $_SESSION['username'] ?? 'Admin',
                'role'  => 'Administrator',
                'extra' => [],
            ];
            $cached_at = microtime(true);
            return $cached;
        }

        if (!empty($_SESSION['staff_id'])) {
            $cached = [
                'id'    => (int)$_SESSION['staff_id'],
                'type'  => 'staff',
                'name'  => $_SESSION['staff_full_name'] ?? $_SESSION['staff_username'] ?? 'Staff',
                'role'  => $_SESSION['staff_role'] ?? 'Staff',
                'extra' => ['username' => $_SESSION['staff_username'] ?? ''],
            ];
            $cached_at = microtime(true);
            return $cached;
        }

        $cached = null;
        $cached_at = microtime(true);
        return $cached;
    }
}

if (!function_exists('clear_login_session')) {
    function clear_login_session(): void {
        $keys = [
            'customer_id', 'first_name', 'last_name', 'email',
            'admin_id', 'Admin_ID', 'admin_username', 'username',
            'is_admin', 'login_success',
            'staff_id', 'staff_username', 'staff_full_name', 'staff_role', 'is_staff',
            'user_id', 'user_type', 'user_name', 'user_role', 'user_extra',
            'login_time', 'activity_id',
        ];
        foreach ($keys as $k) {
            unset($_SESSION[$k]);
        }
    }
}

if (!function_exists('set_login_session')) {
    function set_login_session(string $type, int $id, string $name, string $role = '', array $extra = []): void {
        clear_login_session();

        $_SESSION['user_id']    = $id;
        $_SESSION['user_type']  = $type;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_role']  = $role ?: ucfirst($type);
        $_SESSION['user_extra'] = $extra;
        $_SESSION['login_time'] = time();

        if ($type === 'admin') {
            $_SESSION['admin_id']       = $id;
            $_SESSION['Admin_ID']       = $id;
            $_SESSION['admin_username'] = $name;
            $_SESSION['username']       = $name;
            $_SESSION['is_admin']       = true;
            $_SESSION['login_success']  = true;
        } elseif ($type === 'staff') {
            $_SESSION['staff_id']        = $id;
            $_SESSION['staff_username']  = $extra['username'] ?? $name;
            $_SESSION['staff_full_name'] = $name;
            $_SESSION['staff_role']      = $role;
            $_SESSION['is_staff']        = true;
        } elseif ($type === 'customer') {
            $_SESSION['customer_id'] = $id;
            if (!empty($extra['first_name'])) $_SESSION['first_name'] = $extra['first_name'];
            if (!empty($extra['last_name']))  $_SESSION['last_name']  = $extra['last_name'];
            if (!empty($extra['email']))      $_SESSION['email']      = $extra['email'];
        }
    }
}

/* ══════════════════════════════════════════
   STEP 5: URL DIRECTORY HELPERS
   ══════════════════════════════════════════ */

if (!function_exists('login_url_for')) {
    function login_url_for(string $type): string {
        return match ($type) {
            'admin'    => 'admin_login.php',
            'staff'    => 'staff_login.php',
            'customer' => 'login.php',
            default    => 'login.php',
        };
    }
}

if (!function_exists('dashboard_url_for')) {
    function dashboard_url_for(string $type): string {
        return match ($type) {
            'admin'    => 'dashboard.php',
            'staff'    => 'staff_dashboard.php',
            'customer' => 'userdashboard.php',
            default    => 'login.php',
        };
    }
}

/* ══════════════════════════════════════════
   STEP 6: REQUIRE LOGIN ACCESS CONTROLLERS
   ══════════════════════════════════════════ */

if (!function_exists('require_customer_login')) {
    function require_customer_login(): void {
        $u = current_user();
        if (!$u || $u['type'] !== 'customer') {
            if ($u) {
                header('Location: ' . dashboard_url_for($u['type']));
                exit();
            }
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            header('Location: login.php?redirect=' . $redirect);
            exit();
        }
    }
}

if (!function_exists('require_admin_login')) {
    function require_admin_login(): void {
        $u = current_user();
        if (!$u || $u['type'] !== 'admin') {
            if ($u) {
                header('Location: ' . dashboard_url_for($u['type']));
                exit();
            }
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            header('Location: admin_login.php?redirect=' . $redirect);
            exit();
        }
    }
}

if (!function_exists('require_staff_login')) {
    function require_staff_login(): void {
        $u = current_user();
        if (!$u || $u['type'] !== 'staff') {
            if ($u) {
                header('Location: ' . dashboard_url_for($u['type']));
                exit();
            }
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            header('Location: staff_login.php?redirect=' . $redirect);
            exit();
        }
    }
}

if (!function_exists('require_staff_area')) {
    function require_staff_area(): void {
        $u = current_user();
        if (!$u || !in_array($u['type'], ['admin', 'staff'], true)) {
            if ($u && $u['type'] === 'customer') {
                header('Location: userdashboard.php');
                exit();
            }
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
            header('Location: staff_login.php?redirect=' . $redirect);
            exit();
        }
    }
}

/* ══════════════════════════════════════════
   STEP 7: HELPER CHECKS
   ══════════════════════════════════════════ */

if (!function_exists('is_customer_logged_in')) {
    function is_customer_logged_in(): bool {
        $u = current_user();
        return $u && $u['type'] === 'customer';
    }
}

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool {
        $u = current_user();
        return $u && $u['type'] === 'admin';
    }
}

if (!function_exists('is_staff_logged_in')) {
    function is_staff_logged_in(): bool {
        $u = current_user();
        return $u && $u['type'] === 'staff';
    }
}

if (!function_exists('is_admin_or_staff_logged_in')) {
    function is_admin_or_staff_logged_in(): bool {
        $u = current_user();
        return $u && in_array($u['type'], ['admin', 'staff'], true);
    }
}
?>