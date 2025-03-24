<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db_config.php';

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!isset($data['plugin_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Plugin ID is required']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Verify plugin exists and is active
    $stmt = $db->prepare("
        SELECT id 
        FROM plugins 
        WHERE id = ? 
        AND is_active = TRUE 
        AND (
            EXISTS (SELECT 1 FROM n8n_webhook_settings n WHERE n.plugin_id = plugins.id AND n.is_active = TRUE)
            OR EXISTS (SELECT 1 FROM direct_message_settings d WHERE d.plugin_id = plugins.id AND d.is_active = TRUE)
        )
    ");
    $stmt->execute([$data['plugin_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Invalid or inactive plugin');
    }
    
    // Update or insert user preference
    $stmt = $db->prepare("
        INSERT INTO user_plugin_preferences (user_id, plugin_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
        plugin_id = VALUES(plugin_id)
    ");
    
    $stmt->execute([$_SESSION['user_id'], $data['plugin_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Plugin preference updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update plugin preference: ' . $e->getMessage()
    ]);
} 