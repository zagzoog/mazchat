<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/app/utils/Logger.php';

try {
    $db = getDBConnection();
    
    // Get all messages with their conversation data
    $stmt = $db->query("
        SELECT 
            m.*,
            c.user_id,
            c.title as conversation_title
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        ORDER BY m.created_at ASC
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($messages) . " messages to process\n";
    
    // Clear existing usage stats
    $db->exec("TRUNCATE TABLE usage_stats");
    echo "Cleared existing usage stats\n";
    
    // Reset conversation stats
    $db->exec("
        UPDATE conversations 
        SET message_count = 0,
            total_words = 0
    ");
    echo "Reset conversation stats\n";
    
    // Process each message
    foreach ($messages as $message) {
        // Calculate word count
        $wordCount = str_word_count(strip_tags($message['content']));
        
        // Insert into usage_stats
        $stmt = $db->prepare("
            INSERT INTO usage_stats (
                user_id, 
                conversation_id,
                message_id,
                word_count,
                message_type,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $message['user_id'],
            $message['conversation_id'],
            $message['id'],
            $wordCount,
            $message['role'],
            $message['created_at']
        ]);
        
        // Update conversation stats
        $stmt = $db->prepare("
            UPDATE conversations 
            SET 
                message_count = message_count + 1,
                total_words = total_words + ?
            WHERE id = ?
        ");
        $stmt->execute([$wordCount, $message['conversation_id']]);
        
        // Update message word count
        $stmt = $db->prepare("
            UPDATE messages 
            SET word_count = ? 
            WHERE id = ?
        ");
        $stmt->execute([$wordCount, $message['id']]);
        
        echo "Processed message " . $message['id'] . " with " . $wordCount . " words\n";
    }
    
    echo "\nSuccessfully processed all messages\n";
    
    // Show updated stats
    $stmt = $db->query("SELECT COUNT(*) FROM usage_stats");
    echo "\nTotal records in usage_stats: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT conversation_id) as conversations,
            COUNT(*) as total_records,
            SUM(word_count) as total_words
        FROM usage_stats
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total conversations tracked: " . $stats['conversations'] . "\n";
    echo "Total records: " . $stats['total_records'] . "\n";
    echo "Total words: " . $stats['total_words'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 