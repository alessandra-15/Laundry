<?php
/**
 * profile.php
 * WashFlow — Customer Profile
 * Aligned with existing customer sidebar (same links + icons).
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';
require_once 'csrf_helper.php';

require_customer_login();

$customer_id = (int)($_SESSION['customer_id'] ?? 0);

/* Resolve customer_id from email if session missing */
if ($customer_id <= 0) {
    $email_lookup = $_SESSION['email'] ?? '';
    if ($email_lookup !== '') {
        $stmt = $conn->prepare("SELECT Customer_ID FROM customer_info WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email_lookup);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $customer_id = (int)$row['Customer_ID'];
                $_SESSION['customer_id'] = $customer_id;
            }
            $stmt->close();
        }
    }
}

if ($customer_id <= 0) {
    die('Session error: invalid customer ID. Please <a href="logout.php">logout</a> and login again.');
}

$flash_success = null;
$flash_error   = null;

/* ══════════════════════════════════════════
   FETCH CURRENT PROFILE
   ══════════════════════════════════════════ */
$customer = null;
try {
    $stmt = $conn->prepare("
        SELECT Customer_ID, first_name, last_name, email, contact_number, Address,
               account_type, register_date, discount_rate, student_id_path,
               created_at, updated_at
        FROM customer_info
        WHERE Customer_ID = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    Logger::error('Profile fetch failed', ['error' => $e->getMessage()]);
}

if (!$customer) {
    die('Customer record not found. Please contact support.');
}

/* ══════════════════════════════════════════
   HANDLE: UPDATE PROFILE INFO
   ══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash_error = 'Security token expired. Please refresh.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $contact    = trim($_POST['contact_number'] ?? '');
        $address    = trim($_POST['address']    ?? '');

        /* Validate */
        if ($first_name === '' || $last_name === '') {
            $flash_error = 'First name and last name are required.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash_error = 'Please enter a valid email address.';
        } elseif ($contact !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $contact)) {
            $flash_error = 'Please enter a valid contact number.';
        } else {
            /* Check email uniqueness (if changed) */
            $email_taken = false;
            if ($email !== '' && $email !== ($customer['email'] ?? '')) {
                $chk = $conn->prepare("SELECT Customer_ID FROM customer_info WHERE email = ? AND Customer_ID <> ? LIMIT 1");
                $chk->bind_param('si', $email, $customer_id);
                $chk->execute();
                $email_taken = (bool)$chk->get_result()->fetch_assoc();
                $chk->close();
            }

            if ($email_taken) {
                $flash_error = 'That email is already used by another account.';
            } else {
                try {
                    $stmt = $conn->prepare("
                        UPDATE customer_info
                        SET first_name = ?, last_name = ?, email = ?, contact_number = ?, Address = ?
                        WHERE Customer_ID = ?
                    ");
                    $stmt->bind_param('sssssi',
                        $first_name, $last_name, $email, $contact, $address, $customer_id
                    );
                    $ok = $stmt->execute();
                    $stmt->close();

                    if ($ok) {
                        /* Refresh session display */
                        $_SESSION['first_name'] = $first_name;
                        $_SESSION['last_name']  = $last_name;
                        $_SESSION['email']      = $email;

                        Logger::info('Customer updated profile', ['customer_id' => $customer_id]);
                        $flash_success = 'Profile updated successfully.';

                        /* Refresh local $customer */
                        $customer['first_name']     = $first_name;
                        $customer['last_name']      = $last_name;
                        $customer['email']          = $email;
                        $customer['contact_number'] = $contact;
                        $customer['Address']        = $address;
                    } else {
                        $flash_error = 'Could not update profile. Please try again.';
                    }
                } catch (Exception $e) {
                    Logger::error('Profile update failed', ['error' => $e->getMessage()]);
                    $flash_error = 'Could not update profile. Please try again.';
                }
            }
        }
    }
}

/* ══════════════════════════════════════════
   HANDLE: CHANGE PASSWORD
   ══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash_error = 'Security token expired. Please refresh.';
    } else {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $flash_error = 'Please fill in all password fields.';
        } elseif (strlen($new) < 8) {
            $flash_error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $flash_error = 'New password and confirmation do not match.';
        } else {
            /* Fetch current hash */
            $stmt = $conn->prepare("SELECT password FROM customer_info WHERE Customer_ID = ? LIMIT 1");
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $hash = $row['password'] ?? '';

            if (!password_verify($current, $hash)) {
                $flash_error = 'Current password is incorrect.';
            } else {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE customer_info SET password = ? WHERE Customer_ID = ?");
                $stmt->bind_param('si', $new_hash, $customer_id);
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    Logger::info('Customer changed password', ['customer_id' => $customer_id]);
                    $flash_success = 'Password changed successfully.';
                } else {
                    $flash_error = 'Could not change password. Please try again.';
                }
            }
        }
    }
}

