<?php
session_start();
require_once '../../app/utils/ResponseCompressor.php';
require_once '../../db_config.php';
require_once '../../app/utils/Logger.php';
require_once '../../app/models/Model.php';
require_once '../../app/models/User.php';
require_once '../../app/models/Plugin.php';

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
    $plugin = new Plugin();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List marketplace plugins or get specific plugin
            if (isset($_GET['id'])) {
                $result = $plugin->getById($_GET['id'], true);
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'data' => $result
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Plugin not found']);
                }
            } else {
                // Get marketplace listing with filters
                $filters = [
                    'search' => $_GET['search'] ?? null,
                    'min_rating' => $_GET['min_rating'] ?? null,
                    'is_official' => isset($_GET['official']) ? (bool)$_GET['official'] : null
                ];
                
                $page = max(1, intval($_GET['page'] ?? 1));
                $limit = max(1, min(50, intval($_GET['limit'] ?? 10)));
                
                $plugins = $plugin->listMarketplace($filters, $page, $limit);
                
                echo json_encode([
                    'success' => true,
                    'data' => $plugins,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit
                    ]
                ]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'install':
                        if (!isset($data['plugin_id'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Plugin ID is required']);
                            break;
                        }
                        
                        try {
                            $result = $plugin->install($data['plugin_id'], $user_id);
                            echo json_encode([
                                'success' => true,
                                'message' => 'Plugin installed successfully'
                            ]);
                        } catch (Exception $e) {
                            http_response_code(400);
                            echo json_encode(['error' => $e->getMessage()]);
                        }
                        break;
                        
                    case 'uninstall':
                        if (!isset($data['plugin_id'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Plugin ID is required']);
                            break;
                        }
                        
                        $result = $plugin->uninstall($data['plugin_id'], $user_id);
                        if ($result) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Plugin uninstalled successfully'
                            ]);
                        } else {
                            http_response_code(404);
                            echo json_encode(['error' => 'Plugin not found']);
                        }
                        break;
                        
                    case 'toggle':
                        if (!isset($data['plugin_id'])) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Plugin ID is required']);
                            break;
                        }
                        
                        $result = $plugin->toggleEnabled($data['plugin_id'], $user_id);
                        if ($result) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Plugin status toggled successfully'
                            ]);
                        } else {
                            http_response_code(404);
                            echo json_encode(['error' => 'Plugin not found']);
                        }
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Action is required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    Logger::log('Error in marketplace endpoint', 'ERROR', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} finally {
    $compressor->end();
} 