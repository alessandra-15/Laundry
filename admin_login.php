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
                $_SESSION['Admin_ID']       = $admin['Admin_ID']; // keep both for compatibility
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
    <title>Admin Login - MangTV Laundry Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-blue: #00537A;
            --yellow: #FFD35B;
            --light-blue: #A8E8F9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light-blue) 0%, #e3f5fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 83, 122, 0.15);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Left Side - Image */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, rgba(0,83,122,0.95) 0%, rgba(0,107,153,0.9) 100%),
                        url('https://images.unsplash.com/photo-1517677208171-0bc6725a3e60?w=800&h=1200&fit=crop') center/cover;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(168,232,249,0.2) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(-20px) rotate(5deg); }
        }

        .login-left-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .brand-logo-big {
            width: 100px;
            height: 100px;
            background: var(--yellow);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 30px rgba(255,213,91,0.4);
            animation: bounceIn 1s ease-out;
        }

        @keyframes bounceIn {
            0%   { opacity: 0; transform: scale(0.3); }
            50%  { transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }

        .brand-logo-big i {
            font-size: 3rem;
            color: var(--dark-blue);
        }

        .left-title h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--yellow);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .left-title p {
            font-size: 1rem;
            opacity: 0.95;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            text-align: left;
        }

        .feature-list li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .feature-list i {
            color: var(--yellow);
            margin-right: 1rem;
            font-size: 1.1rem;
            width: 24px;
        }

        /* Right Side - Form */
        .login-right {
            flex: 1;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .brand-logo-small {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .logo-icon-small {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--yellow) 0%, #ffe082 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(255,213,91,0.3);
        }

        .logo-icon-small i {
            font-size: 1.5rem;
            color: var(--dark-blue);
        }

        .brand-text h3 {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
            color: var(--dark-blue);
        }

        .brand-text p {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
        }

        .admin-badge {
            display: inline-block;
            background: var(--dark-blue);
            color: var(--yellow);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 2rem;
        }

        .welcome-text h4 {
            color: var(--dark-blue);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: var(--dark-blue);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.9rem 1rem 0.9rem 3rem;
            font-size: 0.95rem;
            transition: all 0.3s;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--light-blue);
            box-shadow: 0 0 0 0.2rem rgba(168, 232, 249, 0.25);
            outline: none;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
            z-index: 5;
        }

        .password-toggle:hover {
            color: var(--dark-blue);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-input:checked {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
        }

        .forgot-password {
            color: var(--dark-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #006b99;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--yellow) 0%, #ffe082 100%);
            color: var(--dark-blue);
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(255,213,91,0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255,213,91,0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0,83,122,0.3);
            border-top-color: var(--dark-blue);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
        }

        .btn-login.loading .spinner { display: inline-block; }
        .btn-login.loading .btn-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .back-link {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .back-link a {
            color: var(--dark-blue);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #006b99;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-left {
                padding: 2rem;
                min-height: 300px;
            }

            .brand-logo-big {
                width: 80px;
                height: 80px;
            }

            .brand-logo-big i {
                font-size: 2.5rem;
            }

            .left-title h2 {
                font-size: 1.5rem;
            }

            .feature-list {
                display: none;
            }

            .login-right {
                padding: 2rem 1.5rem;
            }

            .form-options {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - Image & Branding -->
        <div class="login-left">
            <div class="login-left-content">
                <div class="brand-logo-big">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div class="left-title">
                    <h2>MangTV Laundry Shop</h2>
                    <p>Manage your laundry business with ease. Access your dashboard to monitor schedules, transactions, and customer data.</p>
                </div>
                <ul class="feature-list">
                    <li>
                        <i class="fas fa-chart-line"></i>
                        <span>Real-time business analytics</span>
                    </li>
                    <li>
                        <i class="fas fa-calendar-check"></i>
                        <span>Schedule management system</span>
                    </li>
                    <li>
                        <i class="fas fa-users"></i>
                        <span>Customer database access</span>
                    </li>
                    <li>
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure admin portal</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <div class="brand-logo-small">
                    <div class="logo-icon-small">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="brand-text">
                        <h3>MangTV Laundry Shop</h3>
                        <p>Admin Portal</p>
                    </div>
                </div>
                <div class="admin-badge">
                    <i class="fas fa-shield-alt me-1"></i> ADMIN ACCESS
                </div>
            </div>

            <div class="welcome-text">
                <h4>Welcome Back!</h4>
                <p>Sign in to access your admin dashboard</p>
            </div>

            <!-- Error Message -->
            <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Login Failed!</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="adminLoginForm" method="POST" action="admin_login.php">
                <div class="form-group">
                    <label class="form-label" for="usernameInput">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input
                            type="text"
                            class="form-control"
                            id="usernameInput"
                            name="username"
                            placeholder="Enter your username"
                            autocomplete="username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="passwordInput">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            class="form-control"
                            id="passwordInput"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-login" id="loginBtn">
                    <span class="spinner"></span>
                    <span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard</span>
                </button>
            </form>

            <div class="back-link">
                <i class="fas fa-arrow-left me-1"></i>
                <a href="login.php">Back to Customer Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('passwordInput');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Form Submission with loading state
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.classList.add('loading');
        });

        // Remember Me functionality
        const rememberMe = document.getElementById('rememberMe');
        const usernameInput = document.getElementById('usernameInput');

        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            if (rememberMe.checked) {
                localStorage.setItem('adminUsername', usernameInput.value);
            } else {
                localStorage.removeItem('adminUsername');
            }
        });

        // Restore remembered username
        document.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem('adminUsername');
            if (saved) {
                usernameInput.value = saved;
                rememberMe.checked = true;
            }
        });
    </script>
</body>
</html>
