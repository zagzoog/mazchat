<?php
require_once 'db_config.php';

$db = getDBConnection();

// Get all conversations with their plugin associations
$stmt = $db->query("
    SELECT 
        c.id as conversation_id,
        c.title,
        c.plugin_id,
        p.name as plugin_name,
        p.is_active as plugin_active,
        c.created_at,
        c.updated_at
    FROM conversations c
    LEFT JOIN plugins p ON c.plugin_id = p.id
    ORDER BY c.created_at DESC
    LIMIT 5
");

$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== Recent Conversations ===\n";
foreach ($conversations as $conv) {
    echo "\nConversation ID: " . $conv['conversation_id'] . "\n";
    echo "Title: " . $conv['title'] . "\n";
    echo "Plugin ID: " . ($conv['plugin_id'] ?? 'none') . "\n";
    echo "Plugin Name: " . ($conv['plugin_name'] ?? 'none') . "\n";
    echo "Plugin Active: " . ($conv['plugin_active'] ? 'yes' : 'no') . "\n";
    echo "Created: " . $conv['created_at'] . "\n";
    echo "Updated: " . $conv['updated_at'] . "\n";
    echo "------------------------\n";
}

// Get the last message sent
$stmt = $db->query("
    SELECT 
        m.*,
        c.plugin_id,
        p.name as plugin_name,
        p.is_active as plugin_active
    FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    LEFT JOIN plugins p ON c.plugin_id = p.id
    ORDER BY m.created_at DESC
    LIMIT 1
");

$lastMessage = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n=== Last Message ===\n";
if ($lastMessage) {
    echo "Message ID: " . $lastMessage['id'] . "\n";
    echo "Conversation ID: " . $lastMessage['conversation_id'] . "\n";
    echo "Content: " . $lastMessage['content'] . "\n";
    echo "Role: " . $lastMessage['role'] . "\n";
    echo "Plugin ID: " . ($lastMessage['plugin_id'] ?? 'none') . "\n";
    echo "Plugin Name: " . ($lastMessage['plugin_name'] ?? 'none') . "\n";
    echo "Plugin Active: " . ($lastMessage['plugin_active'] ? 'yes' : 'no') . "\n";
    echo "Created: " . $lastMessage['created_at'] . "\n";
} else {
    echo "No messages found\n";
} 