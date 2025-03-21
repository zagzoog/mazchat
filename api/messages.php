<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_config.php';

try {
    $db = getDBConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get messages for a conversation
            if (!isset($_GET['conversation_id'])) {
                throw new Exception("Conversation ID is required");
            }
            
            $stmt = $db->prepare("
                SELECT * FROM messages 
                WHERE conversation_id = :conversation_id 
                ORDER BY created_at ASC
            ");
            $stmt->execute([':conversation_id' => $_GET['conversation_id']]);
            echo json_encode($stmt->fetchAll());
            break;
            
        case 'POST':
            // Add new message
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['conversation_id']) || !isset($data['content']) || !isset($data['is_user'])) {
                throw new Exception("Missing required fields");
            }
            
            $stmt = $db->prepare("
                INSERT INTO messages (conversation_id, content, is_user)
                VALUES (:conversation_id, :content, :is_user)
            ");
            
            $stmt->execute([
                ':conversation_id' => $data['conversation_id'],
                ':content' => $data['content'],
                ':is_user' => $data['is_user']
            ]);
            
            // Update conversation title if it's the first message
            if ($data['is_user']) {
                $stmt = $db->prepare("
                    UPDATE conversations 
                    SET title = :title 
                    WHERE id = :conversation_id 
                    AND (SELECT COUNT(*) FROM messages WHERE conversation_id = :conversation_id) = 1
                ");
                $title = substr($data['content'], 0, 30) . '...';
                $stmt->execute([
                    ':conversation_id' => $data['conversation_id'],
                    ':title' => $title
                ]);
            }
            
            echo json_encode([
                'id' => $db->lastInsertId(),
                'conversation_id' => $data['conversation_id'],
                'content' => $data['content'],
                'is_user' => $data['is_user']
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