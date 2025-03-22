<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/app/utils/Logger.php';

try {
    $db = getDBConnection();
    
    // Check total records
    $stmt = $db->query("SELECT COUNT(*) FROM usage_stats");
    $totalRecords = $stmt->fetchColumn();
    echo "Total records in usage_stats: " . $totalRecords . "\n";
    
    // Check records for current user
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $db->prepare("SELECT COUNT(*) FROM usage_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userRecords = $stmt->fetchColumn();
        echo "Records for current user: " . $userRecords . "\n";
        
        // Show sample records
        $stmt = $db->prepare("
            SELECT us.*, c.title as conversation_title 
            FROM usage_stats us 
            JOIN conversations c ON us.conversation_id = c.id 
            WHERE us.user_id = ? 
            ORDER BY us.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nSample records:\n";
        foreach ($records as $record) {
            echo "Conversation: " . $record['conversation_title'] . "\n";
            echo "Word count: " . $record['word_count'] . "\n";
            echo "Created at: " . $record['created_at'] . "\n";
            echo "-------------------\n";
        }
    }
    
    // Check if conversations table has records
    $stmt = $db->query("SELECT COUNT(*) FROM conversations");
    $totalConversations = $stmt->fetchColumn();
    echo "\nTotal conversations: " . $totalConversations . "\n";
    
    // Check if messages table has records
    $stmt = $db->query("SELECT COUNT(*) FROM messages");
    $totalMessages = $stmt->fetchColumn();
    echo "Total messages: " . $totalMessages . "\n";
    
    // Check monthly stats for user 1
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT conversation_id) as total_conversations,
            COUNT(*) as total_questions,
            SUM(word_count) as total_words
        FROM usage_stats
        WHERE user_id = ? 
        AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $stmt->execute([1]);
    $monthlyStats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nMonthly stats for user 1:\n";
    echo "Total conversations: " . $monthlyStats['total_conversations'] . "\n";
    echo "Total questions: " . $monthlyStats['total_questions'] . "\n";
    echo "Total words: " . $monthlyStats['total_words'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 