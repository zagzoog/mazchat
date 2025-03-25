<?php
require_once __DIR__ . '/../db_config.php';

try {
    $db = getDBConnection();
    
    // Check if role column exists
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'role'");
    if ($stmt->rowCount() == 0) {
        // Add role column if it doesn't exist
        $db->exec("ALTER TABLE messages ADD COLUMN role ENUM('user', 'assistant') NOT NULL DEFAULT 'user'");
        echo "Added role column to messages table\n";
    }
    
    // Check if is_user column exists
    $stmt = $db->query("SHOW COLUMNS FROM messages LIKE 'is_user'");
    if ($stmt->rowCount() > 0) {
        // Migrate data from is_user to role
        $db->exec("UPDATE messages SET role = CASE WHEN is_user = 1 THEN 'user' ELSE 'assistant' END");
        // Drop is_user column
        $db->exec("ALTER TABLE messages DROP COLUMN is_user");
        echo "Successfully migrated data from is_user to role column\n";
    } else {
        echo "is_user column does not exist, skipping migration\n";
    }
    
    // Check if index exists
    $stmt = $db->query("SHOW INDEX FROM messages WHERE Key_name = 'idx_conversation_role'");
    if ($stmt->rowCount() == 0) {
        // Add index for better performance
        $db->exec("CREATE INDEX idx_conversation_role ON messages (conversation_id, role)");
        echo "Successfully added index on conversation_id and role\n";
    } else {
        echo "Index already exists, skipping\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
} 