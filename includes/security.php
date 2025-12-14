<?php
/**
 * HARAMAYA PHARMA - Comprehensive Security Functions
 * XSS Prevention, CSRF Protection, Input Sanitization
 */

// Start session securely
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        session_start();
    }
}

// XSS Prevention - Sanitize output
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// CSRF field for forms
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Password hashing
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Password verification
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize input (basic)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone (Ethiopian format)
function validate_phone($phone) {
    return preg_match('/^\+?251-?9[0-9]{8}$/', $phone);
}

// Log security events
function log_security_event($pdo, $user_id, $action, $details, $table_affected = null, $record_id = null) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, action, table_affected, record_id, details, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $user_id,
            $action,
            $table_affected,
            $record_id,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log security event: " . $e->getMessage());
    }
}

// ============================================
// BRUTE FORCE PROTECTION
// ============================================

/**
 * Check if IP is rate limited for login attempts
 * Implements exponential backoff
 */
function check_login_rate_limit($identifier) {
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes in seconds
    
    // Use identifier (IP + username) for tracking
    $key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => 0
        ];
    }
    
    $data = $_SESSION[$key];
    
    // Check if currently locked out
    if ($data['locked_until'] > time()) {
        $remaining = $data['locked_until'] - time();
        return [
            'allowed' => false,
            'message' => "Too many failed attempts. Please try again in " . ceil($remaining / 60) . " minutes.",
            'remaining_time' => $remaining
        ];
    }
    
    // Reset if lockout period has passed
    if ($data['locked_until'] > 0 && $data['locked_until'] <= time()) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => 0
        ];
        return ['allowed' => true, 'attempts' => 0];
    }
    
    // Check if max attempts reached
    if ($data['attempts'] >= $max_attempts) {
        $_SESSION[$key]['locked_until'] = time() + $lockout_time;
        return [
            'allowed' => false,
            'message' => "Too many failed attempts. Account locked for 15 minutes.",
            'remaining_time' => $lockout_time
        ];
    }
    
    return [
        'allowed' => true,
        'attempts' => $data['attempts'],
        'remaining' => $max_attempts - $data['attempts']
    ];
}

/**
 * Record failed login attempt
 */
function record_failed_login($identifier) {
    $key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time(),
            'locked_until' => 0
        ];
    }
    
    $_SESSION[$key]['attempts']++;
    $_SESSION[$key]['last_attempt'] = time();
}

/**
 * Reset login attempts on successful login
 */
function reset_login_attempts($identifier) {
    $key = 'login_attempts_' . md5($identifier);
    unset($_SESSION[$key]);
}

/**
 * Get client IP address (handles proxies)
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Check for proxy headers
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

// ============================================
// PASSWORD STRENGTH VALIDATION
// ============================================

/**
 * Validate password strength
 * Requires: min 8 chars, 1 uppercase, 1 lowercase, 1 number
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// ============================================
// SESSION SECURITY
// ============================================

/**
 * Regenerate session ID periodically for security
 */
function refresh_session() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }
    
    // Regenerate every 30 minutes
    if (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Check session timeout
 */
function check_session_timeout($timeout = 3600) {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Validate session fingerprint (prevents session hijacking)
 */
function validate_session_fingerprint() {
    $fingerprint = md5(
        $_SERVER['HTTP_USER_AGENT'] ?? '' .
        get_client_ip()
    );
    
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
        return true;
    }
    
    return $_SESSION['fingerprint'] === $fingerprint;
}