/* ══════════════════════════════════════════
   HANDLE: STUDENT VERIFICATION UPLOAD
   ══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_student_id') {

    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash_error = 'Security token expired. Please refresh.';
    } elseif (!isset($_FILES['student_id']) || $_FILES['student_id']['error'] !== UPLOAD_ERR_OK) {
        $flash_error = 'Please select a valid student ID image.';
    } else {
        $file = $_FILES['student_id'];

        if ($file['size'] > 5 * 1024 * 1024) {
            $flash_error = 'File is too large. Maximum is 5MB.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'application/pdf' => 'pdf',
            ];

            if (!isset($allowed[$mime])) {
                $flash_error = 'Invalid file type. Only JPG, PNG, or PDF allowed.';
            } else {
                $ext         = $allowed[$mime];
                $newFileName = 'student_' . $customer_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetDir   = __DIR__ . '/uploads/student_ids/';

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
                    $rel_path = 'uploads/student_ids/' . $newFileName;

                    try {
                        $stmt = $conn->prepare("
                            UPDATE customer_info
                            SET account_type = 'student',
                                student_id_path = ?
                            WHERE Customer_ID = ?
                        ");
                        $stmt->bind_param('si', $rel_path, $customer_id);
                        $ok = $stmt->execute();
                        $stmt->close();

                        if ($ok) {
                            Logger::info('Customer uploaded student ID', ['customer_id' => $customer_id]);
                            $flash_success = 'Student ID uploaded! Your account will be verified soon.';

                            $customer['account_type']    = 'student';
                            $customer['student_id_path'] = $rel_path;
                        } else {
                            @unlink($targetPath);
                            $flash_error = 'Could not save student ID. Please try again.';
                        }
                    } catch (Exception $e) {
                        @unlink($targetPath);
                        Logger::error('Student ID upload failed', ['error' => $e->getMessage()]);
                        $flash_error = 'Could not save student ID. Please try again.';
                    }
                }
            }
        }
    }
}

/* ══════════════════════════════════════════
   DERIVED DISPLAY VALUES
   ══════════════════════════════════════════ */
$firstName = $customer['first_name'] ?? '';
$lastName  = $customer['last_name']  ?? '';
$fullName  = trim($firstName . ' ' . $lastName) ?: 'Customer';
$initial   = strtoupper(substr($firstName ?: 'U', 0, 1));
$email     = $customer['email']          ?? '';
$contact   = $customer['contact_number'] ?? '';
$address   = $customer['Address']        ?? '';
$account_type = $customer['account_type'] ?? 'regular';
$is_student   = $account_type === 'student';
$has_student_id = !empty($customer['student_id_path']);

$member_since = !empty($customer['created_at'])
    ? date('F j, Y', strtotime($customer['created_at']))
    : (!empty($customer['register_date'])
        ? date('F j, Y', strtotime($customer['register_date']))
        : '—');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — WashFlow</title>

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

/* LAYOUT */
.wf-layout { display: grid; grid-template-columns: 280px 1fr; height: 100vh; overflow: hidden; }

/* SIDEBAR (same as payments.php) */
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

/* ALERTS */
.wf-alert {
    display: flex; align-items: flex-start; gap: 0.7rem;
    padding: 0.9rem 1.15rem; border-radius: 12px;
    margin-bottom: 1.25rem; font-size: 0.85rem;
    animation: slideDown 0.3s ease-out;
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

/* PROFILE HERO */
.wf-profile-hero {
    background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary) 100%);
    border-radius: 18px;
    padding: 2rem 2.25rem;
    color: white;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 1.75rem;
    flex-wrap: wrap;
}
.wf-profile-hero::before {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(255, 217, 61, 0.08);
}
.wf-profile-hero::after {
    content: '';
    position: absolute;
    right: 40px; bottom: -80px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(168, 232, 249, 0.06);
}
.wf-avatar-large {
    width: 90px; height: 90px;
    border-radius: 22px;
    background: linear-gradient(135deg, var(--yellow) 0%, var(--yellow-soft) 100%);
    color: var(--dark-blue-deep);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 10px 30px rgba(255, 217, 61, 0.25);
    position: relative; z-index: 1;
}
.wf-hero-info { flex: 1; min-width: 200px; position: relative; z-index: 1; }
.wf-hero-info h2 {
    font-size: 1.6rem; font-weight: 800;
    margin: 0 0 0.35rem; color: white;
    letter-spacing: -0.02em;
}
.wf-hero-info p {
    font-size: 0.88rem; margin: 0;
    color: rgba(255, 255, 255, 0.7);
    display: flex; align-items: center; gap: 0.4rem;
    flex-wrap: wrap;
}
.wf-hero-info p i { color: var(--yellow); }

