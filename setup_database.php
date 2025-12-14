<?php
/**
 * HARAMAYA PHARMA - Database Setup Script
 * 
 * This script initializes the database with the required schema.
 * Run this once to set up your database.
 */

echo "Setting up Haramaya Pharma Database...\n";

// Include database configuration
$pdo = require __DIR__ . '/config/database.php';

// Read and execute schema
$schema = file_get_contents(__DIR__ . '/schema.sql');

// Split schema into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $schema)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
    }
);

try {
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            echo "Executing: " . substr(trim($statement), 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    $pdo->commit();
    echo "\n✅ Database setup completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n\n";
    echo "You can now access the application at: http://localhost:8080\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}