<?php
/**
 * register.php
 * WashFlow — Multi-step Registration (Card Split Screen)
 */
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'csrf_helper.php';
require_once 'logger.php';
require_once 'functions.php';

$error_message = '';

$form_data = [
    'firstName' => '', 'lastName' => '', 'email' => '',
    'phone' => '', 'address' => '', 'account_type' => 'Regular'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        Logger::security('CSRF mismatch on register', ['ip' => get_client_ip()]);
        $error_message = 'Security token expired. Please refresh and try again.';
    } else {
        $first_name       = trim($_POST['firstName'] ?? '');
        $last_name        = trim($_POST['lastName'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $contact_number   = trim($_POST['phone'] ?? '');
        $address          = trim($_POST['address'] ?? '');
        $account_type     = $_POST['account_type'] ?? 'Regular';
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirmPassword'] ?? '';

        $form_data['firstName']    = $first_name;
        $form_data['lastName']     = $last_name;
        $form_data['email']        = $email;
        $form_data['phone']        = $contact_number;
        $form_data['address']      = $address;
        $form_data['account_type'] = $account_type;

        $error = false;

        if ($first_name === '' || $last_name === '') {
            $error_message = 'First and last name are required.';
            $error = true;
        } elseif (!validate_email($email)) {
            $error_message = 'Please enter a valid email address.';
            $error = true;
        } elseif (!validate_phone($contact_number)) {
            $error_message = 'Please enter a valid Philippine phone number (e.g., 09123456789).';
            $error = true;
        } elseif ($address === '') {
            $error_message = 'Address is required.';
            $error = true;
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match!';
            $error = true;
        } else {
            $pass_errors = validate_password_strength($password);
            if (!empty($pass_errors)) {
                $error_message = 'Password must contain ' . implode(', ', $pass_errors) . '.';
                $error = true;
            }
        }

        if (!$error) {
            $check = $conn->prepare("SELECT Customer_ID FROM customer_info WHERE email = ? LIMIT 1");
            if ($check) {
                $check->bind_param('s', $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error_message = 'Email is already registered. Please login instead.';
                    $error = true;
                }
                $check->close();
            }
        }

        $student_id_path = null;
        $discount_rate = 0.0;

        if (!$error && $account_type === 'Student') {
            if (!isset($_FILES['student_id']) || $_FILES['student_id']['error'] !== UPLOAD_ERR_OK) {
                $error_message = 'Please upload a valid student ID when selecting Student account.';
                $error = true;
            } else {
                $file = $_FILES['student_id'];

                if ($file['size'] > 5 * 1024 * 1024) {
                    $error_message = 'Student ID file must be less than 5MB.';
                    $error = true;
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowed = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'application/pdf' => 'pdf'
                    ];

                    if (!isset($allowed[$mime])) {
                        $error_message = 'Invalid student ID file type. Allowed: JPG, PNG or PDF.';
                        $error = true;
                    } else {
                        $ext = $allowed[$mime];
                        $newFileName = 'sid_' . generate_filename($ext);
                        $targetDir = __DIR__ . '/uploads/student_ids/';

                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        $htaccess = $targetDir . '.htaccess';
                        if (!file_exists($htaccess)) {
                            file_put_contents($htaccess, "php_flag engine off\nOptions -ExecCGI\nAddType text/plain .php .php3 .phtml .phtml\n");
                        }

                        $targetPath = $targetDir . $newFileName;

                        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $error_message = 'Failed to save uploaded student ID.';
                            $error = true;
                        } else {
                            $student_id_path = 'uploads/student_ids/' . $newFileName;
                            $discount_rate = 0.10;
                        }
                    }
                }
            }
        }

        if (!$error) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $register_date = date("Y-m-d");
            $created_at = date("Y-m-d H:i:s");
            $updated_at = $created_at;

            $stmt = $conn->prepare("INSERT INTO customer_info 
                (first_name, last_name, email, register_date, contact_number, Address, account_type, student_id_path, discount_rate, password, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt === false) {
                Logger::error('Prepare failed on register', ['error' => $conn->error]);
                $error_message = 'Database error. Please try again.';
            } else {
                $stmt->bind_param("ssssssssdsss",
                    $first_name, $last_name, $email, $register_date,
                    $contact_number, $address, $account_type, $student_id_path,
                    $discount_rate, $hashed_password, $created_at, $updated_at);

                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id;
                    Logger::info('New customer registered', [
                        'customer_id' => $new_id,
                        'email' => $email,
                        'ip' => get_client_ip()
                    ]);
                    $_SESSION['flash_success'] = 'Registration successful!';
                    header('Location: login.php?registered=1');
                    exit();
                } else {
                    Logger::error('Registration failed', ['error' => $stmt->error]);
                    $error_message = 'Error: Unable to register. Please try again.';
                }
                $stmt->close();
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
    <title>Create Account — WashFlow</title>

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

        /* Floating decorative circles sa background */
        body::before {
            content: '';
            position: fixed;
            top: -15%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 217, 61, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: floatBg 12s ease-in-out infinite;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            left: -10%;
            width: 700px;
            height: 700px;
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

        /* MAIN WRAPPER */
        .wf-page {
            position: relative;
            z-index: 5;
            width: 100%;
            max-width: 1100px;
        }

        /* TOP BAR (sa labas ng card) */
        .wf-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding: 0 0.5rem;
        }

        .wf-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .wf-logo-mark {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
        }
        .wf-logo-mark svg {
            width: 100%;
            height: 100%;
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

        /* CARD — split screen sa loob */
        .wf-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow:
                0 2px 8px rgba(10, 37, 64, 0.04),
                0 24px 60px rgba(10, 37, 64, 0.15);
            display: grid;
            grid-template-columns: 40% 60%;
            min-height: 620px;
            animation: cardIn 0.7s cubic-bezier(0.165, 0.84, 0.44, 1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ══════════════════════════════════════
           LEFT SIDE — Branding
           ══════════════════════════════════════ */
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
            font-size: 1.75rem;
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

        /* ══════════════════════════════════════
           RIGHT SIDE — Form
           ══════════════════════════════════════ */
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
            max-width: 480px;
            margin: 0 auto;
        }

        /* Form header */
        .wf-form-header { margin-bottom: 1.5rem; }
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
            font-size: 1.6rem;
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

        /* Progress */
        .wf-progress-wrap { margin-bottom: 1.5rem; }
        .wf-progress-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        .wf-progress-track {
            position: absolute;
            top: 17px;
            left: 24px; right: 24px;
            height: 2px;
            background: rgba(201, 220, 232, 0.7);
            border-radius: 2px;
            z-index: 0;
        }
        .wf-progress-line {
            position: absolute;
            top: 17px;
            left: 24px;
            height: 2px;
            background: linear-gradient(90deg, var(--yellow) 0%, var(--primary) 100%);
            border-radius: 2px;
            z-index: 1;
            transition: width 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
            max-width: calc(100% - 48px);
        }
        .wf-progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .wf-progress-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.75rem;
            margin-bottom: 0.4rem;
            box-shadow: 0 2px 6px rgba(10, 37, 64, 0.05);
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .wf-progress-step.active .wf-progress-circle {
            background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
            border-color: var(--yellow);
            color: var(--dark-blue-deep);
            box-shadow: 0 6px 16px rgba(255, 217, 61, 0.5);
            transform: scale(1.12);
        }
        .wf-progress-step.completed .wf-progress-circle {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 90, 133, 0.3);
        }
        .wf-progress-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: color 0.3s;
        }
        .wf-progress-step.active .wf-progress-label { color: var(--dark-blue); font-weight: 700; }
        .wf-progress-step.completed .wf-progress-label { color: var(--primary); }

        /* Form */
        .wf-form-step { display: none; }
        .wf-form-step.active { display: block; animation: stepIn 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); }
        @keyframes stepIn {
            from { opacity: 0; transform: translateX(16px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .wf-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .wf-form-group { margin-bottom: 1rem; }
        .wf-form-label {
            display: block;
            color: var(--dark-blue);
            font-weight: 600;
            margin-bottom: 0.4rem;
            font-size: 0.8rem;
        }
        .wf-form-label .required { color: #dc3545; margin-left: 2px; }

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
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            font-size: 0.875rem;
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

        /* File input */
        .wf-file-input {
            padding: 0.65rem 1rem;
            border: 1.5px dashed var(--border);
            border-radius: 11px;
            width: 100%;
            font-size: 0.82rem;
            background: var(--light-blue-pale);
            cursor: pointer;
            transition: all 0.25s;
            font-family: inherit;
        }
        .wf-file-input:hover { border-color: var(--primary-mid); background: var(--light-blue-soft); }

        /* Account type */
        .wf-account-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
        }
        .wf-account-option { position: relative; cursor: pointer; }
        .wf-account-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .wf-account-option-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.8rem 0.6rem;
            border: 1.5px solid var(--border);
            border-radius: 11px;
            background: white;
            transition: all 0.25s;
            text-align: center;
        }
        .wf-account-option-content i {
            font-size: 1.1rem;
            color: var(--text-muted);
            transition: color 0.25s;
        }
        .wf-account-option-content .opt-title {
            font-weight: 700;
            font-size: 0.82rem;
            color: var(--dark-blue);
        }
        .wf-account-option-content .opt-desc {
            font-size: 0.65rem;
            color: var(--text-secondary);
            line-height: 1.3;
        }
        .wf-account-option input[type="radio"]:checked + .wf-account-option-content {
            border-color: var(--yellow);
            background: linear-gradient(135deg, var(--yellow-soft) 0%, #FFFFFF 100%);
            box-shadow: 0 6px 16px rgba(255, 217, 61, 0.3);
            transform: translateY(-1px);
        }
        .wf-account-option input[type="radio"]:checked + .wf-account-option-content i {
            color: var(--yellow-dark);
        }

        /* Password strength */
        .wf-password-strength {
            display: flex;
            gap: 4px;
            margin-top: 0.4rem;
        }
        .wf-pw-bar {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: var(--border-light);
            transition: background 0.25s;
        }
        .wf-pw-bar.active-weak { background: #dc3545; }
        .wf-pw-bar.active-medium { background: #ffc107; }
        .wf-pw-bar.active-strong { background: #28a745; }
        .wf-pw-strength-text {
            display: block;
            font-size: 0.68rem;
            margin-top: 0.3rem;
            color: var(--text-muted);
        }

        /* Buttons */
        .wf-form-actions {
            display: flex;
            gap: 0.65rem;
            margin-top: 1.25rem;
        }
        .wf-btn-primary {
            flex: 1;
            background: linear-gradient(135deg, var(--yellow) 0%, #FFE066 100%);
            color: var(--dark-blue-deep);
            border: none;
            font-weight: 700;
            font-size: 0.875rem;
            padding: 0.85rem 1.25rem;
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

        .wf-btn-secondary {
            background: white;
            color: var(--dark-blue);
            border: 1.5px solid var(--border);
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.85rem 1.25rem;
            border-radius: 50px;
            transition: all 0.25s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: inherit;
        }
        .wf-btn-secondary:hover {
            border-color: var(--primary-mid);
            color: var(--primary);
            background: var(--light-blue-pale);
        }

        /* Alert */
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

        /* Terms */
        .wf-terms {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            margin-top: 0.4rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .wf-terms input[type="checkbox"] {
            margin-top: 2px;
            width: 15px;
            height: 15px;
            accent-color: var(--primary);
            flex-shrink: 0;
            cursor: pointer;
        }
        .wf-terms a { color: var(--primary); font-weight: 600; }

        /* Student ID */
        .wf-student-section {
            display: none;
            margin-top: 0.9rem;
            padding: 1rem;
            background: linear-gradient(135deg, var(--light-blue-pale) 0%, var(--light-blue-soft) 100%);
            border-radius: 11px;
            border: 1.5px dashed var(--light-blue);
            animation: stepIn 0.3s ease-out;
        }
        .wf-student-section.show { display: block; }
        .wf-student-section .wf-form-label { color: var(--primary); }
        .wf-student-section .note {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .wf-student-section .note i { color: var(--yellow-dark); }

        /* Footer */
        .wf-auth-footer {
            text-align: center;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-light);
            font-size: 0.82rem;
            color: var(--text-secondary);
        }
        .wf-auth-footer a {
            color: var(--primary);
            font-weight: 700;
            transition: color 0.25s;
        }
        .wf-auth-footer a:hover { color: var(--primary-mid); }

        /* ══════════════════════════════════════
           RESPONSIVE
           ══════════════════════════════════════ */
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
            .wf-row { grid-template-columns: 1fr; gap: 0; }
            .wf-account-options { grid-template-columns: 1fr; }
            .wf-form-actions { flex-direction: column; }
            .wf-progress-label { font-size: 0.55rem; }
            .wf-progress-track,
            .wf-progress-line { left: 18px; right: 18px; }
            .wf-progress-circle { width: 30px; height: 30px; font-size: 0.7rem; }
        }
    </style>
</head>
<body>

    <div class="wf-page">

        <!-- TOP BAR (sa labas ng card) -->
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

        <!-- CARD (split screen sa loob) -->
        <div class="wf-card">

            <!-- LEFT: Branding -->
            <aside class="wf-card-left">
                <div class="wf-card-left-content">
                    <h1>Start your <span class="accent">laundry journey</span> today.</h1>
                    <p class="lead">
                        Create your WashFlow account and enjoy a hassle-free experience
                        managing bookings, tracking orders, and handling payments.
                    </p>

                    <ul class="wf-benefits">
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-bolt"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Fast Booking</strong>
                                <span>Book laundry services in seconds</span>
                            </div>
                        </li>
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-truck"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Pickup &amp; Delivery</strong>
                                <span>Convenient doorstep service</span>
                            </div>
                        </li>
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-tags"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Student Discounts</strong>
                                <span>Verified students get 10% off</span>
                            </div>
                        </li>
                        <li>
                            <div class="wf-benefit-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="wf-benefit-text">
                                <strong>Real-time Tracking</strong>
                                <span>Monitor your orders anytime</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="wf-card-left-footer">
                    <i class="fas fa-shield-alt"></i>
                    Secured with CSRF &amp; encrypted passwords
                </div>
            </aside>

            <!-- RIGHT: Form -->
            <main class="wf-card-right">
                <div class="wf-form-container">

                    <div class="wf-form-header">
                        <span class="wf-eyebrow">
                            <i class="fas fa-user-plus"></i> Create Account
                        </span>
                        <h2>Join WashFlow</h2>
                        <p>Fill in the details below to get started.</p>
                    </div>

                    <?php if ($error_message): ?>
                    <div class="wf-alert wf-alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?= e($error_message) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Progress -->
                    <div class="wf-progress-wrap">
                        <div class="wf-progress-steps">
                            <div class="wf-progress-track"></div>
                            <div class="wf-progress-line" id="wfProgressLine"></div>
                            <div class="wf-progress-step active" data-step="1">
                                <div class="wf-progress-circle">1</div>
                                <div class="wf-progress-label">Personal</div>
                            </div>
                            <div class="wf-progress-step" data-step="2">
                                <div class="wf-progress-circle">2</div>
                                <div class="wf-progress-label">Account</div>
                            </div>
                            <div class="wf-progress-step" data-step="3">
                                <div class="wf-progress-circle">3</div>
                                <div class="wf-progress-label">Security</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="" enctype="multipart/form-data" id="wfRegisterForm" novalidate>
                        <?= csrf_field() ?>

                        <!-- STEP 1 -->
                        <div class="wf-form-step active" data-step="1">
                            <div class="wf-row">
                                <div class="wf-form-group">
                                    <label class="wf-form-label">First Name <span class="required">*</span></label>
                                    <div class="wf-input-wrap">
                                        <i class="fas fa-user wf-input-icon"></i>
                                        <input type="text" name="firstName" class="wf-form-control"
                                               placeholder="First name"
                                               value="<?= e($form_data['firstName']) ?>" required>
                                    </div>
                                </div>
                                <div class="wf-form-group">
                                    <label class="wf-form-label">Last Name <span class="required">*</span></label>
                                    <div class="wf-input-wrap">
                                        <i class="fas fa-user wf-input-icon"></i>
                                        <input type="text" name="lastName" class="wf-form-control"
                                               placeholder="Last name"
                                               value="<?= e($form_data['lastName']) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="wf-form-group">
                                <label class="wf-form-label">Email Address <span class="required">*</span></label>
                                <div class="wf-input-wrap">
                                    <i class="fas fa-envelope wf-input-icon"></i>
                                    <input type="email" name="email" class="wf-form-control"
                                           placeholder="you@example.com"
                                           value="<?= e($form_data['email']) ?>" required>
                                </div>
                            </div>

                            <div class="wf-form-group">
                                <label class="wf-form-label">Phone Number <span class="required">*</span></label>
                                <div class="wf-input-wrap">
                                    <i class="fas fa-phone wf-input-icon"></i>
                                    <input type="tel" name="phone" class="wf-form-control"
                                           placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                                           value="<?= e($form_data['phone']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2 -->
                        <div class="wf-form-step" data-step="2">
                            <div class="wf-form-group">
                                <label class="wf-form-label">Address <span class="required">*</span></label>
                                <div class="wf-input-wrap">
                                    <i class="fas fa-map-marker-alt wf-input-icon"></i>
                                    <input type="text" name="address" class="wf-form-control"
                                           placeholder="Enter your complete address"
                                           value="<?= e($form_data['address']) ?>" required>
                                </div>
                            </div>

                            <div class="wf-form-group">
                                <label class="wf-form-label">Account Type <span class="required">*</span></label>
                                <div class="wf-account-options">
                                    <label class="wf-account-option">
                                        <input type="radio" name="account_type" value="Regular"
                                               <?= $form_data['account_type'] === 'Regular' ? 'checked' : '' ?>>
                                        <div class="wf-account-option-content">
                                            <i class="fas fa-user"></i>
                                            <div class="opt-title">Regular</div>
                                            <div class="opt-desc">Standard account</div>
                                        </div>
                                    </label>
                                    <label class="wf-account-option">
                                        <input type="radio" name="account_type" value="Student"
                                               <?= $form_data['account_type'] === 'Student' ? 'checked' : '' ?>>
                                        <div class="wf-account-option-content">
                                            <i class="fas fa-graduation-cap"></i>
                                            <div class="opt-title">Student</div>
                                            <div class="opt-desc">Get 10% off</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="wf-student-section" id="wfStudentSection">
                                <label class="wf-form-label">Upload Student ID <span class="required">*</span></label>
                                <input type="file" name="student_id" id="wfStudentId"
                                       class="wf-file-input" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="note">
                                    <i class="fas fa-info-circle"></i>
                                    Accepted: JPG, PNG, PDF. Max: 5MB.
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3 -->
                        <div class="wf-form-step" data-step="3">
                            <div class="wf-form-group">
                                <label class="wf-form-label">Password <span class="required">*</span></label>
                                <div class="wf-input-wrap">
                                    <i class="fas fa-lock wf-input-icon"></i>
                                    <input type="password" name="password" id="wfPassword"
                                           class="wf-form-control" placeholder="Create a strong password"
                                           required minlength="8">
                                    <i class="fas fa-eye wf-password-toggle" id="wfTogglePassword"></i>
                                </div>
                                <div class="wf-password-strength">
                                    <div class="wf-pw-bar" data-bar="1"></div>
                                    <div class="wf-pw-bar" data-bar="2"></div>
                                    <div class="wf-pw-bar" data-bar="3"></div>
                                    <div class="wf-pw-bar" data-bar="4"></div>
                                </div>
                                <span class="wf-pw-strength-text" id="wfPwText">Use 8+ chars with upper, lower &amp; number</span>
                            </div>

                            <div class="wf-form-group">
                                <label class="wf-form-label">Confirm Password <span class="required">*</span></label>
                                <div class="wf-input-wrap">
                                    <i class="fas fa-lock wf-input-icon"></i>
                                    <input type="password" name="confirmPassword" id="wfConfirmPassword"
                                           class="wf-form-control" placeholder="Re-enter your password"
                                           required minlength="8">
                                    <i class="fas fa-eye wf-password-toggle" id="wfToggleConfirm"></i>
                                </div>
                            </div>

                            <div class="wf-terms">
                                <input type="checkbox" id="wfTerms" required>
                                <label for="wfTerms">
                                    I agree to the <a href="homepage.php#faq" target="_blank">Terms of Service</a>
                                    and <a href="homepage.php#faq" target="_blank">Privacy Policy</a>.
                                </label>
                            </div>
                        </div>

                        <!-- ACTIONS -->
                        <div class="wf-form-actions">
                            <button type="button" class="wf-btn-secondary" id="wfPrevBtn" style="display:none;">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="wf-btn-primary" id="wfNextBtn">
                                Continue <i class="fas fa-arrow-right"></i>
                            </button>
                            <button type="submit" class="wf-btn-primary" id="wfSubmitBtn" style="display:none;">
                                <i class="fas fa-check"></i> Create Account
                            </button>
                        </div>
                    </form>

                    <div class="wf-auth-footer">
                        Already have an account? <a href="login.php">Sign in here</a>
                    </div>

                </div>
            </main>

        </div>

    </div>

    <script>
        (function() {
            const steps = document.querySelectorAll('.wf-form-step');
            const stepIndicators = document.querySelectorAll('.wf-progress-step');
            const progressLine = document.getElementById('wfProgressLine');
            const prevBtn = document.getElementById('wfPrevBtn');
            const nextBtn = document.getElementById('wfNextBtn');
            const submitBtn = document.getElementById('wfSubmitBtn');
            const form = document.getElementById('wfRegisterForm');

            let currentStep = <?= $error_message ? '3' : '1' ?>;
            const totalSteps = steps.length;

            function updateStep() {
                steps.forEach((step, i) => {
                    step.classList.toggle('active', (i + 1) === currentStep);
                });

                stepIndicators.forEach((ind, i) => {
                    const stepNum = i + 1;
                    ind.classList.remove('active', 'completed');
                    if (stepNum === currentStep) ind.classList.add('active');
                    else if (stepNum < currentStep) ind.classList.add('completed');

                    const circle = ind.querySelector('.wf-progress-circle');
                    if (stepNum < currentStep) {
                        circle.innerHTML = '<i class="fas fa-check"></i>';
                    } else {
                        circle.textContent = stepNum;
                    }
                });

                const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
                progressLine.style.width = `calc(${percent}% - ${percent > 0 ? '0px' : '0px'})`;

                prevBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
                nextBtn.style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
                submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
            }

            function validateStep(stepNum) {
                const el = document.querySelector(`.wf-form-step[data-step="${stepNum}"]`);
                if (!el) return true;
                let valid = true;
                const fields = el.querySelectorAll('input[required], select[required]');
                fields.forEach(field => {
                    if (field.type === 'radio') {
                        const group = el.querySelectorAll(`input[name="${field.name}"]`);
                        const anyChecked = Array.from(group).some(g => g.checked);
                        if (!anyChecked) valid = false;
                        return;
                    }
                    if (field.type === 'checkbox') {
                        if (!field.checked) valid = false;
                        return;
                    }
                    field.style.borderColor = '';
                    if (!field.checkValidity()) {
                        field.style.borderColor = '#dc3545';
                        valid = false;
                    }
                });

                if (stepNum === 3) {
                    const pw = document.getElementById('wfPassword').value;
                    const cpw = document.getElementById('wfConfirmPassword').value;
                    if (pw !== cpw) {
                        document.getElementById('wfConfirmPassword').style.borderColor = '#dc3545';
                        alert('Passwords do not match!');
                        valid = false;
                    }
                    if (!document.getElementById('wfTerms').checked) {
                        alert('Please agree to the Terms of Service and Privacy Policy.');
                        valid = false;
                    }
                }
                return valid;
            }

            nextBtn.addEventListener('click', () => {
                if (validateStep(currentStep) && currentStep < totalSteps) {
                    currentStep++;
                    updateStep();
                }
            });

            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateStep();
                }
            });

            form.addEventListener('submit', function(e) {
                for (let s = 1; s <= totalSteps; s++) {
                    if (!validateStep(s)) {
                        currentStep = s;
                        updateStep();
                        e.preventDefault();
                        return false;
                    }
                }

                const selected = document.querySelector('input[name="account_type"]:checked');
                if (selected && selected.value === 'Student') {
                    const sid = document.getElementById('wfStudentId');
                    if (!sid.files.length) {
                        e.preventDefault();
                        alert('Please upload your student ID.');
                        currentStep = 2;
                        updateStep();
                        return false;
                    }
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            });

            // Password toggles
            document.getElementById('wfTogglePassword').addEventListener('click', function() {
                const inp = document.getElementById('wfPassword');
                inp.type = inp.type === 'password' ? 'text' : 'password';
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
            document.getElementById('wfToggleConfirm').addEventListener('click', function() {
                const inp = document.getElementById('wfConfirmPassword');
                inp.type = inp.type === 'password' ? 'text' : 'password';
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            // Password strength
            const pwInput = document.getElementById('wfPassword');
            const bars = document.querySelectorAll('.wf-pw-bar');
            const pwText = document.getElementById('wfPwText');

            pwInput.addEventListener('input', function() {
                const val = this.value;
                let score = 0;
                if (val.length >= 8) score++;
                if (/[a-z]/.test(val)) score++;
                if (/[A-Z]/.test(val)) score++;
                if (/[0-9]/.test(val)) score++;
                if (/[^A-Za-z0-9]/.test(val)) score++;
                score = Math.min(score, 4);

                bars.forEach((b, i) => {
                    b.classList.remove('active-weak', 'active-medium', 'active-strong');
                    if (i < score) {
                        if (score <= 1) b.classList.add('active-weak');
                        else if (score <= 2) b.classList.add('active-medium');
                        else b.classList.add('active-strong');
                    }
                });

                if (!val) {
                    pwText.textContent = 'Use 8+ chars with upper, lower & number';
                    pwText.style.color = 'var(--text-muted)';
                } else if (score <= 1) {
                    pwText.textContent = 'Weak password';
                    pwText.style.color = '#dc3545';
                } else if (score <= 2) {
                    pwText.textContent = 'Medium strength';
                    pwText.style.color = '#ffc107';
                } else if (score === 3) {
                    pwText.textContent = 'Good password';
                    pwText.style.color = '#28a745';
                } else {
                    pwText.textContent = 'Strong password';
                    pwText.style.color = '#28a745';
                }
            });

            // Student toggle
            const accountRadios = document.querySelectorAll('input[name="account_type"]');
            const studentSection = document.getElementById('wfStudentSection');
            const studentInput = document.getElementById('wfStudentId');

            function toggleStudent() {
                const selected = document.querySelector('input[name="account_type"]:checked');
                if (selected && selected.value === 'Student') {
                    studentSection.classList.add('show');
                    studentInput.required = true;
                } else {
                    studentSection.classList.remove('show');
                    studentInput.required = false;
                }
            }
            accountRadios.forEach(r => r.addEventListener('change', toggleStudent));

            updateStep();
            toggleStudent();
        })();
    </script>
</body>
</html>