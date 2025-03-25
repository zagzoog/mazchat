<?php
require_once dirname(__DIR__, 2) . '/db_config.php';
require_once dirname(__DIR__) . '/models/Message.php';
require_once dirname(__DIR__) . '/plugins/PluginManager.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($_GET['conversation_id'])) {
            throw new Exception('Conversation ID is required');
        }
        
        $db = getDBConnection();
        
        // Get messages for conversation
        $stmt = $db->prepare("
            SELECT m.*, 
                   CASE WHEN m.role = 'user' THEN TRUE ELSE FALSE END as is_user
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE m.conversation_id = ? 
            AND c.user_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$_GET['conversation_id'], $_SESSION['user_id']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['conversation_id']) || !isset($data['content'])) {
            throw new Exception('Conversation ID and content are required');
        }
        
        $db = getDBConnection();
        
        // Get conversation and its associated plugin
        $stmt = $db->prepare("
            SELECT c.id, c.plugin_id, p.name as plugin_name, p.class_name 
            FROM conversations c
            JOIN plugins p ON c.plugin_id = p.id
            WHERE c.id = ? AND c.user_id = ? AND p.is_active = TRUE
        ");
        $stmt->execute([$data['conversation_id'], $_SESSION['user_id']]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            throw new Exception('Conversation not found or access denied');
        }
        
        // Load and initialize the plugin
        $pluginClass = $conversation['class_name'];
        $pluginFile = dirname(__DIR__) . "/plugins/{$conversation['plugin_name']}/{$pluginClass}.php";
        
        if (!file_exists($pluginFile)) {
            throw new Exception('Plugin file not found');
        }
        
        require_once $pluginFile;
        $pluginInstance = new $pluginClass($conversation['plugin_id']);
        
        // Store user message
        $messageModel = new Message();
        $messageId = $messageModel->create(
            $data['conversation_id'],
            $_SESSION['user_id'],
            $data['content'],
            'user'
        );
        
        // Process message through plugin
        $response = $pluginInstance->processMessage([
            'conversation_id' => $data['conversation_id'],
            'content' => $data['content'],
            'message_id' => $messageId
        ]);

        if ($response === false) {
            throw new Exception('Failed to process message');
        }
        
        // Store AI response
        $messageModel->create(
            $data['conversation_id'],
            $_SESSION['user_id'],
            $response,
            'assistant'
        );
        
        echo json_encode([
            'success' => true,
            'response' => $response
        ]);
        
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