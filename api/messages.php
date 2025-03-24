<?php
session_start();
require_once '../app/utils/ResponseCompressor.php';
require_once '../db_config.php';
require_once '../app/models/Message.php';
require_once '../app/models/UsageStats.php';
require_once '../app/utils/Cache.php';

// Initialize response compression
$compressor = ResponseCompressor::getInstance();
$compressor->start();

header('Content-Type: application/json; charset=utf-8');

// Enable GZIP compression
if (extension_loaded('zlib')) {
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

try {
    $db = getDBConnection();
    $messageModel = new Message();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get messages for a conversation
            if (!isset($_GET['conversation_id'])) {
                throw new Exception("Conversation ID is required");
            }
            
            $messages = $messageModel->getMessages($_GET['conversation_id'], $_SESSION['user_id']);
            
            // Convert role to is_user for frontend compatibility
            $messages = array_map(function($msg) {
                $msg['is_user'] = $msg['role'] === 'user';
                unset($msg['role']);
                return $msg;
            }, $messages);
            
            echo json_encode($messages);
            break;
            
        case 'POST':
            // Create new message
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['conversation_id']) || !isset($data['content'])) {
                throw new Exception("Conversation ID and content are required");
            }
            
            // Create message using the model
            $messageId = $messageModel->create(
                $data['conversation_id'],
                $_SESSION['user_id'],
                $data['content'],
                isset($data['is_user']) && $data['is_user'] ? 'user' : 'assistant'
            );
            
            // Get the created message
            $messages = $messageModel->getMessages($data['conversation_id'], $_SESSION['user_id']);
            $message = array_filter($messages, function($msg) use ($messageId) {
                return $msg['id'] == $messageId;
            });
            $message = reset($message);
            
            // Convert role to is_user for frontend compatibility
            if ($message) {
                $message['is_user'] = $message['role'] === 'user';
                unset($message['role']);
            }
            
            echo json_encode($message);
            break;
            
        case 'DELETE':
            if (!isset($_GET['message_id']) || !isset($_GET['conversation_id'])) {
                throw new Exception("Message ID and conversation ID are required");
            }
            
            $messageModel->delete($_GET['message_id'], $_GET['conversation_id'], $_SESSION['user_id']);
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in messages API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $compressor->end();
} 