<?php
session_start();
require_once '../app/utils/ResponseCompressor.php';
require_once '../db_config.php';
require_once '../app/models/Message.php';
require_once '../app/models/UsageStats.php';
require_once '../app/utils/Cache.php';
require_once '../app/models/Conversation.php';
require_once '../app/utils/Logger.php';

// Initialize response compression
$compressor = ResponseCompressor::getInstance();
$compressor->start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Only enable zlib compression if no output handler is active
if (extension_loaded('zlib') && !ob_get_level()) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', '5');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    $compressor->end();
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDBConnection();
    $messageModel = new Message();
    
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Get conversation ID from query parameters
            $conversationId = $_GET['conversation_id'] ?? null;
            
            if (!$conversationId) {
                throw new Exception('Conversation ID is required');
            }
            
            // Get messages
            $messages = $messageModel->getMessages($conversationId, $userId);
            
            // Convert role to is_user for frontend compatibility
            $messages = array_map(function($msg) {
                $msg['is_user'] = $msg['role'] === 'user';
                unset($msg['role']);
                return $msg;
            }, $messages);
            
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;
            
        case 'POST':
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['conversation_id']) || !isset($data['content'])) {
                throw new Exception('Missing required fields');
            }
            
            $conversationId = $data['conversation_id'];
            $content = $data['content'];
            
            // Create message
            $messageId = $messageModel->create($conversationId, $userId, $content);
            
            // Get the created message
            $message = $messageModel->getById($messageId);
            
            // Convert role to is_user for frontend compatibility
            if ($message) {
                $message['is_user'] = $message['role'] === 'user';
                unset($message['role']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            break;
            
        case 'DELETE':
            // Get message ID from query parameters
            $messageId = $_GET['id'] ?? null;
            $conversationId = $_GET['conversation_id'] ?? null;
            
            if (!$messageId || !$conversationId) {
                throw new Exception('Message ID and conversation ID are required');
            }
            
            // Delete message
            $success = $messageModel->delete($messageId, $conversationId, $userId);
            
            echo json_encode([
                'success' => $success
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("API Error in messages.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    $compressor->end();
} 