.wf-hero-badges {
    display: flex; gap: 0.5rem; flex-wrap: wrap;
    margin-top: 0.7rem;
    position: relative; z-index: 1;
}
.wf-hero-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.12);
    font-size: 0.72rem; font-weight: 700;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(6px);
}
.wf-hero-badge.gold { background: rgba(255, 217, 61, 0.18); border-color: rgba(255, 217, 61, 0.35); }
.wf-hero-badge.gold i { color: var(--yellow); }

/* GRID */
.wf-profile-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 1.25rem;
    align-items: flex-start;
}
@media (max-width: 1100px) {
    .wf-profile-grid { grid-template-columns: 1fr; }
}

/* CARD */
.wf-card {
    background: white; border-radius: 14px;
    border: 1px solid var(--border); overflow: hidden;
    margin-bottom: 1.25rem;
}
.wf-card:last-child { margin-bottom: 0; }
.wf-card-head {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
}
.wf-card-title { display: flex; align-items: center; gap: 0.8rem; }
.wf-card-title-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--light-blue-soft); color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.wf-card-title-icon.gold   { background: var(--yellow-soft); color: var(--yellow-dark); }
.wf-card-title-icon.green  { background: #EAF7F0; color: #1E7E45; }
.wf-card-title-icon.red    { background: #FDEDEC; color: #A8322D; }
.wf-card-title h3 { font-size: 1rem; font-weight: 700; color: var(--dark-blue); margin: 0; }
.wf-card-title p { font-size: 0.75rem; color: var(--text-muted); margin: 0.1rem 0 0; font-weight: 500; }
.wf-card-body { padding: 1.5rem; }

/* FORM */
.wf-form-row {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 700px) { .wf-form-row { grid-template-columns: 1fr; } }

.wf-form-group { margin-bottom: 1rem; }
.wf-form-group:last-child { margin-bottom: 0; }
.wf-label {
    display: block;
    font-size: 0.78rem; font-weight: 700;
    color: var(--dark-blue);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.wf-label .req { color: var(--red); }

.wf-input, .wf-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid var(--border);
    border-radius: 11px;
    font-size: 0.875rem;
    font-family: inherit;
    color: var(--text-primary);
    background: white;
    transition: all 0.2s;
    outline: none;
}
.wf-input:focus, .wf-select:focus {
    border-color: var(--primary-mid);
    box-shadow: 0 0 0 4px rgba(0, 118, 168, 0.1);
}
.wf-input:disabled { background: #F7FAFC; color: var(--text-muted); cursor: not-allowed; }
.wf-hint { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.35rem; display: block; }

.wf-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.75rem 1.4rem;
    border-radius: 10px;
    font-size: 0.85rem; font-weight: 700;
    cursor: pointer; transition: all 0.2s;
    border: none; font-family: inherit;
}
.wf-btn-primary { background: var(--primary); color: white; }
.wf-btn-primary:hover { background: var(--primary-mid); transform: translateY(-1px); color: white; }
.wf-btn-ghost {
    background: white; color: var(--text-secondary);
    border: 1.5px solid var(--border);
}
.wf-btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
.wf-btn-green {
    background: linear-gradient(135deg, var(--green) 0%, #52BE80 100%);
    color: white;
}
.wf-btn-green:hover { transform: translateY(-1px); color: white; }
.wf-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.wf-form-actions {
    display: flex; gap: 0.6rem; flex-wrap: wrap;
    margin-top: 1.4rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--border-soft);
}

/* INFO LIST */
.wf-info-list {
    display: flex; flex-direction: column;
    gap: 0.85rem;
}
.wf-info-item {
    display: flex; align-items: flex-start; gap: 0.9rem;
    padding: 0.85rem 1rem;
    background: #FAFCFE;
    border: 1px solid var(--border-soft);
    border-radius: 11px;
    transition: all 0.2s;
}
.wf-info-item:hover { background: var(--light-blue-pale); border-color: var(--light-blue); }
.wf-info-icon {
    width: 36px; height: 36px; border-radius: 9px;
    background: var(--light-blue-soft); color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.wf-info-label {
    font-size: 0.7rem; font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.06em;
    margin-bottom: 0.15rem;
}
.wf-info-value {
    font-size: 0.88rem; font-weight: 600;
    color: var(--dark-blue); word-break: break-word;
}
.wf-info-value.muted { color: var(--text-secondary); font-weight: 500; }

/* STUDENT STATUS */
.wf-student-status {
    background: linear-gradient(135deg, #FFF9DB 0%, #FFFCF0 100%);
    border: 1.5px dashed var(--yellow);
    border-radius: 12px;
    padding: 1rem 1.15rem;
    margin-bottom: 1rem;
}
.wf-student-status h4 {
    font-size: 0.85rem; font-weight: 800;
    color: var(--yellow-dark); margin: 0 0 0.4rem;
    display: flex; align-items: center; gap: 0.4rem;
}
.wf-student-status p { font-size: 0.78rem; color: #7A5B00; margin: 0; line-height: 1.55; }

.wf-student-verified {
    background: linear-gradient(135deg, #EAF7F0 0%, #F5FCF8 100%);
    border: 1.5px dashed var(--green);
    border-radius: 12px;
    padding: 1rem 1.15rem;
    margin-bottom: 1rem;
}
.wf-student-verified h4 {
    font-size: 0.85rem; font-weight: 800;
    color: #1E7E45; margin: 0 0 0.4rem;
    display: flex; align-items: center; gap: 0.4rem;
}
.wf-student-verified p { font-size: 0.78rem; color: #14532d; margin: 0; line-height: 1.55; }

.wf-file {
    width: 100%; padding: 0.6rem;
    border: 1.5px dashed var(--border);
    border-radius: 11px;
    font-size: 0.82rem; font-family: inherit;
    cursor: pointer; background: white;
    transition: all 0.2s;
}
.wf-file:hover { border-color: var(--primary); background: var(--light-blue-pale); }

/* BADGES */
.wf-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.3rem 0.65rem;
    border-radius: 7px;
    font-size: 0.7rem; font-weight: 700;
    white-space: nowrap;
}
.wf-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

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
    .wf-profile-hero { padding: 1.5rem; }
    .wf-avatar-large { width: 70px; height: 70px; font-size: 2rem; }
    .wf-hero-info h2 { font-size: 1.3rem; }
}
</style>
</head>
<body>

<button class="wf-sidebar-toggle" id="wfSidebarToggle"><i class="fas fa-bars"></i></button>
<div class="wf-sidebar-overlay" id="wfSidebarOverlay"></div>

<div class="wf-layout">

    <!-- SIDEBAR (aligned with existing customer nav) -->
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
            <a href="payments.php" class="wf-nav-item"><i class="fas fa-credit-card"></i><span>Payments</span></a>
            <div class="wf-nav-section-label">Support</div>
            <a href="notifications.php" class="wf-nav-item"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="my_feedback.php" class="wf-nav-item"><i class="fas fa-star"></i><span>Feedback</span></a>
            <a href="my_complaints.php" class="wf-nav-item"><i class="fas fa-headset"></i><span>Complaints</span></a>
            <div class="wf-nav-section-label">Account</div>
            <a href="profile.php" class="wf-nav-item active"><i class="fas fa-user"></i><span>Profile</span></a>
        </nav>

        <div class="wf-sidebar-footer">
            <div class="wf-user-card">
                <div class="wf-user-avatar"><?= htmlspecialchars($initial) ?></div>
                <div class="wf-user-info">
                    <div class="wf-user-name"><?= htmlspecialchars($fullName) ?></div>
                    <div class="wf-user-role">Customer</div>
                </div>
            </div>
            <a href="logout.php" class="wf-btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="wf-main">

        <div class="wf-page-head">
            <div>
                <h1><i class="fas fa-user-circle" style="color:var(--primary);"></i> My Profile</h1>
                <p>Manage your personal information and account settings</p>
            </div>
        </div>

        <?php if ($flash_success): ?>
            <div class="wf-alert wf-alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= htmlspecialchars($flash_success) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flash_error): ?>
            <div class="wf-alert wf-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($flash_error) ?></div>
            </div>
        <?php endif; ?>

        <!-- HERO -->
        <div class="wf-profile-hero">
            <div class="wf-avatar-large"><?= htmlspecialchars($initial) ?></div>
            <div class="wf-hero-info">
                <h2><?= htmlspecialchars($fullName) ?></h2>
                <p>
                    <i class="fas fa-envelope"></i>
                    <?= htmlspecialchars($email ?: 'No email set') ?>
                    <?php if ($contact): ?>
                        <span style="opacity:0.4;">•</span>
                        <i class="fas fa-phone"></i>
                        <?= htmlspecialchars($contact) ?>
                    <?php endif; ?>
                </p>
                <div class="wf-hero-badges">
                    <span class="wf-hero-badge gold">
                        <i class="fas fa-crown"></i> <?= $is_student ? 'Student' : 'Regular' ?> Member
                    </span>
                    <span class="wf-hero-badge">
                        <i class="fas fa-calendar"></i> Since <?= htmlspecialchars($member_since) ?>
                    </span>
                    <?php if ($is_student && $has_student_id): ?>
                        <span class="wf-hero-badge">
                            <i class="fas fa-id-card"></i> ID Uploaded
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="wf-profile-grid">

            <!-- LEFT: MAIN FORMS -->
            <div>

                <!-- PERSONAL INFO -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon"><i class="fas fa-user-edit"></i></div>
                            <div>
                                <h3>Personal Information</h3>
                                <p>Update your contact details</p>
                            </div>
                        </div>
                    </div>

                    <div class="wf-card-body">
                        <form method="POST" action="profile.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="wf-form-row">
                                <div class="wf-form-group">
                                    <label class="wf-label">First Name <span class="req">*</span></label>
                                    <input type="text" name="first_name" class="wf-input"
                                           value="<?= htmlspecialchars($firstName) ?>"
                                           maxlength="100" required>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Last Name <span class="req">*</span></label>
                                    <input type="text" name="last_name" class="wf-input"
                                           value="<?= htmlspecialchars($lastName) ?>"
                                           maxlength="100" required>
                                </div>
                            </div>

                            <div class="wf-form-row">
                                <div class="wf-form-group">
                                    <label class="wf-label">Email Address</label>
                                    <input type="email" name="email" class="wf-input"
                                           value="<?= htmlspecialchars($email) ?>"
                                           maxlength="150"
                                           placeholder="you@example.com">
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Contact Number</label>
                                    <input type="text" name="contact_number" class="wf-input"
                                           value="<?= htmlspecialchars($contact) ?>"
                                           maxlength="20"
                                           placeholder="09XX XXX XXXX">
                                </div>
                            </div>

                            <div class="wf-form-group">
                                <label class="wf-label">Complete Address</label>
                                <input type="text" name="address" class="wf-input"
                                       value="<?= htmlspecialchars($address) ?>"
                                       maxlength="255"
                                       placeholder="House/Street, Barangay, City, Province">
                            </div>

                            <div class="wf-form-actions">
                                <button type="submit" class="wf-btn wf-btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- CHANGE PASSWORD -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon gold"><i class="fas fa-lock"></i></div>
                            <div>
                                <h3>Change Password</h3>
                                <p>Keep your account secure</p>
                            </div>
                        </div>
                    </div>

                    <div class="wf-card-body">
                        <form method="POST" action="profile.php" id="passwordForm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="wf-form-group">
                                <label class="wf-label">Current Password <span class="req">*</span></label>
                                <input type="password" name="current_password" class="wf-input"
                                       required autocomplete="current-password">
                            </div>

                            <div class="wf-form-row">
                                <div class="wf-form-group">
                                    <label class="wf-label">New Password <span class="req">*</span></label>
                                    <input type="password" name="new_password" class="wf-input"
                                           required minlength="8" autocomplete="new-password">
                                    <span class="wf-hint">Minimum 8 characters.</span>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-label">Confirm New Password <span class="req">*</span></label>
                                    <input type="password" name="confirm_password" class="wf-input"
                                           required minlength="8" autocomplete="new-password">
                                </div>
                            </div>

                            <div class="wf-form-actions">
                                <button type="submit" class="wf-btn wf-btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- RIGHT: ACCOUNT INFO + STUDENT -->
            <div>

                <!-- ACCOUNT INFO -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon green"><i class="fas fa-id-badge"></i></div>
                            <div>
                                <h3>Account Info</h3>
                                <p>Quick overview</p>
                            </div>
                        </div>
                    </div>

                    <div class="wf-card-body">
                        <div class="wf-info-list">
                            <div class="wf-info-item">
                                <div class="wf-info-icon"><i class="fas fa-hashtag"></i></div>
                                <div>
                                    <div class="wf-info-label">Customer ID</div>
                                    <div class="wf-info-value">#<?= (int)$customer['Customer_ID'] ?></div>
                                </div>
                            </div>

                            <div class="wf-info-item">
                                <div class="wf-info-icon"><i class="fas fa-at"></i></div>
                                <div>
                                    <div class="wf-info-label">Username / Email</div>
                                    <div class="wf-info-value muted">
                                        <?= htmlspecialchars($email ?: '—') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="wf-info-item">
                                <div class="wf-info-icon"><i class="fas fa-user-tag"></i></div>
                                <div>
                                    <div class="wf-info-label">Account Type</div>
                                    <div class="wf-info-value">
                                        <span class="wf-badge"
                                              style="color:<?= $is_student ? '#8A6400' : '#00537A' ?>;
                                                     background:<?= $is_student ? '#FFF9DB' : '#EBF5FB' ?>;">
                                            <span class="wf-badge-dot"></span>
                                            <?= $is_student ? 'Student' : 'Regular' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="wf-info-item">
                                <div class="wf-info-icon"><i class="fas fa-percent"></i></div>
                                <div>
                                    <div class="wf-info-label">Discount Rate</div>
                                    <div class="wf-info-value muted">
                                        <?= number_format((float)($customer['discount_rate'] ?? 0), 2) ?>%
                                    </div>
                                </div>
                            </div>

                            <div class="wf-info-item">
                                <div class="wf-info-icon"><i class="fas fa-calendar-check"></i></div>
                                <div>
                                    <div class="wf-info-label">Member Since</div>
                                    <div class="wf-info-value muted"><?= htmlspecialchars($member_since) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STUDENT VERIFICATION -->
                <div class="wf-card">
                    <div class="wf-card-head">
                        <div class="wf-card-title">
                            <div class="wf-card-title-icon gold"><i class="fas fa-graduation-cap"></i></div>
                            <div>
                                <h3>Student Verification</h3>
                                <p>Get student discounts</p>
                            </div>
                        </div>
                    </div>

                    <div class="wf-card-body">
                        <?php if ($is_student && $has_student_id): ?>
                            <div class="wf-student-verified">
                                <h4><i class="fas fa-check-circle"></i> Student Verified</h4>
                                <p>
                                    Verified ka na bilang student. Enjoy your discount sa lahat ng bookings!
                                </p>
                                <div style="margin-top:0.75rem;">
                                    <a href="<?= htmlspecialchars($customer['student_id_path']) ?>"
                                       target="_blank" class="wf-btn wf-btn-ghost"
                                       style="font-size:0.78rem;padding:0.5rem 0.9rem;">
                                        <i class="fas fa-eye"></i> View Uploaded ID
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="wf-student-status">
                                <h4><i class="fas fa-info-circle"></i> Not yet verified</h4>
                                <p>
                                    Are you a student? Upload your valid school ID to get a student discount
                                    on your laundry bookings.
                                </p>
                            </div>

                            <form method="POST" action="profile.php" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="upload_student_id">

                                <div class="wf-form-group">
                                    <label class="wf-label">Upload Student ID</label>
                                    <input type="file" name="student_id" class="wf-file"
                                           accept=".jpg,.jpeg,.png,.pdf" required>
                                    <span class="wf-hint">
                                        Accepted: JPG, PNG, PDF • Max 5MB
                                    </span>
                                </div>

                                <button type="submit" class="wf-btn wf-btn-green"
                                        style="width:100%;justify-content:center;">
                                    <i class="fas fa-upload"></i> Upload & Verify
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>

    </main>
</div>

<script>
(function () {
    const toggle  = document.getElementById('wfSidebarToggle');
    const sidebar = document.getElementById('wfSidebar');
    const overlay = document.getElementById('wfSidebarOverlay');
    if (!toggle) return;
    toggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        overlay.classList.add('open');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    });
})();

/* Password match check */
(function () {
    const form = document.getElementById('passwordForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        const np = form.querySelector('input[name="new_password"]').value;
        const cp = form.querySelector('input[name="confirm_password"]').value;
        if (np !== cp) {
            e.preventDefault();
            alert('New password and confirmation do not match.');
        }
    });
})();
</script>

</body>
</html>