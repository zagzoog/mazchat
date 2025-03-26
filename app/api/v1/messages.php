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

    public function __construct() {
        parent::__construct();
        $this->pluginManager = PluginManager::getInstance();
        // echo "<pre>";
        // print_r($this->pluginManager);
        // echo "</pre>";
        // die;
        
        // Get user ID from session
        $this->userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    private function isAuthenticated() {
        if (!$this->userId) {
            $this->sendError('Unauthorized - Please log in', 401);
            return false;
        }
        return true;
    }

    public function createMessage() {
        error_log("=== Starting message creation process ===");
        
        if (!$this->isAuthenticated()) {
            error_log("Authentication failed - user not logged in");
            return;
        }
        
        error_log("User authenticated successfully - User ID: " . $this->userId);

        $data = json_decode(file_get_contents('php://input'), true);
        error_log("Received message data: " . json_encode($data));
        
        if (!$data || !isset($data['conversation_id']) || !isset($data['content'])) {
            error_log("Invalid message data - missing required fields");
            $this->sendError('Conversation ID and content are required', 400);
            return;
        }
        error_log("Message data validation passed");

        try {
            // Check question limit before proceeding
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

            error_log("Fetching conversation and plugin info for conversation ID: " . $data['conversation_id']);
            // Check if conversation exists and user has access, and get the plugin info
            $stmt = $this->db->prepare("
                SELECT c.id, c.plugin_id, p.name as plugin_name, p.is_active as plugin_active
                FROM conversations c
                LEFT JOIN plugins p ON c.plugin_id = p.id 
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$data['conversation_id'], $this->userId]);
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
            error_log("Created message data structure");

            $pluginResponse = null;
            $assistantMessage = null;

            // Only process through plugin if one is assigned to the conversation
            if ($conversation['plugin_id'] && $conversation['plugin_active']) {
                
                error_log("Attempting to get plugin instance for: " . $conversation['plugin_name']);
                $plugin = $this->pluginManager->getPlugin($conversation['plugin_name']);
                
                if ($plugin) {
                    error_log("Successfully got plugin instance of class: " . get_class($plugin));
                    error_log("Plugin hooks: " . print_r($plugin->getHooks(), true));
                    
                    error_log("Executing before_send_message hook for plugin: " . $conversation['plugin_name']);
                    try {
                        $pluginResponse = $plugin->executeHook('before_send_message', [$message]);
                        error_log("before_send_message hook executed successfully");
                        if ($pluginResponse) {
                            error_log("Got response from before_send_message: " . $pluginResponse);
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

            error_log("Saving message to database");
            // Create message
            $stmt = $this->db->prepare("
                INSERT INTO messages (conversation_id, role, content) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $message['conversation_id'],
                $message['role'],
                $message['content']
            ]);
            $messageId = $this->db->lastInsertId();
            error_log("Message saved successfully with ID: " . $messageId);

            // Process after_send_message hook if plugin exists
            if ($conversation['plugin_id'] && $conversation['plugin_active'] && isset($plugin)) {
                error_log("Executing after_send_message hook for plugin: " . $conversation['plugin_name']);
                try {
                    $afterResponse = $plugin->executeHook('after_send_message', [$message]);
                    error_log("after_send_message hook executed successfully");
                    
                    // Use the response from either hook (prioritize after_send_message)
                    $finalResponse = $afterResponse ?: $pluginResponse;
                    
                    // Save the plugin response as a new message if we got one
                    if ($finalResponse) {
                        error_log("Saving plugin response to database: " . $finalResponse);
                        $stmt = $this->db->prepare("
                            INSERT INTO messages (conversation_id, role, content) 
                            VALUES (?, 'assistant', ?)
                        ");
                        $stmt->execute([
                            $message['conversation_id'],
                            $finalResponse
                        ]);
                        $assistantMessageId = $this->db->lastInsertId();
                        error_log("Plugin response saved with ID: " . $assistantMessageId);
                        
                        // Create the assistant message object for the response
                        $assistantMessage = [
                            'id' => $assistantMessageId,
                            'conversation_id' => $message['conversation_id'],
                            'content' => $finalResponse,
                            'role' => 'assistant'
                        ];
                    }
                } catch (Exception $e) {
                    error_log("ERROR executing after_send_message hook: " . $e->getMessage());
                    error_log($e->getTraceAsString());
                }
            }

            error_log("=== Message creation process completed successfully ===");
            
            // Include both the user message and assistant response in the API response
            $response = [
                'success' => true,
                'data' => [
                    'message' => [
                        'id' => $messageId,
                        'conversation_id' => $message['conversation_id'],
                        'content' => $message['content'],
                        'role' => 'user'
                    ],
                    'assistant_message' => $assistantMessage ? [
                        'id' => $assistantMessage['id'],
                        'conversation_id' => $assistantMessage['conversation_id'],
                        'content' => $assistantMessage['content'],
                        'role' => 'assistant'
                    ] : null
                ]
            ];
            error_log("Sending response to client: " . json_encode($response));
            $this->sendResponse($response, 'Message sent successfully');
        } catch (Exception $e) {
            error_log("ERROR in message creation: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendError($e->getMessage(), 500);
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

// Initialize controller and handle the request
$controller = new MessagesController();
$controller->handleRequest(); 