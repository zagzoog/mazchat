<?php
session_start();
require_once '../app/utils/ResponseCompressor.php';
require_once '../db_config.php';
require_once '../app/utils/Logger.php';
require_once '../app/models/Model.php';
require_once '../app/models/User.php';
require_once '../app/models/Conversation.php';

// Initialize response compression
$compressor = ResponseCompressor::getInstance();
$compressor->start();

header('Content-Type: application/json; charset=utf-8');

// Check authentication (both session and token-based)
$user_id = null;
$db = getDBConnection();

// First check for API token
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
    $token = $matches[1];
    
    // Verify token
    $stmt = $db->prepare("
        SELECT id 
        FROM users 
        WHERE api_token = ? 
        AND api_token_expires > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $user_id = $user['id'];
    }
}

// If no valid token, check session
if (!$user_id && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

// If still no valid user, return unauthorized
if (!$user_id) {
    Logger::log('Unauthorized access attempt', 'WARNING');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    $compressor->end();
    exit;
}

try {
    $conversation = new Conversation();
    
    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List conversations
            $conversations = $conversation->getByUserId($user_id);
            echo json_encode([
                'success' => true,
                'data' => $conversations
            ]);
            break;
            
        case 'POST':
            // Create new conversation
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Title is required']);
                break;
            }
            
            $newConversation = $conversation->create([
                'user_id' => $user_id,
                'title' => $data['title']
            ]);
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'data' => $newConversation
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    Logger::log('Error in conversations', 'ERROR', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} finally {
    $compressor->end();
} 