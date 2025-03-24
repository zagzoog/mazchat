<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Create messages table
    $sql = file_get_contents('create_messages_table.sql');
    $db->exec($sql);
    
    echo "Messages table created successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 