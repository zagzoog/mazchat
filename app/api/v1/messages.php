<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add logging configuration
$logFile = dirname(__DIR__, 3) . '/logs/messages.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}
ini_set('error_log', $logFile);
error_log("Log file location: " . ini_get('error_log'));

session_start();
require_once dirname(__DIR__, 3) . '/db_config.php';
require_once dirname(__DIR__, 3) . '/app/plugins/PluginManager.php';
require_once __DIR__ . '/ApiController.php';


class MessagesController extends ApiController {
    private $pluginManager;
    private $userId;
    protected $isTestMode = false;

    public function __construct($isTest = false) {
        parent::__construct($isTest);
        $this->pluginManager = PluginManager::getInstance();
        $this->isTestMode = $isTest;
        
        // Get user ID from session
        $this->userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    private function isAuthenticated() {
        if ($this->isTestMode) {
            return true;
        }
        
        if (!$this->userId) {
            $this->sendError('Unauthorized - Please log in', 401);
            return false;
        }
        return true;
    }

    public function createMessage() {
        error_log("=== Starting message creation process ===");
        error_log("Session data: " . print_r($_SESSION, true));
        
        if (!$this->isAuthenticated()) {
            error_log("Authentication failed - user not logged in");
            return;
        }
        
        error_log("User authenticated successfully - User ID: " . $this->userId);

        $rawInput = file_get_contents('php://input');
        error_log("Raw input received: " . $rawInput);
        
        $data = json_decode($rawInput, true);
        error_log("Decoded message data: " . json_encode($data));
        
        if (!$data || !isset($data['conversation_id']) || !isset($data['content'])) {
            error_log("Invalid message data - missing required fields");
            error_log("Data received: " . print_r($data, true));
            $this->sendError('Conversation ID and content are required', 400);
            return;
        }
        error_log("Message data validation passed");

        try {
            // Skip question limit check in test mode
            if (!$this->isTestMode) {
                error_log("Checking question limit for user: " . $this->userId);
                require_once dirname(__DIR__, 3) . '/app/models/Membership.php';
                $membership = new Membership();
                if (!$membership->checkQuestionLimit($this->userId)) {
                    error_log("User " . $this->userId . " has reached their monthly question limit");
                    $this->sendError('You have reached your monthly question limit. Please upgrade your membership to continue.', 403, [
                        'limit_reached' => true,
                        'limit_type' => 'questions'
                    ]);
                    return;
                }
                error_log("Question limit check passed");
            }

            error_log("Fetching conversation and plugin info for conversation ID: " . $data['conversation_id']);
            // Check if conversation exists and user has access, and get the plugin info
            $stmt = $this->db->prepare("
                SELECT c.id, c.plugin_id, p.name as plugin_name, p.is_active as plugin_active
                FROM conversations c
                LEFT JOIN plugins p ON c.plugin_id = p.id 
                WHERE c.id = ? AND (c.user_id = ? OR ? = true)
            ");
            error_log("Executing query with params: " . print_r([$data['conversation_id'], $this->userId, $this->isTestMode], true));
            $stmt->execute([$data['conversation_id'], $this->userId, $this->isTestMode]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$conversation) {
                error_log("Conversation not found or access denied for ID: " . $data['conversation_id']);
                $this->sendError('Conversation not found or access denied', 404);
                return;
            }
            error_log("Found conversation - Plugin ID: " . ($conversation['plugin_id'] ?? 'none') . 
                     ", Plugin Name: " . ($conversation['plugin_name'] ?? 'none') . 
                     ", Plugin Active: " . ($conversation['plugin_active'] ? 'yes' : 'no'));

            // Create message data
            $message = [
                'conversation_id' => $data['conversation_id'],
                'content' => $data['content'],
                'role' => 'user'
            ];
            error_log("Created message data structure: " . print_r($message, true));

            $pluginResponse = null;
            $assistantMessage = null;

            // Process through plugin if one is assigned to the conversation
            if ($conversation['plugin_id'] && $conversation['plugin_active']) {
                error_log("Attempting to get plugin instance for: " . $conversation['plugin_name']);
                $plugin = $this->pluginManager->getPlugin($conversation['plugin_name']);
                
                if ($plugin) {
                    error_log("Successfully got plugin instance of class: " . get_class($plugin));
                    error_log("Plugin hooks: " . print_r($plugin->getHooks(), true));
                    
                    // Execute before_send_message hook
                    error_log("Executing before_send_message hook for plugin: " . $conversation['plugin_name']);
                    try {
                        $pluginResponse = $plugin->executeHook('before_send_message', [$message]);
                        error_log("before_send_message hook executed successfully");
                        if ($pluginResponse) {
                            error_log("Got response from before_send_message: " . $pluginResponse);
                            
                            // Save the before_send_message response
                            $stmt = $this->db->prepare("
                                INSERT INTO messages (conversation_id, role, content) 
                                VALUES (?, 'assistant', ?)
                            ");
                            error_log("Executing before hook message insert with params: " . print_r([$message['conversation_id'], $pluginResponse], true));
                            $stmt->execute([
                                $message['conversation_id'],
                                $pluginResponse
                            ]);
                            $beforeMessageId = $this->db->lastInsertId();
                            error_log("Before hook response saved with ID: " . $beforeMessageId);
                        }
                    } catch (Exception $e) {
                        error_log("ERROR executing before_send_message hook: " . $e->getMessage());
                        error_log($e->getTraceAsString());
                    }
                } else {
                    error_log("ERROR: Plugin instance not found for: " . $conversation['plugin_name']);
                }
            } else {
                error_log("No active plugin for this conversation - plugin_id: " . ($conversation['plugin_id'] ?? 'none') . 
                         ", plugin_active: " . ($conversation['plugin_active'] ?? 'no'));
            }

            error_log("Saving user message to database");
            // Create user message
            $stmt = $this->db->prepare("
                INSERT INTO messages (conversation_id, role, content) 
                VALUES (?, ?, ?)
            ");
            error_log("Executing user message insert with params: " . print_r([$message['conversation_id'], $message['role'], $message['content']], true));
            $stmt->execute([
                $message['conversation_id'],
                $message['role'],
                $message['content']
            ]);
            $messageId = $this->db->lastInsertId();
            error_log("User message saved successfully with ID: " . $messageId);

            // Process after_send_message hook if plugin exists
            if ($plugin) {
                error_log("Executing after_send_message hook for plugin: " . $conversation['plugin_name']);
                try {
                    $afterResponse = $plugin->executeHook('after_send_message', [$message]);
                    error_log("after_send_message hook executed successfully");
                    
                    // Save the after_send_message response
                    if ($afterResponse) {
                        error_log("Saving after_send_message response to database: " . $afterResponse);
                        $stmt = $this->db->prepare("
                            INSERT INTO messages (conversation_id, role, content) 
                            VALUES (?, 'assistant', ?)
                        ");
                        error_log("Executing after hook message insert with params: " . print_r([$message['conversation_id'], $afterResponse], true));
                        $stmt->execute([
                            $message['conversation_id'],
                            $afterResponse
                        ]);
                        $afterMessageId = $this->db->lastInsertId();
                        error_log("After hook response saved with ID: " . $afterMessageId);
                        
                        // Use the after_send_message response as the final assistant message
                        $assistantMessage = [
                            'id' => $afterMessageId,
                            'conversation_id' => $message['conversation_id'],
                            'content' => $afterResponse,
                            'role' => 'assistant'
                        ];
                    }
                } catch (Exception $e) {
                    error_log("ERROR executing after_send_message hook: " . $e->getMessage());
                    error_log($e->getTraceAsString());
                }
            }

            // Prepare response
            $response = [
                'success' => true,
                'data' => [
                    'message_id' => $messageId,
                    'assistant_message' => $assistantMessage
                ]
            ];
            error_log("Sending response: " . print_r($response, true));
            $this->sendResponse($response);
            
        } catch (Exception $e) {
            error_log("ERROR in createMessage: " . $e->getMessage());
            error_log($e->getTraceAsString());
            $this->sendError('Error creating message: ' . $e->getMessage(), 500);
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'POST':
                $this->createMessage();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    public function processMessage($message) {
        // Modify the message or generate a response
        $message['content'] = "Modified: " . $message['content'];
        return $message; // Return the modified message
    }
}

// Handle the request
if (php_sapi_name() !== 'cli') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new MessagesController();
        $controller->createMessage();
    } else {
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
            'data' => null
        ]);
    }
} 