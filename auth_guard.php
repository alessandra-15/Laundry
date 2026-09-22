<?php
/**
 * auth_guard.php
 * WashFlow — Role-Based Access Control
 *
 * Usage sa simula ng bawat protected page:
 *   require_once 'auth_guard.php';
 *   $admin_id = require_role(ROLE_ADMIN);
 *   // or
 *   $staff_id = require_role(ROLE_STAFF);
 *   // or
 *   $customer_id = require_role(ROLE_CUSTOMER);
 */

require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    // Use existing session config if available
    if (file_exists(__DIR__ . '/session_config.php')) {
        require_once __DIR__ . '/session_config.php';
    } else {
        session_start();
    }
}

/**
 * Enforce that the current user has the given role.
 * Auto-redirects kung:
 *   - Hindi naka-login → sa tamang login page
 *   - Ibang role → sa tamang dashboard
 *
 * @param string $role  ROLE_ADMIN | ROLE_STAFF | ROLE_CUSTOMER
 * @return int          User ID ng naka-login
 */
function require_role($role) {
    $session_key = match ($role) {
        ROLE_ADMIN    => SESSION_ADMIN_ID,
        ROLE_STAFF    => SESSION_STAFF_ID,
        ROLE_CUSTOMER => SESSION_CUSTOMER_ID,
        default       => null,
    };

    if ($session_key === null) {
        die('Invalid role specified.');
    }

    // 1) Kung naka-login na sa tamang role, OK
    if (!empty($_SESSION[$session_key])) {
        return (int)$_SESSION[$session_key];
    }

    // 2) Kung naka-login sa IBANG role, i-redirect sa tamang dashboard
    if (!empty($_SESSION[SESSION_ADMIN_ID])) {
        header('Location: ' . DASHBOARD_ADMIN);
        exit();
    }
    if (!empty($_SESSION[SESSION_STAFF_ID])) {
        header('Location: ' . DASHBOARD_STAFF);
        exit();
    }
    if (!empty($_SESSION[SESSION_CUSTOMER_ID])) {
        header('Location: ' . DASHBOARD_CUSTOMER);
        exit();
    }

    // 3) Hindi naka-login — sa tamang login page
    switch ($role) {
        case ROLE_ADMIN:    header('Location: ' . LOGIN_ADMIN);    break;
        case ROLE_STAFF:    header('Location: ' . LOGIN_STAFF);    break;
        case ROLE_CUSTOMER: header('Location: ' . LOGIN_CUSTOMER); break;
    }
    exit();
}

/**
 * Check kung ang current user ay may specific role (boolean lang, no redirect).
 */
function has_role($role) {
    $session_key = match ($role) {
        ROLE_ADMIN    => SESSION_ADMIN_ID,
        ROLE_STAFF    => SESSION_STAFF_ID,
        ROLE_CUSTOMER => SESSION_CUSTOMER_ID,
        default       => null,
    };
    return $session_key && !empty($_SESSION[$session_key]);
}

/**
 * Get current user's role (or null if not logged in).
 */
function current_role() {
    if (!empty($_SESSION[SESSION_ADMIN_ID]))    return ROLE_ADMIN;
    if (!empty($_SESSION[SESSION_STAFF_ID]))    return ROLE_STAFF;
    if (!empty($_SESSION[SESSION_CUSTOMER_ID])) return ROLE_CUSTOMER;
    return null;
}