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
        http_response_code($e->getMessage() === 'You have reached your monthly conversation limit. Please upgrade your membership to continue.' ? 403 : 500);
        echo json_encode([
            'error' => $e->getMessage(),
            'limit_reached' => $e->getMessage() === 'You have reached your monthly conversation limit. Please upgrade your membership to continue.'
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['conversation_id']) || !isset($data['plugin_id'])) {
            throw new Exception('Conversation ID and Plugin ID are required');
        }
        
        // Update conversation's plugin
        $conversation->updatePluginId($data['conversation_id'], $_SESSION['user_id'], $data['plugin_id']);
        
        echo json_encode([
            'success' => true
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($_GET['conversation_id'])) {
            // Get all conversations
            $conversations = $conversation->getAll($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
        } else {
            // Get specific conversation
            $conversationData = $conversation->getById($_GET['conversation_id'], $_SESSION['user_id']);
            if (!$conversationData) {
                throw new Exception('Conversation not found');
            }
            echo json_encode([
                'success' => true,
                'data' => $conversationData
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
    echo json_encode([
        'error' => 'Method not allowed'
    ]);
} 