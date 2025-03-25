<?php
require_once 'db_config.php';

try {
    $db = getDBConnection();
    
    // Check recent messages
    $stmt = $db->query("
        SELECT m.*, c.plugin_id, p.name as plugin_name
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        JOIN plugins p ON c.plugin_id = p.id
        ORDER BY m.created_at DESC
        LIMIT 5
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent messages:\n";
    foreach ($messages as $msg) {
        echo "Message ID: " . $msg['id'] . "\n";
        echo "Conversation ID: " . $msg['conversation_id'] . "\n";
        echo "Plugin: " . $msg['plugin_name'] . " (ID: " . $msg['plugin_id'] . ")\n";
        echo "Content: " . substr($msg['content'], 0, 100) . (strlen($msg['content']) > 100 ? '...' : '') . "\n";
        echo "Role: " . $msg['role'] . "\n";
        echo "Created At: " . $msg['created_at'] . "\n";
        echo "-------------------\n";
    }
    
    if (empty($messages)) {
        echo "No messages found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 