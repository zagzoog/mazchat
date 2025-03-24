<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../db_config.php';

try {
    $db = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['plugin_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Plugin ID is required']);
            exit;
        }
        
        // Verify plugin exists and is active
        $stmt = $db->prepare("
            SELECT id 
            FROM plugins 
            WHERE id = ? AND is_active = TRUE
        ");
        $stmt->execute([$data['plugin_id']]);
        
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or inactive plugin']);
            exit;
        }
        
        // Update or insert user preference
        $stmt = $db->prepare("
            INSERT INTO user_plugin_preferences (user_id, plugin_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE plugin_id = VALUES(plugin_id)
        ");
        $stmt->execute([$_SESSION['user_id'], $data['plugin_id']]);
        
        echo json_encode(['success' => true]);
    } else {
        // Get user's current plugin preference
        $stmt = $db->prepare("
            SELECT plugin_id 
            FROM user_plugin_preferences 
            WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $preference = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'plugin_id' => $preference ? $preference['plugin_id'] : null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to handle plugin preference: ' . $e->getMessage()
    ]);
} 