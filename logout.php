<?php
/**
 * logout.php
 * WashFlow — Universal Logout (role-specific)
 *
 * 🆕 Only clears the session of the current role.
 *    Other roles (admin, staff, customer) on other tabs remain logged in.
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

/* Detect current role */
$current_role = $wf_current_role ?? wf_detect_role();

/* Determine the user in THIS session only */
$cu = current_user();
$role = $cu['type'] ?? null;
$user_id = $cu['id'] ?? null;

$redirect_url = 'login.php';
if ($role === 'admin')         $redirect_url = 'admin_login.php';
elseif ($role === 'staff')     $redirect_url = 'staff_login.php';
elseif ($role === 'customer')  $redirect_url = 'login.php';

/* Log the logout */
if ($role && $user_id) {
    Logger::login(ucfirst($role) . ' logged out', [
        'role'    => $role,
        'user_id' => $user_id,
        'ip'      => get_client_ip(),
    ]);
}

/* Update user_activity for customer */
if ($role === 'customer' && !empty($_SESSION['activity_id'])) {
    try {
        $upd = $conn->prepare("UPDATE user_activity SET logout_time = NOW(), status = 'Offline' WHERE id = ?");
        if ($upd) {
            $upd->bind_param('i', $_SESSION['activity_id']);
            $upd->execute();
            $upd->close();
        }
    } catch (Exception $e) {
        Logger::error('Failed to update user_activity on logout', ['error' => $e->getMessage()]);
    }
}

/* Log to system_logs for admin/staff */
if (($role === 'admin' || $role === 'staff') && $user_id) {
    try {
        $action = ucfirst($role) . ' Logout';
        $desc   = ucfirst($role) . " #{$user_id} logged out from IP: " . get_client_ip();
        $admin_id = ($role === 'admin') ? $user_id : 0;
        $log = $conn->prepare("INSERT INTO system_logs (admin_id, action, description) VALUES (?, ?, ?)");
        if ($log) {
            $log->bind_param('iss', $admin_id, $action, $desc);
            $log->execute();
            $log->close();
        }
    } catch (Exception $e) {
        Logger::error('Failed to insert system log on logout', ['error' => $e->getMessage()]);
    }
}

/* 🆕 Clear ONLY this role's session */
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),          // ← ONLY this session name
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

/* Clear remember-me cookie for this role only */
$remember_cookies = [
    'admin'    => 'wf_remember_admin',
    'staff'    => 'wf_remember_staff',
    'customer' => 'wf_remember_customer',
];
if ($role && isset($remember_cookies[$role]) && isset($_COOKIE[$remember_cookies[$role]])) {
    setcookie($remember_cookies[$role], '', time() - 3600, '/');
}

header('Location: ' . $redirect_url);
exit();
?>