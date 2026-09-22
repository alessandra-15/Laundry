<?php
/**
 * login.php
 * WashFlow — Customer Login (Card Split Screen)
 * Direct URL access only. No admin/staff links.
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'csrf_helper.php';
require_once 'rate_limiter.php';
require_once 'logger.php';
require_once 'functions.php';

/* 🆕 Check kung may naka-login na — using unified helper */
$cu = current_user();
if ($cu && $cu['type'] === 'customer') {
    header('Location: userdashboard.php');
    exit();
}

$error_message = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        Logger::security('CSRF mismatch on login', ['ip' => get_client_ip()]);
        $error_message = 'Security token expired. Please refresh and try again.';
    } else {
        $rate_key = 'customer_login_' . get_client_ip();
        $rate_check = check_rate_limit($rate_key, 5, 900);

        if ($rate_check !== true) {
            Logger::security('Customer login rate limit exceeded', ['ip' => get_client_ip()]);
            $error_message = $rate_check;
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $email_value = $email;

            if ($email === '' || $password === '') {
                $error_message = 'Please fill in all fields.';
            } elseif (!validate_email($email)) {
                $error_message = 'Invalid email format.';
            } else {
                $stmt = $conn->prepare("SELECT * FROM customer_info WHERE email = ? LIMIT 1");

                if (!$stmt) {
                    Logger::error('Prepare failed on login', ['error' => $conn->error]);
                    $error_message = 'System error. Please try again.';
                } else {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($user = $res->fetch_assoc()) {
                        $dbPass = $user['password'] ?? '';
                        $authenticated = false;

                        if (!empty($dbPass) && password_verify($password, $dbPass)) {
                            $authenticated = true;
                            if (password_needs_rehash($dbPass, PASSWORD_DEFAULT)) {
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                $up = $conn->prepare("UPDATE customer_info SET password = ? WHERE Customer_ID = ?");
                                if ($up) {
                                    $up->bind_param('si', $newHash, $user['Customer_ID']);
                                    $up->execute();
                                    $up->close();
                                }
                            }
                        } elseif ($password === $dbPass) {
                            $authenticated = true;
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $up = $conn->prepare("UPDATE customer_info SET password = ? WHERE Customer_ID = ?");
                            if ($up) {
                                $up->bind_param('si', $newHash, $user['Customer_ID']);
                                $up->execute();
                                $up->close();
                                Logger::info('Customer password upgraded', ['customer_id' => $user['Customer_ID']]);
                            }
                        }

                        if ($authenticated) {
                            clear_rate_limit($rate_key);
                            session_regenerate_id(true);

                            /* 🆕 Clear ALL previous login data + set new */
                            set_login_session(
                                'customer',
                                (int)$user['Customer_ID'],
                                trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                                'Customer',
                                [
                                    'first_name' => $user['first_name'] ?? '',
                                    'last_name'  => $user['last_name']  ?? '',
                                    'email'      => $user['email']      ?? '',
                                ]
                            );

                            /* Log user activity */
                            $status = 'Online';
                            if ($ins = $conn->prepare("INSERT INTO user_activity (customer_id, login_time, status) VALUES (?, NOW(), ?)")) {
                                $ins->bind_param('is', $user['Customer_ID'], $status);
                                $ins->execute();
                                $_SESSION['activity_id'] = $conn->insert_id;
                                $ins->close();
                            }

                            Logger::login('Customer logged in', [
                                'customer_id' => $user['Customer_ID'],
                                'email' => $email,
                                'ip' => get_client_ip()
                            ]);

                            $redirect = $_GET['redirect'] ?? 'userdashboard.php';
                            if (!preg_match('/^[a-zA-Z0-9_\-\/]+\.php(\?.*)?$/', $redirect)) {
                                $redirect = 'userdashboard.php';
                            }

                            header("Location: " . $redirect);
                            exit();
                        } else {
                            Logger::security('Failed login - wrong password', ['email' => $email, 'ip' => get_client_ip()]);
                            $error_message = 'Invalid email or password. Please try again.';
                        }
                    } else {
                        Logger::security('Failed login - user not found', ['email' => $email, 'ip' => get_client_ip()]);
                        $error_message = 'Invalid email or password. Please try again.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — WashFlow</title>

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

            --bg-light:        #E5EEF5;
            --bg-white:        #FFFFFF;

            --text-primary:    #0A2540;
            --text-secondary:  #5A7184;
            --text-muted:      #94A9B8;

            --border:          #C9DCE8;
            --border-light:    #DCEAF3;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            min-height: 100vh;
            background: linear-gradient(135deg, #E5EEF5 0%, #D4E5F0 30%, #C9DCE8 60%, #B8D2E5 100%);
            position: relative;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body::before {
            content: '';
            position: fixed;
            top: -15%; right: -10%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(255, 217, 61, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: floatBg 12s ease-in-out infinite;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -20%; left: -10%;
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(0, 118, 168, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: floatBg 15s ease-in-out infinite reverse;
        }
        @keyframes floatBg {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
        }

        h1, h2, h3, h4, h5 { font-weight: 700; letter-spacing: -0.02em; }
        a { text-decoration: none; }

        .wf-page {
            position: relative;
            z-index: 5;
            width: 100%;
            max-width: 1080px;
        }

        .wf-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding: 0 0.5rem;
        }

        .wf-logo { display: flex; align-items: center; gap: 10px; }
        .wf-logo-mark { width: 40px; height: 40px; flex-shrink: 0; }
        .wf-logo-mark svg {
            width: 100%; height: 100%;
            filter: drop-shadow(0 4px 8px rgba(4, 38, 64, 0.25));
        }
        .wf-logo-text { display: flex; flex-direction: column; line-height: 1; }
        .wf-logo-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--dark-blue);
            letter-spacing: -0.04em;
            line-height: 1;
        }
        .wf-logo-name .flow { color: var(--primary-mid); }
        .wf-logo-tagline {
            font-size: 0.55rem;
            font-weight: 500;
            color: var(--text-secondary);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-top: 3px;
        }

        .wf-btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            color: var(--dark-blue);
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.55rem 1.1rem;
            border: 1.5px solid rgba(201, 220, 232, 0.8);
            border-radius: 50px;
            transition: all 0.25s;
            box-shadow: 0 4px 12px rgba(10, 37, 64, 0.06);
        }
        .wf-btn-back:hover {
            color: var(--primary);
            border-color: var(--primary-mid);
            background: white;
            transform: translateX(-3px);
            box-shadow: 0 8px 20px rgba(10, 37, 64, 0.12);
        }

        .wf-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow:
                0 2px 8px rgba(10, 37, 64, 0.04),
                0 24px 60px rgba(10, 37, 64, 0.15);
            display: grid;
            grid-template-columns: 42% 58%;
            min-height: 560px;
            animation: cardIn 0.7s cubic-bezier(0.165, 0.84, 0.44, 1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .wf-card-left {
            position: relative;
            padding: 2.5rem 2rem;
            background:
                linear-gradient(150deg, rgba(2, 25, 45, 0.95) 0%, rgba(4, 38, 64, 0.93) 50%, rgba(6, 52, 82, 0.9) 100%),
                url('https://images.unsplash.com/photo-1545173168-9f1947eebb7f?w=800') center/cover;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .wf-card-left::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255, 217, 61, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: pulseGlow 7s ease-in-out infinite;
        }
        .wf-card-left::after {
            content: '';
            position: absolute;
            bottom: -100px; left: -100px;
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(168, 232, 249, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: pulseGlow 9s ease-in-out infinite reverse;
        }
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        .wf-card-left-content {
            position: relative;
            z-index: 2;
        }

        .wf-card-left h1 {
            color: white;
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin-bottom: 1rem;
        }
        .wf-card-left h1 .accent {
            color: var(--yellow);
            position: relative;
            display: inline-block;
        }
        .wf-card-left h1 .accent::after {
            content: '';
            position: absolute;
            bottom: 4px; left: 0; right: 0;
            height: 3px;
            background: rgba(255, 217, 61, 0.4);
            border-radius: 2px;
            z-index: -1;
        }

        .wf-card-left .lead {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.9rem;
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .wf-benefits {
            list-style: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
        }
        .wf-benefits li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .wf-benefit-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, rgba(255, 217, 61, 0.2) 0%, rgba(255, 217, 61, 0.06) 100%);
            border: 1px solid rgba(255, 217, 61, 0.3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--yellow);
            font-size: 0.8rem;
            box-shadow: 0 4px 10px rgba(255, 217, 61, 0.15);
        }
        .wf-benefit-text strong {
            display: block;
            color: white;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 1px;
            letter-spacing: -0.01em;
        }
        .wf-benefit-text span {
            color: rgba(168, 232, 249, 0.7);
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .wf-card-left-footer {
            position: relative;
            z-index: 2;
            font-size: 0.7rem;
            color: rgba(168, 232, 249, 0.5);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(168, 232, 249, 0.12);
        }
        .wf-card-left-footer i { color: var(--yellow); opacity: 0.7; }

        .wf-card-right {
            position: relative;
            padding: 2.5rem 2.75rem;
            background: linear-gradient(180deg, #F2FAFD 0%, #EAF4FA 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        .wf-card-right::-webkit-scrollbar { width: 6px; }
        .wf-card-right::-webkit-scrollbar-track { background: transparent; }
        .wf-card-right::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        .wf-form-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .wf-form-header { margin-bottom: 1.75rem; }
        .wf-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--primary);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 0.65rem;
            padding: 0.3rem 0.8rem;
            background: white;
            border: 1px solid rgba(0, 118, 168, 0.15);
            border-radius: 50px;
            box-shadow: 0 2px 6px rgba(10, 37, 64, 0.04);
        }
        .wf-form-header h2 {
            color: var(--dark-blue);
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-bottom: 0.35rem;
        }
        .wf-form-header p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0;
        }

        .wf-form-group { margin-bottom: 1.1rem; }
        .wf-form-label {
            display: block;
            color: var(--dark-blue);
            font-weight: 600;
            margin-bottom: 0.4rem;
            font-size: 0.82rem;
        }

        .wf-input-wrap { position: relative; }
        .wf-input-icon {
            position: absolute;
            left: 0.95rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 2;
            transition: color 0.25s;
        }
        .wf-form-control {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 11px;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--text-primary);
            background: white;
            transition: all 0.25s;
        }
        .wf-form-control::placeholder { color: var(--text-muted); }
        .wf-form-control:focus {
            border-color: var(--primary-mid);
            box-shadow: 0 0 0 4px rgba(0, 118, 168, 0.12);
            outline: none;
        }
        .wf-input-wrap:focus-within .wf-input-icon { color: var(--primary); }

        .wf-password-toggle {
            position: absolute;
            right: 0.95rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            z-index: 2;
            transition: color 0.25s;
            padding: 4px;
            font-size: 0.85rem;
        }
        .wf-password-toggle:hover { color: var(--primary); }

        .wf-form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.82rem;
        }
        .wf-remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            cursor: pointer;
        }
        .wf-remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .wf-forgot {
            color: var(--primary);
            font-weight: 600;
            transition: color 0.25s;
        }
        .wf-forgot:hover { color: var(--primary-mid); }

        .wf-btn-primary {
            width: 100%;
            background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
            color: var(--dark-blue-deep);
            border: none;
            font-weight: 700;
            font-size: 0.925rem;
            padding: 0.95rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 6px 16px rgba(255, 217, 61, 0.4);
            cursor: pointer;
            font-family: inherit;
        }
        .wf-btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(255, 217, 61, 0.55);
        }
        .wf-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

        .wf-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.5rem 0;
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        .wf-divider::before,
        .wf-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-light);
        }

        .wf-auth-footer {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .wf-auth-footer a {
            color: var(--primary);
            font-weight: 700;
            transition: color 0.25s;
        }
        .wf-auth-footer a:hover { color: var(--primary-mid); }

        .wf-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            padding: 0.8rem 1rem;
            border-radius: 11px;
            margin-bottom: 1.1rem;
            font-size: 0.82rem;
            animation: shake 0.4s ease-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }
        .wf-alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #dc3545;
            color: #7f1d1d;
        }
        .wf-alert-danger i { color: #dc3545; font-size: 1rem; margin-top: 2px; flex-shrink: 0; }

        .wf-alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 4px solid #16a34a;
            color: #14532d;
        }
        .wf-alert-success i { color: #16a34a; font-size: 1rem; margin-top: 2px; flex-shrink: 0; }

        .wf-alert-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-left: 4px solid #f59e0b;
            color: #78350f;
        }
        .wf-alert-warning i { color: #f59e0b; font-size: 1rem; margin-top: 2px; flex-shrink: 0; }

        @media (max-width: 900px) {
            body { padding: 1.25rem 0.75rem; align-items: flex-start; }
            .wf-card {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .wf-card-left {
                padding: 2rem 1.75rem;
                min-height: auto;
            }
            .wf-card-left h1 { font-size: 1.5rem; }
            .wf-card-left .lead { font-size: 0.85rem; margin-bottom: 1.5rem; }
            .wf-benefits {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            .wf-card-left-footer { margin-top: 1.5rem; }
            .wf-card-right { padding: 2rem 1.75rem; }
        }

        @media (max-width: 576px) {
            .wf-topbar { margin-bottom: 0.75rem; padding: 0; }
            .wf-logo-name { font-size: 1.1rem; }
            .wf-logo-mark { width: 34px; height: 34px; }
            .wf-logo-tagline { display: none; }
            .wf-btn-back { font-size: 0.75rem; padding: 0.5rem 0.9rem; }
            .wf-card { border-radius: 20px; }
            .wf-card-left { padding: 1.5rem 1.25rem; }
            .wf-card-left h1 { font-size: 1.3rem; }
            .wf-benefits { grid-template-columns: 1fr; gap: 0.6rem; }
            .wf-benefit-icon { width: 30px; height: 30px; font-size: 0.75rem; }
            .wf-card-right { padding: 1.75rem 1.25rem; }
            .wf-form-header h2 { font-size: 1.35rem; }
        }
    </style>
</head>
<body>

    <div class="wf-page">

        <div class="wf-topbar">
            <a href="homepage.php" class="wf-logo">
                <div class="wf-logo-mark">
                    <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="wfLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#0076A8"/>
                                <stop offset="100%" stop-color="#005A85"/>
                            </linearGradient>
                            <linearGradient id="wfWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#FFD93D"/>
                                <stop offset="100%" stop-color="#A8E8F9"/>
                            </linearGradient>
                        </defs>
                        <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#wfLogoGrad)"/>
                        <path d="M14 24 L20 42 L26 30 L32 42 L38 24" stroke="url(#wfWaveGrad)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <circle cx="44" cy="24" r="2.5" fill="#FFD93D" opacity="0.9"/>
                        <circle cx="48" cy="32" r="1.8" fill="#FFD93D" opacity="0.7"/>
                        <circle cx="44" cy="40" r="1.2" fill="#FFD93D" opacity="0.5"/>
                        <path d="M14 48 Q22 44 32 48 T50 48" stroke="#A8E8F9" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.6"/>
                    </svg>
                </div>
                <div class="wf-logo-text">
                    <span class="wf-logo-name">Wash<span class="flow">Flow</span></span>
                    <span class="wf-logo-tagline">Laundry System</span>
                </div>
            </a>

            <a href="homepage.php" class="wf-btn-back">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <div class="wf-card">

            <aside class="wf-card-left">
                <div class="wf-card-left-content">
                    <h1>Welcome <span class="accent">back</span> to WashFlow.</h1>
                    <p class="lead">
                        Sign in to access your account, manage your bookings, and track your
                        laundry orders in real time.
                    </p>

                    <ul class="wf-benefits">
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Manage Bookings</strong>
                                <span>View and update your orders</span>
                            </div>
                        </li>
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-truck"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Track Deliveries</strong>
                                <span>Real-time status updates</span>
                            </div>
                        </li>
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-receipt"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Payment History</strong>
                                <span>View all your transactions</span>
                            </div>
                        </li>
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-star"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Loyalty Rewards</strong>
                                <span>Earn perks on every order</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="wf-card-left-footer">
                    <i class="fas fa-lock"></i>
                    Your data is encrypted &amp; secured
                </div>
            </aside>

            <main class="wf-card-right">
                <div class="wf-form-container">

                    <div class="wf-form-header">
                        <span class="wf-eyebrow">
                            <i class="fas fa-sign-in-alt"></i> Welcome Back
                        </span>
                        <h2>Sign in to your account</h2>
                        <p>Enter your credentials to continue.</p>
                    </div>

                    <?php if ($error_message): ?>
                    <div class="wf-alert wf-alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?= e($error_message) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['timeout'])): ?>
                    <div class="wf-alert wf-alert-warning">
                        <i class="fas fa-clock"></i>
                        <div>Your session expired. Please sign in again.</div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                    <div class="wf-alert wf-alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>Registration successful! You can now sign in.</div>
                    </div>
                    <?php endif; ?>

                    <form id="wfLoginForm" method="POST" action="login.php" novalidate>
                        <?= csrf_field() ?>

                        <div class="wf-form-group">
                            <label class="wf-form-label" for="wfEmail">Email Address</label>
                            <div class="wf-input-wrap">
                                <i class="fas fa-envelope wf-input-icon"></i>
                                <input type="email" class="wf-form-control" id="wfEmail" name="email"
                                       placeholder="you@example.com"
                                       value="<?= e($email_value) ?>"
                                       autocomplete="email" required>
                            </div>
                        </div>

                        <div class="wf-form-group">
                            <label class="wf-form-label" for="wfPassword">Password</label>
                            <div class="wf-input-wrap">
                                <i class="fas fa-lock wf-input-icon"></i>
                                <input type="password" class="wf-form-control" id="wfPassword" name="password"
                                       placeholder="Enter your password"
                                       autocomplete="current-password" required minlength="6">
                                <i class="fas fa-eye wf-password-toggle" id="wfTogglePassword"></i>
                            </div>
                        </div>

                        <div class="wf-form-options">
                            <label class="wf-remember">
                                <input type="checkbox" id="wfRememberMe" name="rememberMe">
                                <span>Remember me</span>
                            </label>
                            <a href="#" class="wf-forgot">Forgot Password?</a>
                        </div>

                        <button type="submit" class="wf-btn-primary" id="wfLoginBtn">
                            <span class="wf-btn-text"><i class="fas fa-sign-in-alt"></i> Sign In</span>
                        </button>

                        <div class="wf-divider">Don't have an account?</div>

                        <div class="wf-auth-footer">
                            <a href="register.php">Create a new account</a>
                        </div>
                    </form>

                </div>
            </main>

        </div>
    </div>

    <script>
        (function() {
            const togglePassword = document.getElementById('wfTogglePassword');
            const password = document.getElementById('wfPassword');
            const form = document.getElementById('wfLoginForm');
            const loginBtn = document.getElementById('wfLoginBtn');
            const rememberMe = document.getElementById('wfRememberMe');
            const emailInput = document.getElementById('wfEmail');

            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            form.addEventListener('submit', function(e) {
                if (!emailInput.validity.valid || !password.validity.valid) {
                    e.preventDefault();
                    if (!emailInput.validity.valid) emailInput.style.borderColor = '#dc3545';
                    if (!password.validity.valid) password.style.borderColor = '#dc3545';
                    return false;
                }
                loginBtn.disabled = true;
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
            });

            emailInput.addEventListener('input', () => emailInput.style.borderColor = '');
            password.addEventListener('input', () => password.style.borderColor = '');

            form.addEventListener('submit', function() {
                if (rememberMe.checked) {
                    localStorage.setItem('wf_customer_email', emailInput.value);
                } else {
                    localStorage.removeItem('wf_customer_email');
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                const saved = localStorage.getItem('wf_customer_email');
                if (saved && !emailInput.value) {
                    emailInput.value = saved;
                    rememberMe.checked = true;
                }
            });
        })();
    </script>
</body>
</html>