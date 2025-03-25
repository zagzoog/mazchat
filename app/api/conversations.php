<?php
require_once dirname(__DIR__, 2) . '/db_config.php';
require_once dirname(__DIR__) . '/models/Conversation.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conversation = new Conversation();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['plugin_id'])) {
            throw new Exception('Plugin ID is required');
        }
        
        // Create new conversation
        $conversationId = $conversation->create($_SESSION['user_id'], $data['plugin_id']);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $conversationId
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (isset($_GET['id'])) {
            // Get specific conversation
            $conv = $conversation->getById($_GET['id'], $_SESSION['user_id']);
            if (!$conv) {
                http_response_code(404);
                echo json_encode(['error' => 'Conversation not found']);
                exit;
            }
            echo json_encode([
                'success' => true,
                'data' => $conv
            ]);
        } else {
            // Get all conversations
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $result = $conversation->getByUserId($_SESSION['user_id'], $limit, $offset);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} 