<?php
/**
 * HARAMAYA PHARMA - Database Connection
 * Secure PDO connection with error handling
 */

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration (prevent redefinition)
// Check for Heroku DATABASE_URL first
if (isset($_ENV['DATABASE_URL'])) {
    $url = parse_url($_ENV['DATABASE_URL']);
    if (!defined('DB_HOST')) define('DB_HOST', $url['host']);
    if (!defined('DB_NAME')) define('DB_NAME', ltrim($url['path'], '/'));
    if (!defined('DB_USER')) define('DB_USER', $url['user']);
    if (!defined('DB_PASS')) define('DB_PASS', $url['pass']);
} else {
    // Fallback to individual environment variables
    if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'haramaya_pharma');
    if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? '');
}
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// PDO options for security and performance
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $options
    );
} catch (PDOException $e) {
    // Log error securely (don't expose to users)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show generic error to user
    die("Database connection failed. Please contact system administrator.");
}

return $pdo;
