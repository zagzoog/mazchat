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

try {
    $db = getDBConnection();
    
    // Get all active plugins, regardless of settings
    $query = "
        SELECT p.id, p.name, p.description 
        FROM plugins p
        WHERE p.is_active = TRUE
    ";
    
    $stmt = $db->query($query);
    $plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's current plugin preference
    $stmt = $db->prepare("
        SELECT plugin_id 
        FROM user_plugin_preferences 
        WHERE user_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $preference = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'plugins' => $plugins,
        'selected_plugin' => $preference ? $preference['plugin_id'] : null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to get plugins: ' . $e->getMessage()
    ]);
} 