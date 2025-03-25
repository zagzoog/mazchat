<?php
require_once dirname(__DIR__, 2) . '/db_config.php';

try {
    $db = getDBConnection();
    
    // Check if plugin_id column already exists
    $stmt = $db->query("SHOW COLUMNS FROM conversations LIKE 'plugin_id'");
    if ($stmt->rowCount() === 0) {
        // Add plugin_id column to conversations table
        $db->exec("
            ALTER TABLE conversations 
            ADD COLUMN plugin_id INT UNSIGNED NULL,
            ADD CONSTRAINT fk_conversation_plugin 
            FOREIGN KEY (plugin_id) REFERENCES plugins(id) 
            ON DELETE SET NULL
        ");
        
        echo "Successfully added plugin_id to conversations table\n";
    } else {
        echo "plugin_id column already exists in conversations table\n";
    }
    
} catch (Exception $e) {
    echo "Error adding plugin_id to conversations table: " . $e->getMessage() . "\n";
} 