<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_config.php';

try {
    $db = getDBConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all conversations
            $stmt = $db->query("
                SELECT c.*, 
                       m.content as last_message,
                       m.created_at as last_message_time
                FROM conversations c
                LEFT JOIN messages m ON m.conversation_id = c.id
                WHERE m.id = (
                    SELECT MAX(id)
                    FROM messages
                    WHERE conversation_id = c.id
                )
                ORDER BY c.updated_at DESC
            ");
            echo json_encode($stmt->fetchAll());
            break;
            
        case 'POST':
            // Create new conversation
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['title'])) {
                throw new Exception("Title is required");
            }
            
            $stmt = $db->prepare("
                INSERT INTO conversations (id, title)
                VALUES (:id, :title)
            ");
            
            $conversationId = 'session_' . time();
            $stmt->execute([
                ':id' => $conversationId,
                ':title' => $data['title']
            ]);
            
            echo json_encode([
                'id' => $conversationId,
                'title' => $data['title']
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 