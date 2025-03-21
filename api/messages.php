<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDBConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get messages for a conversation
            if (!isset($_GET['conversation_id'])) {
                throw new Exception("Conversation ID is required");
            }
            
            // Verify conversation ownership
            $stmt = $db->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
            $stmt->execute([$_GET['conversation_id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Conversation not found or access denied");
            }
            
            // Get messages
            $stmt = $db->prepare("
                SELECT * FROM messages 
                WHERE conversation_id = ? 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$_GET['conversation_id']]);
            $messages = $stmt->fetchAll();
            
            // Log the messages being returned
            error_log("Retrieved messages for conversation " . $_GET['conversation_id'] . ": " . json_encode($messages));
            
            echo json_encode($messages);
            break;
            
        case 'POST':
            // Add new message
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Log the incoming data
            error_log("Received message data: " . json_encode($data));
            
            if (!isset($data['conversation_id']) || !isset($data['content']) || !isset($data['is_user'])) {
                throw new Exception("Missing required fields");
            }
            
            // Verify conversation ownership
            $stmt = $db->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['conversation_id'], $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Conversation not found or access denied");
            }
            
            $message_id = uniqid();
            $stmt = $db->prepare("
                INSERT INTO messages (id, conversation_id, content, is_user)
                VALUES (?, ?, ?, ?)
            ");
            
            // Convert is_user to proper boolean value
            $is_user = filter_var($data['is_user'], FILTER_VALIDATE_BOOLEAN);
            
            $params = [
                $message_id,
                $data['conversation_id'],
                $data['content'],
                $is_user ? 1 : 0  // Convert to MySQL boolean (1 or 0)
            ];
            
            // Log the parameters being used
            error_log("Inserting message with params: " . json_encode($params));
            
            $stmt->execute($params);
            
            // Log the inserted message
            error_log("Successfully inserted message with ID: " . $message_id);
            
            // Update conversation title if it's the first message
            if ($data['is_user']) {
                $stmt = $db->prepare("
                    UPDATE conversations 
                    SET title = ? 
                    WHERE id = ? 
                    AND (SELECT COUNT(*) FROM messages WHERE conversation_id = ?) = 1
                ");
                $title = substr($data['content'], 0, 30) . '...';
                $stmt->execute([$title, $data['conversation_id'], $data['conversation_id']]);
            }
            
            $response = [
                'id' => $message_id,
                'conversation_id' => $data['conversation_id'],
                'content' => $data['content'],
                'is_user' => $data['is_user']
            ];
            
            // Log the response being sent back
            error_log("Sending response: " . json_encode($response));
            
            echo json_encode($response);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 