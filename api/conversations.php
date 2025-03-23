<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db_config.php';
require_once '../app/models/Membership.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDBConnection();
    $membership = new Membership();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all conversations for the current user with pagination
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            error_log("Fetching conversations with limit: $limit, offset: $offset");
            
            // Get total count
            $countStmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM conversations 
                WHERE user_id = ?
            ");
            $countStmt->execute([$_SESSION['user_id']]);
            $total = $countStmt->fetch()['total'];
            
            error_log("Total conversations found: $total");
            
            // Get paginated conversations
            $stmt = $db->prepare("
                SELECT c.*, 
                       (SELECT content FROM messages 
                        WHERE conversation_id = c.id 
                        ORDER BY created_at DESC LIMIT 1) as last_message,
                       (SELECT created_at FROM messages 
                        WHERE conversation_id = c.id 
                        ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM conversations c
                WHERE c.user_id = ?
                ORDER BY c.updated_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
            $conversations = $stmt->fetchAll();
            
            error_log("Fetched " . count($conversations) . " conversations");
            
            $response = [
                'conversations' => $conversations,
                'total' => $total,
                'hasMore' => ($offset + $limit) < $total
            ];
            
            error_log("Sending response: " . json_encode($response));
            echo json_encode($response);
            break;
            
        case 'POST':
            // Check if user has reached their monthly limit
            if (!$membership->checkUsageLimit($_SESSION['user_id'])) {
                error_log("User " . $_SESSION['user_id'] . " has reached their monthly conversation limit");
                http_response_code(403);
                echo json_encode(['error' => 'You have reached your monthly conversation limit. Please upgrade your membership to continue.']);
                exit;
            }
            
            // Create new conversation
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['title'])) {
                throw new Exception("Title is required");
            }
            
            $stmt = $db->prepare("
                INSERT INTO conversations (user_id, title)
                VALUES (?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $data['title']
            ]);
            
            $conversation_id = $db->lastInsertId();
            
            echo json_encode([
                'id' => $conversation_id,
                'user_id' => $_SESSION['user_id'],
                'title' => $data['title']
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in conversations API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 