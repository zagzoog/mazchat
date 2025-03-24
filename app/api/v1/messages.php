<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 3) . '/db_config.php';
require_once dirname(__DIR__, 3) . '/app/plugins/PluginManager.php';
require_once __DIR__ . '/ApiController.php';

class MessagesController extends ApiController {
    private $pluginManager;

    public function __construct() {
        parent::__construct();
        $this->pluginManager = PluginManager::getInstance();
    }

    public function createMessage() {
        if (!$this->requireAuth()) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['conversation_id']) || !isset($data['content'])) {
            $this->sendError('Conversation ID and content are required', 400);
            return;
        }

        try {
            // Check if conversation exists and user has access
            $stmt = $this->db->prepare("
                SELECT id FROM conversations 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$data['conversation_id'], $this->user['id']]);
            if (!$stmt->fetch()) {
                $this->sendError('Conversation not found or access denied', 404);
                return;
            }

            // Let plugins modify the message
            $message = [
                'conversation_id' => $data['conversation_id'],
                'content' => $data['content'],
                'is_user' => true
            ];
            $this->pluginManager->executeHook('before_send_message', [$message]);

            // Create message
            $stmt = $this->db->prepare("
                INSERT INTO messages (id, conversation_id, content, is_user) 
                VALUES (UUID(), ?, ?, ?)
            ");
            $stmt->execute([
                $message['conversation_id'],
                $message['content'],
                $message['is_user']
            ]);

            $this->pluginManager->executeHook('after_send_message', [$message]);

            $this->sendResponse($message, 'Message sent successfully');
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
}

// Route handling
$controller = new MessagesController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $controller->createMessage();
        break;
    default:
        $controller->sendError('Method not allowed', 405);
} 