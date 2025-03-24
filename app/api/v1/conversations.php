<?php
require_once __DIR__ . '/../ApiController.php';

class ConversationsController extends ApiController {
    public function getConversations() {
        if (!$this->requireAuth()) {
            return;
        }

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Get conversations for the user
        $stmt = $this->db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count,
                   (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
            FROM conversations c
            WHERE c.user_id = ?
            ORDER BY c.updated_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$this->user['id'], $limit, $offset]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM conversations c
            WHERE c.user_id = ?
        ");
        $stmt->execute([$this->user['id']]);
        $total = $stmt->fetchColumn();

        $this->sendResponse([
            'conversations' => $conversations,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function createConversation() {
        if (!$this->requireAuth()) {
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            error_log("Creating conversation with data: " . print_r($data, true));
            error_log("User ID: " . $this->user['id']);
            
            $this->validateInput($data, [
                'title' => 'required|min:1|max:100'
            ]);

            // Generate UUID for conversation ID
            $conversationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            error_log("Generated conversation ID: " . $conversationId);

            // Create conversation
            $stmt = $this->db->prepare("
                INSERT INTO conversations (id, user_id, title)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$conversationId, $this->user['id'], $data['title']]);

            // Get the created conversation
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count,
                       (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
                FROM conversations c
                WHERE c.id = ?
            ");
            $stmt->execute([$conversationId]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Created conversation: " . print_r($conversation, true));

            $this->sendResponse($conversation, 'Conversation created successfully');
        } catch (Exception $e) {
            error_log("Error creating conversation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError('Failed to create conversation: ' . $e->getMessage(), 500);
        }
    }

    public function getConversation($id) {
        if (!$this->requireAuth()) {
            return;
        }

        // Check if user has access to this conversation
        $stmt = $this->db->prepare("
            SELECT c.* FROM conversations c
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$id, $this->user['id']]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$conversation) {
            $this->sendError('Conversation not found or access denied', 404);
        }

        // Get participants
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.role
            FROM conversation_participants cp
            JOIN users u ON cp.user_id = u.id
            WHERE cp.conversation_id = ?
        ");
        $stmt->execute([$id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conversation['participants'] = $participants;

        $this->sendResponse($conversation);
    }

    public function addParticipant($id) {
        if (!$this->requireAuth()) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        $this->validateInput($data, [
            'user_id' => 'required'
        ]);

        // Check if user has access to this conversation
        $stmt = $this->db->prepare("
            SELECT c.* FROM conversations c
            JOIN conversation_participants cp ON c.id = cp.conversation_id
            WHERE c.id = ? AND cp.user_id = ?
        ");
        $stmt->execute([$id, $this->user['id']]);
        if (!$stmt->fetch()) {
            $this->sendError('Access denied', 403);
        }

        // Check if user is already a participant
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM conversation_participants
            WHERE conversation_id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $data['user_id']]);
        if ($stmt->fetchColumn() > 0) {
            $this->sendError('User is already a participant', 400);
        }

        // Add participant
        $stmt = $this->db->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$id, $data['user_id']]);

        $this->sendResponse(null, 'Participant added successfully');
    }
}

// Route handling
$controller = new ConversationsController();
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$id = null;

// Extract conversation ID from URI if present
if (preg_match('/\/conversations\/(\d+)/', $uri, $matches)) {
    $id = $matches[1];
}

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getConversation($id);
        } else {
            $controller->getConversations();
        }
        break;
    case 'POST':
        if ($id && strpos($uri, '/participants') !== false) {
            $controller->addParticipant($id);
        } else {
            $controller->createConversation();
        }
        break;
    default:
        $controller->sendError('Method not allowed', 405);
} 