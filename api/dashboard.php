<?php

session_start();
require_once '../app/utils/ResponseCompressor.php';
require_once '../db_config.php';
require_once '../app/utils/Logger.php';
require_once '../app/models/Model.php';  // Load Model first
require_once '../app/models/User.php';   // Then load User
require_once '../app/models/Membership.php';
require_once '../app/models/UsageStats.php';

// Initialize response compression
$compressor = ResponseCompressor::getInstance();
$compressor->start();

header('Content-Type: application/json; charset=utf-8');

// Log session information
Logger::log('Session data', 'INFO', [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'session_path' => session_save_path(),
    'session_status' => session_status()
]);

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
    // Get user data
    $user = new User();
    $userData = $user->findById($user_id);
    if (!$userData) {
        throw new Exception('User not found');
    }
    
    // Get membership data
    $membership = new Membership();
    $membershipData = $membership->getCurrentMembership($user_id);
    
    // Get conversation stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_conversations,
            COUNT(CASE WHEN DATE(c.created_at) = CURRENT_DATE THEN 1 END) as today_conversations,
            COUNT(CASE WHEN DATE(c.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN 1 END) as week_conversations
        FROM conversations c
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get message stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN m.role = 'user' THEN 1 END) as total_messages,
            COUNT(CASE WHEN m.role = 'user' AND DATE(m.created_at) = CURRENT_DATE THEN 1 END) as today_messages,
            COUNT(CASE WHEN m.role = 'user' AND DATE(m.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN 1 END) as week_messages,
            SUM(LENGTH(m.content) - LENGTH(REPLACE(m.content, ' ', '')) + 1) as total_words,
            SUM(CASE 
                WHEN DATE(m.created_at) = CURRENT_DATE 
                THEN LENGTH(m.content) - LENGTH(REPLACE(m.content, ' ', '')) + 1 
                ELSE 0 
            END) as today_words,
            SUM(CASE 
                WHEN DATE(m.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) 
                THEN LENGTH(m.content) - LENGTH(REPLACE(m.content, ' ', '')) + 1 
                ELSE 0 
            END) as week_words
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $messageStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent conversations
    $stmt = $db->prepare("
        SELECT c.*, 
               m.content as last_message,
               m.created_at as last_message_time
        FROM conversations c
        LEFT JOIN (
            SELECT conversation_id, content, created_at
            FROM messages m1
            WHERE id = (
                SELECT MAX(id)
                FROM messages m2
                WHERE m2.conversation_id = m1.conversation_id
            )
        ) m ON m.conversation_id = c.id
        WHERE c.user_id = ?
        ORDER BY c.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recentConversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'membership' => [
            'type' => $membershipData['type'] ?? 'free',
            'monthly_limit' => $membershipData['monthly_limit'] ?? 100,
            'question_limit' => $membershipData['question_limit'] ?? 1000,
            'current_usage' => (int)$stats['total_conversations'],
            'current_questions' => (int)$messageStats['total_messages']
        ],
        'stats' => [
            'total_conversations' => (int)$stats['total_conversations'],
            'total_questions' => (int)$messageStats['total_messages'],
            'total_words' => (int)$messageStats['total_words'] ?? 0
        ],
        'daily_stats' => [
            [
                'date' => date('Y-m-d'),
                'conversations' => (int)$stats['today_conversations'],
                'questions' => (int)$messageStats['today_messages'],
                'words' => (int)$messageStats['today_words'] ?? 0
            ],
            [
                'date' => date('Y-m-d', strtotime('-1 day')),
                'conversations' => 0,
                'questions' => 0,
                'words' => 0
            ],
            [
                'date' => date('Y-m-d', strtotime('-2 day')),
                'conversations' => 0,
                'questions' => 0,
                'words' => 0
            ],
            [
                'date' => date('Y-m-d', strtotime('-3 day')),
                'conversations' => 0,
                'questions' => 0,
                'words' => 0
            ],
            [
                'date' => date('Y-m-d', strtotime('-4 day')),
                'conversations' => 0,
                'questions' => 0,
                'words' => 0
            ],
            [
                'date' => date('Y-m-d', strtotime('-5 day')),
                'conversations' => 0,
                'questions' => 0,
                'words' => 0
            ],
            [
                'date' => date('Y-m-d', strtotime('-6 day')),
                'conversations' => 0,
                'questions' => 0,
                'words' => 0
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    Logger::log('Error in dashboard', 'ERROR', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
} finally {
    $compressor->end();
} 