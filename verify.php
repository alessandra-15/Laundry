<?php
define('APP_STARTED', true);
require_once 'session_config.php';
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'functions.php';

$message = '';
$success = false;

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid verification link.';
} else {
    $stmt = $conn->prepare("SELECT Customer_ID, email FROM customer_info WHERE verification_token = ? AND token_expires_at > NOW() LIMIT 1");
    
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $upd = $conn->prepare("UPDATE customer_info SET email_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE Customer_ID = ?");
            if ($upd) {
                $upd->bind_param('i', $user['Customer_ID']);
                $upd->execute();
                $upd->close();
                
                $success = true;
                $message = 'Email verified successfully! You can now login.';
                Logger::info('Email verified', ['customer_id' => $user['Customer_ID']]);
            }
        } else {
            $message = 'Invalid or expired verification link.';
        }
        $stmt->close();
    } else {
        $message = 'System error. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - MangTV Laundry Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #A8E8F9 0%, #e3f5fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verify-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,83,122,0.15);
        }
        .verify-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: white;
        }
        .verify-icon.success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .verify-icon.error { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        h3 { color: #00537A; font-weight: 700; margin-bottom: 1rem; }
        p { color: #6c757d; margin-bottom: 1.5rem; }
        .btn-custom {
            background: linear-gradient(135deg, #00537A 0%, #006b99 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 6px 20px rgba(0,83,122,0.3);
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="verify-icon <?= $success ? 'success' : 'error' ?>">
            <i class="fas <?= $success ? 'fa-check' : 'fa-times' ?>"></i>
        </div>
        <h3><?= $success ? 'Verified!' : 'Verification Failed' ?></h3>
        <p><?= e($message) ?></p>
        <a href="login.php" class="btn-custom">Go to Login</a>
    </div>
</body>
</html>