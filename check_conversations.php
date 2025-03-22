<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/app/utils/Logger.php';

try {
    $db = getDBConnection();
    
    // Check total conversations
    $stmt = $db->query("SELECT COUNT(*) FROM conversations");
    $totalConversations = $stmt->fetchColumn();
    echo "Total conversations in database: " . $totalConversations . "\n";
    
    if ($totalConversations > 0) {
        // Show sample conversations
        $stmt = $db->query("
            SELECT c.*, 
                   COUNT(m.id) as message_count,
                   MAX(m.created_at) as last_message_time
            FROM conversations c
            LEFT JOIN messages m ON c.id = m.conversation_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
            LIMIT 5
        ");
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nSample conversations:\n";
        foreach ($conversations as $conv) {
            echo "ID: " . $conv['id'] . "\n";
            echo "Title: " . $conv['title'] . "\n";
            echo "User ID: " . $conv['user_id'] . "\n";
            echo "Message count: " . $conv['message_count'] . "\n";
            echo "Created at: " . $conv['created_at'] . "\n";
            echo "Last message: " . $conv['last_message_time'] . "\n";
            echo "-------------------\n";
        }
    }
    
    // Check messages
    $stmt = $db->query("SELECT COUNT(*) FROM messages");
    $totalMessages = $stmt->fetchColumn();
    echo "\nTotal messages in database: " . $totalMessages . "\n";
    
    if ($totalMessages > 0) {
        // Show sample messages
        $stmt = $db->query("
            SELECT m.*, c.title as conversation_title
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            ORDER BY m.created_at DESC
            LIMIT 5
        ");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nSample messages:\n";
        foreach ($messages as $msg) {
            echo "ID: " . $msg['id'] . "\n";
            echo "Conversation: " . $msg['conversation_title'] . "\n";
            echo "Role: " . $msg['role'] . "\n";
            echo "Content: " . substr($msg['content'], 0, 50) . "...\n";
            echo "Created at: " . $msg['created_at'] . "\n";
            echo "-------------------\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 