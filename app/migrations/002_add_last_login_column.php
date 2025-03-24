<?php
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/../utils/Logger.php';

try {
    // Initialize database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Add last_login column to users table
    $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER created_at");
    
    Logger::log("Added last_login column to users table", 'INFO');
    echo "Migration completed successfully\n";
} catch (PDOException $e) {
    Logger::log("Error in migration: " . $e->getMessage(), 'ERROR');
    echo "Error: " . $e->getMessage() . "\n";
} 