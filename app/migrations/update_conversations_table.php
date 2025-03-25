<?php
require_once dirname(__DIR__, 2) . '/db_config.php';

try {
    $db = getDBConnection();
    
    // Modify title column to allow NULL values
    $db->exec("
        ALTER TABLE conversations 
        MODIFY COLUMN title VARCHAR(255) NULL DEFAULT 'محادثة جديدة'
    ");
    
    echo "Successfully updated conversations table\n";
    
} catch (Exception $e) {
    echo "Error updating conversations table: " . $e->getMessage() . "\n";
} 