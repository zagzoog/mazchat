<?php

require_once dirname(__DIR__, 3) . '/db_config.php';

// Ensure user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_n8n_settings') {
    try {
        $db = getDBConnection();
        
        // Validate input
        $webhook_url = filter_var($_POST['webhook_url'], FILTER_SANITIZE_URL);
        $timeout = filter_var($_POST['timeout'], FILTER_VALIDATE_INT);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (!$webhook_url) {
            throw new Exception('Invalid webhook URL');
        }
        
        if ($timeout < 1 || $timeout > 300) {
            throw new Exception('Timeout must be between 1 and 300 seconds');
        }
        
        // Update settings
        $stmt = $db->prepare("
            UPDATE n8n_webhook_settings 
            SET webhook_url = ?, 
                timeout = ?, 
                is_active = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");
        
        $stmt->execute([$webhook_url, $timeout, $is_active]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
} 