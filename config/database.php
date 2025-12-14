<?php
/**
 * HARAMAYA PHARMA - Database Configuration
 * 
 * This file contains the database connection settings.
 * For development, we'll use SQLite for simplicity.
 * For production, you can switch to MySQL/MariaDB.
 */
// Database configuration
$config = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'haramaya_pharma',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

try {
    // MySQL connection
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    return $pdo;
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    
    // In development, show the actual error
    if (defined('APP_DEBUG') &&APP_DEBUG) {
        die("Database connection failed: " . $e->getMessage());
    }
    
    // In production, show generic error
    die("Database connection failed. Please contact the administrator.");
}