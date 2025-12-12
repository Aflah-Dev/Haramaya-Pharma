<?php
/**
 * HARAMAYA PHARMA - Login Page
 */

$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

secure_session_start();

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$error = '';
$success = '';

// Check for timeout or invalid session
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
} elseif (isset($_GET['invalid'])) {
    $error = 'Invalid session detected. Please login again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $result = login_user($pdo, $username, $password);
            
            if ($result['success']) {
                header('Location: ../dashboard/index.php');
                exit;
            } else {
                $error = $result['message'];
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
    <title>Login - Haramaya Pharma</title>
    <link rel="icon" type="image/jpeg" href="../../assets/images/favicon.jpg">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: var(--text-secondary);
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../../assets/images/image.jpg" alt="Haramaya Pharma" class="logo-img"
                     style="width: 100px; height: 100px; object-fit: contain; margin-bottom: 1rem; border-radius: 12px;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                <i class="fas fa-pills" style="font-size: 4rem; color: var(--primary-color); display: none;"></i>
                <h1>Haramaya Pharma</h1>
                <p>Professional Pharmacy Management System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo clean($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo clean($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" 
                           name="username" 
                           class="form-control" 
                           required 
                           autofocus
                           placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" 
                           name="password" 
                           class="form-control" 
                           required
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.875rem;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>
