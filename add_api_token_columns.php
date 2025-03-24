<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Add api_token and api_token_expires columns to users table
    $db->exec("
        ALTER TABLE users 
        ADD COLUMN api_token VARCHAR(255) NULL,
        ADD COLUMN api_token_expires TIMESTAMP NULL
    ");
    
    echo "Added API token columns successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 