<?php
session_start();
include 'db_connect.php';

// If already logged in as admin, go straight to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($admin = $result->fetch_assoc()) {
            $dbPass = $admin['password'] ?? '';
            $authenticated = false;

            // Support both plain-text and hashed passwords
            if (!empty($dbPass) && password_verify($password, $dbPass)) {
                $authenticated = true;
            } elseif ($password === $dbPass) {
                $authenticated = true;
                // Upgrade to hash
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE admin SET password = ? WHERE Admin_ID = ?");
                $up->bind_param('si', $newHash, $admin['Admin_ID']);
                $up->execute();
                $up->close();
            }

            if ($authenticated) {
                $_SESSION['admin_id']       = $admin['Admin_ID'];
                $_SESSION['username']       = $admin['username'];
                $_SESSION['is_admin']       = true;
                $_SESSION['login_success']  = true;
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid username or password. Please try again.';
            }
        } else {
            $error_message = 'Invalid username or password. Please try again.';
        }
        $stmt->close();
    } else {
        $error_message = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — MangTV Laundry Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-blue: #00537A;
            --mid-blue:  #006b99;
            --light-blue: #A8E8F9;
            --yellow: #FFD35B;
            --admin-accent: #1a3a5c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #0a1628 0%, #1a3a5c 50%, #00537A 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated background orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(168,232,249,0.08) 0%, transparent 70%);
            top: -150px; right: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255,211,91,0.06) 0%, transparent 70%);
            bottom: -100px; left: -80px;
            animation-delay: -4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(-30px) rotate(5deg); }
        }

        .card-wrapper {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 10;
            animation: slideUp 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Shield / badge header */
        .admin-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }

        .shield-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--yellow) 0%, #ffb700 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(255,211,91,0.4), 0 0 0 8px rgba(255,211,91,0.1);
            margin-bottom: 1rem;
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 8px 32px rgba(255,211,91,0.4), 0 0 0 8px rgba(255,211,91,0.1); }
            50%       { box-shadow: 0 8px 32px rgba(255,211,91,0.6), 0 0 0 16px rgba(255,211,91,0.05); }
        }

        .shield-icon i { font-size: 2rem; color: #0a1628; }

        .admin-badge h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .admin-badge p {
            color: rgba(255,255,255,0.55);
            font-size: 0.85rem;
            margin-top: 2px;
        }

        /* Card */
        .login-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 24px 64px rgba(0,0,0,0.4);
        }

        .form-label {
            color: rgba(255,255,255,0.75);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.35);
            font-size: 0.9rem;
            z-index: 5;
            transition: color 0.3s;
        }

        .form-control {
            background: rgba(255,255,255,0.07);
            border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            color: #fff;
            padding: 0.8rem 1rem 0.8rem 2.75rem;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            width: 100%;
        }

        .form-control::placeholder { color: rgba(255,255,255,0.25); }

        .form-control:focus {
            outline: none;
            background: rgba(255,255,255,0.1);
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(255,211,91,0.15);
            color: #fff;
        }

        .form-control:focus + .input-icon,
        .input-wrap:focus-within .input-icon {
            color: var(--yellow);
        }

        .toggle-pw {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.35);
            cursor: pointer;
            z-index: 5;
            transition: color 0.3s;
            background: none;
            border: none;
            padding: 0;
        }
        .toggle-pw:hover { color: var(--yellow); }

        .form-group { margin-bottom: 1.25rem; }

        /* Error alert */
        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.35);
            border-radius: 12px;
            color: #ff8a93;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        /* Submit button */
        .btn-admin-login {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, var(--yellow) 0%, #ffb700 100%);
            border: none;
            border-radius: 12px;
            color: #0a1628;
            font-weight: 800;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-admin-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255,211,91,0.4);
            background: linear-gradient(135deg, #ffe082 0%, var(--yellow) 100%);
        }

        .btn-admin-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-admin-login .spinner {
            display: none;
            width: 18px; height: 18px;
            border: 2px solid rgba(10,22,40,0.3);
            border-top-color: #0a1628;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .btn-admin-login.loading .spinner { display: inline-block; }
        .btn-admin-login.loading .btn-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .divider {
            text-align: center;
            margin: 1.5rem 0 1rem;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            width: 100%; height: 1px;
            background: rgba(255,255,255,0.08);
        }
        .divider span {
            position: relative;
            background: transparent;
            padding: 0 0.75rem;
            color: rgba(255,255,255,0.3);
            font-size: 0.78rem;
        }

        .back-link {
            display: block;
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 0.85rem;
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-link:hover { color: var(--light-blue); }
        .back-link i { margin-right: 4px; }

        /* Security notice */
        .security-note {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
            color: rgba(255,255,255,0.25);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>

<div class="card-wrapper">
    <!-- Badge -->
    <div class="admin-badge">
        <div class="shield-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        <h1>Admin Portal</h1>
        <p>MangTV Laundry Shop — Restricted Access</p>
    </div>

    <!-- Card -->
    <div class="login-card">

        <?php if ($error_message): ?>
        <div class="alert-error">
            <i class="fas fa-circle-xmark"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <form id="adminLoginForm" method="POST" action="admin_login.php" novalidate>

            <div class="form-group">
                <label class="form-label" for="usernameInput">Username</label>
                <div class="input-wrap">
                    <input
                        type="text"
                        class="form-control"
                        id="usernameInput"
                        name="username"
                        placeholder="Enter admin username"
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                    >
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="passwordInput">Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        class="form-control"
                        id="passwordInput"
                        name="password"
                        placeholder="Enter admin password"
                        autocomplete="current-password"
                        required
                    >
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="toggle-pw" onclick="togglePassword()" tabindex="-1">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-admin-login" id="loginBtn">
                <span class="btn-text"><i class="fas fa-right-to-bracket"></i> Sign In</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="divider"><span>or</span></div>
        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Customer Login</a>
    </div>

    <div class="security-note">
        <i class="fas fa-lock"></i>
        Secure connection · Authorized personnel only
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon  = document.getElementById('toggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.getElementById('adminLoginForm').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.classList.add('loading');
    });
</script>
</body>
</html>
