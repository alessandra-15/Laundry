<?php
/**
 * constants.php
 * WashFlow — Role & System Constants
 * I-include ito sa lahat ng protected pages.
 */

// Prevent multiple inclusion issues
if (defined('WF_CONSTANTS_LOADED')) return;
define('WF_CONSTANTS_LOADED', true);

/* ══════════════════════════════════════════
   ROLES
   ══════════════════════════════════════════ */
define('ROLE_ADMIN',    'admin');
define('ROLE_STAFF',    'staff');
define('ROLE_CUSTOMER', 'customer');

/* ══════════════════════════════════════════
   SESSION KEYS
   ══════════════════════════════════════════ */
define('SESSION_ADMIN_ID',    'admin_id');
define('SESSION_STAFF_ID',    'staff_id');
define('SESSION_CUSTOMER_ID', 'customer_id');

/* ══════════════════════════════════════════
   LOGIN REDIRECTS
   ══════════════════════════════════════════ */
define('LOGIN_ADMIN',    'admin_login.php');
define('LOGIN_STAFF',    'staff_login.php');
define('LOGIN_CUSTOMER', 'customer_login.php');

/* ══════════════════════════════════════════
   DASHBOARD REDIRECTS
   ══════════════════════════════════════════ */
define('DASHBOARD_ADMIN',    'dashboard.php');       // existing admin dashboard
define('DASHBOARD_STAFF',    'staff_dashboard.php');
define('DASHBOARD_CUSTOMER', 'userdashboard.php');   // existing customer dashboard

/* ══════════════════════════════════════════
   BOOKING STATUS
   ══════════════════════════════════════════ */
define('BOOKING_STATUSES', ['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled']);