<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Create api_keys table
    $db->exec("
        CREATE TABLE IF NOT EXISTS api_keys (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            api_key VARCHAR(255) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            last_used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_api_key (api_key)
        )
    ");
    
    echo "API keys table created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error creating API keys table: " . $e->getMessage() . "\n";
    exit(1);
} 