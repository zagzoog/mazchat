<?php
require_once __DIR__ . '/db_config.php';

try {
    $db = getDBConnection();
    
    // Drop and recreate usage_stats table
    $db->exec("DROP TABLE IF EXISTS usage_stats");
    $db->exec("
        CREATE TABLE usage_stats (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            conversation_id INT UNSIGNED NOT NULL,
            message_id INT UNSIGNED NOT NULL,
            word_count INT UNSIGNED NOT NULL DEFAULT 0,
            topic VARCHAR(255) NULL,
            message_type ENUM('user', 'assistant') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
        )
    ");
    echo "Recreated usage_stats table\n";
    
    // Check if columns exist in conversations table
    $stmt = $db->query("DESCRIBE conversations");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    // Add message_count if it doesn't exist
    if (!in_array('message_count', $columns)) {
        $db->exec("
            ALTER TABLE conversations
            ADD COLUMN message_count INT UNSIGNED NOT NULL DEFAULT 0
        ");
        echo "Added message_count column to conversations\n";
    }
    
    // Add total_words if it doesn't exist
    if (!in_array('total_words', $columns)) {
        $db->exec("
            ALTER TABLE conversations
            ADD COLUMN total_words INT UNSIGNED NOT NULL DEFAULT 0
        ");
        echo "Added total_words column to conversations\n";
    }
    
    // Check if word_count column exists in messages table
    $stmt = $db->query("DESCRIBE messages");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    // Add word_count if it doesn't exist
    if (!in_array('word_count', $columns)) {
        $db->exec("
            ALTER TABLE messages
            ADD COLUMN word_count INT UNSIGNED NOT NULL DEFAULT 0
        ");
        echo "Added word_count column to messages\n";
    }
    
    echo "Migration completed successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 