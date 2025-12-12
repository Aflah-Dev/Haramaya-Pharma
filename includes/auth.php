<?php
/**
 * HARAMAYA PHARMA - Authentication Functions
 */

require_once __DIR__ . '/security.php';

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data
function get_logged_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

// Require login (redirect if not logged in)
function require_login() {
    // Check session timeout (1 hour)
    if (!check_session_timeout(3600)) {
        session_unset();
        session_destroy();
        $script_path = $_SERVER['SCRIPT_NAME'];
        if (strpos($script_path, '/modules/') !== false) {
            header('Location: ../auth/login.php?timeout=1');
        } else {
            header('Location: modules/auth/login.php?timeout=1');
        }
        exit;
    }
    
    // Validate session fingerprint
    if (!validate_session_fingerprint()) {
        session_unset();
        session_destroy();
        $script_path = $_SERVER['SCRIPT_NAME'];
        if (strpos($script_path, '/modules/') !== false) {
            header('Location: ../auth/login.php?invalid=1');
        } else {
            header('Location: modules/auth/login.php?invalid=1');
        }
        exit;
    }
    
    if (!is_logged_in()) {
        // Determine relative path to login based on current location
        $script_path = $_SERVER['SCRIPT_NAME'];
        if (strpos($script_path, '/modules/') !== false) {
            // We're in a module directory, go up to modules then to auth
            header('Location: ../auth/login.php');
        } else {
            // Fallback to relative path
            header('Location: modules/auth/login.php');
        }
        exit;
    }
    
    // Refresh session periodically
    refresh_session();
}

// Check user role
function has_role($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['role'];
    
    // Admin has access to everything
    if ($user_role === 'admin') {
        return true;
    }
    
    // Check specific role
    if (is_array($required_role)) {
        return in_array($user_role, $required_role);
    }
    
    return $user_role === $required_role;
}

// Require specific role
function require_role($required_role) {
    if (!has_role($required_role)) {
        http_response_code(403);
        die('Access Denied: Insufficient permissions');
    }
}

// Login user with brute force protection
function login_user($pdo, $username, $password) {
    // Check rate limit
    $identifier = get_client_ip() . '_' . $username;
    $rate_check = check_login_rate_limit($identifier);
    
    if (!$rate_check['allowed']) {
        return ['success' => false, 'message' => $rate_check['message']];
    }
    
    try {
        $stmt = $pdo->prepare(
            "SELECT user_id, username, password_hash, full_name, role, is_active 
             FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            record_failed_login($identifier);
            log_security_event($pdo, null, 'FAILED_LOGIN', "Username: $username, IP: " . get_client_ip());
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated'];
        }
        
        if (!verify_password($password, $user['password_hash'])) {
            record_failed_login($identifier);
            log_security_event($pdo, null, 'FAILED_LOGIN', "Username: $username, IP: " . get_client_ip());
            
            $remaining = 5 - ($rate_check['attempts'] + 1);
            if ($remaining > 0) {
                return ['success' => false, 'message' => "Invalid credentials. $remaining attempts remaining."];
            }
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Reset login attempts on success
        reset_login_attempts($identifier);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '' . get_client_ip());
        
        // Update last login
        $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $update->execute([$user['user_id']]);
        
        // Log successful login
        log_security_event($pdo, $user['user_id'], 'LOGIN', 'Successful login from IP: ' . get_client_ip());
        
        return ['success' => true, 'user' => $user];
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'System error occurred'];
    }
}

// Logout user
function logout_user($pdo) {
    if (is_logged_in()) {
        log_security_event($pdo, $_SESSION['user_id'], 'LOGOUT', 'User logged out');
    }
    
    $_SESSION = [];
    session_destroy();
}
